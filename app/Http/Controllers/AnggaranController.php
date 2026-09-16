<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnggaranController extends Controller
{
    // Menampilkan halaman Tautan Anggaran DIPA
    public function index()
    {
        $linkFilePath = storage_path('app/anggaran_link.txt');
        $linkAnggaran = null;
        if (file_exists($linkFilePath)) {
            $linkAnggaran = trim(file_get_contents($linkFilePath));
        }

        return view('anggaran.index', compact('linkAnggaran'));
    }

    // Proses Admin mengunggah/update Tautan Google Drive Anggaran DIPA
    public function upload(Request $request)
    {
        // Pastikan hanya Admin yang bisa upload/update
        if (Auth::user()->role != 'Admin Keuangan') {
            abort(403, 'Hanya Admin Keuangan yang dapat mengupdate tautan anggaran.');
        }

        // Validasi tautan Google Drive
        $request->validate([
            'link_anggaran' => 'required|url'
        ]);

        $linkFilePath = storage_path('app/anggaran_link.txt');
        file_put_contents($linkFilePath, $request->link_anggaran);

        return back()->with('success', 'Tautan Link Google Drive PDF Anggaran DIPA berhasil diperbarui!');
    }
}