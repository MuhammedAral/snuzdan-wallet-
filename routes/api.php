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
});

