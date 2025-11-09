<?php

namespace App\Imports;

use App\Models\Achievement;
use App\Models\Student;
use App\Models\Department;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AchievementsImport implements ToCollection, WithHeadingRow
{
    /** cached departments */
    protected array $deptIndex = [];

    public $report = [
        'success' => 0,
        'skip'    => 0,
        'errors'  => [], // [ ['row'=>int,'message'=>string], ... ]
        'notes'   => [], // optional warnings
    ];

    public function headingRow(): int { return 1; }

    public function __construct()
    {
        $this->deptIndex = Department::query()
            ->get(['id','name'])
            ->map(fn($d) => [
                'id' => (int)$d->id,
                'name' => (string)$d->name,
                'norm' => $this->normalize((string)$d->name),
            ])->all();
    }

    /**
     * Expected columns (case-insensitive due to WithHeadingRow):
     * - department (or department_id)
     * - team
     * - team_type (Individu|Kelompok)
     * - level (Region|National|International)
     * - field (Akademik|NonAkademik)
     * - organizer
     * - month (MM)
     * - year  (YYYY)
     * - competition
     * - rank (optional)
     * - students OR students_name_nim OR student_list (values separated by ; , | or newline)
     *   each student entry supports: "Name (NIM)" | "NIM" | "Name"
     *
     * Note: certificates/photos cannot be imported via Excel; pivot certificate will be null.
     */
    public function collection(Collection $rows)
    {
        $rowNum = 1; // header
        foreach ($rows as $row) {
            $rowNum++;

            // --- read fields (WithHeadingRow => lowercase keys) ---
            $deptRaw     = trim((string)($row['department'] ?? $row['department_id'] ?? ''));
            $team        = trim((string)($row['team'] ?? ''));
            $teamType    = trim((string)($row['team_type'] ?? ''));
            $level       = trim((string)($row['level'] ?? ''));
            $field       = trim((string)($row['field'] ?? ''));
            $organizer   = trim((string)($row['organizer'] ?? ''));
            $monthRaw    = $row['month'] ?? '';
            $yearRaw     = $row['year'] ?? '';
            $competition = trim((string)($row['competition'] ?? ''));
            $rank        = trim((string)($row['rank'] ?? ''));
            // students column (flexible name)
            $studentsRaw = trim((string)(
                $row['students'] ??
                $row['students_name_nim'] ??
                $row['student_list'] ??
                $row['student_names'] ??
                ''
            ));

            // --- required validation similar to controller ---
            $missing = [];
            if ($deptRaw === '') $missing[] = 'department';
            if ($team === '') $missing[] = 'team';
            if ($teamType === '') $missing[] = 'team_type';
            if ($level === '') $missing[] = 'level';
            if ($field === '') $missing[] = 'field';
            if ($organizer === '') $missing[] = 'organizer';
            if (($monthRaw === '' && $monthRaw !== 0) || !preg_match('/^\d{1,2}$/', (string)$monthRaw)) $missing[] = 'month';
            if (($yearRaw === '' && $yearRaw !== 0) || !preg_match('/^\d{4}$/', (string)$yearRaw)) $missing[] = 'year';
            if ($competition === '') $missing[] = 'competition';
            if ($studentsRaw === '') $missing[] = 'students';

            if (!empty($missing)) {
                $this->skip($rowNum, 'Kolom wajib kosong atau format tidak valid: '.implode(', ', $missing));
                continue;
            }

            // coerce month/year
            $month = $this->toIntSafe($monthRaw);
            $year  = $this->toIntSafe($yearRaw);
            if ($month === null || $month < 1 || $month > 12) {
                $this->skip($rowNum, "Kolom month tidak valid: {$monthRaw}");
                continue;
            }
            if ($year === null || $year < 1900 || $year > 2100) {
                $this->skip($rowNum, "Kolom year tidak valid: {$yearRaw}");
                continue;
            }

            // check team_type based min participants
            $minParticipants = (strtolower($teamType) === 'kelompok') ? 2 : 1;

            // resolve department id (accept numeric id or fuzzy name)
            $departmentId = $this->resolveDepartment($deptRaw);
            if ($departmentId === null) {
                $this->skip($rowNum, "Department tidak ditemukan: {$deptRaw}");
                continue;
            }

            // parse students entries
            $studentEntries = preg_split('/[;,\|\r\n]+/', $studentsRaw);
            $students = []; // each item => ['name'=>..., 'nim'=>...]
            foreach ($studentEntries as $entry) {
                $entry = trim((string)$entry);
                if ($entry === '') continue;

                $name = null;
                $nim  = null;

                // "Name (NIM)"
                if (preg_match('/^(.*?)\s*\(\s*([0-9A-Za-z\-\_\.\/]+)\s*\)\s*$/u', $entry, $m)) {
                    $name = trim($m[1]);
                    $nim  = trim($m[2]);
                } else {
                    // if token looks like NIM (contains digits and is single token)
                    if (preg_match('/^[0-9A-Za-z\-\_\.\/]+$/u', $entry) && preg_match('/\d/', $entry)) {
                        $nim = $entry;
                        $name = $entry; // fallback name = nim
                    } else {
                        // assume name only
                        $name = $entry;
                        $nim  = '';
                    }
                }
                $students[] = ['name' => $name, 'nim' => $nim];
            }

            // count effective participants
            $effective = 0;
            foreach ($students as $s) {
                if (!empty(trim((string)$s['name'])) || !empty(trim((string)$s['nim']))) $effective++;
            }
            if ($effective < $minParticipants) {
                $this->skip($rowNum, "Jumlah peserta ({$effective}) kurang dari minimal untuk tipe tim '{$teamType}' (minimal {$minParticipants}).");
                continue;
            }

            // All validations passed — create Achievement and students attachment
            DB::beginTransaction();
            try {
                $ach = Achievement::create([
                    'department_id'    => $departmentId,
                    'team'             => $team,
                    'type_achievement' => $teamType,
                    'field'            => $field,
                    'level'            => $level,
                    'competition'      => $competition,
                    'rank'             => $rank !== '' ? $rank : null,
                    'organizer'        => $organizer,
                    'month'            => sprintf('%02d', $month),
                    'year'             => (int)$year,
                ]);

                $attachedStudentIds = [];
                foreach ($students as $s) {
                    $sName = trim((string)$s['name']);
                    $sNim  = trim((string)$s['nim']);

                    $student = null;
                    if ($sNim !== '') {
                        // try find by NIM with same department first
                        $student = Student::where('nim', $sNim)
                            ->where('department_id', $departmentId)
                            ->first();

                        // fallback: find by nim globally
                        if (!$student) {
                            $student = Student::where('nim', $sNim)->first();
                        }
                    }

                    if (!$student) {
                        // create new student. if nim empty -> generate placeholder
                        $nimToSave = $sNim !== '' ? $sNim : strtoupper(uniqid('NIM'));
                        $nameToSave = $sName !== '' ? $sName : $nimToSave;

                        $student = Student::create([
                            'nim' => $nimToSave,
                            'name' => $nameToSave,
                            'department_id' => $departmentId,
                            'photo' => null,
                        ]);
                    } else {
                        // update name if it's empty or equals nim placeholder (OPTIONAL: conservative update)
                        if ((empty($student->name) || $student->name === $student->nim) && $sName !== '') {
                            $student->name = $sName;
                            $student->save();
                        }
                        // if department is empty, set it
                        if (empty($student->department_id)) {
                            $student->department_id = $departmentId;
                            $student->save();
                        }
                    }

                    $attachedStudentIds[] = (int)$student->id;

                    // Attach to pivot with certificate=null (we cannot import certificate file via excel)
                    $ach->students()->attach($student->id, [
                        'certificate' => null,
                    ]);
                }

                DB::commit();
                $this->report['success']++;
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->skip($rowNum, 'Gagal menyimpan: '.$e->getMessage());
            }
        } // endforeach rows
    }

    // ---------- helpers ----------

    protected function skip(int $row, string $msg): void
    {
        $this->report['skip']++;
        $this->report['errors'][] = ['row' => $row, 'message' => $msg];
    }

    protected function normalize(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9\s]+/u', ' ', $s);
        $s = preg_replace('/\s+/u', ' ', $s);
        return trim($s);
    }

    /** try resolve department (id or fuzzy name) */
    protected function resolveDepartment(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '') return null;

        // numeric id?
        if (ctype_digit((string)$raw)) {
            $id = (int)$raw;
            if (Department::where('id', $id)->exists()) return $id;
            return null;
        }

        $norm = $this->normalize($raw);
        // exact normalized match
        foreach ($this->deptIndex as $d) {
            if ($d['norm'] === $norm) return $d['id'];
        }

        // best like match
        $dept = Department::whereRaw('LOWER(name) like ?', ['%'.mb_strtolower($raw).'%'])->first();
        if ($dept) return (int)$dept->id;

        // levenshtein fallback (small set)
        $best = null; $bestDist = PHP_INT_MAX;
        foreach ($this->deptIndex as $d) {
            $dist = levenshtein($norm, $d['norm']);
            if ($dist < $bestDist) { $bestDist = $dist; $best = $d; }
        }
        $threshold = max(2, (int)ceil(mb_strlen($norm) * 0.2));
        if ($best && $bestDist <= $threshold) return $best['id'];

        return null;
    }

    protected function toIntSafe($v): ?int
    {
        if (is_int($v)) return $v;
        if (is_float($v)) return (int)round($v);
        if (is_string($v) && trim($v) !== '' && is_numeric($v)) return (int)round((float)$v);
        return null;
    }

    public function summary(): array
    {
        return $this->report;
    }
}
