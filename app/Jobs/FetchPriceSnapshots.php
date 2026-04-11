<?php

namespace App\Jobs;

use App\Models\Asset;
use App\Services\PriceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * FetchPriceSnapshots — Fiyat Güncelleme Job'ı
 *
 * Tüm aktif Asset'lerin fiyatlarını çeker ve cache'e yazar.
 * Laravel Scheduler ile her 5 dakikada bir çalışır.
 *
 * @see app/Console/Kernel.php — Schedule tanımı
 */
class FetchPriceSnapshots implements ShouldQueue
{
    use Queueable;

    /**
     * Job'ın kaç kere denenebileceği.
     */
    public int $tries = 3;

    /**
     * Job'ın timeout süresi (saniye).
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * Tüm assets tablosundaki varlıkları çeker ve her biri için
     * PriceService::fetchAndCache() çağırır.
     */
    public function handle(PriceService $priceService): void
    {
        $assets = Asset::all();

        $successCount = 0;
        $failCount = 0;

        foreach ($assets as $asset) {
            try {
                $priceService->fetchAndCache($asset);
                $successCount++;
            } catch (\Exception $e) {
                $failCount++;
                Log::error("FetchPriceSnapshots: {$asset->symbol} fiyatı alınamadı", [
                    'asset_id' => $asset->id,
                    'symbol'   => $asset->symbol,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        Log::info("FetchPriceSnapshots tamamlandı", [
            'total'   => $assets->count(),
            'success' => $successCount,
            'fail'    => $failCount,
        ]);
    }
}
