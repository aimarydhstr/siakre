<?php

namespace App\Exports;

use App\Models\Achievement;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;

class userExportField implements FromView
{
    use Exportable;

    /** @var string|null 'Akademik'|'NonAkademik' */
    private ?string $fieldType = null;
    /** @var string|null 'Region'|'National'|'International' */
    private ?string $level = null;
    /** @var int|string|null */
    private $year = null;

    public function setAkademikRegion()         { $this->fieldType = 'Akademik';    $this->level = 'Region';        $this->year = session('akademikSESSION');     return $this; }
    public function setAkademikNational()       { $this->fieldType = 'Akademik';    $this->level = 'National';      $this->year = session('akademikSESSION');     return $this; }
    public function setAkademikInternational()  { $this->fieldType = 'Akademik';    $this->level = 'International'; $this->year = session('akademikSESSION');     return $this; }

    public function setNonAkademikRegion()      { $this->fieldType = 'NonAkademik'; $this->level = 'Region';        $this->year = session('nonAkademikSESSION');  return $this; }
    public function setNonAkademikNational()    { $this->fieldType = 'NonAkademik'; $this->level = 'National';      $this->year = session('nonAkademikSESSION');  return $this; }
    public function setNonAkademikInternational(){ $this->fieldType = 'NonAkademik'; $this->level = 'International'; $this->year = session('nonAkademikSESSION');  return $this; }

    public function view(): View
    {
        // Ambil data lengkap + relasi untuk dipakai di blade
        $achievements = Achievement::query()
            ->with([
                'department:id,name',
                'students' => function ($q) {
                    // prefix nama tabel untuk hindari ambiguous column
                    $q->select('students.id','students.name','students.nim');
                },
            ])
            ->where('field', $this->fieldType)
            ->where('level', $this->level)
            ->when($this->year, fn($q) => $q->where('year', (int)$this->year))
            ->orderByDesc('year')
            ->orderBy('competition')
            ->get([
                'id',
                'department_id',
                'team',
                'field',
                'level',
                'competition',
                'rank',        // <- gunakan rank
                'organizer',
                'month',
                'year',
            ]);

        /**
         * Flatten ke baris per mahasiswa (supaya cocok untuk sheet per peserta).
         * Field yang disediakan:
         * - student_name, student_nim
         * - competition, rank, year
         * - department_name, team, field, level, organizer, month
         * - certificate (dari pivot kalau relasi Achievement::students menggunakan ->withPivot('certificate'))
         */
        $rows = collect();

        foreach ($achievements as $a) {
            foreach ($a->students as $s) {
                $rows->push((object) [
                    'student_name'    => $s->name ?? '—',
                    'student_nim'     => $s->nim  ?? '—',

                    'competition'     => $a->competition ?? '—',
                    'rank'            => $a->rank ?? '—',              // <— pakai rank, bukan achievement
                    'year'            => $a->year ?? '—',

                    'department_name' => optional($a->department)->name ?? '—',
                    'team'            => $a->team ?? '—',
                    'field'           => $a->field ?? '—',
                    'level'           => $a->level ?? '—',
                    'organizer'       => $a->organizer ?? '—',
                    'month'           => $a->month ?? '—',

                    // butuh relasi pivot 'certificate' -> pastikan di model Achievement:
                    // return $this->belongsToMany(Student::class, 'student_achievements')->withPivot('certificate');
                    'certificate'     => $s->pivot->certificate ?? null,
                ]);
            }
        }

        // Kirim ke blade: excel.field (silakan sesuaikan blade untuk menampilkan kolom yang diinginkan)
        return view('excel.field', [
            'data' => $rows->values(),
        ]);
    }
}
