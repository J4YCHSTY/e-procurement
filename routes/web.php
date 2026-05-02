<?php

use App\Models\HardwareRequest;
use App\Models\SoftwareRequest;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequestController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    $userId = Auth::id();
    $hardwareHistory = HardwareRequest::where('user_id', $userId)->get();
    $softwareHistory = SoftwareRequest::where('user_id', $userId)->get();

    return Inertia::render('Dashboard',[
        'hardwareHistory' => $hardwareHistory,
        'softwareHistory' => $softwareHistory,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/request/hardware', [RequestController::class, 'storeHardware'])->name('request.hardware.store');
    Route::post('/request/software', [RequestController::class, 'storeSoftware'])->name('request.software.store');
});

require __DIR__.'/auth.php';
