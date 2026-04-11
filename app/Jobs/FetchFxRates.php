<?php

namespace App\Jobs;

use App\Services\FxService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * FetchFxRates — Döviz Kuru Güncelleme Job'ı
 *
 * Desteklenen para çiftleri için döviz kurlarını çeker ve cache'e yazar.
 * Laravel Scheduler ile her saat başı çalışır.
 *
 * Desteklenen çiftler: USD/TRY, EUR/TRY, EUR/USD
 *
 * @see app/Console/Kernel.php — Schedule tanımı
 */
class FetchFxRates implements ShouldQueue
{
    use Queueable;

    /**
     * Job'ın kaç kere denenebileceği.
     */
    public int $tries = 3;

    /**
     * Job'ın timeout süresi (saniye).
     */
    public int $timeout = 60;

    /**
     * Desteklenen döviz çiftleri
     */
    private const CURRENCY_PAIRS = [
        ['USD', 'TRY'],
        ['EUR', 'TRY'],
        ['EUR', 'USD'],
    ];

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
     * Her para çifti için FxService::fetchAndCache() çağırır.
     */
    public function handle(FxService $fxService): void
    {
        $successCount = 0;
        $failCount = 0;

        foreach (self::CURRENCY_PAIRS as [$base, $quote]) {
            try {
                $fxService->fetchAndCache($base, $quote);
                $successCount++;
            } catch (\Exception $e) {
                $failCount++;
                Log::error("FetchFxRates: {$base}/{$quote} kuru alınamadı", [
                    'base'  => $base,
                    'quote' => $quote,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("FetchFxRates tamamlandı", [
            'total'   => count(self::CURRENCY_PAIRS),
            'success' => $successCount,
            'fail'    => $failCount,
        ]);
    }
}
