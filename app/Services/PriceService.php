<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\PriceSnapshot;
use App\Providers\BinanceProvider;
use App\Providers\YahooProvider;
use App\Providers\AlphaVantageProvider;
use App\Providers\PriceProviderInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * PriceService — Fiyat Soyutlama Katmanı
 *
 * Asset_class'a göre doğru provider'ı seçer, fiyatı çeker, cache'e yazar.
 * Fallback mekanizması: Yahoo → AlphaVantage → DB'deki son fiyat.
 *
 * Cache stratejisi:
 * - Redis key: "price:{asset_id}"
 * - TTL: 1 dakika (60 saniye)
 *
 * @see ARCHITECTURE.md §3 — Price Provider Abstraction
 */
class PriceService
{
    /**
     * Cache TTL in seconds (1 dakika)
     */
    private const CACHE_TTL = 60;

    /**
     * Mevcut provider'lar
     *
     * @var PriceProviderInterface[]
     */
    private array $providers;

    /**
     * Fallback provider (Yahoo çöktüğünde kullanılır)
     */
    private AlphaVantageProvider $fallbackProvider;

    public function __construct()
    {
        $this->providers = [
            new BinanceProvider(),
            new YahooProvider(),
        ];
        $this->fallbackProvider = new AlphaVantageProvider();
    }

    /**
     * Fiyatı çek ve cache'e yaz.
     *
     * 1. asset_class'a göre doğru provider'ı seç
     * 2. Fiyatı çek (hata verirse fallback'e geç)
     * 3. price_snapshots tablosuna INSERT et
     * 4. Redis'e cache'le
     *
     * @param Asset $asset Fiyatı çekilecek varlık
     * @return float       Çekilen fiyat
     *
     * @throws RuntimeException Tüm provider'lar başarısız olursa
     */
    public function fetchAndCache(Asset $asset): float
    {
        $provider = $this->resolveProvider($asset->asset_class);
        $price = null;

        // Ana provider ile dene
        try {
            $price = $provider->fetchPrice($asset->symbol, $asset->base_currency);
        } catch (RuntimeException $e) {
            Log::warning("Ana provider başarısız ({$asset->symbol}): {$e->getMessage()}");

            // Fallback: Yahoo çöktüyse AlphaVantage'ı dene
            if ($this->fallbackProvider->supports($asset->asset_class)) {
                try {
                    $price = $this->fallbackProvider->fetchPrice($asset->symbol, $asset->base_currency);
                    Log::info("Fallback provider başarılı ({$asset->symbol})");
                } catch (RuntimeException $fallbackEx) {
                    Log::error("Fallback provider de başarısız ({$asset->symbol}): {$fallbackEx->getMessage()}");
                }
            }
        }

        if ($price === null) {
            throw new RuntimeException(
                "Hiçbir provider'dan fiyat alınamadı: {$asset->symbol} ({$asset->asset_class})"
            );
        }

        // price_snapshots tablosuna INSERT
        $source = $provider instanceof BinanceProvider ? 'binance' : 'yahoo';
        PriceSnapshot::create([
            'asset_id'   => $asset->id,
            'price'      => $price,
            'currency'   => $asset->base_currency,
            'source'     => $source,
            'fetched_at' => now(),
        ]);

        // Redis'e cache'le (TTL: 1 dakika)
        $cacheKey = "price:{$asset->id}";
        Cache::put($cacheKey, $price, self::CACHE_TTL);

        return $price;
    }

    /**
     * Bir varlığın son fiyatını getir.
     *
     * Öncelik sırası:
     * 1. Redis cache
     * 2. price_snapshots tablosundaki son kayıt
     * 3. fetchAndCache() ile canlı çek
     * 4. Son çare: DB'deki en eski fiyat (tüm provider'lar çökmüşse)
     *
     * @param Asset $asset Fiyatı istenen varlık
     * @return float       Son bilinen fiyat
     *
     * @throws RuntimeException Hiçbir yerden fiyat bulunamazsa
     */
    public function getLatestPrice(Asset $asset): float
    {
        $cacheKey = "price:{$asset->id}";

        // 1. Redis cache'e bak
        $cachedPrice = Cache::get($cacheKey);
        if ($cachedPrice !== null) {
            return (float) $cachedPrice;
        }

        // 2. DB'den son snapshot'ı al
        $lastSnapshot = PriceSnapshot::where('asset_id', $asset->id)
            ->orderByDesc('fetched_at')
            ->first();

        if ($lastSnapshot) {
            // Cache'e tekrar yaz
            Cache::put($cacheKey, $lastSnapshot->price, self::CACHE_TTL);

            // Eğer snapshot 5 dakikadan eski değilse direkt döndür
            if ($lastSnapshot->fetched_at->diffInMinutes(now()) < 5) {
                return (float) $lastSnapshot->price;
            }
        }

        // 3. Canlı fiyat çekmeyi dene
        try {
            return $this->fetchAndCache($asset);
        } catch (RuntimeException $e) {
            Log::warning("Canlı fiyat çekilemedi ({$asset->symbol}): {$e->getMessage()}");
        }

        // 4. Son çare: DB'deki eski fiyat (iki provider da çökmüşse)
        if ($lastSnapshot) {
            Log::warning("Eski DB fiyatı kullanılıyor ({$asset->symbol}): {$lastSnapshot->fetched_at}");
            return (float) $lastSnapshot->price;
        }

        throw new RuntimeException(
            "Hiçbir kaynaktan fiyat bulunamadı: {$asset->symbol}"
        );
    }

    /**
     * Asset_class'a göre doğru provider'ı seç.
     *
     * @param string $assetClass 'CRYPTO', 'STOCK' veya 'FX'
     * @return PriceProviderInterface
     *
     * @throws RuntimeException Destekleyen provider yoksa
     */
    private function resolveProvider(string $assetClass): PriceProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($assetClass)) {
                return $provider;
            }
        }

        throw new RuntimeException("Destekleyen provider bulunamadı: {$assetClass}");
    }
}
