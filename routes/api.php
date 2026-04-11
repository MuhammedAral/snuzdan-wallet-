<?php

use App\Http\Controllers\PortfolioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Portfolio API (Muhammed Ali)
|--------------------------------------------------------------------------
|
| Akif'in Dashboard'daki NetWorthCard ve TopMoversCard component'leri
| bu endpoint'leri kullanır. İlerideki mobil app de buradan veri çekecek.
|
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/portfolio/summary', [PortfolioController::class, 'summary']);
    Route::get('/portfolio/movers', [PortfolioController::class, 'movers']);

    Route::get('/categories', [\App\Http\Controllers\CategoryController::class, 'index']);
    Route::post('/categories', [\App\Http\Controllers\CategoryController::class, 'store']);
    Route::put('/categories/{id}', [\App\Http\Controllers\CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [\App\Http\Controllers\CategoryController::class, 'destroy']);

    // Gider Modülü (append_only middleware ile)
    Route::get('/expenses', [\App\Http\Controllers\ExpenseController::class, 'index']);
    
    // Gelir Modülü (append_only middleware ile)
    Route::get('/incomes', [\App\Http\Controllers\IncomeController::class, 'index']);

    // Yapay Zeka
    Route::post('/ai/parse', [\App\Http\Controllers\AiController::class, 'parseTransaction']);

    Route::middleware('append_only')->group(function () {
        // Expenses
        Route::post('/expenses', [\App\Http\Controllers\ExpenseController::class, 'store']);
        Route::post('/expenses/{id}/void', [\App\Http\Controllers\ExpenseController::class, 'void']);

        // Incomes
        Route::post('/incomes', [\App\Http\Controllers\IncomeController::class, 'store']);
        Route::post('/incomes/{id}/void', [\App\Http\Controllers\IncomeController::class, 'void']);
    });
});

