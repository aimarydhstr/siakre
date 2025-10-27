<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Lecturer;
use App\Models\DepartmentHead;
use App\Models\Expertise;
use App\Models\ExpertiseField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class LecturerController extends Controller
{
    /**
     * Daftar dosen milik prodi Kaprodi yang login.
     */
    public function index()
    {
        $user = Auth::user();
        if ($user->role !== 'department_head') {
            abort(403, 'Hanya Kaprodi yang dapat mengakses halaman ini.');
        }

        $deptId = DepartmentHead::where('user_id', $user->id)->value('department_id');
        if (!$deptId) {
            abort(403, 'Akun Kaprodi belum terhubung ke Program Studi.');
        }

        // Urut nama berdasar users.name, eager load untuk hindari N+1
        $lecturers = Lecturer::query()
            ->where('lecturers.department_id', $deptId)
            ->join('users', 'users.id', '=', 'lecturers.user_id')
            ->orderBy('users.name')
            ->select('lecturers.*')
            ->with([
                'user:id,name,email',
                'expertiseField:id,expertise_id,name',
                'expertiseField.expertise:id,name',
            ])
            ->paginate(10);

        $positions = ['Asisten Ahli','Lektor','Lektor Kepala','Profesor'];
        $maritals  = ['Menikah','Belum Menikah'];
        $expertises = Expertise::orderBy('name')->get(['id','name']);

        return view('lecturers.index', compact('lecturers', 'user', 'positions', 'maritals', 'expertises'));
    }

    /**
     * Simpan dosen baru di prodi Kaprodi (1 dosen 1 sub-bidang).
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'department_head') {
            abort(403, 'Hanya Kaprodi yang dapat menambahkan dosen.');
        }

        $deptId = DepartmentHead::where('user_id', $user->id)->value('department_id');
        if (!$deptId) {
            return back()->withErrors(['general' => 'Akun Kaprodi belum terhubung ke Program Studi.'])->withInput();
        }

        // Validasi akun user + profil dosen
        $request->validate([
            // user
            'name'                  => ['required','string','max:255'],
            'email'                 => ['required','email','max:255','unique:users,email'],
            'password'              => ['required','string','min:6','confirmed'],

            // profil dosen (lecturers)
            'nik'                   => ['nullable','string','max:32','unique:lecturers,nik'],
            'nidn'                  => ['nullable','string','max:32','unique:lecturers,nidn'],
            'birth_place'           => ['nullable','string','max:255'],
            'birth_date'            => ['nullable','date'],
            'address'               => ['nullable','string'],
            'position'              => ['nullable', Rule::in(['Asisten Ahli','Lektor','Lektor Kepala','Profesor'])],
            'marital_status'        => ['nullable', Rule::in(['Menikah','Belum Menikah'])],
            'expertise_field_id'    => ['nullable','exists:expertise_fields,id'], // satu dosen satu sub-bidang
        ]);

        // Buat user
        $user = new User();
        $user->name     = $request->name;
        $user->email    = $request->email;
        $user->role     = 'lecturer';
        $user->password = Hash::make($request->password);
        $user->save();

        // Buat lecturer
        Lecturer::create([
            'user_id'            => $user->id,
            'department_id'      => $deptId,
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

    /**
     * Update dosen (akun + profil) milik prodi Kaprodi.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->role !== 'department_head') {
            abort(403, 'Hanya Kaprodi yang dapat mengubah data dosen.');
        }

        $deptId = DepartmentHead::where('user_id', $user->id)->value('department_id');
        if (!$deptId) {
            return back()->withErrors(['general' => 'Akun Kaprodi belum terhubung ke Program Studi.'])->withInput();
        }

        $lecturer = Lecturer::with('user')
            ->where('id',$id)
            ->where('department_id',$deptId)
            ->firstOrFail();

        // Validasi
        $request->validate([
            'name'                  => ['required','string','max:255'],
            'email'                 => ['required','email','max:255', Rule::unique('users','email')->ignore($lecturer->user_id)],
            'password'              => ['nullable','string','min:6','confirmed'],

            'nik'                   => ['nullable','string','max:32', Rule::unique('lecturers','nik')->ignore($lecturer->id)],
            'nidn'                  => ['nullable','string','max:32', Rule::unique('lecturers','nidn')->ignore($lecturer->id)],
            'birth_place'           => ['nullable','string','max:255'],
            'birth_date'            => ['nullable','date'],
            'address'               => ['nullable','string'],
            'position'              => ['nullable', Rule::in(['Asisten Ahli','Lektor','Lektor Kepala','Profesor'])],
            'marital_status'        => ['nullable', Rule::in(['Menikah','Belum Menikah'])],
            'expertise_field_id'    => ['nullable','exists:expertise_fields,id'],
        ]);

        // Update akun user
        $u = $lecturer->user;
        $u->name  = $request->name;
        $u->email = $request->email;
        $u->role  = 'lecturer'; // pastikan tetap lecturer
        if ($request->filled('password')) {
            $u->password = Hash::make($request->password);
        }
        $u->save();

        // Update profil lecturer
        $lecturer->update([
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
        if ($user->role !== 'department_head') {
            abort(403, 'Hanya Kaprodi yang dapat menghapus dosen.');
        }

        $deptId = DepartmentHead::where('user_id', $user->id)->value('department_id');
        if (!$deptId) {
            return back()->withErrors(['general' => 'Akun Kaprodi belum terhubung ke Program Studi.']);
        }

        $lecturer = Lecturer::with('user')
            ->where('id',$id)
            ->where('department_id',$deptId)
            ->firstOrFail();

        // Hapus profil lecturer
        $lecturer->delete();

        // Kebijakan: hapus akun user juga
        if ($lecturer->user) {
            $lecturer->user->delete();
        }

        return redirect()->route('lecturers.index')->with('success', 'Dosen berhasil dihapus.');
    }
}
