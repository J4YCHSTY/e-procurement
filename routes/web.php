<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\DashboardController;
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
    Route::post('/request/hardware', [RequestController::class, 'storeHardware'])->name('request.hardware.store');
    Route::post('/request/software', [RequestController::class, 'storeSoftware'])->name('request.software.store');

    Route::post('/request/hardware/{hardwareRequest}/approve', [ApprovalController::class, 'approveHardware'])->name('request.hardware.approve');
    Route::post('/request/hardware/{hardwareRequest}/reject', [ApprovalController::class, 'rejectHardware'])->name('request.hardware.reject');
    Route::post('/request/software/{softwareRequest}/approve', [ApprovalController::class, 'approveSoftware'])->name('request.software.approve');
    Route::post('/request/software/{softwareRequest}/reject', [ApprovalController::class, 'rejectSoftware'])->name('request.software.reject');

    // User Management - otorisasinya dicek per-method lewat UserPolicy,
    // bukan di sini, biar konsisten sama pola approval (lihat ApprovalController).
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('/users/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])->name('users.toggle-active');
});

require __DIR__.'/auth.php';