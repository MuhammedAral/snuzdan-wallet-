<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * YahooProvider — Hisse Senedi ve Döviz Fiyat Sağlayıcısı
 *
 * Yahoo Finance API'sini kullanarak STOCK ve FX fiyatlarını çeker.
 * Ticker formatı: Hisse → 'AAPL', Döviz → 'EURUSD=X'
 *
 * NOT: Yahoo Finance'in resmi API'si yok. Bu provider Yahoo Finance v8 quote
 * endpoint'ini kullanır. Kararsız olabilir — AlphaVantageProvider fallback olarak kullanılır.
 */
class YahooProvider implements PriceProviderInterface
{
    /**
     * Yahoo Finance quote API base URL
     */
    private const BASE_URL = 'https://query1.finance.yahoo.com/v8/finance/chart';

    /**
     * {@inheritdoc}
     *
     * STOCK ve FX asset'lerini destekler.
     */
    public function supports(string $assetClass): bool
    {
        return in_array($assetClass, ['STOCK', 'FX'], true);
    }

    /**
     * Hisse senedi veya döviz fiyatını çek.
     *
     * @param string $symbol   Varlık sembolü (örn: 'AAPL', 'GOOGL' veya 'EUR/USD')
     * @param string $currency İstenen para birimi (FX pair oluştururken kullanılır)
     * @return float           Anlık fiyat
     *
     * @throws RuntimeException API hatası durumunda
     */
    public function fetchPrice(string $symbol, string $currency = 'USD'): float
    {
        $ticker = $this->formatTicker($symbol, $currency);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get(self::BASE_URL . '/' . $ticker, [
                    'interval' => '1d',
                    'range' => '1d',
                ]);

            if ($response->failed()) {
                throw new RuntimeException(
                    "Yahoo Finance API hatası ({$ticker}): HTTP {$response->status()}"
                );
            }

            $data = $response->json();

            // Response yapısından fiyatı çıkar
            $price = $data['chart']['result'][0]['meta']['regularMarketPrice'] ?? null;

            if ($price === null) {
                throw new RuntimeException(
                    "Yahoo Finance'den fiyat alınamadı ({$ticker}): Geçersiz response yapısı"
                );
            }

            return (float) $price;
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error("YahooProvider fetchPrice hatası: {$e->getMessage()}", [
                'symbol' => $symbol,
                'ticker' => $ticker,
                'currency' => $currency,
            ]);
            throw new RuntimeException(
                "Yahoo Finance bağlantı hatası ({$ticker}): {$e->getMessage()}"
            );
        }
    }

    /**
     * Sembolü Yahoo Finance ticker formatına çevir.
     *
     * Hisse senedi: 'AAPL' → 'AAPL'
     * Döviz çifti:  'EUR/USD' → 'EURUSD=X'
     * Döviz tekil:  'TRY' (currency=USD) → 'USDTRY=X'
     *
     * @param string $symbol   Orijinal sembol
     * @param string $currency Quote currency
     * @return string          Yahoo Finance ticker
     */
    private function formatTicker(string $symbol, string $currency): string
    {
        // "EUR/USD" gibi slash içeren döviz çifti
        if (str_contains($symbol, '/')) {
            $parts = explode('/', $symbol);
            return strtoupper($parts[0]) . strtoupper($parts[1]) . '=X';
        }

        // 3 haneli tekil döviz kodu (FX asset olarak)
        if (strlen($symbol) === 3 && ctype_alpha($symbol)) {
            // Kontrol: Bu gerçekten bir döviz kodu mu yoksa kısa hisse mi?
            $commonFx = ['EUR', 'GBP', 'TRY', 'JPY', 'CHF', 'AUD', 'CAD', 'CNY', 'INR', 'BRL'];
            if (in_array(strtoupper($symbol), $commonFx, true)) {
                return strtoupper($currency) . strtoupper($symbol) . '=X';
            }
        }

        // Hisse senedi — olduğu gibi döndür
        return strtoupper($symbol);
    }
}
