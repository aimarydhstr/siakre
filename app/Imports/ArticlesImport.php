<?php

namespace App\Imports;

use App\Models\Article;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\Department;
use App\Models\LecturerArticle;
use App\Models\StudentArticle;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ArticlesImport implements ToCollection, WithHeadingRow
{
    /** @var array<int, array{id:int,name:string,norm:string}> */
    protected $deptIndex = [];

    /** Ringkasan hasil import */
    public $report = [
        'success' => 0,
        'skip'    => 0,
        'errors'  => [], // [['row'=>int,'message'=>string], ...]
    ];

    /** Header ada di baris 1 */
    public function headingRow(): int
    {
        return 1;
    }

    public function __construct()
    {
        // Cache daftar prodi + nama ternormalisasi (sekali saja)
        $this->deptIndex = Department::query()
            ->get(['id','name'])
            ->map(function ($d) {
                return [
                    'id'   => (int) $d->id,
                    'name' => (string) $d->name,
                    'norm' => $this->normalize((string) $d->name),
                ];
            })
            ->all();
    }

    public function collection(Collection $rows)
    {
        $rowNum = 1; // baris header
        foreach ($rows as $row) {
            $rowNum++;

            // ---- Ambil kolom (case-insensitive heading) ----
            // WithHeadingRow lowercases headings automatically
            $deptName     = trim((string)($row['department']   ?? '')); // WAJIB
            $title        = trim((string)($row['title']        ?? ''));
            $issn         = trim((string)($row['issn']         ?? ''));
            $typeJournal  = trim((string)($row['type_journal'] ?? ''));
            $url          = trim((string)($row['url']          ?? ''));
            $doi          = trim((string)($row['doi']          ?? ''));
            $publisher    = trim((string)($row['publisher']    ?? ''));
            $dateRaw      = ($row['date'] ?? ''); // jangan trim dulu; bisa numeric (serial excel)
            $category     = trim((string)($row['category']     ?? ''));
            $volume       = trim((string)($row['volume']       ?? ''));
            $number       = trim((string)($row['number']       ?? ''));
            // lecturers: HANYA NIDN list (pisah ; , | newline)
            $lectNidnRaw  = trim((string)($row['lecturers_nidn'] ?? $row['lecturers'] ?? ''));
            // students: bisa kolom students_nim (NIM list) atau students (Name (NIM) / NIM / Name)
            $studRaw      = trim((string)($row['students_nim'] ?? $row['students'] ?? ''));

            // ---- Validasi minimum ----
            $missing = [];
            if ($deptName === '')   $missing[] = 'department';
            if ($title === '')      $missing[] = 'title';
            if ($issn === '')       $missing[] = 'issn';
            if ($typeJournal === '')$missing[] = 'type_journal';
            if ($url === '')        $missing[] = 'url';
            if ($doi === '')        $missing[] = 'doi';
            if ($publisher === '')  $missing[] = 'publisher';
            if ($dateRaw === '' && $dateRaw !== 0) $missing[] = 'date';
            if ($category === '')   $missing[] = 'category';

            if (!empty($missing)) {
                $this->skip($rowNum, 'Kolom wajib kosong: ' . implode(', ', $missing));
                continue;
            }

            // ---- Cari Department by nama (toleran) ----
            $dept = $this->matchDepartmentByName($deptName);
            if (!$dept) {
                $this->skip($rowNum, "Department tidak ditemukan: \"{$deptName}\"");
                continue;
            }
            $departmentId = $dept['id'];

            // ---- Cek duplikat DOI/ISSN ----
            if (Article::where('doi', $doi)->orWhere('issn', $issn)->exists()) {
                $this->skip($rowNum, "Duplikasi DOI/ISSN (doi: {$doi}, issn: {$issn})");
                continue;
            }

            // ---- Parse tanggal (format umum + serial excel) ----
            $date = $this->parseDateFlexible($row['date']);
            if ($date === null) {
                $this->skip($rowNum, "Format tanggal tidak didukung: {$dateRaw}");
                continue;
            }

            // ---- Validasi kategori ----
            $allowedCat = ['dosen', 'mahasiswa', 'gabungan'];
            $cat = strtolower($category);
            if (!in_array($cat, $allowedCat, true)) {
                $this->skip($rowNum, "Kategori tidak valid: {$category}");
                continue;
            }

            DB::beginTransaction();
            try {
                // Simpan artikel (file PDF tidak diimport dari Excel)
                $article = Article::create([
                    'department_id' => $departmentId,
                    'title'         => $title,
                    'issn'          => $issn,
                    'type_journal'  => $typeJournal,
                    'url'           => $url,
                    'doi'           => $doi,
                    'publisher'     => $publisher,
                    'date'          => $date, // Y-m-d
                    'category'      => $cat,
                    'volume'        => $volume !== '' ? $volume : null,
                    'number'        => $number !== '' ? $number : null,
                    'file'          => null,
                ]);

                // -----------------------------
                // Penulis: dosen (HANYA NIDN)
                // Kolom diterima sebagai list NIDN: "001234;009876" atau "001234,009876"
                // -----------------------------
                if ($lectNidnRaw !== '') {
                    $nidnParts = preg_split('/[;,\|\r\n]+/', $lectNidnRaw);
                    $lecturerIds = [];
                    foreach ($nidnParts as $nidn) {
                        $nidn = trim((string)$nidn);
                        if ($nidn === '') continue;
                        // HANYA cari berdasarkan nidn, tidak parse "Name (NIDN)"
                        $lec = Lecturer::where('nidn', $nidn)->first();
                        if ($lec) $lecturerIds[] = (int)$lec->id;
                    }
                    $lecturerIds = array_values(array_unique($lecturerIds));
                    if (!empty($lecturerIds)) {
                        $pairs = array_map(function ($lid) use ($article) {
                            return ['lecturer_id' => $lid, 'article_id' => $article->id];
                        }, $lecturerIds);
                        LecturerArticle::insert($pairs);
                    }
                }

                // -----------------------------
                // Penulis: mahasiswa (flexible parsing)
                // - $studRaw supports "Name (NIM)", "NIM", "Name"
                // - multiple entries separated by ; , | newline
                // -----------------------------
                if ($studRaw !== '') {
                    $parts = preg_split('/[;,\|\r\n]+/', $studRaw);
                    $studentIds = [];

                    foreach ($parts as $part) {
                        $part = trim((string)$part);
                        if ($part === '') continue;

                        $name = null;
                        $nim  = null;

                        // Coba match "Name (NIM)"
                        if (preg_match('/^(.*?)\s*\(\s*([0-9A-Za-z\-\_\.\/]+)\s*\)\s*$/u', $part, $m)) {
                            $name = trim($m[1]);
                            $nim  = trim($m[2]);
                        } else {
                            // jika token tampak seperti NIM (mengandung angka, hanya token alnum/punc tertentu)
                            if (preg_match('/^[0-9A-Za-z\-\_\.\/]+$/u', $part) && preg_match('/\d/', $part)) {
                                $nim = $part;
                                $name = $part; // sementara gunakan nim sebagai nama jika nama tidak tersedia
                            } else {
                                // anggap ini nama saja
                                $name = $part;
                                $nim = '';
                            }
                        }

                        // Cari student by nim jika ada
                        $student = null;
                        if (!empty($nim)) {
                            $student = Student::where('nim', $nim)->first();
                        }

                        if (!$student) {
                            // Buat student baru. Jika nim kosong, generate placeholder unik.
                            $nimToSave = $nim !== '' ? $nim : strtoupper(uniqid('NIM'));
                            $nameToSave = $name !== '' ? $name : $nimToSave;

                            $student = Student::create([
                                'nim'           => $nimToSave,
                                'name'          => $nameToSave,
                                'photo'         => null,
                                'department_id' => $departmentId,
                            ]);
                        } else {
                            // jika student ditemukan dan department kosong, update department agar konsisten
                            if (empty($student->department_id)) {
                                $student->department_id = $departmentId;
                                $student->save();
                            }
                        }

                        $studentIds[] = (int)$student->id;
                    } // end foreach parts

                    $studentIds = array_values(array_unique($studentIds));
                    if (!empty($studentIds)) {
                        $pairs = array_map(function ($sid) use ($article) {
                            return ['student_id' => $sid, 'article_id' => $article->id];
                        }, $studentIds);
                        StudentArticle::insert($pairs);
                    }
                }

                DB::commit();
                $this->report['success']++;
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->skip($rowNum, 'Gagal simpan: ' . $e->getMessage());
            }
        }
    }

    // ================= Helpers =================

    protected function skip(int $row, string $msg): void
    {
        $this->report['skip']++;
        $this->report['errors'][] = ['row' => $row, 'message' => $msg];
    }

    protected function normalize(string $s): string
    {
        $s = mb_strtolower(trim($s));
        // ganti non-alfanumerik (kecuali spasi) dengan spasi
        $s = preg_replace('/[^a-z0-9\s]+/u', ' ', $s);
        // kolaps spasi
        $s = preg_replace('/\s+/u', ' ', $s);
        return trim($s);
    }

    /**
     * Cari Department by nama toleran:
     * 1) exact normalized match
     * 2) best Levenshtein (batas dinamis: max(2, ceil(20% panjang)))
     */
    protected function matchDepartmentByName(string $needle): ?array
    {
        $n = $this->normalize($needle);
        if ($n === '') return null;

        foreach ($this->deptIndex as $d) {
            if ($d['norm'] === $n) return $d;
        }

        $best = null;
        $bestDist = PHP_INT_MAX;
        foreach ($this->deptIndex as $d) {
            $dist = levenshtein($n, $d['norm']);
            if ($dist < $bestDist) {
                $bestDist = $dist;
                $best = $d;
            }
        }

        $threshold = max(2, (int)ceil(mb_strlen($n) * 0.2));
        if ($best && $bestDist <= $threshold) {
            return $best;
        }
        return null;
    }

    /**
     * Parse tanggal fleksibel:
     * - string: d-m-Y, Y-m-d, d/m/Y
     * - numeric: Excel serial (e.g., 45213)
     * Return 'Y-m-d' atau null.
     */
    protected function parseDateFlexible($value): ?string
    {
        // Excel serial number
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            // jika string numeric, cast ke float
            $num = is_string($value) ? (float)$value : $value;
            try {
                $dt = ExcelDate::excelToDateTimeObject($num);
                return Carbon::instance($dt)->format('Y-m-d');
            } catch (\Throwable $e) {
                // lanjut ke parsing string di bawah
            }
        }

        // String formats
        $str = trim((string)$value);
        if ($str === '') return null;

        foreach (['d-m-Y', 'Y-m-d', 'd/m/Y', 'd M Y', 'd F Y'] as $fmt) {
            try {
                $c = Carbon::createFromFormat($fmt, $str);
                if ($c !== false) return $c->format('Y-m-d');
            } catch (\Throwable $e) {
                // continue
            }
        }

        // Fallback strtotime (31 Oct 2025, etc.)
        $ts = strtotime($str);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }
        return null;
    }

    /** Opsional: ringkasan cepat */
    public function summary(): array
    {
        return $this->report;
    }
}
