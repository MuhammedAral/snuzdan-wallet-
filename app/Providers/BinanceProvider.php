<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * BinanceProvider — Kripto Para Fiyat Sağlayıcısı
 *
 * Binance REST API'sini kullanarak kripto varlık fiyatlarını çeker.
 * API key gerektirmez (public endpoint).
 *
 * @see https://binance-docs.github.io/apidocs/spot/en/#symbol-price-ticker
 */
class BinanceProvider implements PriceProviderInterface
{
    /**
     * Binance API base URL
     */
    private const BASE_URL = 'https://api.binance.com/api/v3';

    /**
     * {@inheritdoc}
     *
     * Sadece CRYPTO asset'leri destekler.
     */
    public function supports(string $assetClass): bool
    {
        return $assetClass === 'CRYPTO';
    }

    /**
     * Tek bir kripto varlığın fiyatını çek.
     *
     * @param string $symbol   Kripto sembolü (örn: 'BTC', 'ETH', 'SOL')
     * @param string $currency Quote currency (örn: 'USDT' — Binance genelde USDT pair kullanır)
     * @return float           Anlık fiyat
     *
     * @throws RuntimeException API hatası durumunda
     */
    public function fetchPrice(string $symbol, string $currency = 'USD'): float
    {
        // Binance'te quote currency genelde USDT
        $quoteCurrency = $currency === 'USD' ? 'USDT' : strtoupper($currency);
        $pair = strtoupper($symbol) . $quoteCurrency;

        try {
            $response = Http::timeout(10)
                ->get(self::BASE_URL . '/ticker/price', [
                    'symbol' => $pair,
                ]);

            if ($response->failed()) {
                throw new RuntimeException(
                    "Binance API hatası ({$pair}): HTTP {$response->status()}"
                );
            }

            $data = $response->json();

            if (!isset($data['price'])) {
                throw new RuntimeException(
                    "Binance API'den fiyat alınamadı ({$pair}): Geçersiz response"
                );
            }

            return (float) $data['price'];
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error("BinanceProvider fetchPrice hatası: {$e->getMessage()}", [
                'symbol' => $symbol,
                'currency' => $currency,
                'pair' => $pair,
            ]);
            throw new RuntimeException(
                "Binance API bağlantı hatası ({$pair}): {$e->getMessage()}"
            );
        }
    }

    /**
     * Birden fazla kripto fiyatını aynı anda çek.
     *
     * Binance /ticker/price endpoint'i parametresiz çağrılırsa TÜM fiyatları döndürür.
     * Bu metod istenen sembolleri filtreler.
     *
     * @param array  $symbols  Sembol listesi (örn: ['BTC', 'ETH', 'SOL'])
     * @param string $currency Quote currency (default: 'USD' → 'USDT')
     * @return array           ['BTC' => 65000.50, 'ETH' => 3200.75, ...]
     *
     * @throws RuntimeException API hatası durumunda
     */
    public function fetchBatch(array $symbols, string $currency = 'USD'): array
    {
        $quoteCurrency = $currency === 'USD' ? 'USDT' : strtoupper($currency);

        try {
            $response = Http::timeout(15)
                ->get(self::BASE_URL . '/ticker/price');

            if ($response->failed()) {
                throw new RuntimeException(
                    "Binance batch API hatası: HTTP {$response->status()}"
                );
            }

            $allPrices = $response->json();
            $result = [];

            // İstenen sembolleri filtrele
            $targetPairs = [];
            foreach ($symbols as $symbol) {
                $targetPairs[strtoupper($symbol) . $quoteCurrency] = strtoupper($symbol);
            }

            foreach ($allPrices as $ticker) {
                $pairSymbol = $ticker['symbol'] ?? '';
                if (isset($targetPairs[$pairSymbol])) {
                    $result[$targetPairs[$pairSymbol]] = (float) $ticker['price'];
                }
            }

            return $result;
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error("BinanceProvider fetchBatch hatası: {$e->getMessage()}");
            throw new RuntimeException(
                "Binance batch API bağlantı hatası: {$e->getMessage()}"
            );
        }
    }
}
