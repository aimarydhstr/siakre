<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use App\Models\DepartmentHead;
use App\Models\Lecturer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Auth;

class UserController extends Controller
{
    /**
     * Tampilkan daftar user (tanpa search).
     */
    public function index()
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return abort(403, 'Hanya Admin yang dapat mengakses halaman ini.');
        }
        $users = User::orderBy('name')->paginate(10);
        $departments = Department::orderBy('name')->get();
        $roles = ['admin', 'department_head', 'lecturer'];

        return view('users.index', compact('users', 'departments', 'roles', 'user'));
    }

    /**
     * Simpan user baru.
     */
    public function store(Request $request)
    {
        $roles = ['admin', 'department_head', 'lecturer'];

        $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255', 'unique:users,email'],
            'role'         => ['required', Rule::in($roles)],
            'password'     => ['required', 'string', 'min:6', 'confirmed'],
            'department_id'=> [
                Rule::requiredIf(in_array($request->input('role'), ['department_head','lecturer'])),
                'nullable', 'exists:departments,id',
            ],
        ]);

        // Cek unik kaprodi untuk department terkait
        if ($request->role === 'department_head') {
            $deptId = $request->input('department_id');
            $exists = DepartmentHead::where('department_id', $deptId)->exists();
            if ($exists) {
                return back()->withErrors([
                    'department_id' => 'Program Studi tersebut sudah memiliki Ketua.',
                ])->withInput();
            }
        }

        // Buat user
        $user = new User();
        $user->name     = $request->name;
        $user->email    = $request->email;
        $user->role     = $request->role;
        $user->password = Hash::make($request->password);
        $user->save();

        // Sinkronisasi tabel peran turunan
        if (in_array($user->role, ['department_head', 'lecturer'])) {
            $deptId = $request->input('department_id');

            if ($user->role === 'department_head') {
                DepartmentHead::create([
                    'user_id'       => $user->id,
                    'department_id' => $deptId,
                ]);
            }

            if ($user->role === 'lecturer') {
                Lecturer::create([
                    'user_id'       => $user->id,
                    'department_id' => $deptId,
                ]);
            }
        }

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    /**
     * Update user.
     */
    public function update(Request $request, $id)
    {
        $roles = ['admin', 'department_head', 'lecturer'];
        $user  = User::findOrFail($id);
        $oldRole = $user->role;

        $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role'         => ['required', Rule::in($roles)],
            'password'     => ['nullable', 'string', 'min:6'],
            'department_id'=> [
                Rule::requiredIf(in_array($request->input('role'), ['department_head','lecturer'])),
                'nullable', 'exists:departments,id',
            ],
        ]);

        // Validasi unik kaprodi saat set role ke department_head
        if ($request->role === 'department_head') {
            $deptId = $request->input('department_id');
            $exists = DepartmentHead::where('department_id', $deptId)
                ->where('user_id', '!=', $user->id)
                ->exists();
            if ($exists) {
                return back()->withErrors([
                    'department_id' => 'Program Studi tersebut sudah memiliki Ketua.',
                ])->withInput();
            }
        }

        // Update data user
        $user->name  = $request->name;
        $user->email = $request->email;
        $user->role  = $request->role;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        // Hapus record role lama jika role berubah
        if ($oldRole === 'department_head' && $user->role !== 'department_head') {
            DepartmentHead::where('user_id', $user->id)->delete();
        }
        if ($oldRole === 'lecturer' && $user->role !== 'lecturer') {
            Lecturer::where('user_id', $user->id)->delete();
        }

        // Buat/Update record role baru
        if (in_array($user->role, ['department_head', 'lecturer'])) {
            $deptId = $request->input('department_id');

            if ($user->role === 'department_head') {
                DepartmentHead::updateOrCreate(
                    ['user_id' => $user->id],
                    ['department_id' => $deptId]
                );
            }

            if ($user->role === 'lecturer') {
                Lecturer::updateOrCreate(
                    ['user_id' => $user->id],
                    ['department_id' => $deptId]
                );
            }
        }

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    /**
     * Hapus user dan record peran terkait.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        DepartmentHead::where('user_id', $user->id)->delete();
        Lecturer::where('user_id', $user->id)->delete();

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }
}
