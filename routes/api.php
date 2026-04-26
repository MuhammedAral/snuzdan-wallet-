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

    // Assets (Yatırım varlıkları arama)
    Route::get('/assets', function (Request $request) {
        $query = \App\Models\Asset::query();
        if ($request->has('search') && $request->input('search') !== '') {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('symbol', 'ILIKE', "%{$search}%")
                  ->orWhere('name', 'ILIKE', "%{$search}%");
            });
        }
        if ($request->has('asset_class') && $request->input('asset_class') !== '') {
            $query->where('asset_class', $request->input('asset_class'));
        }
        return response()->json($query->orderBy('symbol')->limit(50)->get());
    });

    // Yeni varlık oluştur
    Route::post('/assets', function (Request $request) {
        $validated = $request->validate([
            'asset_class'   => 'required|string|in:CRYPTO,STOCK,FX',
            'symbol'        => 'required|string|max:20',
            'name'          => 'required|string|max:255',
            'base_currency' => 'nullable|string|size:3',
        ], [
            'asset_class.required' => 'Varlık sınıfı zorunludur.',
            'symbol.required'      => 'Sembol zorunludur.',
            'name.required'        => 'Varlık adı zorunludur.',
        ]);

        // Duplicate kontrol
        $existing = \Illuminate\Support\Facades\DB::table('assets')
            ->where('asset_class', $validated['asset_class'])
            ->where('symbol', strtoupper($validated['symbol']))
            ->first();

        if ($existing) {
            return response()->json($existing);
        }

        $id = \Illuminate\Support\Str::uuid()->toString();
        \Illuminate\Support\Facades\DB::table('assets')->insert([
            'id'            => $id,
            'asset_class'   => $validated['asset_class'],
            'symbol'        => strtoupper($validated['symbol']),
            'name'          => $validated['name'],
            'base_currency' => $validated['base_currency'] ?? 'USD',
        ]);

        $asset = \Illuminate\Support\Facades\DB::table('assets')->where('id', $id)->first();
        return response()->json($asset, 201);
    });

    // Manuel fiyat güncelleme
    Route::post('/assets/{id}/price', function (Request $request, string $id) {
        $validated = $request->validate([
            'price'    => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
        ]);

        $asset = \Illuminate\Support\Facades\DB::table('assets')->where('id', $id)->first();
        if (!$asset) {
            return response()->json(['message' => 'Varlık bulunamadı.'], 404);
        }

        \App\Models\PriceSnapshot::create([
            'asset_id'   => $id,
            'price'      => $validated['price'],
            'currency'   => $validated['currency'] ?? $asset->base_currency,
            'source'     => 'manual',
            'fetched_at' => now(),
        ]);

        // Redis cache güncelle
        \Illuminate\Support\Facades\Cache::put("price:{$id}", $validated['price'], 60);

        // Canlı yayın (WebSocket) - Tüm bağlı istemcilere fiyatın güncellendiğini haber ver
        \App\Events\PriceUpdated::dispatch(
            $asset->symbol,
            (float) $validated['price'],
            0 // Manuel güncellemede 24h değişimi bilinemediğinden 0 geçiyoruz
        );

        return response()->json([
            'message' => 'Fiyat güncellendi.',
            'price'   => (float) $validated['price'],
            'symbol'  => $asset->symbol,
        ]);
    });

    // Varlığın son fiyatını getir
    Route::get('/assets/{id}/price', function (string $id) {
        $snapshot = \App\Models\PriceSnapshot::where('asset_id', $id)
            ->orderByDesc('fetched_at')
            ->first();

        return response()->json([
            'price'      => $snapshot ? (float) $snapshot->price : null,
            'source'     => $snapshot?->source,
            'fetched_at' => $snapshot?->fetched_at,
        ]);
    });

    // Gider Modülü (append_only middleware ile)
    Route::get('/expenses', [\App\Http\Controllers\ExpenseController::class, 'index']);
    
    // Gelir Modülü (append_only middleware ile)
    Route::get('/incomes', [\App\Http\Controllers\IncomeController::class, 'index']);

    // Yapay Zeka
    Route::post('/ai/parse', [\App\Http\Controllers\AiController::class, 'parseTransaction']);

    // Dashboard
    Route::get('/dashboard/summary', [\App\Http\Controllers\DashboardController::class, 'summary']);
    Route::get('/dashboard/activities', [\App\Http\Controllers\DashboardController::class, 'recentActivity']);

    Route::middleware('append_only')->group(function () {
        // Expenses
        Route::post('/expenses', [\App\Http\Controllers\ExpenseController::class, 'store']);
        Route::post('/expenses/{id}/void', [\App\Http\Controllers\ExpenseController::class, 'void']);

        // Incomes
        Route::post('/incomes', [\App\Http\Controllers\IncomeController::class, 'store']);
        Route::post('/incomes/{id}/void', [\App\Http\Controllers\IncomeController::class, 'void']);
    });
});

