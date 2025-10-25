<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Auth;

class ArticleStatsController extends Controller
{
    /**
     * Tampilkan daftar artikel untuk: kategori (dosen|mahasiswa|mix),
     * bucket (TS|TS-1|TS-2), dan type_journal.
     *
     * Query string opsional:
     *  - start=YYYY-MM-DD
     *  - end=YYYY-MM-DD
     */
    public function bucket(Request $request, string $category, string $bucket, string $type)
    {
        // Window tanggal
        [$start, $end] = $this->resolveWindow($bucket, $request->query('start'), $request->query('end'));

        // Query base
        $q = Article::query()
            ->with([
                'lecturers' => function ($q) {
                    $q->select('lecturers.id','lecturers.user_id')
                      ->with(['user' => function ($uq) {
                          $uq->select('users.id','users.name');
                      }]);
                },
                'students' => function ($q) {
                    $q->select('students.id','students.name','students.nim');
                },
            ])
            ->where('type_journal', $type);

        // Filter kategori
        if ($category === 'dosen') {
            $q->where('category', 'dosen');
        } elseif ($category === 'mahasiswa') {
            $q->where('category', 'mahasiswa');
        } else {
            // mix: jangan filter category
        }

        // Filter rentang tanggal
        if ($start && $end) {
            $q->whereBetween('date', [$start->startOfDay(), $end->endOfDay()]);
        }

        $articles = $q->orderByDesc('date')->orderBy('title')->get([
            'id','department_id','title','type_journal','url','doi',
            'publisher','date','volume','number','category'
        ]);

        return view('article.bucket', [
            'user'     => Auth::user(),
            'category' => $category,
            'bucket'   => $bucket,
            'type'     => $type,
            'start'    => $start ? $start->toDateString() : null,
            'end'      => $end   ? $end->toDateString()   : null,
            'articles' => $articles,
        ]);

    }

    /**
     * Hitung window TS/TS-1/TS-2 berdasarkan tahun akademik (1 Sep – 31 Aug).
     * Bisa dioverride dengan start/end query.
     */
    private function resolveWindow(string $bucket, ?string $start, ?string $end): array
    {
        if ($start && $end) {
            try {
                return [Carbon::parse($start), Carbon::parse($end)];
            } catch (\Throwable $e) {
                // fallback ke bawah
            }
        }

        // Anchor: tanggal hari ini (boleh kamu ganti ke anchor lain)
        $now = Carbon::today();

        // Tentukan TS (1 Sep – 31 Aug) yang mencakup 'now'
        if ($now->month >= 9) {
            $tsStart = Carbon::create($now->year, 9, 1);
            $tsEnd   = Carbon::create($now->year + 1, 8, 31);
        } else {
            $tsStart = Carbon::create($now->year - 1, 9, 1);
            $tsEnd   = Carbon::create($now->year, 8, 31);
        }

        $ts1Start = $tsStart->copy()->subYear();
        $ts1End   = $tsEnd->copy()->subYear();
        $ts2Start = $tsStart->copy()->subYears(2);
        $ts2End   = $tsEnd->copy()->subYears(2);

        switch ($bucket) {
            case 'TS':
                return [$tsStart, $tsEnd];
            case 'TS-1':
                return [$ts1Start, $ts1End];
            case 'TS-2':
                return [$ts2Start, $ts2End];
            default:
                return [$tsStart, $tsEnd];
        }
    }
}
