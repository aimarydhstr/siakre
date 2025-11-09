<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Lecturer;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\FacultyHead;
use App\Models\DepartmentHead;
use App\Models\Expertise;
use App\Models\ExpertiseField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\LecturersImport;

class LecturerController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin','faculty_head','department_head'], true)) {
            abort(403, 'Hak akses tidak valid.');
        }

        // Base query + eager load untuk hindari N+1
        $query = Lecturer::query()
            ->from('lecturers') // pastikan alias utama
            ->select('lecturers.*')
            ->with([
                'department:id,name,faculty_id',
                'expertiseField:id,expertise_id,name',
                'expertiseField.expertise:id,name',
            ])
            // join ke departments agar bisa filter faculty_id saat faculty_head
            ->leftJoin('departments', 'departments.id', '=', 'lecturers.department_id');

        // Scope per-role
        $departments = collect(); // untuk form (admin & faculty_head)
        if ($user->role === 'admin') {
            // tanpa filter
            $departments = Department::orderBy('name')->get(['id','name','faculty_id']);
        } elseif ($user->role === 'faculty_head') {
            $facultyId = FacultyHead::where('user_id', $user->id)->value('faculty_id');
            if (!$facultyId) {
                abort(403, 'Akun Dekan belum terhubung ke Fakultas.');
            }
            $query->where('departments.faculty_id', $facultyId);

            // kirim hanya prodi di fakultasnya untuk dropdown
            $departments = Department::where('faculty_id', $facultyId)
                ->orderBy('name')->get(['id','name','faculty_id']);
        } else { // department_head
            $deptId = DepartmentHead::where('user_id', $user->id)->value('department_id');
            if (!$deptId) {
                abort(403, 'Akun Kaprodi belum terhubung ke Program Studi.');
            }
            $query->where('lecturers.department_id', $deptId);
            // Kaprodi tidak perlu dropdown prodi (diset otomatis), biarkan $departments kosong
        }

        // Urut nama, distinct untuk cegah duplikasi akibat join
        $lecturers = $query
            ->orderBy('lecturers.name')
            ->distinct('lecturers.id')
            ->paginate(10)
            ->appends(request()->query());

        $positions  = ['Asisten Ahli','Lektor','Lektor Kepala','Profesor'];
        $maritals   = ['Menikah','Belum Menikah'];
        $expertises = Expertise::orderBy('name')->get(['id','name']);

        return view('lecturers.index', compact(
            'lecturers', 'user', 'positions', 'maritals', 'expertises', 'departments'
        ));
    }


    /**
     * Simpan dosen baru di prodi Kaprodi (1 dosen 1 sub-bidang).
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin','faculty_head','department_head'], true)) {
            abort(403, 'Hak akses tidak valid.');
        }

        // Tentukan department_id sesuai role
        $departmentId = null;
        if ($user->role === 'admin') {
            $request->validate([
                'department_id' => ['required','exists:departments,id'],
            ]);
            $departmentId = (int) $request->department_id;
        } elseif ($user->role === 'faculty_head') {
            $request->validate([
                'department_id' => ['required','exists:departments,id'],
            ]);
            $facultyId = FacultyHead::where('user_id', $user->id)->value('faculty_id');
            if (!$facultyId) {
                return back()->withErrors(['general' => 'Akun Dekan belum terhubung ke Fakultas.'])->withInput();
            }
            $ok = Department::where('id', $request->department_id)
                ->where('faculty_id', $facultyId)
                ->exists();
            if (!$ok) {
                return back()->withErrors(['department_id' => 'Prodi tidak termasuk dalam fakultas Anda.'])->withInput();
            }
            $departmentId = (int) $request->department_id;
        } else { // department_head
            $departmentId = DepartmentHead::where('user_id', $user->id)->value('department_id');
            if (!$departmentId) {
                return back()->withErrors(['general' => 'Akun Kaprodi belum terhubung ke Program Studi.'])->withInput();
            }
        }

        // Validasi field dosen
        $request->validate([
            'name'               => ['required','string','max:255'],
            'nik'                => ['required','string','max:32','unique:lecturers,nik'],
            'nidn'               => ['required','string','max:32','unique:lecturers,nidn'],
            'birth_place'        => ['required','string','max:255'],
            'birth_date'         => ['required','date'],
            'address'            => ['required','string'],
            'position'           => ['required', Rule::in(['Asisten Ahli','Lektor','Lektor Kepala','Profesor'])],
            'marital_status'     => ['required', Rule::in(['Menikah','Belum Menikah'])],
            'expertise_field_id' => ['required','exists:expertise_fields,id'],
        ]);

        Lecturer::create([
            'name'               => $request->name,
            'department_id'      => $departmentId,
            'nik'                => $request->nik,
            'nidn'               => $request->nidn,
            'birth_place'        => $request->birth_place,
            'birth_date'         => $request->birth_date,
            'address'            => $request->address,
            'position'           => $request->position,
            'marital_status'     => $request->marital_status,
            'expertise_field_id' => $request->expertise_field_id,
        ]);

        return redirect()->route('lecturers.index')->with('success', 'Dosen berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin','faculty_head','department_head'], true)) {
            abort(403, 'Hak akses tidak valid.');
        }

        // Ambil lecturer sesuai scope role
        if ($user->role === 'admin') {
            $lecturer = Lecturer::findOrFail($id);
        } elseif ($user->role === 'faculty_head') {
            $facultyId = FacultyHead::where('user_id', $user->id)->value('faculty_id');
            if (!$facultyId) {
                return back()->withErrors(['general' => 'Akun Dekan belum terhubung ke Fakultas.']);
            }
            $lecturer = Lecturer::query()
                ->join('departments','departments.id','=','lecturers.department_id')
                ->where('lecturers.id',$id)
                ->where('departments.faculty_id',$facultyId)
                ->select('lecturers.*')
                ->firstOrFail();
        } else { // department_head
            $deptId = DepartmentHead::where('user_id', $user->id)->value('department_id');
            if (!$deptId) {
                return back()->withErrors(['general' => 'Akun Kaprodi belum terhubung ke Program Studi.'])->withInput();
            }
            $lecturer = Lecturer::where('id',$id)
                ->where('department_id',$deptId)
                ->firstOrFail();
        }

        // Validasi umum
        $request->validate([
            'name'               => ['required','string','max:255'],
            'nik'                => ['required','string','max:32', Rule::unique('lecturers','nik')->ignore($lecturer->id)],
            'nidn'               => ['required','string','max:32', Rule::unique('lecturers','nidn')->ignore($lecturer->id)],
            'birth_place'        => ['required','string','max:255'],
            'birth_date'         => ['required','date'],
            'address'            => ['required','string'],
            'position'           => ['required', Rule::in(['Asisten Ahli','Lektor','Lektor Kepala','Profesor'])],
            'marital_status'     => ['required', Rule::in(['Menikah','Belum Menikah'])],
            'expertise_field_id' => ['required','exists:expertise_fields,id'],
        ]);

        // Opsional: admin & faculty_head boleh memindahkan prodi dengan syarat
        $newDepartmentId = $lecturer->department_id; // default tetap
        if (in_array($user->role, ['admin','faculty_head'], true) && $request->filled('department_id')) {
            $request->validate([
                'department_id' => ['required','exists:departments,id'],
            ]);
            if ($user->role === 'faculty_head') {
                $facultyId = FacultyHead::where('user_id', $user->id)->value('faculty_id');
                $ok = Department::where('id', $request->department_id)
                    ->where('faculty_id', $facultyId)
                    ->exists();
                if (!$ok) {
                    return back()->withErrors(['department_id' => 'Prodi tujuan tidak termasuk dalam fakultas Anda.'])->withInput();
                }
            }
            $newDepartmentId = (int) $request->department_id;
        }
        // department_head tidak boleh mengubah department_id

        $lecturer->update([
            'name'               => $request->name,
            'department_id'      => $newDepartmentId,
            'nik'                => $request->nik,
            'nidn'               => $request->nidn,
            'birth_place'        => $request->birth_place,
            'birth_date'         => $request->birth_date,
            'address'            => $request->address,
            'position'           => $request->position,
            'marital_status'     => $request->marital_status,
            'expertise_field_id' => $request->expertise_field_id,
        ]);

        return redirect()->route('lecturers.index')->with('success', 'Data dosen berhasil diperbarui.');
    }


    /**
     * Hapus dosen (profil + user) milik prodi Kaprodi.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin','faculty_head','department_head'], true)) {
            abort(403, 'Hak akses tidak valid.');
        }

        // Ambil lecturer sesuai scope role
        if ($user->role === 'admin') {
            $lecturer = Lecturer::findOrFail($id);

        } elseif ($user->role === 'faculty_head') {
            // Pastikan dekan terhubung ke fakultas
            $facultyId = FacultyHead::where('user_id', $user->id)->value('faculty_id');
            if (!$facultyId) {
                return back()->withErrors(['general' => 'Akun Dekan belum terhubung ke Fakultas.']);
            }

            // Boleh hapus jika lecturer berada di department yang berada di faculty_id tersebut
            $lecturer = Lecturer::query()
                ->join('departments','departments.id','=','lecturers.department_id')
                ->where('lecturers.id', $id)
                ->where('departments.faculty_id', $facultyId)
                ->select('lecturers.*')
                ->firstOrFail();

        } else { // department_head
            $deptId = \App\Models\DepartmentHead::where('user_id', $user->id)->value('department_id');
            if (!$deptId) {
                return back()->withErrors(['general' => 'Akun Kaprodi belum terhubung ke Program Studi.']);
            }

            $lecturer = Lecturer::where('id', $id)
                ->where('department_id', $deptId)
                ->firstOrFail();
        }

        $lecturer->articles()->detach();
        $lecturer->books()->detach();
        $lecturer->hkis()->detach();

        $lecturer->delete();

        return redirect()->route('lecturers.index')->with('success', 'Dosen berhasil dihapus.');
    }


    public function importForm()
    {
        // Tidak perlu cek role — asumsi halaman ini hanya diakses admin
        return view('lecturers.import', [
            'user' => Auth::user(),
        ]);
    }

    public function importStore(Request $request)
    {
        $request->validate([
            'excel' => ['required','file','mimes:xlsx,xls,csv','max:20480'], // 20 MB
        ]);

        $import = new LecturersImport();

        try {
            Excel::import($import, $request->file('excel'));
        } catch (\Throwable $e) {
            return back()->withErrors(['excel' => 'Gagal membaca file: '.$e->getMessage()]);
        }

        $sum = $import->summary();
        $msg = "Import selesai. Tambah: {$sum['inserted']}, Update: {$sum['updated']}, Error: ".count($sum['errors']).".";

        return back()
            ->with('status', $msg)
            ->with('import_errors', $sum['errors']);
    }
}
