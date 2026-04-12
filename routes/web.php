<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\PortfolioController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'page'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Settings (Aşama 8)
    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'page'])->name('settings.index');
    Route::patch('/settings/profile', [\App\Http\Controllers\SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::patch('/settings/currency', [\App\Http\Controllers\SettingsController::class, 'updateCurrency'])->name('settings.currency.update');
    Route::patch('/settings/theme', [\App\Http\Controllers\SettingsController::class, 'updateTheme'])->name('settings.theme.update');
    Route::delete('/settings/linked-accounts/{id}', [\App\Http\Controllers\SettingsController::class, 'removeLinkedAccount'])->name('settings.linked-accounts.destroy');

    // Yatırım İşlemleri (Muhammed Ali)
    Route::get('/investments', [InvestmentController::class, 'index'])->name('investments.index');
    Route::middleware('append_only')->group(function () {
        Route::post('/investments', [InvestmentController::class, 'store'])->name('investments.store');
        Route::post('/investments/{id}/void', [InvestmentController::class, 'void'])->name('investments.void');
    });

    // Portföy (Muhammed Ali)
    Route::get('/portfolio', [PortfolioController::class, 'index'])->name('portfolio.index');

    // Gider Modülü (Akif)
    Route::get('/expenses', [\App\Http\Controllers\ExpenseController::class, 'page'])->name('expenses.index');

    // Gelir Modülü (Akif)
    Route::get('/incomes', [\App\Http\Controllers\IncomeController::class, 'page'])->name('incomes.index');
});

require __DIR__.'/auth.php';

