<?php

namespace App\Services;

use App\Models\FxRateSnapshot;
use App\Providers\YahooProvider;
use App\Providers\AlphaVantageProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * FxService — Döviz Kuru Servisi
 *
 * Döviz kurlarını çeker, cache'ler ve para birimi dönüşümü yapar.
 *
 * Cache stratejisi:
 * - Redis key: "fx:{base}:{quote}"
 * - TTL: 1 saat (3600 saniye)
 *
 * Desteklenen çiftler: USD/TRY, EUR/TRY, EUR/USD
 *
 * @see ARCHITECTURE.md §3 — FxService
 */
class FxService
{
    /**
     * Cache TTL in seconds (1 saat)
     */
    private const CACHE_TTL = 3600;

    private YahooProvider $yahooProvider;
    private AlphaVantageProvider $fallbackProvider;

    public function __construct()
    {
        $this->yahooProvider = new YahooProvider();
        $this->fallbackProvider = new AlphaVantageProvider();
    }

    /**
     * Döviz kurunu çek ve cache'le.
     *
     * Önce Yahoo Finance'ten dener, başarısız olursa AlphaVantage'a düşer.
     *
     * @param string $baseCurrency  Kaynak para birimi (örn: 'USD')
     * @param string $quoteCurrency Hedef para birimi (örn: 'TRY')
     * @return float                Döviz kuru
     *
     * @throws RuntimeException Tüm provider'lar başarısız olursa
     */
    public function fetchAndCache(string $baseCurrency, string $quoteCurrency): float
    {
        $pair = strtoupper($baseCurrency) . '/' . strtoupper($quoteCurrency);
        $rate = null;

        // Yahoo Finance ile dene
        try {
            $rate = $this->yahooProvider->fetchPrice($pair, $quoteCurrency);
        } catch (RuntimeException $e) {
            Log::warning("Yahoo FX kuru alınamadı ({$pair}): {$e->getMessage()}");

            // Fallback: AlphaVantage
            try {
                $rate = $this->fallbackProvider->fetchPrice($pair, $quoteCurrency);
                Log::info("AlphaVantage FX fallback başarılı ({$pair})");
            } catch (RuntimeException $fallbackEx) {
                Log::error("AlphaVantage FX de başarısız ({$pair}): {$fallbackEx->getMessage()}");
            }
        }

        if ($rate === null) {
            throw new RuntimeException("FX kuru alınamadı: {$pair}");
        }

        // fx_rate_snapshots tablosuna INSERT
        FxRateSnapshot::create([
            'base_currency'  => strtoupper($baseCurrency),
            'quote_currency' => strtoupper($quoteCurrency),
            'rate'           => $rate,
            'source'         => 'yahoo',
            'fetched_at'     => now(),
        ]);

        // Redis'e cache'le (TTL: 1 saat)
        $cacheKey = "fx:" . strtoupper($baseCurrency) . ":" . strtoupper($quoteCurrency);
        Cache::put($cacheKey, $rate, self::CACHE_TTL);

        return $rate;
    }

    /**
     * İki para birimi arasındaki kuru döndür.
     *
     * Öncelik: Redis cache → DB son kayıt → Canlı çek
     *
     * @param string $from Kaynak para birimi (örn: 'USD')
     * @param string $to   Hedef para birimi (örn: 'TRY')
     * @return float       Döviz kuru
     */
    public function getRate(string $from, string $to): float
    {
        // Aynı para birimiyse kur 1.0
        if (strtoupper($from) === strtoupper($to)) {
            return 1.0;
        }

        $cacheKey = "fx:" . strtoupper($from) . ":" . strtoupper($to);

        // 1. Redis cache'e bak
        $cachedRate = Cache::get($cacheKey);
        if ($cachedRate !== null) {
            return (float) $cachedRate;
        }

        // 2. DB'den son snapshot
        $lastSnapshot = FxRateSnapshot::where('base_currency', strtoupper($from))
            ->where('quote_currency', strtoupper($to))
            ->orderByDesc('fetched_at')
            ->first();

        if ($lastSnapshot) {
            Cache::put($cacheKey, $lastSnapshot->rate, self::CACHE_TTL);
            return (float) $lastSnapshot->rate;
        }

        // 3. Ters kuru dene (TRY/USD yerine USD/TRY)
        $reverseSnapshot = FxRateSnapshot::where('base_currency', strtoupper($to))
            ->where('quote_currency', strtoupper($from))
            ->orderByDesc('fetched_at')
            ->first();

        if ($reverseSnapshot && (float) $reverseSnapshot->rate > 0) {
            $rate = 1.0 / (float) $reverseSnapshot->rate;
            Cache::put($cacheKey, $rate, self::CACHE_TTL);
            return $rate;
        }

        // 4. Canlı çek
        try {
            return $this->fetchAndCache($from, $to);
        } catch (RuntimeException $e) {
            Log::error("FX kuru hiçbir yerden bulunamadı ({$from}/{$to}): {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Tutarı kullanıcının base currency'sine çevir.
     *
     * @param float  $amount       Çevrilecek tutar
     * @param string $currency     Tutarın mevcut para birimi
     * @param string $baseCurrency Hedef para birimi (kullanıcının base_currency'si)
     * @return float               Çevrilmiş tutar
     */
    public function convertToBase(float $amount, string $currency, string $baseCurrency): float
    {
        if (strtoupper($currency) === strtoupper($baseCurrency)) {
            return $amount;
        }

        $rate = $this->getRate($currency, $baseCurrency);
        return $amount * $rate;
    }
}
