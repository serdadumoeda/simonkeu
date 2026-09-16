<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Hanya Admin yang boleh masuk
        if (Auth::user()->role != 'Admin Keuangan') {
            abort(403, 'Akses Ditolak');
        }

        $query = User::query()->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('no_wa', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('bidang')) {
            $query->where('bidang', $request->bidang);
        }

        // Ambil daftar role & bidang unik untuk dropdown filter
        $daftarRole = User::distinct()->pluck('role')->filter()->sort()->values();
        $daftarBidang = User::distinct()->pluck('bidang')->filter()->sort()->values();

        $users = $query->paginate(10)->withQueryString();

        return view('users.index', compact('users', 'daftarRole', 'daftarBidang'));
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
            'no_wa' => 'required|string|max:30',
        ]);

        try {
            try {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
            } catch (\Throwable $e) {}

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
                'no_wa' => trim($request->no_wa),
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
            'no_wa' => 'required|string|max:30',
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
            $user->no_wa = trim($request->no_wa);

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

    // PENGATURAN AKUN USER (PROFIL SAYA)
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255|unique:users,name,' . $user->id,
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|min:4',
            'no_wa' => 'required|string|max:30',
        ]);

        try {
            $user->name = trim($request->name);
            $user->email = trim($request->email);
            $user->no_wa = trim($request->no_wa);

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            return back()->with('success', 'Pengaturan akun Anda berhasil diperbarui!');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal memperbarui akun: ' . $e->getMessage());
        }
    }

    // FITUR IMPERSONATE (INTIP AKSES USER OLEH SUPERADMIN)
    public function impersonate($id)
    {
        $currentUser = Auth::user();

        // Hanya Admin Keuangan (atau yang sedang mengintip dan punya impersonator_id) yang boleh mengintip
        if ($currentUser->role != 'Admin Keuangan' && !session()->has('impersonator_id')) {
            abort(403, 'Akses Ditolak: Hanya Admin Keuangan (Superadmin) yang dapat mengintip akses user.');
        }

        if ($id == Auth::id()) {
            return back()->with('error', 'Anda sudah berada di akun ini.');
        }

        $targetUser = User::findOrFail($id);

        // Simpan ID admin asli jika belum ada
        if (!session()->has('impersonator_id')) {
            session(['impersonator_id' => Auth::id()]);
        }

        // Switch login ke target user
        Auth::loginUsingId($targetUser->id);

        return redirect()->route('dashboard')->with('success', 'MODE INTIP AKTIF: Anda sekarang mengakses sistem sebagai ' . $targetUser->name . ' (' . $targetUser->role . ').');
    }

    public function stopImpersonate()
    {
        if (session()->has('impersonator_id')) {
            $adminId = session('impersonator_id');
            session()->forget('impersonator_id');
            Auth::loginUsingId($adminId);

            return redirect()->route('users.index')->with('success', 'MODE INTIP SELESAI: Anda telah kembali ke akun Admin Super.');
        }

        return redirect()->route('dashboard');
    }
}