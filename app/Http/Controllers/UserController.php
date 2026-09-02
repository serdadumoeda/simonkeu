<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        // Hanya Admin yang boleh masuk
        if (Auth::user()->role != 'Admin Keuangan') {
            abort(403, 'Akses Ditolak');
        }

        $users = User::orderBy('created_at', 'desc')->get();
        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->role != 'Admin Keuangan') {
            abort(403, 'Akses Ditolak');
        }

        $request->validate([
            'name' => 'required|unique:users,name',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:4',
            'role' => 'required|string',
            'bidang' => 'required|string',
        ]);

        try {
            $bidangVal = trim($request->bidang);
            if ($bidangVal === 'custom') {
                $bidangVal = 'UPTD';
            }

            User::create([
                'name' => trim($request->name),
                'email' => trim($request->email),
                'password' => Hash::make($request->password),
                'role' => trim($request->role),
                'bidang' => $bidangVal,
            ]);

            return redirect()->route('users.index')->with('success', 'Akun pengguna baru berhasil ditambahkan!');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal menambahkan pengguna: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        if (Auth::user()->role != 'Admin Keuangan') {
            abort(403, 'Akses Ditolak');
        }

        $user = User::findOrFail($id);
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->role != 'Admin Keuangan') {
            abort(403, 'Akses Ditolak');
        }

        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|unique:users,name,' . $id,
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|min:4',
            'role' => 'required|string',
            'bidang' => 'required|string',
        ]);

        try {
            $bidangVal = trim($request->bidang);
            if ($bidangVal === 'custom') {
                $bidangVal = 'UPTD';
            }

            $user->name = trim($request->name);
            $user->email = trim($request->email);
            $user->role = trim($request->role);
            $user->bidang = $bidangVal;

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            return redirect()->route('users.index')->with('success', 'Akun pengguna berhasil diperbarui!');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal memperbarui pengguna: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        if (Auth::user()->role != 'Admin Keuangan') {
            abort(403, 'Akses Ditolak');
        }

        if ($id == Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang digunakan!');
        }

        try {
            $user = User::findOrFail($id);
            $user->delete();

            return redirect()->route('users.index')->with('success', 'Akun pengguna berhasil dihapus!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus pengguna: ' . $e->getMessage());
        }
    }
}