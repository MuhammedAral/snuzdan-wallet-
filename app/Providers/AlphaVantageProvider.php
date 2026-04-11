<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * AlphaVantageProvider — Yedek (Fallback) Fiyat Sağlayıcısı
 *
 * Yahoo Finance çökerse veya hata verirse bu provider devreye girer.
 * Alpha Vantage free tier: 25 istek/gün. Dikkatli kullanılmalı.
 *
 * API Key: .env'den ALPHA_VANTAGE_API_KEY olarak okunur.
 *
 * @see https://www.alphavantage.co/documentation/
 */
class AlphaVantageProvider implements PriceProviderInterface
{
    /**
     * Alpha Vantage API base URL
     */
    private const BASE_URL = 'https://www.alphavantage.co/query';

    /**
     * {@inheritdoc}
     *
     * STOCK ve FX asset'lerini destekler (Yahoo ile aynı kapsam — fallback).
     */
    public function supports(string $assetClass): bool
    {
        return in_array($assetClass, ['STOCK', 'FX'], true);
    }

    /**
     * Hisse senedi veya döviz fiyatını Alpha Vantage'dan çek.
     *
     * @param string $symbol   Varlık sembolü (örn: 'AAPL' veya 'EUR/USD')
     * @param string $currency Quote currency
     * @return float           Anlık fiyat
     *
     * @throws RuntimeException API hatası veya key eksikliğinde
     */
    public function fetchPrice(string $symbol, string $currency = 'USD'): float
    {
        $apiKey = config('services.alpha_vantage.api_key');

        if (empty($apiKey)) {
            throw new RuntimeException(
                'Alpha Vantage API key bulunamadı. ALPHA_VANTAGE_API_KEY .env dosyasına ekleyin.'
            );
        }

        // FX mi yoksa hisse mi?
        if ($this->isFxSymbol($symbol)) {
            return $this->fetchFxPrice($symbol, $currency, $apiKey);
        }

        return $this->fetchStockPrice($symbol, $apiKey);
    }

    /**
     * Hisse senedi fiyatını çek (GLOBAL_QUOTE endpoint).
     */
    private function fetchStockPrice(string $symbol, string $apiKey): float
    {
        try {
            $response = Http::timeout(10)
                ->get(self::BASE_URL, [
                    'function' => 'GLOBAL_QUOTE',
                    'symbol'   => strtoupper($symbol),
                    'apikey'   => $apiKey,
                ]);

            if ($response->failed()) {
                throw new RuntimeException(
                    "Alpha Vantage API hatası ({$symbol}): HTTP {$response->status()}"
                );
            }

            $data = $response->json();

            // Rate limit kontrolü
            if (isset($data['Note']) || isset($data['Information'])) {
                throw new RuntimeException(
                    "Alpha Vantage rate limit aşıldı ({$symbol}): " . ($data['Note'] ?? $data['Information'])
                );
            }

            $price = $data['Global Quote']['05. price'] ?? null;

            if ($price === null) {
                throw new RuntimeException(
                    "Alpha Vantage'dan fiyat alınamadı ({$symbol}): Geçersiz response"
                );
            }

            return (float) $price;
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error("AlphaVantageProvider fetchStockPrice hatası: {$e->getMessage()}", [
                'symbol' => $symbol,
            ]);
            throw new RuntimeException(
                "Alpha Vantage bağlantı hatası ({$symbol}): {$e->getMessage()}"
            );
        }
    }

    /**
     * Döviz kuru fiyatını çek (CURRENCY_EXCHANGE_RATE endpoint).
     */
    private function fetchFxPrice(string $symbol, string $currency, string $apiKey): float
    {
        // "EUR/USD" → from=EUR, to=USD
        if (str_contains($symbol, '/')) {
            $parts = explode('/', $symbol);
            $fromCurrency = strtoupper($parts[0]);
            $toCurrency = strtoupper($parts[1]);
        } else {
            $fromCurrency = strtoupper($currency);
            $toCurrency = strtoupper($symbol);
        }

        try {
            $response = Http::timeout(10)
                ->get(self::BASE_URL, [
                    'function'      => 'CURRENCY_EXCHANGE_RATE',
                    'from_currency' => $fromCurrency,
                    'to_currency'   => $toCurrency,
                    'apikey'        => $apiKey,
                ]);

            if ($response->failed()) {
                throw new RuntimeException(
                    "Alpha Vantage FX API hatası ({$fromCurrency}/{$toCurrency}): HTTP {$response->status()}"
                );
            }

            $data = $response->json();

            // Rate limit kontrolü
            if (isset($data['Note']) || isset($data['Information'])) {
                throw new RuntimeException(
                    "Alpha Vantage rate limit aşıldı: " . ($data['Note'] ?? $data['Information'])
                );
            }

            $price = $data['Realtime Currency Exchange Rate']['5. Exchange Rate'] ?? null;

            if ($price === null) {
                throw new RuntimeException(
                    "Alpha Vantage'dan FX kuru alınamadı ({$fromCurrency}/{$toCurrency}): Geçersiz response"
                );
            }

            return (float) $price;
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error("AlphaVantageProvider fetchFxPrice hatası: {$e->getMessage()}", [
                'from' => $fromCurrency ?? $symbol,
                'to' => $toCurrency ?? $currency,
            ]);
            throw new RuntimeException(
                "Alpha Vantage FX bağlantı hatası: {$e->getMessage()}"
            );
        }
    }

    /**
     * Sembolün FX (döviz) olup olmadığını kontrol et.
     */
    private function isFxSymbol(string $symbol): bool
    {
        // "EUR/USD" formatı
        if (str_contains($symbol, '/')) {
            return true;
        }

        // 3 haneli bilinen döviz kodları
        $fxCodes = ['EUR', 'GBP', 'TRY', 'JPY', 'CHF', 'AUD', 'CAD', 'CNY', 'INR', 'BRL'];
        return in_array(strtoupper($symbol), $fxCodes, true);
    }
}
