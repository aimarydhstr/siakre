<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use App\Models\DepartmentHead;
use App\Models\Faculty;
use App\Models\FacultyHead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function __construct()
    {
        // Wajib login & admin untuk semua method
        $this->middleware(function ($request, $next) {
            $auth = Auth::user();
            if (!$auth) {
                return redirect('/user/login')->with('alert', 'Kamu harus login dulu');
            }
            if ($auth->role !== 'admin') {
                abort(403, 'Hanya Admin yang dapat mengakses halaman ini.');
            }
            return $next($request);
        });
    }

    /**
     * Tampilkan daftar user.
     */
    public function index()
    {
        $user        = Auth::user();
        $users       = User::orderBy('name')->paginate(10);
        $departments = Department::orderBy('name')->get(['id','name']);
        $faculties   = Faculty::orderBy('name')->get(['id','name']);
        $roles       = ['admin', 'department_head', 'faculty_head'];

        return view('users.index', compact('users', 'departments', 'faculties', 'roles', 'user'));
    }

    /**
     * Simpan user baru.
     */
    public function store(Request $request)
    {
        $roles = ['admin', 'department_head', 'faculty_head'];

        // Normalisasi email
        $email = strtolower(trim((string) $request->input('email')));
        $request->merge(['email' => $email]);

        $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'email'  => ['required', 'email', 'max:255', 'unique:users,email'],
            'role'   => ['required', Rule::in($roles)],
            'password' => ['required', 'string', 'min:6', 'confirmed'],

            // Wajib isi salah satu sesuai role
            'department_id' => [
                Rule::requiredIf($request->input('role') === 'department_head'),
                'nullable', 'exists:departments,id',
            ],
            'faculty_id' => [
                Rule::requiredIf($request->input('role') === 'faculty_head'),
                'nullable', 'exists:faculties,id',
            ],
        ]);

        // Cek unik ketua prodi
        if ($request->role === 'department_head') {
            $deptId = (int) $request->input('department_id');
            $exists = DepartmentHead::where('department_id', $deptId)->exists();
            if ($exists) {
                return back()->withErrors([
                    'department_id' => 'Program Studi tersebut sudah memiliki Ketua.',
                ])->withInput();
            }
        }

        // Cek unik ketua fakultas
        if ($request->role === 'faculty_head') {
            $facId = (int) $request->input('faculty_id');
            $exists = FacultyHead::where('faculty_id', $facId)->exists();
            if ($exists) {
                return back()->withErrors([
                    'faculty_id' => 'Fakultas tersebut sudah memiliki Ketua.',
                ])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            // Buat user
            $user = new User();
            $user->name     = $request->name;
            $user->email    = $email;
            $user->role     = $request->role;
            $user->password = Hash::make($request->password);
            $user->save();

            // Sinkronisasi tabel peran turunan
            if ($user->role === 'department_head') {
                DepartmentHead::create([
                    'user_id'       => $user->id,
                    'department_id' => (int) $request->input('department_id'),
                ]);
            }

            if ($user->role === 'faculty_head') {
                FacultyHead::create([
                    'user_id'   => $user->id,
                    'faculty_id'=> (int) $request->input('faculty_id'),
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['general' => 'Gagal menambahkan user: '.$e->getMessage()])->withInput();
        }

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    /**
     * Update user.
     */
    public function update(Request $request, $id)
    {
        $roles   = ['admin', 'department_head', 'faculty_head'];
        $user    = User::findOrFail($id);
        $oldRole = $user->role;

        // Normalisasi email
        $email = strtolower(trim((string) $request->input('email')));
        $request->merge(['email' => $email]);

        $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role'  => ['required', Rule::in($roles)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],

            'department_id' => [
                Rule::requiredIf($request->input('role') === 'department_head'),
                'nullable', 'exists:departments,id',
            ],
            'faculty_id' => [
                Rule::requiredIf($request->input('role') === 'faculty_head'),
                'nullable', 'exists:faculties,id',
            ],
        ]);

        // Larangan mengganti peran admin terakhir (diri sendiri)
        if ($user->id === Auth::id()) {
            $isChangingRoleFromAdmin = ($oldRole === 'admin' && $request->role !== 'admin');
            if ($isChangingRoleFromAdmin) {
                $adminCount = User::where('role', 'admin')->where('id', '!=', $user->id)->count();
                if ($adminCount === 0) {
                    return back()->withErrors(['role' => 'Tidak dapat mengubah peran: ini adalah admin terakhir.'])->withInput();
                }
            }
        }

        // Validasi unik ketua prodi
        if ($request->role === 'department_head') {
            $deptId = (int) $request->input('department_id');
            $exists = DepartmentHead::where('department_id', $deptId)
                ->where('user_id', '!=', $user->id)
                ->exists();
            if ($exists) {
                return back()->withErrors([
                    'department_id' => 'Program Studi tersebut sudah memiliki Ketua.',
                ])->withInput();
            }
        }

        // Validasi unik ketua fakultas
        if ($request->role === 'faculty_head') {
            $facId = (int) $request->input('faculty_id');
            $exists = FacultyHead::where('faculty_id', $facId)
                ->where('user_id', '!=', $user->id)
                ->exists();
            if ($exists) {
                return back()->withErrors([
                    'faculty_id' => 'Fakultas tersebut sudah memiliki Ketua.',
                ])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            // Update data user
            $user->name  = $request->name;
            $user->email = $email;
            $user->role  = $request->role;
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            $user->save();

            // Jika role berubah, bersihkan record lama
            if ($oldRole !== $user->role) {
                if ($oldRole === 'department_head') {
                    DepartmentHead::where('user_id', $user->id)->delete();
                }
                if ($oldRole === 'faculty_head') {
                    FacultyHead::where('user_id', $user->id)->delete();
                }
            }

            // Sinkronisasi record role baru
            if ($user->role === 'department_head') {
                DepartmentHead::updateOrCreate(
                    ['user_id' => $user->id],
                    ['department_id' => (int) $request->input('department_id')]
                );
            } elseif ($user->role === 'faculty_head') {
                FacultyHead::updateOrCreate(
                    ['user_id' => $user->id],
                    ['faculty_id' => (int) $request->input('faculty_id')]
                );
            } else {
                // Kalau jadi admin, pastikan tidak ada sisa record turunan
                DepartmentHead::where('user_id', $user->id)->delete();
                FacultyHead::where('user_id', $user->id)->delete();
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['general' => 'Gagal memperbarui user: '.$e->getMessage()])->withInput();
        }

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    /**
     * Hapus user dan record peran terkait.
     */
    public function destroy($id)
    {
        $target = User::findOrFail($id);

        // Cegah hapus diri sendiri
        if ($target->id === Auth::id()) {
            return back()->withErrors(['general' => 'Tidak dapat menghapus akun diri sendiri.']);
        }

        // Cegah hapus admin terakhir
        if ($target->role === 'admin') {
            $otherAdminCount = User::where('role', 'admin')->where('id', '!=', $target->id)->count();
            if ($otherAdminCount === 0) {
                return back()->withErrors(['general' => 'Tidak dapat menghapus admin terakhir.']);
            }
        }

        DB::beginTransaction();
        try {
            DepartmentHead::where('user_id', $target->id)->delete();
            FacultyHead::where('user_id', $target->id)->delete();
            $target->delete();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['general' => 'Gagal menghapus user: '.$e->getMessage()]);
        }

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }
}
