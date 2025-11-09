<?php

namespace App\Imports;

use App\Models\Book;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\Department;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BooksImport implements ToCollection, WithHeadingRow
{
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

    public function collection(Collection $rows)
    {
        $rowNum = 1; // header
        foreach ($rows as $row) {
            $rowNum++;

            // Ambil kolom (WithHeadingRow => keys lowercase)
            $isbn         = trim((string)($row['isbn'] ?? ''));
            $title        = trim((string)($row['title'] ?? ''));
            $publisher    = trim((string)($row['publisher'] ?? ''));
            $publishMonth = $row['publish_month'] ?? ''; // bisa angka atau string
            $publishYear  = $row['publish_year']  ?? '';
            $city         = trim((string)($row['city'] ?? ''));

            // lecturers: HANYA NIDN (tetap seperti sebelumnya)
            $lectNidnRaw = trim((string)($row['lecturers_nidn'] ?? $row['lecturers'] ?? ''));

            // students: fleksibel: 'Name (NIM)', 'NIM', atau 'Name'
            $studRaw = trim((string)($row['students_nim'] ?? $row['students'] ?? ''));

            // Cari kolom department (bisa header 'department_id','department','dept_id','prodi')
            $deptRaw = null;
            foreach (['department_id','department','dept_id','prodi'] as $k) {
                if (isset($row[$k]) && trim((string)$row[$k]) !== '') {
                    $deptRaw = trim((string)$row[$k]);
                    break;
                }
            }

            // Validasi minimum
            $missing = [];
            if ($isbn === '')      $missing[] = 'isbn';
            if ($title === '')     $missing[] = 'title';
            if ($publisher === '') $missing[] = 'publisher';
            if ($publishMonth === '' && $publishMonth !== 0) $missing[] = 'publish_month';
            if ($publishYear  === '' && $publishYear  !== 0) $missing[] = 'publish_year';
            if ($city === '')      $missing[] = 'city';
            if ($deptRaw === null || $deptRaw === '') $missing[] = 'department (department_id/prodi)';

            if (!empty($missing)) {
                $this->skip($rowNum, 'Kolom wajib kosong: '.implode(', ', $missing));
                continue;
            }

            // Koersi angka (mampu menerima string/float)
            $pm = $this->toIntSafe($publishMonth);
            $py = $this->toIntSafe($publishYear);

            if ($pm === null || $pm < 1 || $pm > 12) {
                $this->skip($rowNum, "publish_month tidak valid: {$publishMonth}");
                continue;
            }
            if ($py === null || $py < 1900 || $py > 2100) {
                $this->skip($rowNum, "publish_year tidak valid: {$publishYear}");
                continue;
            }

            // Resolve department: terima ID numeric atau cari berdasarkan nama (like)
            $deptId = $this->resolveDepartmentId($deptRaw);
            if ($deptId === null) {
                $this->skip($rowNum, "Department tidak ditemukan atau tidak valid: {$deptRaw}");
                continue;
            }

            // Cek duplikat ISBN (anggap unik global)
            if (Book::where('isbn', $isbn)->exists()) {
                $this->skip($rowNum, "ISBN sudah ada: {$isbn}");
                continue;
            }

            DB::beginTransaction();
            try {
                // 1) Simpan Buku (file NULL karena tidak diimport dari Excel)
                $book = Book::create([
                    'isbn'          => $isbn,
                    'title'         => $title,
                    'publisher'     => $publisher,
                    'publish_month' => $pm,
                    'publish_year'  => $py,
                    'city'          => $city,
                    'file'          => null,
                    'department_id' => $deptId,
                ]);

                // 2) Dosen via lecturers_nidn (HANYA NIDN; pisah dengan ; , | newline)
                $lecturerIds = [];
                if ($lectNidnRaw !== '') {
                    $nidns = preg_split('/[;,\|\r\n]+/', $lectNidnRaw);
                    foreach ($nidns as $nidn) {
                        $nidn = trim((string)$nidn);
                        if ($nidn === '') continue;
                        // hanya cari berdasarkan nidn (tidak mencoba parse "Name (NIDN)")
                        $lec = Lecturer::where('nidn', $nidn)->first();
                        if ($lec) $lecturerIds[] = (int)$lec->id;
                    }
                    $lecturerIds = array_values(array_unique($lecturerIds));
                }
                if (!empty($lecturerIds)) {
                    $book->lecturers()->attach($lecturerIds);
                }

                // 3) Mahasiswa via students (flexible: "Name (NIM)", "NIM", "Name")
                $studentIds = [];
                if ($studRaw !== '') {
                    $parts = preg_split('/[;,\|\r\n]+/', $studRaw);
                    foreach ($parts as $item) {
                        $item = trim((string)$item);
                        if ($item === '') continue;

                        $name = null;
                        $nim  = null;

                        // match "Name (NIM)"
                        if (preg_match('/^(.*?)\s*\(\s*([0-9A-Za-z\-\_\.\/]+)\s*\)\s*$/u', $item, $m)) {
                            $name = trim($m[1]);
                            $nim  = trim($m[2]);
                        } else {
                            // jika token tampak seperti NIM (mengandung angka, token alfanum/punc tertentu)
                            if (preg_match('/^[0-9A-Za-z\-\_\.\/]+$/u', $item) && preg_match('/\d/', $item)) {
                                $nim = $item;
                                $name = $item; // sementara gunakan nim sebagai nama jika nama tidak tersedia
                            } else {
                                // anggap ini nama saja
                                $name = $item;
                                $nim = '';
                            }
                        }

                        // cari existing student by nim jika ada
                        $student = null;
                        if (!empty($nim)) {
                            $student = Student::where('nim', $nim)->first();
                        }

                        if (!$student) {
                            // buat student baru. Jika nim kosong, generate placeholder unik.
                            $nimToSave = $nim !== '' ? $nim : strtoupper(uniqid('NIM'));
                            $nameToSave = $name !== '' ? $name : $nimToSave;

                            $student = Student::create([
                                'nim'           => $nimToSave,
                                'name'          => $nameToSave,
                                'department_id' => $deptId,
                            ]);
                        } else {
                            // jika student exists dan belum ada department, set ke dept buku
                            if (empty($student->department_id)) {
                                $student->department_id = $deptId;
                                $student->save();
                            }
                        }

                        $studentIds[] = (int)$student->id;
                    }

                    $studentIds = array_values(array_unique($studentIds));
                }
                if (!empty($studentIds)) {
                    $book->students()->attach($studentIds);
                }

                DB::commit();
                $this->report['success']++;
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->skip($rowNum, 'Gagal simpan: '.$e->getMessage());
            }
        }
    }

    /**
     * Resolve department identifier from raw cell value.
     * - Jika numeric dan ada Department::find(id) => return id
     * - Jika bukan numeric, coba cari Department::where('name','like', "%$raw%")->first()
     * - Jika tidak ditemukan => null
     */
    protected function resolveDepartmentId(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '') return null;

        // numeric? treat as ID
        if (ctype_digit($raw)) {
            $id = (int)$raw;
            if (Department::where('id', $id)->exists()) return $id;
            return null;
        }

        // try exact name first (case-insensitive), then like
        $dept = Department::whereRaw('LOWER(name) = ?', [mb_strtolower($raw)])->first();
        if (!$dept) {
            $dept = Department::where('name', 'like', "%{$raw}%")->first();
        }
        return $dept ? (int)$dept->id : null;
    }

    /** Catat baris yang di-skip */
    protected function skip(int $row, string $msg): void
    {
        $this->report['skip']++;
        $this->report['errors'][] = ['row' => $row, 'message' => $msg];
    }

    /** Konversi aman ke integer (menerima string/float/int); gagal → null */
    protected function toIntSafe($val): ?int
    {
        if (is_int($val)) return $val;
        if (is_float($val)) return (int)round($val);
        if (is_string($val)) {
            $val = trim($val);
            // izinkan "10.0" → 10
            if ($val === '') return null;
            if (is_numeric($val)) return (int)round((float)$val);
        }
        return null;
    }

    /** Opsional: ringkasan cepat */
    public function summary(): array
    {
        return $this->report;
    }
}
