<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| ROOT
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login'); // 🔥 FIX
});

/*
|--------------------------------------------------------------------------
| AUTH REQUIRED
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // DASHBOARD
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // PROFILE
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // REQUEST
    Route::get('/request', [RequestController::class, 'index'])->name('request.index');
    Route::get('/request/create', [RequestController::class, 'create'])->name('request.create');
    Route::post('/request/store', [RequestController::class, 'store'])->name('request.store');

    // APPROVAL
    Route::get('/approve/{id}', [ApprovalController::class, 'approve'])->name('approve');

    // REJECT
    Route::post('/reject/{id}', [ApprovalController::class, 'reject'])->name('reject');

    // PGA ACTION
    Route::get('/request/proses/{id}', [RequestController::class, 'proses'])->name('proses');
    Route::get('/request/selesai/{id}', [RequestController::class, 'selesai'])->name('selesai');

    // PDF
    Route::get('/request/pdf/{id}', [RequestController::class, 'pdfChecklist'])->name('request.pdf');
    Route::get('/request/pdf-serah/{id}', [RequestController::class, 'pdfSerah'])->name('request.pdf.serah');

});

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';