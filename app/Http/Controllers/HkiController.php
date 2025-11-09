<?php

namespace App\Http\Controllers;

use App\Models\Hki;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\FacultyHead;
use App\Models\Department;
use App\Models\DepartmentHead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\HkiImport;

class HkiController extends Controller
{
    /** INDEX: daftar HKI + pencarian + RBAC scope */
    public function index(Request $request)
    {
        $user   = Auth::user();
        $search = trim($request->input('search', ''));

        if (!in_array($user->role, ['admin', 'faculty_head', 'department_head'], true)) {
            abort(403, 'Hak akses tidak valid.');
        }

        // Base query + eager load
        $query = Hki::query()->with([
            'department' => fn($q) => $q->select('id','name','faculty_id'),
            'lecturers'  => fn($q) => $q->select('lecturers.id','lecturers.name','lecturers.department_id'),
            'students'   => fn($q) => $q->select('students.id','students.name','students.nim'),
        ]);

        // ===== RBAC scope berdasarkan hki.department_id =====
        if ($user->role === 'faculty_head') {
            $facultyId = FacultyHead::where('user_id', $user->id)->value('faculty_id');
            if (!$facultyId) {
                abort(403, 'Akun Dekan belum terhubung ke Fakultas.');
            }
            // filter hkis.department.faculty_id = facultyId
            $query->whereHas('department', fn($dq) => $dq->where('departments.faculty_id', (int)$facultyId));
        } elseif ($user->role === 'department_head') {
            $deptId = DepartmentHead::where('user_id', $user->id)->value('department_id');
            if (!$deptId) {
                abort(403, 'Akun Kaprodi belum terhubung ke Program Studi.');
            }
            $query->where('hkis.department_id', (int)$deptId);
        }
        // admin: tanpa filter

        // ===== Pencarian (gunakan prefix agar tidak ambigu) =====
        if ($search !== '') {
            $query->where(function ($w) use ($search) {
                $w->where('hkis.name',   'like', "%{$search}%")
                  ->orWhere('hkis.number','like', "%{$search}%")
                  ->orWhere('hkis.holder','like', "%{$search}%")
                  ->orWhereHas('lecturers', function ($lq) use ($search) {
                      $lq->where('lecturers.name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('students', function ($sq) use ($search) {
                      $sq->where('students.name', 'like', "%{$search}%")
                         ->orWhere('students.nim',  'like', "%{$search}%");
                  })
                  ->orWhereHas('department', function ($dq) use ($search) {
                      $dq->where('departments.name', 'like', "%{$search}%");
                  });
            });
        }

        // Urutkan terbaru (berdasar tanggal, fallback id)
        $hkis = $query
            ->orderByDesc('hkis.date')
            ->orderByDesc('hkis.id')
            ->paginate(10)
            ->withQueryString();

        return view('hki.index', [
            'hkis'   => $hkis,
            'user'   => $user,
            'search' => $search,
        ]);
    }


    /** CREATE PAGE: form tambah */
    public function create()
    {
        $user = Auth::user();

        // daftar departments (difilter sesuai role)
        $departments = Department::query()
            ->select('id','name','faculty_id')
            ->when($user->role === 'faculty_head', fn($q) => $q->where('faculty_id', FacultyHead::where('user_id', $user->id)->value('faculty_id') ?: 0))
            ->when($user->role === 'department_head', fn($q) => $q->where('id', DepartmentHead::where('user_id', $user->id)->value('department_id') ?: 0))
            ->orderBy('name')
            ->get();

        $lecturers = Lecturer::query()
            ->select('id','name','department_id')
            ->when($user->role === 'faculty_head', function ($q) use ($user) {
                $facultyId = FacultyHead::where('user_id', $user->id)->value('faculty_id');
                if ($facultyId) {
                    $q->whereHas('department', fn($dq)=>$dq->where('faculty_id',$facultyId));
                } else {
                    $q->whereRaw('1=0');
                }
            })
            ->when($user->role === 'department_head', function ($q) use ($user) {
                $deptId = DepartmentHead::where('user_id', $user->id)->value('department_id');
                if ($deptId) {
                    $q->where('department_id', $deptId);
                } else {
                    $q->whereRaw('1=0');
                }
            })
            ->orderBy('name','asc')
            ->get();

        return view('hki.create', compact('lecturers', 'user', 'departments'));
    }


    /** STORE: simpan HKI baru */
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'            => 'required|string|max:255',
            'number'          => 'required|string|max:255',
            'holder'          => 'required|string|max:255',
            'date'            => 'required|date',
            'file'            => 'required|mimes:pdf|max:10240',
            'department_id'   => 'required|exists:departments,id',
            'lecturer_ids'    => 'sometimes|array',
            'lecturer_ids.*'  => 'nullable|exists:lecturers,id',
            'student_names'   => 'sometimes|array',
            'student_names.*' => 'nullable|string|max:255',
            'student_nims'    => 'sometimes|array',
            'student_nims.*'  => 'nullable|string|max:50',
        ]);

        // RBAC server-side: pastikan user boleh membuat untuk department_id itu
        $deptId = (int)$request->input('department_id');
        if ($user->role === 'department_head') {
            $myDept = DepartmentHead::where('user_id', $user->id)->value('department_id');
            if (!$myDept || (int)$myDept !== $deptId) {
                return back()->withErrors(['department_id' => 'Kamu hanya boleh menambahkan HKI untuk Program Studi Anda.'])->withInput();
            }
        } elseif ($user->role === 'faculty_head') {
            $myFac = FacultyHead::where('user_id', $user->id)->value('faculty_id');
            $deptFac = Department::where('id', $deptId)->value('faculty_id');
            if (!$myFac || (int)$deptFac !== (int)$myFac) {
                return back()->withErrors(['department_id' => 'Kamu hanya boleh menambahkan HKI untuk Program Studi di bawah Fakultas Anda.'])->withInput();
            }
        }

        // Upload file
        $fileName = null;
        if ($request->hasFile('file')) {
            $f = $request->file('file');
            $fileName = time().'_'.$f->getClientOriginalName();
            $f->move(public_path('hki'), $fileName);
        }

        DB::beginTransaction();
        try {
            $hki = Hki::create([
                'name'          => $request->name,
                'number'        => $request->number,
                'date'          => $request->date,
                'holder'        => $request->holder,
                'file'          => $fileName,
                'department_id' => $deptId,
            ]);

            // Attach lecturers (jika ada field)
            if ($request->has('lecturer_ids')) {
                $lecturerIds = collect($request->input('lecturer_ids', []))
                    ->filter(fn($x) => !empty($x))
                    ->map(fn($x) => (int)$x)
                    ->unique()
                    ->values()
                    ->all();
                if (!empty($lecturerIds)) {
                    $hki->lecturers()->attach($lecturerIds);
                }
            }

            // Attach students (jika ada field)
            if ($request->hasAny(['student_names','student_nims'])) {
                $studentNames = $request->input('student_names', []);
                $studentNims  = $request->input('student_nims', []);
                $rows = max(count($studentNames), count($studentNims));
                $studentIds = [];
                for ($i=0; $i<$rows; $i++) {
                    $name = trim($studentNames[$i] ?? '');
                    $nim  = trim($studentNims[$i]  ?? '');
                    if ($name === '' && $nim === '') continue;

                    $student = null;
                    if ($nim !== '') {
                        $student = Student::where('nim', $nim)->first();
                    }
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
                if (!empty($studentIds)) {
                    $hki->students()->attach(array_unique($studentIds));
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($fileName) {
                $path = public_path('hki/'.$fileName);
                if (File::exists($path)) File::delete($path);
            }
            return back()->withErrors(['general' => 'Gagal menyimpan HKI: '.$e->getMessage()])->withInput();
        }

        return redirect()->route('hki.index')->with('status','HKI berhasil ditambahkan');
    }

    /** DOWNLOAD file PDF */
    public function download($file)
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        $auth = Auth::user();
        $safeName = basename($file);

        // Ambil hki dengan relasi department (untuk cek otorisasi)
        $hki = Hki::where('file', $safeName)
            ->with(['department:id,faculty_id'])
            ->firstOrFail();

        // Otorisasi berdasarkan hki.department_id
        if ($auth->role !== 'admin') {
            if ($auth->role === 'faculty_head') {
                $facultyId = FacultyHead::where('user_id', $auth->id)->value('faculty_id');
                $ok = $facultyId && optional($hki->department)->faculty_id == $facultyId;
                if (!$ok) abort(403, 'Unauthorized');
            } elseif ($auth->role === 'department_head') {
                $deptId = DepartmentHead::where('user_id', $auth->id)->value('department_id');
                $ok = $deptId && ((int)$hki->department_id === (int)$deptId);
                if (!$ok) abort(403, 'Unauthorized');
            } else {
                abort(403, 'Hak akses tidak valid.');
            }
        }

        $candidates = [
            public_path('hki/'.$safeName),
            storage_path('app/hki/'.$safeName),
        ];
        $path = null;
        foreach ($candidates as $p) if (File::exists($p)) { $path = $p; break; }
        if (!$path) abort(404, 'File tidak ditemukan.');

        return response()->download($path, $safeName);
    }


    /** EDIT PAGE: form edit */
    public function edit($id)
    {
        // eager-load dengan department
        $hki = Hki::with([
            'department:id,faculty_id',
            'lecturers' => fn($q) => $q->select('lecturers.id','lecturers.name','lecturers.department_id'),
            'students'  => fn($q) => $q->select('students.id','students.name','students.nim'),
        ])->findOrFail($id);

        $user = Auth::user();

        // RBAC: cek akses terhadap hki -> berdasarkan hki.department_id
        if ($user->role === 'admin') {
            // ok
        } elseif ($user->role === 'faculty_head') {
            $facultyId = FacultyHead::where('user_id', $user->id)->value('faculty_id');
            $ok = $facultyId && optional($hki->department)->faculty_id == $facultyId;
            if (!$ok) abort(403, 'Unauthorized');
        } elseif ($user->role === 'department_head') {
            $deptId = DepartmentHead::where('user_id', $user->id)->value('department_id');
            $ok = $deptId && ((int)$hki->department_id === (int)$deptId);
            if (!$ok) abort(403, 'Unauthorized');
        } else {
            abort(403, 'Hak akses tidak valid.');
        }

        // daftar dosen & departments (difilter sesuai role)
        $lecturers = Lecturer::query()
            ->select('lecturers.id', 'lecturers.name', 'lecturers.department_id')
            ->with(['department' => fn($q)=>$q->select('departments.id','departments.faculty_id')])
            ->when($user->role === 'faculty_head', function ($q) use ($user) {
                $facultyId = FacultyHead::where('user_id', $user->id)->value('faculty_id');
                if ($facultyId) {
                    $q->whereHas('department', fn($dq)=>$dq->where('faculty_id', $facultyId));
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

        $departments = Department::query()
            ->select('id','name','faculty_id')
            ->when($user->role === 'faculty_head', fn($q) => $q->where('faculty_id', FacultyHead::where('user_id',$user->id)->value('faculty_id') ?: 0))
            ->when($user->role === 'department_head', fn($q) => $q->where('id', DepartmentHead::where('user_id',$user->id)->value('department_id') ?: 0))
            ->orderBy('name')
            ->get();

        return view('hki.edit', compact('hki', 'lecturers', 'user', 'departments'));
    }



    /** UPDATE: simpan perubahan */
    public function update(Request $request, $id)
    {
        $hki = Hki::findOrFail($id);
        $user = Auth::user();

        $request->validate([
            'name'            => 'required|string|max:255',
            'number'          => 'required|string|max:255',
            'holder'          => 'required|string|max:255',
            'date'            => 'required|date',
            'file'            => 'nullable|mimes:pdf|max:10240',
            'department_id'   => 'required|exists:departments,id',
            'lecturer_ids'    => 'sometimes|array',
            'lecturer_ids.*'  => 'nullable|exists:lecturers,id',
            'student_names'   => 'sometimes|array',
            'student_names.*' => 'nullable|string|max:255',
            'student_nims'    => 'sometimes|array',
            'student_nims.*'  => 'nullable|string|max:50',
        ]);

        // RBAC checks:
        // 1) user must be allowed to edit this HKI (based on original hki.department_id)
        if ($user->role === 'faculty_head') {
            $facultyId = FacultyHead::where('user_id', $user->id)->value('faculty_id');
            $ok = $facultyId && optional($hki->department)->faculty_id == $facultyId;
            if (!$ok) abort(403, 'Unauthorized');
        } elseif ($user->role === 'department_head') {
            $deptId = DepartmentHead::where('user_id', $user->id)->value('department_id');
            $ok = $deptId && ((int)$hki->department_id === (int)$deptId);
            if (!$ok) abort(403, 'Unauthorized');
        }

        // 2) user must be allowed to set the new department_id (if changed)
        $newDeptId = (int)$request->input('department_id');
        if ($user->role === 'department_head') {
            $myDept = DepartmentHead::where('user_id', $user->id)->value('department_id');
            if (!$myDept || (int)$myDept !== $newDeptId) {
                return back()->withErrors(['department_id' => 'Kamu hanya boleh menetapkan Program Studi Anda.'])->withInput();
            }
        } elseif ($user->role === 'faculty_head') {
            $myFac = FacultyHead::where('user_id', $user->id)->value('faculty_id');
            $deptFac = Department::where('id', $newDeptId)->value('faculty_id');
            if (!$myFac || (int)$deptFac !== (int)$myFac) {
                return back()->withErrors(['department_id' => 'Kamu hanya boleh menetapkan Program Studi di bawah Fakultas Anda.'])->withInput();
            }
        }

        // Upload file baru
        $fileName = $hki->file;
        if ($request->hasFile('file')) {
            $f = $request->file('file');
            $fileName = time().'_'.$f->getClientOriginalName();
            $f->move(public_path('hki'), $fileName);

            // hapus lama
            if ($hki->file) {
                $old = public_path('hki/'.$hki->file);
                if (File::exists($old)) File::delete($old);
            }
        }

        DB::beginTransaction();
        try {
            // Update field dasar (termasuk department_id)
            $hki->update([
                'name'          => $request->name,
                'number'        => $request->number,
                'holder'        => $request->holder,
                'date'          => $request->date,
                'file'          => $fileName,
                'department_id' => $newDeptId,
            ]);

            // Sync lecturers (hanya jika dikirim)
            if ($request->has('lecturer_ids')) {
                $lecturerIds = collect($request->input('lecturer_ids', []))
                    ->filter(fn($x) => !empty($x))
                    ->map(fn($x) => (int)$x)
                    ->unique()
                    ->values()
                    ->all();
                $hki->lecturers()->sync($lecturerIds);
            }

            // Sync students (hanya jika dikirim)
            if ($request->hasAny(['student_names','student_nims'])) {
                $studentNames = $request->input('student_names', []);
                $studentNims  = $request->input('student_nims', []);
                $rows = max(count($studentNames), count($studentNims));

                $studentIds = [];
                for ($i=0; $i<$rows; $i++) {
                    $name = trim($studentNames[$i] ?? '');
                    $nim  = trim($studentNims[$i]  ?? '');
                    if ($name === '' && $nim === '') continue;

                    $student = null;
                    if ($nim !== '') {
                        $student = Student::where('nim', $nim)->first();
                    }
                    if (!$student) {
                        $student = Student::create([
                            'nim'  => $nim !== '' ? $nim : strtoupper(uniqid('NIM')),
                            'name' => $name !== '' ? $name : ($nim ?: 'Tanpa Nama'),
                            'department_id' => $newDeptId,
                        ]);
                    } else {
                        if (empty($student->department_id)) {
                            $student->department_id = $newDeptId;
                            $student->save();
                        }
                    }
                    $studentIds[] = $student->id;
                }
                $hki->students()->sync(array_unique($studentIds));
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['general' => 'Gagal memperbarui HKI: '.$e->getMessage()])->withInput();
        }

        return redirect()->route('hki.index')->with('status','HKI berhasil diperbarui');
    }

    /** DESTROY */
    public function destroy($id)
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        $auth = Auth::user();
        $hki  = Hki::findOrFail($id);

        // Otorisasi berdasarkan hki.department_id
        if ($auth->role !== 'admin') {
            if ($auth->role === 'faculty_head') {
                $facultyId = FacultyHead::where('user_id', $auth->id)->value('faculty_id');
                $ok = $facultyId && optional($hki->department)->faculty_id == $facultyId;
                if (!$ok) abort(403, 'Unauthorized');
            } elseif ($auth->role === 'department_head') {
                $deptId = DepartmentHead::where('user_id', $auth->id)->value('department_id');
                $ok = $deptId && ((int)$hki->department_id === (int)$deptId);
                if (!$ok) abort(403, 'Unauthorized');
            } else {
                abort(403, 'Hak akses tidak valid.');
            }
        }

        DB::beginTransaction();
        try {
            if ($hki->file) {
                $path = public_path('hki/'.$hki->file);
                if (File::exists($path)) File::delete($path);
            }

            $hki->lecturers()->detach();
            $hki->students()->detach();
            $hki->delete();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['general' => 'Gagal menghapus HKI: '.$e->getMessage()]);
        }

        return redirect()->route('hki.index')->with('status', 'HKI berhasil dihapus');
    }

    /** IMPORT FORM */
    public function importForm()
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        // Kirim departments agar admin/kaprodi/dekan bisa memilih default target (opsional)
        $user = Auth::user();
        $departments = Department::query()
            ->select('id','name','faculty_id')
            ->when($user->role === 'faculty_head', fn($q) => $q->where('faculty_id', FacultyHead::where('user_id',$user->id)->value('faculty_id') ?: 0))
            ->when($user->role === 'department_head', fn($q) => $q->where('id', DepartmentHead::where('user_id',$user->id)->value('department_id') ?: 0))
            ->orderBy('name')
            ->get();

        return view('hki.import', ['user' => $user, 'departments' => $departments]);
    }

    public function importStore(Request $request)
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        $request->validate([
            'excel' => ['required','file','mimes:xlsx,xls,csv','max:20480'], // 20 MB
            'department_id' => ['nullable','exists:departments,id'], // optional default/override
        ]);

        $import = new HkiImport();

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
}
