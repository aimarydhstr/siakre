<?php

namespace App\Http\Controllers;

use App\Models\Lecturer;
use App\Models\Article;
use App\Models\Book;
use App\Models\Hki;
use App\Models\FacultyHead;
use App\Models\DepartmentHead;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OutputController extends Controller
{
    /** Form pencarian dosen + (opsional) daftar hasil */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin','faculty_head','department_head'], true)) {
            abort(403, 'Hak akses tidak valid.');
        }

        $q = trim($request->input('q', ''));  // nidn atau nama
        $lecturers = collect();

        if ($q !== '') {
            $lecturers = Lecturer::query()
                ->from('lecturers')
                ->select('lecturers.*')
                ->with(['department:id,name,faculty_id'])
                ->leftJoin('departments', 'departments.id', '=', 'lecturers.department_id')
                // RBAC scope
                ->when($user->role === 'faculty_head', function($qq) use ($user) {
                    $facultyId = FacultyHead::where('user_id', $user->id)->value('faculty_id');
                    if (!$facultyId) abort(403, 'Akun Dekan belum terhubung ke Fakultas.');
                    $qq->where('departments.faculty_id', $facultyId);
                })
                ->when($user->role === 'department_head', function($qq) use ($user) {
                    $deptId = DepartmentHead::where('user_id', $user->id)->value('department_id');
                    if (!$deptId) abort(403, 'Akun Kaprodi belum terhubung ke Program Studi.');
                    $qq->where('lecturers.department_id', $deptId);
                })
                // Pencarian (sekarang pakai lecturers.name / lecturers.nidn)
                ->where(function($qq) use ($q) {
                    if (is_numeric($q)) {
                        $qq->where('lecturers.nidn', 'like', "%{$q}%");
                    } else {
                        $qq->where('lecturers.name', 'like', "%{$q}%")
                           ->orWhere('lecturers.nidn', 'like', "%{$q}%");
                    }
                })
                ->orderBy('lecturers.name')
                ->distinct('lecturers.id')
                ->take(20)
                ->get(['lecturers.*']);
        }

        return view('output.index', [
            'user'      => $user,
            'q'         => $q,
            'lecturers' => $lecturers,
        ]);
    }

    /** Redirect helper */
    public function find(Request $request)
    {
        $request->validate([
            'q' => 'required|string|max:255',
        ]);

        // cukup lempar balik ke index dengan query, biarkan user pilih kalau >1
        return redirect()->route('output.index', ['q' => trim($request->q)]);
    }

    /** Detail luaran dosen + filter per tabel */
    public function show($lecturerId, Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin','faculty_head','department_head'], true)) {
            abort(403, 'Hak akses tidak valid.');
        }

        $lecturer = Lecturer::with('department:id,name,faculty_id')->findOrFail($lecturerId);

        if ($user->role === 'faculty_head') {
            $facultyId = FacultyHead::where('user_id', $user->id)->value('faculty_id');
            if (!$facultyId || (int)$lecturer->department->faculty_id !== (int)$facultyId) {
                abort(403, 'Tidak berwenang melihat luaran dosen di luar fakultas Anda.');
            }
        } elseif ($user->role === 'department_head') {
            $deptId = DepartmentHead::where('user_id', $user->id)->value('department_id');
            if (!$deptId || (int)$lecturer->department_id !== (int)$deptId) {
                abort(403, 'Tidak berwenang melihat luaran dosen di luar prodi Anda.');
            }
        }

        // ===== Filters =====
        $artFrom   = $request->query('art_from');
        $artTo     = $request->query('art_to');

        $bookYFrom = $request->query('book_yfrom');
        $bookMFrom = $request->query('book_mfrom');
        $bookYTo   = $request->query('book_yto');
        $bookMTo   = $request->query('book_mto');

        $hkiFrom   = $request->query('hki_from');
        $hkiTo     = $request->query('hki_to');

        // ===== Artikel =====
        $articles = Article::query()
            ->with([
                'lecturers' => function ($q) {
                    // prefix kolom untuk hindari ambiguous "id"
                    $q->select('lecturers.id','lecturers.name');
                },
            ])
            ->whereHas('lecturers', fn($q) => $q->where('lecturers.id', $lecturer->id))
            ->when($artFrom && $artTo, fn($q) => $q->whereBetween('date', [$artFrom, $artTo]))
            ->when($artFrom && !$artTo, fn($q) => $q->where('date', '>=', $artFrom))
            ->when(!$artFrom && $artTo, fn($q) => $q->where('date', '<=', $artTo))
            ->orderBy('date', 'desc')
            ->paginate(10, ['*'], 'ap');

        // ===== Buku (year+month) =====
        $yf = $bookYFrom !== null && $bookYFrom !== '' ? (int)$bookYFrom : null;
        $mf = $bookMFrom !== null && $bookMFrom !== '' ? (int)$bookMFrom : null;
        $yt = $bookYTo   !== null && $bookYTo   !== '' ? (int)$bookYTo   : null;
        $mt = $bookMTo   !== null && $bookMTo   !== '' ? (int)$bookMTo   : null;

        $books = Book::query()
            ->with([
                'lecturers' => function ($q) {
                    $q->select('lecturers.id','lecturers.name');
                },
            ])
            ->whereHas('lecturers', fn($q) => $q->where('lecturers.id', $lecturer->id))
            ->when($yf || $mf || $yt || $mt, function($q) use ($yf,$mf,$yt,$mt) {
                if ($yf || $mf) {
                    $q->where(function($w) use ($yf,$mf) {
                        if ($yf) {
                            $w->where('publish_year', '>', $yf)
                            ->orWhere(function($w2) use ($yf,$mf) {
                                $w2->where('publish_year', '=', $yf);
                                if ($mf) $w2->where('publish_month', '>=', $mf);
                            });
                        } else {
                            if ($mf) $w->where('publish_month', '>=', $mf);
                        }
                    });
                }
                if ($yt || $mt) {
                    $q->where(function($w) use ($yt,$mt) {
                        if ($yt) {
                            $w->where('publish_year', '<', $yt)
                            ->orWhere(function($w2) use ($yt,$mt) {
                                $w2->where('publish_year', '=', $yt);
                                if ($mt) $w2->where('publish_month', '<=', $mt);
                            });
                        } else {
                            if ($mt) $w->where('publish_month', '<=', $mt);
                        }
                    });
                }
            })
            ->orderBy('publish_year', 'desc')
            ->orderBy('publish_month', 'desc')
            ->paginate(10, ['*'], 'bp');

        // ===== HKI =====
        $hkis = Hki::query()
            ->with([
                'lecturers' => function ($q) {
                    $q->select('lecturers.id','lecturers.name');
                },
            ])
            ->whereHas('lecturers', fn($q) => $q->where('lecturers.id', $lecturer->id))
            ->when($hkiFrom && $hkiTo, fn($q) => $q->whereBetween('date', [$hkiFrom, $hkiTo]))
            ->when($hkiFrom && !$hkiTo, fn($q) => $q->where('date', '>=', $hkiFrom))
            ->when(!$hkiFrom && $hkiTo, fn($q) => $q->where('date', '<=', $hkiTo))
            ->orderBy('date', 'desc')
            ->paginate(10, ['*'], 'hp');

        // Keep query string
        $articles->appends($request->query());
        $books->appends($request->query());
        $hkis->appends($request->query());

        return view('output.show', [
            'user'       => $user,
            'lecturer'   => $lecturer,
            'articles'   => $articles,
            'books'      => $books,
            'hkis'       => $hkis,

            'artFrom'    => $artFrom,
            'artTo'      => $artTo,

            'bookYFrom'  => $bookYFrom,
            'bookMFrom'  => $bookMFrom,
            'bookYTo'    => $bookYTo,
            'bookMTo'    => $bookMTo,

            'hkiFrom'    => $hkiFrom,
            'hkiTo'      => $hkiTo,
        ]);
    }

}
