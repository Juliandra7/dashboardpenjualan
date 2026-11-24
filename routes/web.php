<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExecutiveDashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SalesEtlController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/analysis', [ExecutiveDashboardController::class, 'index'])->name('dashboard.analysis');
    Route::get('/etl', [SalesEtlController::class, 'index'])->name('etl.index');
    Route::post('/etl/manual', [SalesEtlController::class, 'store'])->name('etl.manual');
    Route::post('/etl/csv/preview', [SalesEtlController::class, 'preview'])->name('etl.csv.preview');
    Route::post('/etl/csv/import', [SalesEtlController::class, 'import'])->name('etl.csv.import');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::put('/transactions/{sale}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('/transactions/{sale}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
    Route::get('/transactions/{sale}', [TransactionController::class, 'show'])->name('transactions.show');

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/data/level-1', [ExecutiveDashboardController::class, 'level1'])->name('level1');
        Route::get('/data/level-2', [ExecutiveDashboardController::class, 'level2'])->name('level2');
        Route::get('/data/state', [ExecutiveDashboardController::class, 'cities'])->name('state');
        Route::get('/data/months', [ExecutiveDashboardController::class, 'months'])->name('months');
        Route::get('/data/what-if', [ExecutiveDashboardController::class, 'whatIf'])->name('what-if');
    });
});
