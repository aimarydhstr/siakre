<?php

namespace App\Exports;

use App\Models\Achievement;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;

class userExport implements FromView
{
    use Exportable;

    /** @var string|null 'Akademik'|'NonAkademik' */
    private ?string $field = null;

    public function setAkademik()    { $this->field = 'Akademik';    return $this; }
    public function setNonAkademik() { $this->field = 'NonAkademik'; return $this; }

    public function view(): View
    {
        // Ambil semua achievement sesuai bidang
        $all = Achievement::query()
            ->when($this->field, fn($q) => $q->where('field', $this->field))
            ->get(['team','field','level','competition','organizer','year']);

        // Kumpulkan daftar tahun unik (desc)
        $years = $all->pluck('year')->filter()->unique()->sortDesc()->values()->all();

        // Siapkan struktur hasil
        $regionCounts = [];
        $nationalCounts = [];
        $internationalCounts = [];

        // Hitung per tahun
        foreach ($years as $y) {
            // Set untuk deduplikasi
            $seenRegion = [];
            $seenNational = [];
            $seenInternational = [];

            foreach ($all as $row) {
                if ((int)$row->year !== (int)$y) continue;

                // kunci unik per kompetisi / tim / penyelenggara / tahun
                $key = trim(($row->competition ?? ''))
                    .'|'.trim(($row->team ?? ''))
                    .'|'.trim(($row->organizer ?? ''))
                    .'|'.(string)$y;

                $level = $row->level;

                if ($level === 'Region') {
                    if (!isset($seenRegion[$key])) {
                        $seenRegion[$key] = true;
                    }
                } elseif ($level === 'National') {
                    if (!isset($seenNational[$key])) {
                        $seenNational[$key] = true;
                    }
                } elseif ($level === 'International') {
                    if (!isset($seenInternational[$key])) {
                        $seenInternational[$key] = true;
                    }
                }
            }

            $regionCounts[]        = count($seenRegion);
            $nationalCounts[]      = count($seenNational);
            $internationalCounts[] = count($seenInternational);
        }

        // Render ke blade yang sudah kamu pakai
        return view('excel.export', [
            'year_array'         => $years,
            'region_array'       => $regionCounts,
            'national_array'     => $nationalCounts,
            'international_array'=> $internationalCounts,
        ]);
    }
}
