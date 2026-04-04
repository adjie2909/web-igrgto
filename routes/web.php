<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TicketController;

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
    Route::post('approval/{id}/approve', [ApprovalController::class, 'approve'])->name('approval.approve');

    // REJECT
    Route::post('approval/{id}/reject', [ApprovalController::class, 'reject'])->name('approval.reject');

    // PGA ACTION
    Route::get('/request/proses/{id}', [RequestController::class, 'proses'])->name('proses');
    Route::get('/request/selesai/{id}', [RequestController::class, 'selesai'])->name('selesai');

    // PDF
    Route::get('/request/pdf/{id}', [RequestController::class, 'pdfChecklist'])->name('request.pdf');
    Route::get('/request/pdf-serah/{id}', [RequestController::class, 'pdfSerah'])->name('request.pdf.serah');

    // TICKETING
    Route::get('/ticket', [TicketController::class, 'index'])->name('ticket.index');
    Route::get('/ticket/create', [TicketController::class, 'create'])->name('ticket.create');
    Route::post('/ticket/store', [TicketController::class, 'store'])->name('ticket.store');

    Route::get('/ticket/{id}', [TicketController::class, 'show'])->name('ticket.show');
    Route::post('/ticket/reply/{id}', [TicketController::class, 'reply'])->name('ticket.reply');
    Route::post('/ticket/escalate/{id}', [TicketController::class, 'escalate'])
    ->name('ticket.escalate');

    Route::post('/ticket/close/{id}', [TicketController::class, 'close'])->name('ticket.close');


});

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {

    Route::resource('user', \App\Http\Controllers\Admin\UserController::class)->except(['create','store']);
    Route::resource('barang', \App\Http\Controllers\Admin\BarangController::class);

    // 🔥 reset password
    Route::get('user/reset/{id}', [\App\Http\Controllers\Admin\UserController::class, 'resetPassword'])
        ->name('user.reset');

    Route::get('/', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');
});

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';