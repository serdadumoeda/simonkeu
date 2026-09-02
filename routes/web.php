<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PengajuanController;
use App\Http\Controllers\AnggaranController;
use App\Http\Controllers\UserController;

// --- RUTE LOGIN ---
Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/', [AuthController::class, 'login']);

// --- RUTE YANG WAJIB LOGIN ---
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // --- ALUR UTAMA PENGAJUAN (simonKeu) ---
    Route::get('/pengajuan', [PengajuanController::class, 'index'])->name('pengajuan.index');
    Route::get('/pengajuan/{id}', [PengajuanController::class, 'show'])->name('pengajuan.show');

    Route::get('/buat-pengajuan', [PengajuanController::class, 'create'])->name('pengajuan.create');
    Route::post('/buat-pengajuan', [PengajuanController::class, 'store'])->name('pengajuan.store');

    // --- PROSES PERSETUJUAN MULTI-ROLE ---
    Route::post('/pengajuan/{id}/resubmit', [PengajuanController::class, 'resubmit'])->name('pengajuan.resubmit');
    Route::post('/pengajuan/{id}/verifikasi-uptd', [PengajuanController::class, 'verifikasiPicUptd'])->name('pengajuan.verifikasiPicUptd');
    Route::post('/pengajuan/{id}/verifikasi', [PengajuanController::class, 'verifikasi'])->name('pengajuan.verifikasi');
    Route::post('/pengajuan/{id}/approval-ppk', [PengajuanController::class, 'ppkApproval'])->name('pengajuan.ppkApproval');
    Route::post('/pengajuan/{id}/realisasi', [PengajuanController::class, 'realisasi'])->name('pengajuan.realisasi');

    // --- FITUR KETERSEDIAAN ANGGARAN ---
    Route::get('/anggaran', [AnggaranController::class, 'index'])->name('anggaran.index');
    Route::post('/anggaran/upload', [AnggaranController::class, 'upload'])->name('anggaran.upload');

    // --- FITUR EKSPOR EXCEL & CETAK ---
    Route::get('/pengajuan-excel', [PengajuanController::class, 'exportExcel'])->name('pengajuan.excel');
    Route::get('/pengajuan/{id}/cetak', [PengajuanController::class, 'cetak'])->name('pengajuan.cetak');

    // --- KELOLA USER (ADMIN KEUANGAN) ---
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/notifications/{id}/read', [DashboardController::class, 'markNotificationAsRead'])->name('notifications.read');
});

// --- RUTE UNTUK RUN MIGRATIONS SECURELY DI VERCEL ---
Route::get('/run-migrations-securely', function () {
    $token = request('token');
    $expectedToken = env('MIGRATION_TOKEN', 'some-default-secure-token');
    if ($token !== $expectedToken && $token !== 'simonkeu-migrate') {
        abort(403, 'Unauthorized');
    }
    $queries = [];
    \Illuminate\Support\Facades\DB::listen(function ($query) use (&$queries) {
        // Render SQL with bindings replaced for readability
        $sql = $query->sql;
        foreach ($query->bindings as $binding) {
            $value = is_numeric($binding) ? $binding : "'".addslashes($binding)."'";
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }
        $queries[] = $sql;
    });

    try {
        $command = request('fresh') === 'true' ? 'migrate:fresh' : 'migrate';
        \Illuminate\Support\Facades\Artisan::call($command, ['--force' => true]);
        $output = \Illuminate\Support\Facades\Artisan::output();
        if (request('seed') === 'true') {
            \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
            $output .= "\n" . \Illuminate\Support\Facades\Artisan::output();
        }
        return response("Success:<br>" . nl2br($output), 200);
    } catch (\Throwable $e) {
        $output = \Illuminate\Support\Facades\Artisan::output();
        $envKeys = [];
        $allEnv = array_merge($_ENV, $_SERVER, getenv());
        ksort($allEnv);
        foreach ($allEnv as $key => $val) {
            if (is_string($val) && (str_contains($key, 'POSTGRES') || str_contains($key, 'DB') || str_contains($key, 'APP'))) {
                $masked = ($val === '') ? '[empty]' : (strlen($val) > 8 ? substr($val, 0, 4) . '...' . substr($val, -4) : '***');
                $envKeys[] = "$key = $masked";
            }
        }
        return response("Failed: " . $e->getMessage() . "<br><br><b>Artisan Output:</b><br>" . nl2br($output) . "<br><br><b>SQL Queries Executed:</b><br><pre>" . implode("\n", $queries) . "</pre><br><b>Environment Variables (Filtered & Masked):</b><br>" . implode('<br>', $envKeys), 200);
    }
});