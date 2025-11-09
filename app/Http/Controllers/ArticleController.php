<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Article;
use App\Imports\ArticlesImport;
use App\Models\Student;
use App\Models\Lecturer;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\FacultyHead;
use Maatwebsite\Excel\Facades\Excel;
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

        if (!in_array($user->role, ['admin','faculty_head','department_head'], true)) {
            abort(403, 'Hak akses tidak valid.');
        }

        // Eager-load tanpa lecturers.user
        $query = Article::query()->with([
            'students' => function($q){
                $q->select('students.id','students.name','students.nim');
            },
            'lecturers' => function($q){
                $q->select('lecturers.id','lecturers.name','lecturers.department_id');
            }
        ]);

        // ===== RBAC =====
        if ($user->role === 'faculty_head') {
            $facultyId = FacultyHead::where('user_id', $user->id)->value('faculty_id');
            if (!$facultyId) abort(403, 'Akun Dekan belum terhubung ke Fakultas.');

            // Artikel yang ditulis dosen dalam fakultas ini (via relasi ke department.faculty_id)
            
            $query->whereHas('department', fn($dq) => $dq->where('departments.faculty_id', (int)$facultyId));

        } elseif ($user->role === 'department_head') {
            $deptId = DepartmentHead::where('user_id', $user->id)->value('department_id');
            if (!$deptId) abort(403, 'Akun Kaprodi belum terhubung ke Prodi.');

            // Boleh 2 cara: (a) berdasarkan kolom articles.department_id
            $query->where('articles.department_id', (int)$deptId);
            // atau (b) berdasarkan dosen penulis artikel berada di departemen tsb:
            // $query->whereHas('lecturers', fn($lq)=>$lq->where('lecturers.department_id', (int)$deptId));
        }

        // ===== Pencarian (prefix tabel agar aman) =====
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('articles.title', 'like', "%{$search}%")
                ->orWhere('articles.publisher', 'like', "%{$search}%")
                ->orWhere('articles.issn', 'like', "%{$search}%")
                ->orWhere('articles.doi', 'like', "%{$search}%")
                ->orWhereHas('students', function ($sq) use ($search) {
                    $sq->where('students.name', 'like', "%{$search}%")
                        ->orWhere('students.nim', 'like', "%{$search}%");
                })
                ->orWhereHas('lecturers', function ($lq) use ($search) {
                    $lq->where('lecturers.name', 'like', "%{$search}%");
                });
            });
        }

        $articles = $query->orderBy('articles.date','desc')
                        ->orderBy('articles.id','desc')
                        ->paginate(5)
                        ->withQueryString();

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

        if (!in_array($auth->role, ['admin','faculty_head','department_head'], true)) {
            abort(403, 'Hak akses tidak valid.');
        }

        // ===== Tentukan cakupan department untuk form =====
        $departments = null;
        $lecturersQ  = Lecturer::query()
            ->select('lecturers.id','lecturers.name','lecturers.department_id')
            ->with(['department:id,faculty_id']);

        if ($auth->role === 'admin') {
            $departments = Department::select('id','name')->orderBy('name')->get();
            // admin melihat semua dosen
        } elseif ($auth->role === 'faculty_head') {
            $facultyId = FacultyHead::where('user_id',$auth->id)->value('faculty_id');
            if (!$facultyId) abort(403, 'Akun Dekan belum terhubung ke Fakultas.');

            // Opsional: tampilkan daftar prodi di fakultas untuk UX yang lebih jelas
            $departments = Department::select('id','name')
                ->where('faculty_id',(int)$facultyId)->orderBy('name')->get();

            $lecturersQ->whereHas('department', fn($dq)=>$dq->where('faculty_id',(int)$facultyId));
        } elseif ($auth->role === 'department_head') {
            $deptId = DepartmentHead::where('user_id',$auth->id)->value('department_id');
            if (!$deptId) abort(403, 'Akun Kaprodi belum terhubung ke Prodi.');

            $departments = Department::select('id','name')->where('id',(int)$deptId)->get();
            $lecturersQ->where('lecturers.department_id',(int)$deptId);
        }

        $lecturers = $lecturersQ->orderBy('lecturers.name','asc')->get();

        return view('article.add', [
            'departments' => $departments, // untuk admin/dekan
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
            return redirect('/user/login')->with('alert', 'Kamu harus login dulu');
        }
        $auth = Auth::user();
        if (!in_array($auth->role, ['admin','faculty_head','department_head'], true)) {
            abort(403, 'Hak akses tidak valid.');
        }

        // Tentukan department_id
        if ($auth->role === 'admin') {
            $request->validate(['department_id' => ['required','exists:departments,id']]);
            $departmentId = (int)$request->department_id;
        } elseif ($auth->role === 'faculty_head') {
            $facultyId = FacultyHead::where('user_id',$auth->id)->value('faculty_id');
            if (!$facultyId) return back()->withErrors(['general'=>'Akun Dekan belum terhubung ke Fakultas.'])->withInput();

            // Wajib pilih department di fakultasnya
            $request->validate(['department_id' => ['required','exists:departments,id']]);
            $departmentId = (int)$request->department_id;
            $valid = Department::where('id',$departmentId)->where('faculty_id',$facultyId)->exists();
            if (!$valid) return back()->withErrors(['department_id'=>'Prodi tidak berada dalam fakultas Anda.'])->withInput();

        } else { // department_head
            $departmentId = DepartmentHead::where('user_id',$auth->id)->value('department_id');
            if (!$departmentId) return back()->withErrors(['general'=>'Akun Kaprodi belum terhubung ke Prodi.'])->withInput();
        }

        // Validasi utama
        $request->validate([
            'title'        => 'required|string|max:255',
            'issn'         => 'required|string|max:255|unique:articles,issn',
            'type_journal' => 'required|in:Seminar Nasional,Seminar Internasional,Jurnal Internasional,Jurnal Internasional Bereputasi,Jurnal Nasional Terakreditasi,Jurnal Nasional Tidak Terakreditasi',
            'url'          => 'required|url|max:500',
            'doi'          => 'required|string|max:255|unique:articles,doi',
            'publisher'    => 'required|string|max:255',
            'date'         => 'required|string',
            'category'     => 'required|in:dosen,mahasiswa,gabungan',
            'volume'       => 'nullable|string|max:50',
            'number'       => 'nullable|string|max:50',
            'file'         => 'required|mimes:pdf|max:10240',

            'lecturer_ids'   => 'nullable|array',
            'lecturer_ids.*' => 'nullable|exists:lecturers,id',

            'student_names'   => 'nullable|array',
            'student_names.*' => 'nullable|string|max:255',
            'student_nims'    => 'nullable|array',
            'student_nims.*'  => 'nullable|string|max:50',
        ]);

        // Parse tanggal
        try {
            $date = Carbon::createFromFormat('d-m-Y', $request->date)->format('Y-m-d');
        } catch (\Throwable $e) {
            return back()->withErrors(['date' => 'Format tanggal harus dd-mm-yyyy.'])->withInput();
        }

        // Upload file
        $pdf = $request->file('file');
        $fileName = time().'_'.$pdf->getClientOriginalName();
        $pdf->move(public_path('article'), $fileName);

        // Simpan artikel
        $article = Article::create([
            'department_id' => $departmentId,
            'title'         => $request->title,
            'issn'          => $request->issn,
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

        // Penulis Dosen
        $lecturerIds = collect($request->input('lecturer_ids', []))
            ->filter(fn($x)=>!empty($x))
            ->map(fn($x)=>(int)$x)
            ->unique()
            ->values();

        if ($lecturerIds->isNotEmpty()) {
            $pairs = $lecturerIds->map(fn($lid)=>['lecturer_id'=>$lid,'article_id'=>$article->id])->toArray();
            LecturerArticle::insert($pairs);
        }

        // Penulis Mahasiswa
        $studentNames = $request->input('student_names', []);
        $studentNims  = $request->input('student_nims',  []);
        $rows = max(count($studentNames), count($studentNims));
        for ($i=0; $i<$rows; $i++) {
            $name = trim($studentNames[$i] ?? '');
            $nim  = trim($studentNims[$i]  ?? '');
            if ($name==='' && $nim==='') continue;

            $student = $nim !== '' ? Student::where('nim',$nim)->first() : null;
            if (!$student) {
                $student = Student::create([
                    'nim'           => $nim,
                    'name'          => $name !== '' ? $name : $nim,
                    'photo'         => null,
                    'department_id' => $departmentId,
                ]);
            } else if ($name !== '' && $student->name !== $name) {
                if (empty($student->name) || strlen($student->name) < strlen($name)) {
                    $student->name = $name; $student->save();
                }
            }

            StudentArticle::create(['student_id'=>$student->id,'article_id'=>$article->id]);
        }

        return redirect()->route('article')->with('status','Artikel berhasil ditambahkan');
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
        if (!in_array($auth->role, ['admin','faculty_head','department_head'], true)) {
            abort(403, 'Hak akses tidak valid.');
        }

        $safeName = basename($file);
        $article  = Article::where('file',$safeName)->firstOrFail();

        if ($auth->role !== 'admin') {
            if ($auth->role === 'faculty_head') {
                $facultyId = FacultyHead::where('user_id',$auth->id)->value('faculty_id');
                if (!$facultyId) abort(403,'Akun Dekan belum terhubung ke Fakultas.');
                $ok = Department::where('id',$article->department_id)->where('faculty_id',$facultyId)->exists();
                if (!$ok) abort(403,'Unauthorized');
            } else { // department_head
                $deptId = DepartmentHead::where('user_id',$auth->id)->value('department_id');
                if (!$deptId || (int)$article->department_id !== (int)$deptId) abort(403,'Unauthorized');
            }
        }

        $candidates = [
            public_path('article/'.$safeName),
            storage_path('app/articles/'.$safeName),
        ];
        $path = null;
        foreach ($candidates as $p) if (File::exists($p)) { $path = $p; break; }
        if (!$path) abort(404,'File tidak ditemukan.');

        return response()->download($path, $safeName);
    }


    // Tampilkan form edit artikel
    public function edit($id)
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }
        $auth = Auth::user();
        if (!in_array($auth->role, ['admin','faculty_head','department_head'], true)) {
            abort(403, 'Hak akses tidak valid.');
        }

        // Ambil artikel
        $article = Article::with('department:id,name,faculty_id')->findOrFail($id);

        // Otorisasi area
        if ($auth->role === 'faculty_head') {
            $facultyId = FacultyHead::where('user_id',$auth->id)->value('faculty_id');
            if (!$facultyId || (int)$article->department->faculty_id !== (int)$facultyId) {
                abort(403,'Unauthorized');
            }
        } elseif ($auth->role === 'department_head') {
            $deptId = DepartmentHead::where('user_id',$auth->id)->value('department_id');
            if (!$deptId || (int)$article->department_id !== (int)$deptId) {
                abort(403,'Unauthorized');
            }
        }

        // Dropdown department & lecturers sesuai role
        $departments = null;
        $lecturersQ  = Lecturer::query()
            ->select('lecturers.id','lecturers.name','lecturers.department_id')
            ->with('department:id,faculty_id');

        if ($auth->role === 'admin') {
            $departments = Department::select('id','name')->orderBy('name')->get();
        } elseif ($auth->role === 'faculty_head') {
            $facultyId = FacultyHead::where('user_id',$auth->id)->value('faculty_id');
            $departments = Department::select('id','name')->where('faculty_id',(int)$facultyId)->orderBy('name')->get();
            $lecturersQ->whereHas('department', fn($dq)=>$dq->where('faculty_id',(int)$facultyId));
        } else { // department_head
            $deptId = DepartmentHead::where('user_id',$auth->id)->value('department_id');
            $departments = Department::select('id','name')->where('id',(int)$deptId)->get();
            $lecturersQ->where('lecturers.department_id',(int)$deptId);
        }
        $lecturers = $lecturersQ->orderBy('lecturers.name','asc')->get();

        // Data penulis sekarang
        $currentLecturerIds = LecturerArticle::where('article_id',$article->id)->pluck('lecturer_id')->toArray();
        $currentStudents = Student::select('students.id','students.name','students.nim')
            ->join('student_articles','student_articles.student_id','=','students.id')
            ->where('student_articles.article_id',$article->id)
            ->get();

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


    public function update(Request $request, $id)
    {
        $article = Article::findOrFail($id);

        // Validasi dasar
        $request->validate([
            'department_id' => 'sometimes|exists:departments,id', // admin/dekan boleh ganti
            'title'         => 'required|string|max:255',
            'issn'          => 'required|string|max:255|unique:articles,issn,' . $article->id,
            'type_journal'  => 'required|in:Seminar Nasional,Seminar Internasional,Jurnal Internasional,Jurnal Internasional Bereputasi,Jurnal Nasional Terakreditasi,Jurnal Nasional Tidak Terakreditasi',
            'url'           => 'required|url|max:500',
            'doi'           => 'required|string|max:255|unique:articles,doi,' . $article->id,
            'publisher'     => 'required|string|max:255',
            'date'          => 'required|string',
            'category'      => 'required|in:dosen,mahasiswa,gabungan',
            'volume'        => 'nullable|string|max:50',
            'number'        => 'nullable|string|max:50',
            'file'          => 'nullable|mimes:pdf|max:10240',

            'lecturer_ids'   => 'nullable|array',
            'lecturer_ids.*' => 'nullable|exists:lecturers,id',

            'student_names'   => 'nullable|array',
            'student_names.*' => 'nullable|string|max:255',
            'student_nims'    => 'nullable|array',
            'student_nims.*'  => 'nullable|string|max:50',
        ]);

        // Format tanggal
        try {
            $date = Carbon::createFromFormat('d-m-Y', $request->date)->format('Y-m-d');
        } catch (\Throwable $e) {
            return back()->withErrors(['date' => 'Format tanggal harus dd-mm-yyyy.'])->withInput();
        }

        // RBAC saat ubah department_id
        $auth = Auth::user();
        $newDeptId = $article->department_id;
        if ($request->filled('department_id')) {
            if ($auth->role === 'admin') {
                $newDeptId = (int)$request->department_id;
            } elseif ($auth->role === 'faculty_head') {
                $facultyId = FacultyHead::where('user_id',$auth->id)->value('faculty_id');
                $ok = Department::where('id',$request->department_id)->where('faculty_id',$facultyId)->exists();
                if (!$ok) return back()->withErrors(['department_id'=>'Prodi tidak berada dalam fakultas Anda.'])->withInput();
                $newDeptId = (int)$request->department_id;
            } elseif ($auth->role === 'department_head') {
                // kaprodi tidak boleh pindah ke prodi lain
                $deptId = DepartmentHead::where('user_id',$auth->id)->value('department_id');
                if ((int)$request->department_id !== (int)$deptId) {
                    return back()->withErrors(['department_id'=>'Anda hanya bisa menetapkan prodi Anda sendiri.'])->withInput();
                }
                $newDeptId = (int)$deptId;
            }
        }

        // Update file jika ada upload baru
        $fileName = $article->file;
        if ($request->hasFile('file')) {
            $pdf = $request->file('file');
            $fileName = time().'_'.$pdf->getClientOriginalName();
            $pdf->move(public_path('article'), $fileName);

            $oldPath = public_path('article/'.$article->file);
            if ($article->file && file_exists($oldPath)) { @unlink($oldPath); }
        }

        // Update artikel
        $article->update([
            'department_id' => $newDeptId,
            'title'         => $request->title,
            'issn'          => $request->issn,
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

        // Reset & isi ulang penulis dosen
        LecturerArticle::where('article_id',$article->id)->delete();
        $lecturerIds = collect($request->input('lecturer_ids', []))
            ->filter(fn($x)=>!empty($x))
            ->map(fn($x)=>(int)$x)
            ->unique()
            ->values();

        if ($lecturerIds->isNotEmpty()) {
            $pairs = $lecturerIds->map(fn($lid)=>['lecturer_id'=>$lid,'article_id'=>$article->id])->toArray();
            LecturerArticle::insert($pairs);
        }

        // Reset & isi ulang penulis mahasiswa
        StudentArticle::where('article_id',$article->id)->delete();
        $studentNames = $request->input('student_names', []);
        $studentNims  = $request->input('student_nims',  []);
        $rows = max(count($studentNames), count($studentNims));
        for ($i=0; $i<$rows; $i++) {
            $name = trim($studentNames[$i] ?? '');
            $nim  = trim($studentNims[$i]  ?? '');
            if ($name==='' && $nim==='') continue;

            $student = $nim !== '' ? Student::where('nim',$nim)->first() : null;
            if (!$student) {
                $student = Student::create([
                    'nim'           => $nim,
                    'name'          => $name !== '' ? $name : $nim,
                    'photo'         => null,
                    'department_id' => $newDeptId,
                ]);
            } else if ($name !== '' && $student->name !== $name) {
                if (empty($student->name) || strlen($student->name) < strlen($name)) {
                    $student->name = $name; $student->save();
                }
            }
            StudentArticle::create(['student_id'=>$student->id,'article_id'=>$article->id]);
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
        if (!in_array($auth->role, ['admin','faculty_head','department_head'], true)) {
            abort(403, 'Hak akses tidak valid.');
        }

        $article = Article::with('department:id,faculty_id')->findOrFail($id);

        if ($auth->role === 'faculty_head') {
            $facultyId = FacultyHead::where('user_id',$auth->id)->value('faculty_id');
            if (!$facultyId || (int)$article->department->faculty_id !== (int)$facultyId) {
                abort(403,'Unauthorized');
            }
        } elseif ($auth->role === 'department_head') {
            $deptId = DepartmentHead::where('user_id',$auth->id)->value('department_id');
            if (!$deptId || (int)$article->department_id !== (int)$deptId) {
                abort(403,'Unauthorized');
            }
        }

        DB::beginTransaction();
        try {
            if ($article->file) {
                $path = public_path('article/'.$article->file);
                if (File::exists($path)) File::delete($path);
            }
            LecturerArticle::where('article_id',$article->id)->delete();
            StudentArticle::where('article_id',$article->id)->delete();
            $article->delete();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['general'=>'Gagal menghapus artikel: '.$e->getMessage()]);
        }

        return redirect()->route('article')->with('status','Artikel berhasil dihapus');
    }


    public function importForm()
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        // Tidak ada pilihan prodi di form; prodi diambil per-baris dari Excel
        return view('article.import', [
            'user' => Auth::user(),
        ]);
    }

    public function importStore(Request $request)
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        // Validasi file excel saja (tanpa department_id)
        $request->validate([
            'excel' => ['required','file','mimes:xlsx,xls,csv','max:20480'], // 20 MB
        ]);

        $import = new ArticlesImport; // tidak perlu departmentId di constructor

        try {
            Excel::import($import, $request->file('excel'));
        } catch (\Throwable $e) {
            return back()->withErrors(['excel' => 'Gagal membaca file: '.$e->getMessage()]);
        }

        $summary = $import->report; // ['success'=>N,'skip'=>M,'errors'=>[...]]
        $msg = "Import selesai. Sukses: {$summary['success']}, Terlewat: {$summary['skip']}.";

        return back()
            ->with('status', $msg)
            ->with('import_errors', $summary['errors']);
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
}
