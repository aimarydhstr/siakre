<?php

namespace App\Http\Controllers;

use App\Models\Lecturer;
use App\Models\User;
use App\Models\DepartmentHead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class LecturerController extends Controller
{
    /**
     * List dosen di prodi Kaprodi yang login.
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

        // Urutkan nama dosen via join ke users (hindari N+1 dengan with('user'))
        $lecturers = Lecturer::where('department_id', $deptId)
            ->join('users', 'users.id', '=', 'lecturers.user_id')
            ->orderBy('users.name')
            ->select('lecturers.*')
            ->with('user')
            ->paginate(10);

        return view('lecturers.index', compact('lecturers', 'user'));
    }

    /**
     * Tambah dosen baru ke prodi Kaprodi.
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

        $request->validate([
            'name'                  => ['required','string','max:255'],
            'email'                 => ['required','email','max:255','unique:users,email'],
            'password'              => ['required','string','min:6','confirmed'], // butuh password_confirmation
        ]);

        // Buat akun user (role lecturer)
        $newUser = new User();
        $newUser->name     = $request->name;
        $newUser->email    = $request->email;
        $newUser->role     = 'lecturer';
        $newUser->password = Hash::make($request->password);
        $newUser->save();

        // Buat profil lecturer pada prodi Kaprodi (dept terkunci)
        Lecturer::create([
            'user_id'       => $newUser->id,
            'department_id' => $deptId,
        ]);

        return redirect()->route('lecturers.index')->with('success', 'Dosen berhasil ditambahkan ke Program Studi Anda.');
    }

    /**
     * Edit data dosen (nama/email/password). Prodi tidak dapat diubah oleh Kaprodi.
     * $id mengacu pada lecturer.id
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
            ->where('id', $id)
            ->where('department_id', $deptId) // pastikan milik prodi Kaprodi
            ->firstOrFail();

        $request->validate([
            'name'                  => ['required','string','max:255'],
            'email'                 => ['required','email','max:255', Rule::unique('users','email')->ignore($lecturer->user_id)],
            'password'              => ['nullable','string','min:6','confirmed'], // jika diisi, harus sama dg konfirmasi
        ]);

        // Update akun user
        $u = $lecturer->user;
        $u->name  = $request->name;
        $u->email = $request->email;
        // pastikan role tetap lecturer
        $u->role  = 'lecturer';
        if ($request->filled('password')) {
            $u->password = Hash::make($request->password);
        }
        $u->save();

        return redirect()->route('lecturers.index')->with('success', 'Data dosen berhasil diperbarui.');
    }

    /**
     * Hapus dosen (profil + akun user).
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
            ->where('id', $id)
            ->where('department_id', $deptId)
            ->firstOrFail();

        // Hapus profil lecturer
        $lecturer->delete();

        // (Kebijakan) Hapus akun user juga
        $lecturer->user->delete();

        return redirect()->route('lecturers.index')->with('success', 'Dosen berhasil dihapus.');
    }
}
