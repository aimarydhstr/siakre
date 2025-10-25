<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Article;
use App\Models\Student;
use App\Models\Lecturer;
use App\Models\Department;
use App\Models\User;
use App\Models\StudentArticle;
use App\Models\LecturerArticle;
use App\Models\DepartmentHead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class ArticleController extends Controller
{
    // Menampilkan daftar artikel dengan pencarian
    public function index(Request $request)
    {
        $user   = Auth::user();
        $search = trim($request->input('search', ''));

        $query = Article::query()
            ->with(['students', 'lecturers.user']); // cegah N+1

        // === RBAC scope ===
        if ($user->role === 'lecturer') {
            // cari lecturer_id dari user yang login
            $lecturerId = Lecturer::where('user_id', $user->id)->value('id');

            if ($lecturerId) {
                $query->whereHas('lecturers', function ($q) use ($lecturerId) {
                    $q->where('lecturers.id', $lecturerId);
                });
            } else {
                // jika belum punya profil lecturer, kosongkan hasil
                $query->whereRaw('1=0');
            }

        } elseif ($user->role === 'department_head') {
            // batasi ke departemen kepala jurusan tsb
            $deptId = DepartmentHead::where('user_id', $user->id)->value('department_id');

            if ($deptId) {
                $query->where('department_id', $deptId);
            } else {
                $query->whereRaw('1=0');
            }

        } elseif ($user->role === 'admin') {
            // admin: no scope (lihat semua)
        }

        // === Pencarian (di-group agar OR tidak bocor) ===
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                ->orWhere('publisher', 'like', "%{$search}%")
                ->orWhereHas('students', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('nim', 'like', "%{$search}%");
                })
                ->orWhereHas('lecturers', function ($lq) use ($search) {
                    $lq->whereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
                });
            });
        }

        $articles = $query
            ->orderBy('date', 'desc')
            ->paginate(5)
            ->withQueryString(); // jaga query ?search= saat pindah halaman

        return view('article.index', [
            'user' => $user,
            'data' => $articles,
        ]);
    }

    // Tampilkan form tambah artikel
    public function create()
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        $auth = Auth::user();

        // department_id user (untuk non-admin)
        $departmentId = null;
        if ($auth->role === 'department_head') {
            // relasi optional: $auth->department_head harus sudah diset di model User
            $departmentId = optional($auth->department_head)->department_id;
        } elseif ($auth->role === 'lecturer') {
            $departmentId = optional($auth->lecturer)->department_id;
        }

        // Admin bisa memilih program studi
        $departments = null;
        if ($auth->role === 'admin') {
            $departments = Department::select('id','name')->orderBy('name')->get();
        }

        // Daftar dosen untuk dropdown (dibatasi prodi user jika non-admin)
        $lecturers = Lecturer::query()
            ->with(['user:id,name,email'])
            ->when($auth->role !== 'admin' && $departmentId, function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })
            ->orderBy('id', 'asc') // ganti sesuai preferensi (atau sort by user.name dengan join manual)
            ->get(['id','user_id','department_id']);

        return view('article.add', [
            'departments' => $departments,
            'lecturers'   => $lecturers,
            'user'        => $auth,
        ]);
    }

    /**
     * Simpan artikel baru.
     * Form action yang diharapkan: route('article-add-send')
     */
    public function store(Request $request)
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        $auth = Auth::user();

        // Tentukan department_id (admin pilih; non-admin ikut departemen akunnya)
        $departmentId = null;
        if ($auth->role === 'admin') {
            $request->validate([
                'department_id' => ['required','exists:departments,id'],
            ]);
            $departmentId = (int) $request->department_id;
        } elseif ($auth->role === 'department_head') {
            $departmentId = optional($auth->department_head)->department_id;
        } elseif ($auth->role === 'lecturer') {
            $departmentId = optional($auth->lecturer)->department_id;
        }

        if (!$departmentId) {
            return back()->withErrors(['general' => 'Akun belum terhubung ke Program Studi.'])->withInput();
        }

        // Validasi utama (DOI wajib & unik)
        $request->validate([
            'title'        => 'required|string|max:255',
            'type_journal' => 'required|in:Seminar Nasional,Seminar Internasional,Jurnal Internasional,Jurnal Internasional Bereputasi,Jurnal Nasional Terakreditasi,Jurnal Nasional Tidak Terakreditasi',
            'url'          => 'required|url|max:500',
            'doi'          => 'required|string|max:255|unique:articles,doi',
            'publisher'    => 'required|string|max:255',
            'date'         => 'required|string', // dd-mm-yyyy
            'category'     => 'required|in:dosen,mahasiswa,gabungan',
            'volume'       => 'nullable|string|max:50',
            'number'       => 'nullable|string|max:50',
            'file'         => 'required|mimes:pdf|max:10240', // maks 10MB

            // Penulis dosen (opsional)
            'lecturer_ids'   => 'nullable|array',
            'lecturer_ids.*' => 'nullable|exists:lecturers,id',

            // Penulis mahasiswa (opsional, dinamis)
            'student_names'   => 'nullable|array',
            'student_names.*' => 'nullable|string|max:255',
            'student_nims'    => 'nullable|array',
            'student_nims.*'  => 'nullable|string|max:50',
        ]);

        // Parse tanggal dd-mm-yyyy -> Y-m-d
        try {
            $date = Carbon::createFromFormat('d-m-Y', $request->date)->format('Y-m-d');
        } catch (\Throwable $e) {
            return back()->withErrors(['date' => 'Format tanggal harus dd-mm-yyyy.'])->withInput();
        }

        // Upload file PDF
        $pdf = $request->file('file');
        $fileName = time().'_'.$pdf->getClientOriginalName();
        $pdf->move(public_path('article'), $fileName);

        // Simpan artikel
        $article = Article::create([
            'department_id' => $departmentId,
            'title'         => $request->title,
            'type_journal'  => $request->type_journal,
            'url'           => $request->url,
            'doi'           => $request->doi,
            'publisher'     => $request->publisher,
            'date'          => $date,
            'category'      => $request->category,
            'volume'        => $request->volume ?: null,
            'number'        => $request->number ?: null,
            'file'          => $fileName,
        ]);

        // -------- Penulis Dosen (pivot lecturer_articles) --------
        // dari dropdown lecturer_ids[]
        $lecturerIds = collect($request->input('lecturer_ids', []))
            ->filter(function ($x) { return !empty($x); })
            ->map(function ($x) { return (int) $x; })
            ->unique()
            ->values();

        if ($lecturerIds->isNotEmpty()) {
            $pairs = [];
            foreach ($lecturerIds as $lid) {
                $pairs[] = ['lecturer_id' => $lid, 'article_id' => $article->id];
            }
            if (!empty($pairs)) {
                LecturerArticle::insert($pairs);
            }
        }

        // -------- Penulis Mahasiswa (pivot student_articles) --------
        $studentNames = $request->input('student_names', []);
        $studentNims  = $request->input('student_nims', []);
        $rows = max(count($studentNames), count($studentNims));

        for ($i = 0; $i < $rows; $i++) {
            $name = trim(isset($studentNames[$i]) ? $studentNames[$i] : '');
            $nim  = trim(isset($studentNims[$i])  ? $studentNims[$i]  : '');
            if ($name === '' && $nim === '') {
                continue; // skip baris kosong
            }

            // Cari/buat student pada departemen terkait
            $student = null;
            if ($nim !== '') {
                $student = Student::where('nim', $nim)
                    ->where('department_id', $departmentId)
                    ->first();
            }
            if (!$student) {
                $student = Student::create([
                    'nim'           => $nim,
                    'name'          => $name !== '' ? $name : $nim, // fallback
                    'photo'         => null,
                    'department_id' => $departmentId,
                ]);
            } else {
                // update nama jika diisi (agar sinkron)
                if ($name !== '') {
                    $student->name = $name;
                    $student->save();
                }
            }

            StudentArticle::create([
                'student_id' => $student->id,
                'article_id' => $article->id,
            ]);
        }

        // Redirect ke index artikel (ganti route bila beda)
        return redirect()->route('article')->with('status', 'Artikel berhasil ditambahkan');
    }

    // Menampilkan detail artikel
    public function show($id)
    {
        $user = Auth::user();
        $data = Article::findOrFail($id);

        return view('article.detail', compact('user','data'));
    }

    // Download file artikel
    public function download($file)
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        $auth = Auth::user();

        // Hindari path traversal dan pastikan file tercatat di DB
        $safeName = basename($file);
        $article  = Article::where('file', $safeName)->firstOrFail();

        // Tentukan department user untuk non-admin
        $userDeptId = null;
        if ($auth->role === 'department_head') {
            $userDeptId = DepartmentHead::where('user_id', $auth->id)->value('department_id');
        } elseif ($auth->role === 'lecturer') {
            $userDeptId = Lecturer::where('user_id', $auth->id)->value('department_id');
        }

        // Otorisasi: admin bebas; non-admin hanya boleh akses artikel di departemennya
        if ($auth->role !== 'admin') {
            if (!$userDeptId || (int)$article->department_id !== (int)$userDeptId) {
                abort(403, 'Unauthorized');
            }
        }

        // Lokasi file yang mungkin (utama di public/, fallback ke storage/)
        $candidates = [
            public_path('article/'.$safeName),
            storage_path('app/articles/'.$safeName),
        ];

        $path = null;
        foreach ($candidates as $p) {
            if (File::exists($p)) {
                $path = $p;
                break;
            }
        }

        if (!$path) {
            abort(404, 'File tidak ditemukan.');
        }

        // Unduh dengan nama asli di DB
        return response()->download($path, $safeName);
    }


    public function select(Request $request)
    {
        if(!Auth::check()){
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        $user = Auth::user();
        $department_id = ($user->role != 'admin') ? $user->department_id : null;
        $search = $request->cari;

        // Ambil artikel sesuai department dan kategori
        $articles = \App\Models\Article::with(['students', 'lecturers'])
            ->when($department_id, fn($q) => $q->where('department_id', $department_id))
            ->when($search, fn($q) => $q->where('title', 'like', "%{$search}%"))
            ->paginate(10);

        return view('article.select', compact('user', 'articles'));
    }

    public function selectPost(Request $request)
    {
        if(!Auth::check()){
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        $user = Auth::user();
        $department_id = ($user->role != 'admin') ? $user->department_id : null;

        $valueDosen = $request->valueDosen ?? 1;
        $valuePeserta = $request->valuePeserta;
        $category = $request->category;

        // Langsung kirim ke route add dengan query parameters
        return redirect()->route('article-add', [
            'category' => $category,
            'mahasiswa' => $valuePeserta,
            'dosen' => $valueDosen,
            'department_id' => $department_id
        ]);
    }

    // Tampilkan form edit artikel
    public function edit($id)
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        $auth = Auth::user();

        // department_id user (untuk non-admin)
        $userDeptId = null;
        if ($auth->role === 'department_head') {
            $userDeptId = DepartmentHead::where('user_id', $auth->id)->value('department_id');
        } elseif ($auth->role === 'lecturer') {
            $userDeptId = Lecturer::where('user_id', $auth->id)->value('department_id');
        }

        // Ambil artikel + relasi
        $article = Article::with([
            'department:id,name',
            // Jika punya relasi, boleh tambahkan: 'lecturers.user:id,name'
        ])->findOrFail($id);

        // Batasi akses lintas prodi utk non-admin
        if ($auth->role !== 'admin' && $userDeptId && (int)$article->department_id !== (int)$userDeptId) {
            abort(403, 'Anda tidak memiliki akses ke data departemen ini.');
        }

        // Admin: daftar prodi
        $departments = ($auth->role === 'admin')
            ? Department::select('id','name')->orderBy('name')->get()
            : null;

        // Daftar dosen untuk dropdown (dibatasi prodi user jika non-admin)
        $lecturers = Lecturer::query()
            ->with(['user:id,name,email'])
            ->when($auth->role !== 'admin' && $userDeptId, function ($q) use ($userDeptId) {
                $q->where('department_id', $userDeptId);
            })
            ->orderBy('id', 'asc')
            ->get(['id','user_id','department_id']);

        // Ambil penulis dosen & mahasiswa yang ter-attach sekarang
        $currentLecturerIds = LecturerArticle::where('article_id', $article->id)->pluck('lecturer_id')->toArray();
        $currentStudents     = Student::select('students.id','students.name','students.nim')
            ->join('student_articles','student_articles.student_id','=','students.id')
            ->where('student_articles.article_id', $article->id)
            ->get();

        // Format tanggal dd-mm-YYYY untuk field
        $dateValue = $article->date ? Carbon::parse($article->date)->format('d-m-Y') : '';

        return view('article.update', [
            'user'               => $auth,
            'article'            => $article,
            'departments'        => $departments,
            'lecturers'          => $lecturers,
            'currentLecturerIds' => $currentLecturerIds,
            'currentStudents'    => $currentStudents,
            'dateValue'          => $dateValue,
        ]);
    }

    /**
     * Proses Update Artikel
     * Route (PUT): article-update
     */
    public function update(Request $request, $id)
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        $auth = Auth::user();

        // Ambil artikel
        $article = Article::findOrFail($id);

        // Tentukan department_id (admin pilih; non-admin ikut prodi akun)
        $departmentId = null;
        if ($auth->role === 'admin') {
            $request->validate([
                'department_id' => ['required','exists:departments,id'],
            ]);
            $departmentId = (int) $request->department_id;
        } elseif ($auth->role === 'department_head') {
            $departmentId = DepartmentHead::where('user_id', $auth->id)->value('department_id');
        } elseif ($auth->role === 'lecturer') {
            $departmentId = Lecturer::where('user_id', $auth->id)->value('department_id');
        }

        if (!$departmentId) {
            return back()->withErrors(['general' => 'Akun belum terhubung ke Program Studi.'])->withInput();
        }

        // Otorisasi non-admin
        if ($auth->role !== 'admin' && (int)$article->department_id !== (int)$departmentId) {
            abort(403, 'Anda tidak memiliki akses ke data departemen ini.');
        }

        // Validasi (DOI unik kecuali dirinya)
        $request->validate([
            'title'        => 'required|string|max:255',
            'type_journal' => 'required|in:Seminar Nasional,Seminar Internasional,Jurnal Internasional,Jurnal Internasional Bereputasi,Jurnal Nasional Terakreditasi,Jurnal Nasional Tidak Terakreditasi',
            'url'          => 'required|url|max:500',
            'doi'          => 'required|string|max:255|unique:articles,doi,'.$article->id,
            'publisher'    => 'required|string|max:255',
            'date'         => 'required|string', // dd-mm-yyyy
            'category'     => 'required|in:dosen,mahasiswa',
            'volume'       => 'nullable|string|max:50',
            'number'       => 'nullable|string|max:50',
            'file'         => 'nullable|mimes:pdf|max:10240', // boleh kosong saat edit

            // Dosen
            'lecturer_ids'   => 'nullable|array',
            'lecturer_ids.*' => 'nullable|exists:lecturers,id',

            // Mahasiswa
            'student_names'   => 'nullable|array',
            'student_names.*' => 'nullable|string|max:255',
            'student_nims'    => 'nullable|array',
            'student_nims.*'  => 'nullable|string|max:50',
        ]);

        if ($request->category === 'dosen') {
            $request->validate(['lecturer_ids' => 'required|array|min:1']);
        } else { // mahasiswa
            $request->validate([
                'lecturer_ids'  => 'required|array|min:1',
                'student_names' => 'required|array|min:1',
            ]);
        }

        // Parse tanggal
        try {
            $date = Carbon::createFromFormat('d-m-Y', $request->date)->format('Y-m-d');
        } catch (\Throwable $e) {
            return back()->withErrors(['date' => 'Format tanggal harus dd-mm-yyyy.'])->withInput();
        }

        // Handle file PDF (opsional ganti)
        $fileName = $article->file; // default keep
        if ($request->hasFile('file')) {
            $pdf = $request->file('file');
            $newName = time().'_'.$pdf->getClientOriginalName();
            $pdf->move(public_path('article'), $newName);
            // Hapus file lama jika ada
            if ($article->file) {
                $oldPath = public_path('article/'.$article->file);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }
            $fileName = $newName;
        }

        // Update kolom Article
        $article->department_id = $departmentId;
        $article->title         = $request->title;
        $article->type_journal  = $request->type_journal;
        $article->url           = $request->url;
        $article->doi           = $request->doi;
        $article->publisher     = $request->publisher;
        $article->date          = $date;
        $article->category      = $request->category;
        $article->volume        = $request->volume ?: null;
        $article->number        = $request->number ?: null;
        $article->file          = $fileName;
        $article->save();

        // ------- Sinkron Penulis Dosen -------
        LecturerArticle::where('article_id', $article->id)->delete();
        $lecturerIdsInput = $request->input('lecturer_ids', []);
        $lecturerIds = [];
        if (is_array($lecturerIdsInput)) {
            foreach ($lecturerIdsInput as $lid) {
                if (!empty($lid)) {
                    $lid = (int)$lid;
                    if (!in_array($lid, $lecturerIds, true)) {
                        $lecturerIds[] = $lid;
                    }
                }
            }
        }
        if (!empty($lecturerIds)) {
            $rows = [];
            foreach ($lecturerIds as $lid) {
                $rows[] = ['lecturer_id' => $lid, 'article_id' => $article->id];
            }
            LecturerArticle::insert($rows);
        }

        // ------- Sinkron Penulis Mahasiswa -------
        StudentArticle::where('article_id', $article->id)->delete();

        // Tampilkan mahasiswa HANYA jika kategori 'mahasiswa' (sesuai aturan UI)
        if ($request->category === 'mahasiswa') {
            $studentNames = $request->input('student_names', []);
            $studentNims  = $request->input('student_nims', []);
            $rows = max(
                is_array($studentNames) ? count($studentNames) : 0,
                is_array($studentNims)  ? count($studentNims)  : 0
            );

            for ($i = 0; $i < $rows; $i++) {
                $name = '';
                $nim  = '';
                if (is_array($studentNames) && array_key_exists($i, $studentNames)) {
                    $name = trim($studentNames[$i]);
                }
                if (is_array($studentNims) && array_key_exists($i, $studentNims)) {
                    $nim = trim($studentNims[$i]);
                }
                if ($name === '' && $nim === '') {
                    continue; // skip baris kosong
                }

                // Cari/buat student di departemen ini
                $student = null;
                if ($nim !== '') {
                    $student = Student::where('nim', $nim)
                        ->where('department_id', $departmentId)
                        ->first();
                }
                if (!$student) {
                    $student = Student::create([
                        'nim'           => $nim,
                        'name'          => $name !== '' ? $name : $nim,
                        'photo'         => null,
                        'department_id' => $departmentId,
                    ]);
                } else {
                    if ($name !== '') {
                        $student->name = $name;
                        $student->save();
                    }
                }

                StudentArticle::create([
                    'student_id' => $student->id,
                    'article_id' => $article->id,
                ]);
            }
        }

        return redirect()->route('article')->with('status','Artikel berhasil diperbarui');
    }

    // Hapus artikel
    public function destroy($id)
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        $auth = Auth::user();

        // Ambil artikel
        $article = Article::findOrFail($id);

        // Tentukan department_id user (untuk non-admin)
        $userDeptId = null;
        if ($auth->role === 'department_head') {
            $userDeptId = DepartmentHead::where('user_id', $auth->id)->value('department_id');
        } elseif ($auth->role === 'lecturer') {
            $userDeptId = Lecturer::where('user_id', $auth->id)->value('department_id');
        }

        // Otorisasi: admin bebas; non-admin hanya boleh hapus artikel di departemennya
        if ($auth->role !== 'admin') {
            if (!$userDeptId || (int)$article->department_id !== (int)$userDeptId) {
                abort(403, 'Unauthorized');
            }
        }

        DB::beginTransaction();
        try {
            // 1) Hapus file PDF jika ada
            if ($article->file) {
                $path = public_path('article/'.$article->file);
                if (File::exists($path)) {
                    File::delete($path);
                }
            }

            // 2) Hapus relasi pivot
            LecturerArticle::where('article_id', $article->id)->delete();
            StudentArticle::where('article_id', $article->id)->delete();

            // 3) Hapus artikel
            $article->delete();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['general' => 'Gagal menghapus artikel: '.$e->getMessage()]);
        }

        return redirect()->route('article-index')->with('status', 'Artikel berhasil dihapus');
    }

}
