<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — CivicAI Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => redirect('/dashboard'));

Route::prefix('dashboard')->group(function () {
    Route::get('/',                          [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/complaints',                [DashboardController::class, 'complaints'])->name('dashboard.complaints');
    Route::get('/complaints/{ticket}',       [DashboardController::class, 'complaintDetail'])->name('dashboard.complaint.detail');
    Route::get('/analytics',                 [DashboardController::class, 'analytics'])->name('dashboard.analytics');
});
