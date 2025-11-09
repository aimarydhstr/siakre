<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\Department;
use App\Models\DepartmentHead;
use App\Models\FacultyHead;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\BooksImport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BookController extends Controller
{
    /** INDEX: daftar buku + pencarian + RBAC scope */
    public function index(Request $request)
    {
        $user   = Auth::user();
        $search = trim($request->input('search', ''));

        if (!in_array($user->role, ['admin','faculty_head','department_head'], true)) {
            abort(403, 'Hak akses tidak valid.');
        }

        $query = Book::query()->with([
            'lecturers' => function ($q) {
                $q->select('lecturers.id','lecturers.name','lecturers.department_id');
            },
            'students' => function ($q) {
                $q->select('students.id','students.name','students.nim');
            },
            'department' => function($q){
                $q->select('departments.id','departments.name','departments.faculty_id');
            }
        ]);

        // ===== RBAC scope berdasarkan books.department_id =====
        if ($user->role === 'faculty_head') {
            $facultyId = FacultyHead::where('user_id', $user->id)->value('faculty_id');
            if (!$facultyId) abort(403, 'Akun Dekan belum terhubung ke Fakultas.');
            // filter buku yang department.faculty_id = facultyId
            $query->whereHas('department', fn($dq) => $dq->where('departments.faculty_id', (int)$facultyId));
        } elseif ($user->role === 'department_head') {
            $deptId = DepartmentHead::where('user_id', $user->id)->value('department_id');
            if (!$deptId) abort(403, 'Akun Kaprodi belum terhubung ke Program Studi.');
            $query->where('books.department_id', (int)$deptId);
        }

        // ===== Pencarian (prefix kolom) =====
        if ($search !== '') {
            $query->where(function ($w) use ($search) {
                $w->where('books.title',   'like', "%{$search}%")
                ->orWhere('books.isbn',  'like', "%{$search}%")
                ->orWhere('books.publisher','like', "%{$search}%")
                ->orWhere('books.city',  'like', "%{$search}%")
                ->orWhere('books.publish_year', 'like', "%{$search}%")
                ->orWhereHas('lecturers', fn($lq) =>
                        $lq->where('lecturers.name','like',"%{$search}%")
                )
                ->orWhereHas('students', fn($sq) =>
                        $sq->where('students.name','like',"%{$search}%")
                        ->orWhere('students.nim','like',"%{$search}%")
                );
            });
        }

        $books = $query
            ->orderByDesc('books.publish_year')
            ->orderByDesc('books.publish_month')
            ->orderByDesc('books.id')
            ->paginate(10)
            ->withQueryString();

        return view('books.index', compact('books','user','search'));
    }


    /** CREATE PAGE */
    public function create()
    {
        $user = Auth::user();

        // Daftar dosen (dengan filter sesuai role)
        $lecturers = Lecturer::query()
            ->select('lecturers.id','lecturers.name','lecturers.department_id')
            ->with(['department' => fn($q)=>$q->select('departments.id','departments.faculty_id')])
            ->when($user->role === 'faculty_head', function ($q) use ($user) {
                $facultyId = FacultyHead::where('user_id', $user->id)->value('faculty_id');
                if ($facultyId) {
                    $q->whereHas('department', fn($dq)=>$dq->where('departments.faculty_id',$facultyId));
                } else {
                    $q->whereRaw('1=0');
                }
            })
            ->when($user->role === 'department_head', function ($q) use ($user) {
                $deptId = DepartmentHead::where('user_id', $user->id)->value('department_id');
                if ($deptId) {
                    $q->where('lecturers.department_id', $deptId);
                } else {
                    $q->whereRaw('1=0');
                }
            })
            ->orderBy('lecturers.name','asc')
            ->get();

        // Juga kirim daftar department (untuk admin / faculty_head memilih department saat tambah buku)
        $departments = Department::query()
            ->select('id','name','faculty_id')
            ->when($user->role === 'faculty_head', function($q) use ($user) {
                $facId = FacultyHead::where('user_id', $user->id)->value('faculty_id');
                if ($facId) $q->where('faculty_id', $facId);
                else $q->whereRaw('1=0');
            })
            ->when($user->role === 'department_head', function($q) use ($user) {
                $deptId = DepartmentHead::where('user_id', $user->id)->value('department_id');
                if ($deptId) $q->where('id', $deptId);
                else $q->whereRaw('1=0');
            })
            ->orderBy('name')
            ->get();

        return view('books.create', compact('lecturers','user','departments'));
    }


    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'isbn'           => 'required|string|max:50',
            'title'          => 'required|string|max:255',
            'publisher'      => 'required|string|max:255',
            'publish_month'  => 'required|integer|min:1|max:12',
            'publish_year'   => 'required|integer|min:1900|max:2100',
            'city'           => 'required|string|max:255',
            'file'           => 'required|mimes:pdf|max:10240',

            'department_id'  => 'required|exists:departments,id',

            'lecturer_ids'   => 'sometimes|array',
            'lecturer_ids.*' => 'nullable|exists:lecturers,id',

            'student_names'  => 'sometimes|array',
            'student_names.*'=> 'nullable|string|max:255',
            'student_nims'   => 'sometimes|array',
            'student_nims.*' => 'nullable|string|max:50',
        ]);

        // Validasi RBAC pada department_id:
        $deptId = (int) $request->input('department_id');
        if ($user->role === 'department_head') {
            $myDept = DepartmentHead::where('user_id', $user->id)->value('department_id');
            if (!$myDept || (int)$myDept !== $deptId) {
                return back()->withErrors(['department_id' => 'Kamu hanya boleh menambahkan buku untuk Program Studi Anda.'])->withInput();
            }
        } elseif ($user->role === 'faculty_head') {
            $myFac = FacultyHead::where('user_id', $user->id)->value('faculty_id');
            $deptFac = Department::where('id', $deptId)->value('faculty_id');
            if (!$myFac || (int)$deptFac !== (int)$myFac) {
                return back()->withErrors(['department_id' => 'Kamu hanya boleh menambahkan buku untuk Program Studi di bawah Fakultas Anda.'])->withInput();
            }
        }

        // Upload
        $fileName = null;
        if ($request->hasFile('file')) {
            $f = $request->file('file');
            $fileName = time().'_'.$f->getClientOriginalName();
            $f->move(public_path('books'), $fileName);
        }

        DB::beginTransaction();
        try {
            $book = Book::create([
                'isbn'          => $request->isbn,
                'title'         => $request->title,
                'publisher'     => $request->publisher,
                'publish_month' => $request->publish_month,
                'publish_year'  => $request->publish_year,
                'city'          => $request->city,
                'file'          => $fileName,
                'department_id' => $deptId,
            ]);

            // Dosen
            if ($request->has('lecturer_ids')) {
                $lecturerIds = collect($request->input('lecturer_ids', []))
                    ->filter(fn($x)=>!empty($x))
                    ->map(fn($x)=>(int)$x)->unique()->values()->all();
                if (!empty($lecturerIds)) $book->lecturers()->attach($lecturerIds);
            }

            // Mahasiswa
            if ($request->hasAny(['student_names','student_nims'])) {
                $names = $request->input('student_names', []);
                $nims  = $request->input('student_nims',  []);
                $rows  = max(count($names), count($nims));
                $studentIds = [];
                for ($i=0; $i<$rows; $i++) {
                    $name = trim($names[$i] ?? '');
                    $nim  = trim($nims[$i]  ?? '');
                    if ($name==='' && $nim==='') continue;

                    $student = $nim !== '' ? Student::where('nim',$nim)->first() : null;
                    if (!$student) {
                        $student = Student::create([
                            'nim'  => $nim !== '' ? $nim : strtoupper(uniqid('NIM')),
                            'name' => $name !== '' ? $name : ($nim ?: 'Tanpa Nama'),
                            'department_id' => $deptId, // cocokkan department mahasiswa dengan buku
                        ]);
                    } else {
                        // jika student exists dan belum ada department, atau beda, jangan overwrite kecuali diperlukan
                        if (empty($student->department_id)) {
                            $student->department_id = $deptId;
                            $student->save();
                        }
                    }
                    $studentIds[] = $student->id;
                }
                if (!empty($studentIds)) $book->students()->attach(array_unique($studentIds));
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($fileName) {
                $p = public_path('books/'.$fileName);
                if (File::exists($p)) File::delete($p);
            }
            return back()->withErrors(['general'=>'Gagal menyimpan buku: '.$e->getMessage()])->withInput();
        }

        return redirect()->route('books.index')->with('status','Buku berhasil ditambahkan');
    }


    /** EDIT PAGE */
    public function edit($id)
    {
        $book = Book::with([
            'lecturers' => fn($q)=>$q->select('lecturers.id','lecturers.name','lecturers.department_id'),
            'students'  => fn($q)=>$q->select('students.id','students.name','students.nim'),
            'department'=> fn($q)=>$q->select('departments.id','departments.name','departments.faculty_id'),
        ])->findOrFail($id);

        $user = Auth::user();

        // RBAC scope pada objek menggunakan book.department_id
        if ($user->role === 'department_head') {
            $deptId = DepartmentHead::where('user_id',$user->id)->value('department_id');
            $ok = $deptId && ((int)$book->department_id === (int)$deptId);
            if (!$ok) abort(403,'Unauthorized');
        } elseif ($user->role === 'faculty_head') {
            $facId = FacultyHead::where('user_id',$user->id)->value('faculty_id');
            $ok = $facId && optional($book->department)->faculty_id == $facId;
            if (!$ok) abort(403,'Unauthorized');
        } elseif ($user->role !== 'admin') {
            abort(403,'Hak akses tidak valid.');
        }

        // daftar dosen untuk pilihan (disaring role)
        $lecturers = Lecturer::query()
            ->select('lecturers.id','lecturers.name','lecturers.department_id')
            ->with(['department'=>fn($q)=>$q->select('departments.id','departments.faculty_id')])
            ->when($user->role === 'faculty_head', function ($q) use ($user) {
                $facId = FacultyHead::where('user_id',$user->id)->value('faculty_id');
                if ($facId) { $q->whereHas('department', fn($dq)=>$dq->where('departments.faculty_id',$facId)); }
                else { $q->whereRaw('1=0'); }
            })
            ->when($user->role === 'department_head', function ($q) use ($user) {
                $deptId = DepartmentHead::where('user_id',$user->id)->value('department_id');
                if ($deptId) { $q->where('lecturers.department_id',$deptId); }
                else { $q->whereRaw('1=0'); }
            })
            ->orderBy('lecturers.name','asc')
            ->get();

        // daftar departments (untuk edit department_id jika admin/faculty_head)
        $departments = Department::query()
            ->select('id','name','faculty_id')
            ->when($user->role === 'faculty_head', function($q) use ($user) {
                $facId = FacultyHead::where('user_id', $user->id)->value('faculty_id');
                if ($facId) $q->where('faculty_id', $facId);
                else $q->whereRaw('1=0');
            })
            ->when($user->role === 'department_head', function($q) use ($user) {
                $deptId = DepartmentHead::where('user_id', $user->id)->value('department_id');
                if ($deptId) $q->where('id', $deptId);
                else $q->whereRaw('1=0');
            })
            ->orderBy('name')
            ->get();

        return view('books.edit', compact('book','lecturers','user','departments'));
    }


    /** UPDATE (dengan update/replace PDF opsional) */
    public function update(Request $request, $id)
    {
        $book = Book::findOrFail($id);
        $user = Auth::user();

        $request->validate([
            'isbn'           => 'required|string|max:50',
            'title'          => 'required|string|max:255',
            'publisher'      => 'required|string|max:255',
            'publish_month'  => 'required|integer|min:1|max:12',
            'publish_year'   => 'required|integer|min:1900|max:2100',
            'city'           => 'required|string|max:255',
            'file'           => 'nullable|mimes:pdf|max:10240',

            'department_id'  => 'required|exists:departments,id',

            'lecturer_ids'   => 'sometimes|array',
            'lecturer_ids.*' => 'nullable|exists:lecturers,id',

            'student_names'  => 'sometimes|array',
            'student_names.*'=> 'nullable|string|max:255',
            'student_nims'   => 'sometimes|array',
            'student_nims.*' => 'nullable|string|max:50',
        ]);

        // Validasi RBAC pada department_id:
        $deptId = (int) $request->input('department_id');
        if ($user->role === 'department_head') {
            $myDept = DepartmentHead::where('user_id', $user->id)->value('department_id');
            if (!$myDept || (int)$myDept !== $deptId) {
                return back()->withErrors(['department_id' => 'Kamu hanya boleh menetapkan Program Studi Anda.'])->withInput();
            }
        } elseif ($user->role === 'faculty_head') {
            $myFac = FacultyHead::where('user_id', $user->id)->value('faculty_id');
            $deptFac = Department::where('id', $deptId)->value('faculty_id');
            if (!$myFac || (int)$deptFac !== (int)$myFac) {
                return back()->withErrors(['department_id' => 'Kamu hanya boleh menetapkan Program Studi di bawah Fakultas Anda.'])->withInput();
            }
        }

        // File baru (opsional)
        $fileName = $book->file;
        if ($request->hasFile('file')) {
            $f = $request->file('file');
            $fileName = time().'_'.$f->getClientOriginalName();
            $f->move(public_path('books'), $fileName);
            if ($book->file) {
                $old = public_path('books/'.$book->file);
                if (File::exists($old)) File::delete($old);
            }
        }

        DB::beginTransaction();
        try {
            $book->update([
                'isbn'          => $request->isbn,
                'title'         => $request->title,
                'publisher'     => $request->publisher,
                'publish_month' => $request->publish_month,
                'publish_year'  => $request->publish_year,
                'city'          => $request->city,
                'file'          => $fileName,
                'department_id' => $deptId,
            ]);

            // Sync lecturers (hanya jika dikirim)
            if ($request->has('lecturer_ids')) {
                $lecturerIds = collect($request->input('lecturer_ids', []))
                    ->filter(fn($x)=>!empty($x))
                    ->map(fn($x)=>(int)$x)->unique()->values()->all();
                $book->lecturers()->sync($lecturerIds);
            }

            // Sync students (hanya jika dikirim)
            if ($request->hasAny(['student_names','student_nims'])) {
                $names = $request->input('student_names', []);
                $nims  = $request->input('student_nims',  []);
                $rows  = max(count($names), count($nims));

                $studentIds = [];
                for ($i=0; $i<$rows; $i++) {
                    $name = trim($names[$i] ?? '');
                    $nim  = trim($nims[$i]  ?? '');
                    if ($name==='' && $nim==='') continue;

                    $student = $nim !== '' ? Student::where('nim',$nim)->first() : null;
                    if (!$student) {
                        $student = Student::create([
                            'nim'  => $nim !== '' ? $nim : strtoupper(uniqid('NIM')),
                            'name' => $name !== '' ? $name : ($nim ?: 'Tanpa Nama'),
                            'department_id' => $deptId,
                        ]);
                    } else {
                        if (empty($student->department_id)) {
                            $student->department_id = $deptId;
                            $student->save();
                        }
                    }
                    $studentIds[] = $student->id;
                }
                $book->students()->sync(array_unique($studentIds));
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['general'=>'Gagal memperbarui buku: '.$e->getMessage()])->withInput();
        }

        return redirect()->route('books.index')->with('status','Buku berhasil diperbarui');
    }


    /** DOWNLOAD PDF */
    public function download($file)
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        $auth = Auth::user();
        $safe = basename($file);

        $book = Book::where('file', $safe)
            ->with(['department:id,faculty_id'])
            ->firstOrFail();

        // Otorisasi berdasarkan book.department_id
        if ($auth->role !== 'admin') {
            if ($auth->role === 'department_head') {
                $deptId = DepartmentHead::where('user_id',$auth->id)->value('department_id');
                $ok = $deptId && ((int)$book->department_id === (int)$deptId);
                if (!$ok) abort(403, 'Unauthorized');
            } elseif ($auth->role === 'faculty_head') {
                $facId = FacultyHead::where('user_id',$auth->id)->value('faculty_id');
                $ok = $facId && optional($book->department)->faculty_id == $facId;
                if (!$ok) abort(403, 'Unauthorized');
            } else {
                abort(403, 'Hak akses tidak valid.');
            }
        }

        $candidates = [
            public_path('books/'.$safe),
            storage_path('app/books/'.$safe),
        ];
        $path = null;
        foreach ($candidates as $p) if (File::exists($p)) { $path = $p; break; }
        if (!$path) abort(404, 'File tidak ditemukan.');

        return response()->download($path, $safe);
    }


    /** DESTROY */
    public function destroy($id)
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        $auth = Auth::user();
        $book = Book::with(['department:id,faculty_id'])->findOrFail($id);

        // Otorisasi berdasarkan book.department_id
        if ($auth->role !== 'admin') {
            if ($auth->role === 'department_head') {
                $deptId = DepartmentHead::where('user_id',$auth->id)->value('department_id');
                $ok = $deptId && ((int)$book->department_id === (int)$deptId);
                if (!$ok) abort(403,'Unauthorized');
            } elseif ($auth->role === 'faculty_head') {
                $facId = FacultyHead::where('user_id',$auth->id)->value('faculty_id');
                $ok = $facId && optional($book->department)->faculty_id == $facId;
                if (!$ok) abort(403,'Unauthorized');
            } else {
                abort(403,'Hak akses tidak valid.');
            }
        }

        DB::beginTransaction();
        try {
            if ($book->file) {
                $p = public_path('books/'.$book->file);
                if (File::exists($p)) File::delete($p);
            }
            $book->lecturers()->detach();
            $book->students()->detach();
            $book->delete();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['general'=>'Gagal menghapus buku: '.$e->getMessage()]);
        }

        return redirect()->route('books.index')->with('status','Buku berhasil dihapus');
    }


    public function importForm()
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        // Tidak perlu pilih prodi; buku punya department_id (jika diperlukan pilih di import logic)
        return view('books.import', [
            'user' => Auth::user(),
        ]);
    }

    public function importStore(Request $request)
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        // Validasi file excel
        $request->validate([
            'excel' => ['required','file','mimes:xlsx,xls,csv','max:20480'], // 20 MB
        ]);

        $import = new BooksImport;

        try {
            Excel::import($import, $request->file('excel'));
        } catch (\Throwable $e) {
            return back()->withErrors(['excel' => 'Gagal membaca file: '.$e->getMessage()]);
        }

        $summary = $import->report ?? ['success'=>0,'skip'=>0,'errors'=>[]]; // safety
        $msg = "Import selesai. Sukses: {$summary['success']}, Terlewat: {$summary['skip']}.";

        return back()
            ->with('status', $msg)
            ->with('import_errors', $summary['errors'] ?? []);
    }
}
