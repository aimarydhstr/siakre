<?php

namespace App\Imports;

use App\Models\Lecturer;
use App\Models\Department;
use App\Models\ExpertiseField;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class LecturersImport implements ToCollection, WithHeadingRow
{
    /** @var array<string,array{id:int,name:string}> */
    private $deptMap = []; // normalized_name => ['id'=>..,'name'=>..]

    /** lowercased whitelists */
    private $positions = ['asisten ahli','lektor','lektor kepala','profesor'];
    private $maritals  = ['menikah','belum menikah'];

    private $inserted = 0;
    private $updated  = 0;
    private $errors   = [];

    /** mapping untuk PHP 7.4 (pengganti match) */
    private $positionMap = [
        'asisten ahli'  => 'Asisten Ahli',
        'lektor'        => 'Lektor',
        'lektor kepala' => 'Lektor Kepala',
        'profesor'      => 'Profesor',
    ];
    private $maritalMap = [
        'menikah'       => 'Menikah',
        'belum menikah' => 'Belum Menikah',
    ];

    public function __construct()
    {
        foreach (Department::select('id','name')->get() as $d) {
            $this->deptMap[$this->norm($d->name)] = ['id' => (int)$d->id, 'name' => (string)$d->name];
        }
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function collection(Collection $rows)
    {
        $rowNum = 1; // header
        foreach ($rows as $row) {
            $rowNum++;

            $nidn = trim((string)($row['nidn'] ?? ''));
            $name = trim((string)($row['name'] ?? ''));
            $dept = trim((string)($row['department'] ?? ''));

            // Optional
            $nik          = trim((string)($row['nik'] ?? ''));
            $birthPlace   = trim((string)($row['birth_place'] ?? ''));
            $birthDateStr = trim((string)($row['birth_date'] ?? ''));
            $address      = trim((string)($row['address'] ?? ''));
            $positionStr  = trim((string)($row['position'] ?? ''));
            $maritalStr   = trim((string)($row['marital_status'] ?? ''));
            $expFieldStr  = trim((string)($row['expertise_field'] ?? ''));

            if ($nidn === '' || $name === '' || $dept === '') {
                $this->errors[] = "Baris {$rowNum}: nidn, name, dan department wajib diisi.";
                continue;
            }

            // Resolve department by name (tahan typo ringan)
            $departmentId = $this->resolveDepartmentId($dept);
            if (!$departmentId) {
                $this->errors[] = "Baris {$rowNum}: Department '{$dept}' tidak ditemukan.";
                continue;
            }

            // Parse tanggal lahir (opsional)
            $birthDate = null;
            if ($birthDateStr !== '') {
                try {
                    $birthDate = Carbon::parse($birthDateStr)->toDateString();
                } catch (\Throwable $e) {
                    $this->errors[] = "Baris {$rowNum}: Tanggal lahir tidak valid: '{$birthDateStr}'.";
                    continue;
                }
            }

            // Normalisasi position & marital (PHP 7.4)
            $position = null;
            if ($positionStr !== '') {
                $p = mb_strtolower($positionStr);
                if (in_array($p, $this->positions, true)) {
                    $position = isset($this->positionMap[$p]) ? $this->positionMap[$p] : null;
                }
            }

            $marital = null;
            if ($maritalStr !== '') {
                $m = mb_strtolower($maritalStr);
                if (in_array($m, $this->maritals, true)) {
                    $marital = isset($this->maritalMap[$m]) ? $this->maritalMap[$m] : null;
                }
            }

            // Resolve expertise field (opsional, by name exact lower)
            $expertiseFieldId = null;
            if ($expFieldStr !== '') {
                $ef = ExpertiseField::whereRaw('LOWER(name) = ?', [mb_strtolower($expFieldStr)])->first();
                if ($ef) {
                    $expertiseFieldId = (int)$ef->id;
                }
            }

            try {
                $existing = Lecturer::where('nidn', $nidn)->first();

                $payload = [
                    'name'               => $name,
                    'department_id'      => (int)$departmentId,
                    'birth_place'        => $birthPlace ?: ($existing ? $existing->birth_place : null),
                    'birth_date'         => $birthDate ?: ($existing ? $existing->birth_date : null),
                    'address'            => $address   ?: ($existing ? $existing->address : null),
                    'position'           => $position  ?: ($existing ? $existing->position : null),
                    'marital_status'     => $marital   ?: ($existing ? $existing->marital_status : null),
                    'expertise_field_id' => $expertiseFieldId ?: ($existing ? $existing->expertise_field_id : null),
                ];
                if ($nik !== '') {
                    $payload['nik'] = $nik; // kolom NIK opsional
                }

                if ($existing) {
                    $existing->update($payload);
                    $this->updated++;
                } else {
                    $payload['nidn'] = $nidn;
                    if (!isset($payload['nik'])) {
                        $payload['nik'] = null;
                    }
                    Lecturer::create($payload);
                    $this->inserted++;
                }
            } catch (\Throwable $e) {
                $this->errors[] = "Baris {$rowNum}: " . $e->getMessage();
            }
        }
    }

    public function summary(): array
    {
        return [
            'inserted' => $this->inserted,
            'updated'  => $this->updated,
            'errors'   => $this->errors,
        ];
    }

    // === Helpers ===
    private function norm(string $s): string
    {
        $s = mb_strtolower($s);
        $out = preg_replace('/[^a-z0-9]+/u', '', $s);
        return $out !== null ? $out : '';
    }

    private function resolveDepartmentId(string $deptName): ?int
    {
        $key = $this->norm($deptName);
        if (isset($this->deptMap[$key])) {
            return (int)$this->deptMap[$key]['id'];
        }

        // fallback contains (pakai Str::contains untuk PHP 7.4)
        foreach ($this->deptMap as $k => $v) {
            if (Str::contains($k, $key) || Str::contains($key, $k)) {
                return (int)$v['id'];
            }
        }

        // fallback hard query
        $hit = Department::whereRaw('LOWER(name) = ?', [mb_strtolower($deptName)])->first();
        if (!$hit) {
            $hit = Department::where('name', 'LIKE', '%' . $deptName . '%')->first();
        }
        return $hit ? (int)$hit->id : null;
    }
}
