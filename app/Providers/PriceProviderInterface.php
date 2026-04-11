<?php

namespace App\Providers;

/**
 * PriceProviderInterface — Fiyat Sağlayıcı Kontratı
 *
 * Tüm fiyat sağlayıcıları (Binance, Yahoo, AlphaVantage) bu interface'i implement eder.
 * PriceService, asset_class'a göre doğru provider'ı seçer.
 *
 * @see \App\Services\PriceService
 */
interface PriceProviderInterface
{
    /**
     * Tek bir varlığın fiyatını çek.
     *
     * @param string $symbol   Varlık sembolü (örn: 'BTC', 'AAPL', 'EUR/USD')
     * @param string $currency Fiyatın hangi para biriminde döneceği (örn: 'USD')
     * @return float           Anlık fiyat
     *
     * @throws \RuntimeException API isteği başarısız olursa
     */
    public function fetchPrice(string $symbol, string $currency): float;

    /**
     * Bu provider'ın desteklediği asset_class'ı kontrol et.
     *
     * @param string $assetClass 'CRYPTO', 'STOCK' veya 'FX'
     * @return bool
     */
    public function supports(string $assetClass): bool;
}
