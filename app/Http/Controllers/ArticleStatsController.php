<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Department;
use App\Models\DepartmentHead;
use App\Models\FacultyHead;
use App\Models\Lecturer;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ArticleStatsController extends Controller
{
    /**
     * Tampilkan daftar artikel untuk: kategori (dosen|mahasiswa|mix),
     * bucket (TS|TS-1|TS-2), dan type_journal.
     *
     * Query string opsional:
     *  - start=YYYY-MM-DD
     *  - end=YYYY-MM-DD
     *  - department_id (only admin/faculty_head)
     */
    // Controller: ArticleStatsController (only bucket method shown)
public function bucket(Request $request, string $category, string $bucket, string $type)
{
    // sanitasi input dasar
    $allowedCategories = ['dosen', 'mahasiswa', 'mix'];
    $allowedBuckets = ['TS', 'TS-1', 'TS-2'];

    $category = in_array(strtolower($category), $allowedCategories, true) ? strtolower($category) : 'mix';
    $bucket   = in_array($bucket, $allowedBuckets, true) ? $bucket : 'TS';
    $type     = urldecode($type); // decode dari URL

    // Resolve window (Carbon instances) — jika query start/end diberikan, itu akan dipakai
    [$start, $end] = $this->resolveWindow($bucket, $request->query('start'), $request->query('end'));

    // Base query (with eager-load)
    $q = Article::query()
        ->with([
            // lecturers: hanya ambil id + nidn (sesuai preferensi: tampilkan nidn)
            'lecturers' => function ($q) {
                $q->select('lecturers.id','lecturers.nidn','lecturers.department_id', 'lecturers.name');
            },
            // students: ambil id,name,nim
            'students' => function ($q) {
                $q->select('students.id','students.name','students.nim');
            },
            'department' => function($q){
                $q->select('departments.id','departments.name');
            }
        ])
        ->where('type_journal', $type);

    // ===== Scope department sesuai role =====
    $auth = Auth::user();
    $reqDept = $request->query('department_id');

    if ($auth && $auth->role !== 'admin') {
        // non-admin: department scope berasal dari akun (department_head or lecturer or faculty_head)
        if ($auth->role === 'department_head') {
            $deptId = DepartmentHead::where('user_id', $auth->id)->value('department_id');
            if ($deptId) $q->where('department_id', $deptId);
        } elseif ($auth->role === 'lecturer') {
            $deptId = Lecturer::where('user_id', $auth->id)->value('department_id');
            if ($deptId) $q->where('department_id', $deptId);
        } elseif ($auth->role === 'faculty_head') {
            // fakultas: faculty_head boleh filter department dalam fakultasnya lewat query param
            $facultyId = FacultyHead::where('user_id', $auth->id)->value('faculty_id');
            if ($reqDept && ctype_digit((string)$reqDept) && $facultyId) {
                // pastikan department yang diminta memang di fakultas yang dipegang
                $ok = Department::where('id', (int)$reqDept)->where('faculty_id', $facultyId)->exists();
                if ($ok) $q->where('department_id', (int)$reqDept);
            } else {
                // default: tampilkan semua prodi di fakultas
                if ($facultyId) {
                    $q->whereIn('department_id', Department::where('faculty_id', $facultyId)->pluck('id')->toArray());
                }
            }
        }
    } else {
        // admin: boleh memfilter via ?department_id=
        if ($reqDept && ctype_digit((string)$reqDept)) {
            $q->where('department_id', (int)$reqDept);
        }
    }

    // ===== Filter kategori (dosen/mahasiswa/mix) =====
    if ($category === 'dosen') {
        $q->where('category', 'dosen');
    } elseif ($category === 'mahasiswa') {
        $q->where('category', 'mahasiswa');
    } // else mix -> no category filter

    // ===== Filter rentang tanggal =====
    if ($start && $end) {
        $q->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
    }

    // Ambil data (pilih kolom utama)
    $articles = $q->orderByDesc('date')->orderBy('title')->get([
        'id','department_id','title','type_journal','url','doi',
        'publisher','date','volume','number','category','issn','file'
    ]);

    return view('article.bucket', [
        'user'     => $auth,
        'category' => $category,
        'bucket'   => $bucket,
        'type'     => $type,
        'start'    => $start ? $start->toDateString() : null,
        'end'      => $end   ? $end->toDateString()   : null,
        'articles' => $articles,
    ]);
}


    /**
     * Resolve window mengikuti aturan: TS spans Mar(startYear) -> Feb(startYear+1),
     * di mana startYear dipilih supaya 'now' ada di dalam periode TS:
     *  - jika now.month >= 3 -> startYear = now.year
     *  - jika now.month < 3  -> startYear = now.year - 1
     *
     * TS   => Mar(startYear) .. Feb(startYear+1)
     * TS-1 => Mar(startYear-1) .. Feb(startYear)
     * TS-2 => Mar(startYear-2) .. Feb(startYear-1)
     *
     * Jika query start/end diberikan, gunakan itu (override).
     */
    private function resolveWindow(string $bucket, ?string $start, ?string $end): array
    {
        if ($start && $end) {
            try {
                return [Carbon::parse($start), Carbon::parse($end)];
            } catch (\Throwable $e) {
                // fallback ke default di bawah
            }
        }

        // Anchor: tanggal hari ini
        $now = Carbon::today();

        // tentukan startYear agar 'now' termasuk pada periode Mar(startYear)..Feb(startYear+1)
        $startYear = ($now->month >= 3) ? $now->year : $now->year - 1;

        // TS periode
        $tsStart = Carbon::create($startYear, 3, 1)->startOfDay();                // Mar startYear-03-01
        $tsEnd   = Carbon::create($startYear + 1, 2, 1)->endOfMonth()->endOfDay(); // Feb startYear+1-02-(28/29)

        // TS-1 dan TS-2
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
