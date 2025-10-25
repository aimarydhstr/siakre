<?php

namespace App\Exports;

use App\Models\Article; // <- pakai model yang benar (PascalCase)
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;

class listExportArticle implements FromView
{
    use Exportable;

    /** @var string|null */
    private ?string $type = null;

    // Sesuai controller yg sudah ada:
    public function setsn()  { $this->type = "Seminar Nasional"; return $this; }
    public function setsi()  { $this->type = "Seminar Internasional"; return $this; }
    public function setji()  { $this->type = "Jurnal Internasional"; return $this; }
    public function setjib() { $this->type = "Jurnal Internasional Bereputasi"; return $this; }
    public function setjnt() { $this->type = "Jurnal Nasional Terakreditasi"; return $this; }
    public function setjntt(){ $this->type = "Jurnal Nasional Tidak Terakreditasi"; return $this; }

    public function view(): View
    {
        // Fail-safe: kalau belum diset, kembalikan kosong saja
        if (!$this->type) {
            $data_article = collect();
            return view('excel.article_list', compact('data_article'));
        }

        // Ambil data + relasi untuk dosen & mahasiswa agar blade bisa render rapi
        $data_article = Article::query()
            ->with([
                'lecturers.user:id,name', // lecturer -> user.name
                'students:id,name,nim',   // student name + nim
            ])
            ->where('type_journal', $this->type)
            ->orderByDesc('date')
            ->orderBy('title')
            ->get([
                'id',
                'department_id',
                'title',
                'type_journal',
                'url',
                'doi',
                'publisher',
                'date',
                'volume',
                'number',
            ]);

        return view('excel.article_list', [
            'data_article' => $data_article,
        ]);
    }
}
