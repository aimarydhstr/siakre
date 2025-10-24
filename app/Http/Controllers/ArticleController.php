<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Student;
use App\Models\Lecturer;
use App\Models\Department;
use App\Models\User;
use App\Models\ArticleStudent;
use App\Models\ArticleLecturer;
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
        $pdf->move(public_path('article-files'), $fileName);

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
        return redirect()->route('article-index')->with('status', 'Artikel berhasil ditambahkan');
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
        if(!Auth::check()){
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        $downloadPath = storage_path("app/articles/{$file}");
        if (!file_exists($downloadPath)) {
            abort(404, 'File tidak ditemukan.');
        }

        return response()->download($downloadPath);
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
    public function edit(Request $request, $id)
    {
        $user = Auth::user();
        $data = Article::with(['students','lecturers'])->findOrFail($id);

        if($request->setting != NULL){
            $setting = $request->setting; 
        }
        else{
            if($data->category=="mahasiswa")
                $setting = $data->count_mahasiswa;
            else
                $setting = $data->count_dosen;
        }

        session(['setting' => $setting]);

        return view('article.update', compact('user','data', 'setting'));
    }

    // Update artikel
    public function update(Request $request, $id)
    {
        $article = Article::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'type_journal' => 'nullable|string|max:255',
            'publisher' => 'required|string|max:255',
            'url' => 'required|url',
            'date' => 'nullable|date',
            'file' => 'nullable|mimes:pdf|max:10000',
            'students' => 'array',
            'lecturers' => 'array',
        ]);

        // Upload file baru jika ada
        if ($request->file('file')) {
            File::delete(public_path('article/' . $article->file));
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('article'), $fileName);
            $article->file = $fileName;
        }

        $article->title = $request->title;
        $article->type_journal = $request->type_journal ?? $article->type_journal;
        $article->publisher = $request->publisher;
        $article->url = $request->url;
        $article->date = $request->date ?? $article->date;
        $article->save();

        // Sync mahasiswa atau dosen
        if ($article->category == 'mahasiswa') {
            $article->students()->sync($request->students ?? []);
        } else {
            $article->lecturers()->sync($request->lecturers ?? []);
        }

        return redirect()->route('articles.index')->with('success','Artikel berhasil diperbarui');
    }

    // Hapus artikel
    public function destroy($id)
    {
        $article = Article::findOrFail($id);

        // Hapus file fisik
        File::delete(public_path('article/' . $article->file));

        // Hapus relasi pivot otomatis karena Eloquent Many-to-Many
        $article->students()->detach();
        $article->lecturers()->detach();

        $article->delete();

        return redirect()->route('articles.index')->with('success','Artikel berhasil dihapus');
    }
}
