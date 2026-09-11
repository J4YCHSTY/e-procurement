<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FulfillmentController;
use App\Http\Controllers\RequestDetailController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Tanda tangan digital - semuanya milik user yang sedang login,
    // makanya tidak ada satu pun route di sini yang menerima ID user.
    Route::get('/signature/me', [SignatureController::class, 'show'])->name('signature.show');
    Route::post('/signature', [SignatureController::class, 'store'])->name('signature.store');
    Route::delete('/signature', [SignatureController::class, 'destroy'])->name('signature.destroy');

    Route::post('/request/hardware', [RequestController::class, 'storeHardware'])->name('request.hardware.store');
    Route::post('/request/software', [RequestController::class, 'storeSoftware'])->name('request.software.store');

    // Lampiran preferensi barang - disajikan lewat route ber-otorisasi
    // (RequestPolicy::view), bukan dari folder publik.
    Route::get('/request/hardware/{hardwareRequest}/preference-image', [RequestController::class, 'hardwarePreferenceImage'])->name('request.hardware.preference-image');
    Route::get('/request/software/{softwareRequest}/preference-image', [RequestController::class, 'softwarePreferenceImage'])->name('request.software.preference-image');

    Route::post('/request/hardware/{hardwareRequest}/approve', [ApprovalController::class, 'approveHardware'])->name('request.hardware.approve');
    Route::post('/request/hardware/{hardwareRequest}/reject', [ApprovalController::class, 'rejectHardware'])->name('request.hardware.reject');
    Route::post('/request/software/{softwareRequest}/approve', [ApprovalController::class, 'approveSoftware'])->name('request.software.approve');
    Route::post('/request/software/{softwareRequest}/reject', [ApprovalController::class, 'rejectSoftware'])->name('request.software.reject');

    // Halaman detail + tahap serah terima. Beda sama route approval di atas
    // yang digandakan per jenis: di sini jenisnya jadi satu segmen URL, jadi
    // menambah tahap baru tidak berarti menambah dua route sekaligus.
    // Segmen {type} dibatasi di sini, bukan cuma di controller, supaya URL
    // yang ngawur berhenti di router dan tidak sempat menyentuh database.
    Route::get('/pengajuan/{type}/{id}', [RequestDetailController::class, 'show'])
        ->whereIn('type', ['hardware', 'software'])->whereNumber('id')
        ->name('request.show');

    Route::post('/pengajuan/{type}/{id}/barang-dikirim', [FulfillmentController::class, 'markOnTheWay'])
        ->whereIn('type', ['hardware', 'software'])->whereNumber('id')
        ->name('request.mark-on-the-way');

    Route::post('/pengajuan/{type}/{id}/barang-diserahkan', [FulfillmentController::class, 'markHandedOver'])
        ->whereIn('type', ['hardware', 'software'])->whereNumber('id')
        ->name('request.mark-handed-over');

    Route::post('/pengajuan/{type}/{id}/tanda-tangan-bast', [FulfillmentController::class, 'signBast'])
        ->whereIn('type', ['hardware', 'software'])->whereNumber('id')
        ->name('request.sign-bast');

    // User Management - otorisasinya dicek per-method lewat UserPolicy,
    // bukan di sini, biar konsisten sama pola approval (lihat ApprovalController).
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('/users/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])->name('users.toggle-active');
});

require __DIR__.'/auth.php';