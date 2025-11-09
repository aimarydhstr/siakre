<?php

namespace App\Imports;

use App\Models\Hki;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\Department;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class HkiImport implements ToCollection, WithHeadingRow
{
    /** @var array<int, array{id:int,name:string,norm:string}> */
    protected $deptIndex = [];

    /** Ringkasan hasil import */
    public $report = [
        'success' => 0,
        'skip'    => 0,
        'errors'  => [], // [['row'=>int,'message'=>string], ...]
    ];

    /** Heading mulai baris 1 */
    public function headingRow(): int
    {
        return 1;
    }

    public function __construct()
    {
        // Cache daftar prodi + nama ternormalisasi (sekali)
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

            // Ambil kolom dari Excel (case-insensitive, WithHeadingRow → lowercase)
            $deptName    = trim((string)($row['department']   ?? '')); // baru: wajib
            $name        = trim((string)($row['name']   ?? ''));
            $number      = trim((string)($row['number'] ?? ''));
            $holder      = trim((string)($row['holder'] ?? ''));
            $dateRaw     = ($row['date'] ?? ''); // jangan trim dulu untuk numeric
            $lectNidnRaw = trim((string)($row['lecturers_nidn'] ?? ''));  // opsional: "00123;00456" (NIDN list)
            // students: bisa berisi "Name (NIM)", "NIM", atau "Name" — dipisah ; , | newline
            $studRaw     = trim((string)($row['students_nim'] ?? $row['students'] ?? ''));

            // Validasi minimum (sekarang termasuk department)
            $missing = [];
            if ($deptName === '')   $missing[] = 'department';
            if ($name === '')       $missing[] = 'name';
            if ($number === '')     $missing[] = 'number';
            if ($holder === '')     $missing[] = 'holder';
            if ($dateRaw === '' && $dateRaw !== 0) $missing[] = 'date';

            if (!empty($missing)) {
                $this->skip($rowNum, 'Kolom wajib kosong: '.implode(', ', $missing));
                continue;
            }

            // Cari department by nama (toleran)
            $dept = $this->matchDepartmentByName($deptName);
            if (!$dept) {
                $this->skip($rowNum, "Department tidak ditemukan: \"{$deptName}\"");
                continue;
            }
            $departmentId = $dept['id'];

            // Parse tanggal fleksibel
            $date = $this->tryParseDate($dateRaw);
            if (!$date) {
                $this->skip($rowNum, "Format tanggal tidak dikenali: {$dateRaw}");
                continue;
            }

            // Skip duplikat by number (anggap unik)
            if (Hki::where('number', $number)->exists()) {
                $this->skip($rowNum, "Nomor HKI sudah ada: {$number}");
                continue;
            }

            DB::beginTransaction();
            try {
                // 1) Buat HKI (file tidak diimport dari Excel)
                $hki = Hki::create([
                    'name'   => $name,
                    'number' => $number,
                    'holder' => $holder,
                    'date'   => $date,   // yyyy-mm-dd
                    'file'   => null,    // tidak diimport dari excel
                    'department_id' => $departmentId, // tambahan
                ]);

                // 2) Dosen via lecturers_nidn (HANYA NIDN)
                $lecturerIds = [];
                if ($lectNidnRaw !== '') {
                    $nidns = preg_split('/[;,\|\r\n]+/', $lectNidnRaw);
                    foreach ($nidns as $nidn) {
                        $nidn = trim((string)$nidn);
                        if ($nidn === '') continue;
                        $lec = Lecturer::where('nidn', $nidn)->first();
                        if ($lec) $lecturerIds[] = (int)$lec->id;
                    }
                    $lecturerIds = array_values(array_unique($lecturerIds));
                }
                if (!empty($lecturerIds)) {
                    $hki->lecturers()->attach($lecturerIds);
                }

                // 3) Mahasiswa: parsing flexible "Name (NIM)" | "NIM" | "Name"
                $studentIds = [];
                if ($studRaw !== '') {
                    $parts = preg_split('/[;,\|\r\n]+/', $studRaw);
                    foreach ($parts as $part) {
                        $part = trim((string)$part);
                        if ($part === '') continue;

                        $namePart = null;
                        $nimPart  = null;

                        // match "Name (NIM)"
                        if (preg_match('/^(.*?)\s*\(\s*([0-9A-Za-z\-\_\.\/]+)\s*\)\s*$/u', $part, $m)) {
                            $namePart = trim($m[1]);
                            $nimPart  = trim($m[2]);
                        } else {
                            // jika token tampak seperti NIM (mengandung angka dan hanya token alnum/punc tertentu)
                            if (preg_match('/^[0-9A-Za-z\-\_\.\/]+$/u', $part) && preg_match('/\d/', $part)) {
                                $nimPart = $part;
                                $namePart = $part; // fallback name = nim jika nama tidak tersedia
                            } else {
                                // anggap ini nama saja
                                $namePart = $part;
                                $nimPart = '';
                            }
                        }

                        // cari student by nim jika ada
                        $student = null;
                        if (!empty($nimPart)) {
                            $student = Student::where('nim', $nimPart)->first();
                        }

                        if (!$student) {
                            // buat student baru. Jika nim kosong, generate placeholder unik.
                            $nimToSave = $nimPart !== '' ? $nimPart : strtoupper(uniqid('NIM'));
                            $nameToSave = $namePart !== '' ? $namePart : $nimToSave;

                            $student = Student::create([
                                'nim'           => $nimToSave,
                                'name'          => $nameToSave,
                                'department_id' => $departmentId,
                            ]);
                        } else {
                            // jika student exists dan department kosong, set department
                            if (empty($student->department_id)) {
                                $student->department_id = $departmentId;
                                $student->save();
                            }
                            // optional: jika existing student punya nama placeholder (nim) dan import menyediakan a proper name,
                            // kita bisa update name. Untuk sekarang saya tidak mengubah nama existing agar aman.
                        }

                        $studentIds[] = (int)$student->id;
                    } // end foreach parts

                    $studentIds = array_values(array_unique($studentIds));
                }

                if (!empty($studentIds)) {
                    $hki->students()->attach($studentIds);
                }

                DB::commit();
                $this->report['success']++;
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->skip($rowNum, 'Gagal simpan: '.$e->getMessage());
            }
        }
    }

    /** catat baris terlewat */
    protected function skip(int $row, string $msg): void
    {
        $this->report['skip']++;
        $this->report['errors'][] = ['row' => $row, 'message' => $msg];
    }

    /**
     * Coba parse berbagai format tanggal → 'Y-m-d' atau null.
     * Mendukung: ISO (Y-m-d), dd-mm-yyyy, dd/mm/yyyy, natural (strtotime),
     * dan serial Excel jika PhpSpreadsheet tersedia.
     */
    protected function tryParseDate($in): ?string
    {
        // Serial Excel (numeric)
        if (is_numeric($in)) {
            // gunakan PhpSpreadsheet jika tersedia
            if (class_exists('\PhpOffice\PhpSpreadsheet\Shared\Date')) {
                try {
                    $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$in);
                    return $dt->format('Y-m-d');
                } catch (\Throwable $e) {
                    // fallback lanjut ke bawah
                }
            } else {
                // fallback kasar: Excel epoch 1899-12-30
                try {
                    $base = \DateTime::createFromFormat('Y-m-d', '1899-12-30');
                    if ($base) {
                        $interval = new \DateInterval('P'.((int)$in).'D');
                        $base->add($interval);
                        return $base->format('Y-m-d');
                    }
                } catch (\Throwable $e) {
                    // lanjut
                }
            }
        }

        $s = trim((string)$in);
        if ($s === '') return null;

        // ISO yyyy-mm-dd
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
            return $s;
        }

        // dd-mm-yyyy
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $s)) {
            list($d,$m,$y) = explode('-', $s);
            if (checkdate((int)$m,(int)$d,(int)$y)) {
                return sprintf('%04d-%02d-%02d',(int)$y,(int)$m,(int)$d);
            }
            return null;
        }

        // dd/mm/yyyy
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $s)) {
            list($d,$m,$y) = explode('/', $s);
            if (checkdate((int)$m,(int)$d,(int)$y)) {
                return sprintf('%04d-%02d-%02d',(int)$y,(int)$m,(int)$d);
            }
            return null;
        }

        // Natural (e.g., "31 Oct 2025")
        $ts = strtotime($s);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }

        return null;
    }

    // ================= Department matching helpers =================

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

    /** Opsional: ringkasan cepat */
    public function summary(): array
    {
        return $this->report;
    }
}
