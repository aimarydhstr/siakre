<?php

namespace App\Exports;

use App\Models\Article;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Carbon\Carbon;

class dataExportArticle implements FromView
{
    use Exportable;

    /** @var 'mahasiswa'|'dosen'|null */
    private ?string $category = null;
    private ?int $departmentId = null;

    public function setMahasiswa(?int $departmentId = null): self
    {
        $this->category     = 'mahasiswa';
        $this->departmentId = $departmentId;
        return $this;
    }

    public function setDosen(?int $departmentId = null): self
    {
        $this->category     = 'dosen';
        $this->departmentId = $departmentId;
        return $this;
    }

    public function view(): View
    {
        $data_type_array = [
            "Seminar Nasional",
            "Seminar Internasional",
            "Jurnal Internasional",
            "Jurnal Internasional Bereputasi",
            "Jurnal Nasional Terakreditasi",
            "Jurnal Nasional Tidak Terakreditasi",
        ];

        // Siapkan counter
        $TS_array   = array_fill(0, count($data_type_array), 0);
        $TS_1_array = array_fill(0, count($data_type_array), 0);
        $TS_2_array = array_fill(0, count($data_type_array), 0);

        // Ambil artikel + filter kategori & department sesuai controller-mu
        $articles = Article::query()
            ->when($this->category === 'mahasiswa', function ($q) {
                $q->where('category', 'mahasiswa')
                  ->when($this->departmentId, fn($qq) =>
                      $qq->whereHas('students', fn($s) => $s->where('students.department_id', $this->departmentId))
                  );
            })
            ->when($this->category === 'dosen', function ($q) {
                $q->where('category', 'dosen')
                  ->when($this->departmentId, fn($qq) =>
                      $qq->whereHas('lecturers', fn($l) => $l->where('lecturers.department_id', $this->departmentId))
                  );
            })
            ->get(['type_journal', 'date']);

        if ($articles->isEmpty()) {
            // Render kosong saja
            return view('excel.article', compact('data_type_array', 'TS_array', 'TS_1_array', 'TS_2_array'));
        }

        // Helper parse tanggal (support Y-m-d, d-m-Y, timestamp)
        $parseDate = function ($val): ?Carbon {
            if ($val instanceof Carbon) return $val;
            if (is_numeric($val)) {
                try { return Carbon::createFromTimestamp((int)$val); } catch (\Throwable $e) { return null; }
            }
            if (is_string($val)) {
                $val = trim($val);
                // Coba Y-m-d
                try { return Carbon::createFromFormat('Y-m-d', $val); } catch (\Throwable $e) {}
                // Coba d-m-Y
                try { return Carbon::createFromFormat('d-m-Y', $val); } catch (\Throwable $e) {}
                // Coba generic parse
                try { return Carbon::parse($val); } catch (\Throwable $e) {}
            }
            return null;
        };

        // Tentukan ANCHOR berdasarkan tanggal TERBARU di dataset (bukan "hari ini")
        $latest = null;
        foreach ($articles as $row) {
            $d = $parseDate($row->date);
            if ($d && (!$latest || $d->gt($latest))) {
                $latest = $d->copy();
            }
        }
        if (!$latest) {
            return view('excel.article', compact('data_type_array', 'TS_array', 'TS_1_array', 'TS_2_array'));
        }

        // Hitung window tahun akademik (1 Sep – 31 Aug) berbasis $latest
        // Jika bulan latest >= 9, maka TS: Sep(latest->year) .. Aug(latest->year+1)
        // Jika bulan latest < 9, maka TS: Sep(latest->year-1) .. Aug(latest->year)
        if ($latest->month >= 9) {
            $tsStart = Carbon::create($latest->year, 9, 1)->startOfDay();
            $tsEnd   = Carbon::create($latest->year + 1, 8, 31)->endOfDay();
        } else {
            $tsStart = Carbon::create($latest->year - 1, 9, 1)->startOfDay();
            $tsEnd   = Carbon::create($latest->year, 8, 31)->endOfDay();
        }
        $ts1Start = $tsStart->copy()->subYear();
        $ts1End   = $tsEnd->copy()->subYear();
        $ts2Start = $tsStart->copy()->subYear(2);
        $ts2End   = $tsEnd->copy()->subYear(2);

        // Fungsi between inklusif (tanpa bergantung versi Carbon)
        $betweenInc = fn (Carbon $d, Carbon $a, Carbon $b) => $d->gte($a) && $d->lte($b);

        // Normalisasi type map (trim)
        $indexOfType = function (string $type) use ($data_type_array): int {
            $t = trim($type);
            foreach ($data_type_array as $i => $label) {
                if ($t === $label) return $i;
            }
            // fallback: case-insensitive
            foreach ($data_type_array as $i => $label) {
                if (strcasecmp($t, $label) === 0) return $i;
            }
            return -1;
        };

        // Akumulasi
        foreach ($articles as $row) {
            $idx = $indexOfType((string)($row->type_journal ?? ''));
            if ($idx < 0) continue;

            $d = $parseDate($row->date);
            if (!$d) continue;

            if ($betweenInc($d, $tsStart, $tsEnd)) {
                $TS_array[$idx]++;
            } elseif ($betweenInc($d, $ts1Start, $ts1End)) {
                $TS_1_array[$idx]++;
            } elseif ($betweenInc($d, $ts2Start, $ts2End)) {
                $TS_2_array[$idx]++;
            }
        }

        return view('excel.article', [
            'data_type_array' => $data_type_array,
            'TS_array'        => $TS_array,
            'TS_1_array'      => $TS_1_array,
            'TS_2_array'      => $TS_2_array,
        ]);
    }
}
