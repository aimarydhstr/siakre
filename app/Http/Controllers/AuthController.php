<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Menampilkan halaman login
    public function index()
    {
        return view('auth.login');
    }

    // Login menggunakan Auth
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Redirect berdasarkan role
            switch ($user->role) {
                case 'admin':
                    return redirect('/');
                case 'department_head':
                    return redirect('/');
                case 'lecturer':
                    return redirect('/');
                default:
                    Auth::logout();
                    return redirect()->route('login')->withErrors([
                        'email' => 'Role tidak dikenali!',
                    ]);
            }
        }

        return back()->withErrors([
            'email' => 'Email atau password salah!',
        ]);
    }

    // Menampilkan halaman register/create user
    public function create()
    {
        return view('auth.registration');
    }

    // Register user baru
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:6',
            'role' => 'required|in:admin,department_head,lecturer',
            'department_id' => 'required_if:role,department_head,lecturer|nullable|exists:departments,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => bcrypt($request->password),
        ]);

        // Buat relasi department jika perlu
        if ($user->role === 'department_head') {
            $user->departmentHead()->create([
                'department_id' => $request->department_id,
            ]);
        } elseif ($user->role === 'lecturer') {
            $user->lecturer()->create([
                'department_id' => $request->department_id,
            ]);
        }

        return redirect()->route('login')->with('success', 'User berhasil dibuat, silakan login');
    }

    // Menampilkan halaman edit profil
    public function edit()
    {
        $user = Auth::user();
        return view('auth.update', compact('user'));
    }

    // Update profil user
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|confirmed|min:6',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        return redirect()->route('auth.edit')->with('success', 'Profil berhasil diperbarui');
    }

    // Logout user
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda sudah logout');
    }
}
