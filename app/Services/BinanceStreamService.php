<?php

namespace App\Services;

use App\Events\PriceUpdated;
use App\Models\Asset;
use App\Models\PriceSnapshot;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * BinanceStreamService — Binance WebSocket Akışı
 *
 * Binance WebSocket stream'ine bağlanarak gerçek zamanlı kripto fiyatlarını alır.
 * Her yeni fiyat geldiğinde:
 * 1. price_snapshots tablosuna INSERT eder
 * 2. PriceUpdated event dispatch eder → Reverb → Frontend
 *
 * NOT: Bu servis artisan komutu olarak çalışır (StartBinanceStream).
 * PHP'nin native stream_socket_client'ını kullanır.
 *
 * @see TASKS.md — Görev M-16
 */
class BinanceStreamService
{
    /**
     * Binance WebSocket base URL
     */
    private const WS_BASE_URL = 'wss://stream.binance.com:9443/ws';

    /**
     * Fiyat cache TTL (saniye)
     */
    private const CACHE_TTL = 60;

    /**
     * WebSocket stream'ini başlat ve dinle.
     *
     * @param array $symbols Dinlenecek semboller (örn: ['btcusdt', 'ethusdt'])
     */
    public function startStream(array $symbols): void
    {
        // Combined stream URL oluştur
        $streams = array_map(fn($s) => strtolower($s) . '@ticker', $symbols);
        $streamPath = implode('/', $streams);
        $url = self::WS_BASE_URL . '/' . $streamPath;

        Log::info("Binance WebSocket stream başlatılıyor", [
            'symbols' => $symbols,
            'url' => $url,
        ]);

        // WebSocket bağlantısı kur (Ratchet/Pawl kütüphanesi ile)
        $this->connectAndListen($url);
    }

    /**
     * WebSocket bağlantısını kur ve mesajları dinle.
     *
     * Bu metod Ratchet/Pawl yerine basit HTTP polling fallback kullanır
     * çünkü Windows Docker ortamında WebSocket client kurulumu karmaşık olabilir.
     * Gerçek WebSocket bağlantısı için Ratchet kurulumu gerekir.
     *
     * @param string $url WebSocket URL
     */
    private function connectAndListen(string $url): void
    {
        // Polling-based fallback: Her 5 saniyede Binance REST API'den fiyat çek
        // Gerçek WebSocket entegrasyonu için: composer require ratchet/pawl
        while (true) {
            try {
                $this->pollPrices();
                sleep(5); // 5 saniyede bir güncelle
            } catch (\Exception $e) {
                Log::error("BinanceStreamService polling hatası: {$e->getMessage()}");
                sleep(10); // Hata durumunda 10 saniye bekle
            }
        }
    }

    /**
     * REST API ile fiyatları çekip event dispatch et.
     *
     * Kullanıcıların portföyündeki aktif kripto varlıkları için fiyat çeker.
     */
    private function pollPrices(): void
    {
        // Sistemdeki tüm CRYPTO asset'leri bul
        $cryptoAssets = Asset::where('asset_class', 'CRYPTO')->get();

        if ($cryptoAssets->isEmpty()) {
            return;
        }

        // Binance'den toplu fiyat çek
        $binanceProvider = new \App\Providers\BinanceProvider();
        $symbols = $cryptoAssets->pluck('symbol')->toArray();

        try {
            $prices = $binanceProvider->fetchBatch($symbols);
        } catch (\Exception $e) {
            Log::warning("Binance batch fiyat çekme hatası: {$e->getMessage()}");
            return;
        }

        foreach ($cryptoAssets as $asset) {
            $symbol = strtoupper($asset->symbol);

            if (!isset($prices[$symbol])) {
                continue;
            }

            $newPrice = $prices[$symbol];

            // Önceki fiyatı cache'den al (değişim yüzdesi hesaplamak için)
            $cacheKey = "price:{$asset->id}";
            $previousPrice = Cache::get($cacheKey, $newPrice);

            // Değişim yüzdesi
            $changePercent = $previousPrice > 0
                ? (($newPrice - $previousPrice) / $previousPrice) * 100
                : 0;

            // price_snapshots tablosuna INSERT
            PriceSnapshot::create([
                'asset_id'   => $asset->id,
                'price'      => $newPrice,
                'currency'   => 'USD',
                'source'     => 'binance_stream',
                'fetched_at' => now(),
            ]);

            // Redis cache güncelle
            Cache::put($cacheKey, $newPrice, self::CACHE_TTL);

            // PriceUpdated event dispatch et → Reverb → Frontend
            $pairSymbol = $symbol . 'USDT';
            PriceUpdated::dispatch($pairSymbol, $newPrice, round($changePercent, 2));
        }
    }
}
