<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Asset;

/**
 * AssetSeeder — Popüler Yatırım Varlıkları
 *
 * Kripto, Hisse Senedi ve Döviz varlıklarını seed eder.
 * Kullanıcıların TradeEntryForm'da hızlıca seçebilmesi için.
 */
class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $assets = [
            // ── KRİPTO ──
            ['asset_class' => 'CRYPTO', 'symbol' => 'BTC',   'name' => 'Bitcoin',          'base_currency' => 'USD'],
            ['asset_class' => 'CRYPTO', 'symbol' => 'ETH',   'name' => 'Ethereum',         'base_currency' => 'USD'],
            ['asset_class' => 'CRYPTO', 'symbol' => 'BNB',   'name' => 'Binance Coin',     'base_currency' => 'USD'],
            ['asset_class' => 'CRYPTO', 'symbol' => 'SOL',   'name' => 'Solana',           'base_currency' => 'USD'],
            ['asset_class' => 'CRYPTO', 'symbol' => 'XRP',   'name' => 'Ripple',           'base_currency' => 'USD'],
            ['asset_class' => 'CRYPTO', 'symbol' => 'ADA',   'name' => 'Cardano',          'base_currency' => 'USD'],
            ['asset_class' => 'CRYPTO', 'symbol' => 'DOGE',  'name' => 'Dogecoin',         'base_currency' => 'USD'],
            ['asset_class' => 'CRYPTO', 'symbol' => 'DOT',   'name' => 'Polkadot',         'base_currency' => 'USD'],
            ['asset_class' => 'CRYPTO', 'symbol' => 'AVAX',  'name' => 'Avalanche',        'base_currency' => 'USD'],
            ['asset_class' => 'CRYPTO', 'symbol' => 'MATIC', 'name' => 'Polygon',          'base_currency' => 'USD'],
            ['asset_class' => 'CRYPTO', 'symbol' => 'LINK',  'name' => 'Chainlink',        'base_currency' => 'USD'],
            ['asset_class' => 'CRYPTO', 'symbol' => 'UNI',   'name' => 'Uniswap',          'base_currency' => 'USD'],
            ['asset_class' => 'CRYPTO', 'symbol' => 'ATOM',  'name' => 'Cosmos',           'base_currency' => 'USD'],
            ['asset_class' => 'CRYPTO', 'symbol' => 'LTC',   'name' => 'Litecoin',         'base_currency' => 'USD'],
            ['asset_class' => 'CRYPTO', 'symbol' => 'NEAR',  'name' => 'NEAR Protocol',    'base_currency' => 'USD'],

            // ── HİSSE SENETLERİ ──
            ['asset_class' => 'STOCK', 'symbol' => 'AAPL',  'name' => 'Apple Inc.',                'base_currency' => 'USD'],
            ['asset_class' => 'STOCK', 'symbol' => 'MSFT',  'name' => 'Microsoft Corp.',           'base_currency' => 'USD'],
            ['asset_class' => 'STOCK', 'symbol' => 'GOOGL', 'name' => 'Alphabet Inc.',             'base_currency' => 'USD'],
            ['asset_class' => 'STOCK', 'symbol' => 'AMZN',  'name' => 'Amazon.com Inc.',           'base_currency' => 'USD'],
            ['asset_class' => 'STOCK', 'symbol' => 'NVDA',  'name' => 'NVIDIA Corp.',              'base_currency' => 'USD'],
            ['asset_class' => 'STOCK', 'symbol' => 'META',  'name' => 'Meta Platforms Inc.',       'base_currency' => 'USD'],
            ['asset_class' => 'STOCK', 'symbol' => 'TSLA',  'name' => 'Tesla Inc.',                'base_currency' => 'USD'],
            ['asset_class' => 'STOCK', 'symbol' => 'NFLX',  'name' => 'Netflix Inc.',              'base_currency' => 'USD'],
            ['asset_class' => 'STOCK', 'symbol' => 'AMD',   'name' => 'Advanced Micro Devices',    'base_currency' => 'USD'],
            ['asset_class' => 'STOCK', 'symbol' => 'DIS',   'name' => 'Walt Disney Co.',           'base_currency' => 'USD'],
            ['asset_class' => 'STOCK', 'symbol' => 'THYAO', 'name' => 'Türk Hava Yolları',         'base_currency' => 'TRY'],
            ['asset_class' => 'STOCK', 'symbol' => 'GARAN', 'name' => 'Garanti BBVA',              'base_currency' => 'TRY'],
            ['asset_class' => 'STOCK', 'symbol' => 'ASELS', 'name' => 'Aselsan',                   'base_currency' => 'TRY'],
            ['asset_class' => 'STOCK', 'symbol' => 'SISE',  'name' => 'Şişe Cam',                  'base_currency' => 'TRY'],
            ['asset_class' => 'STOCK', 'symbol' => 'KCHOL', 'name' => 'Koç Holding',               'base_currency' => 'TRY'],

            // ── DÖVİZ / EMTİA ──
            ['asset_class' => 'FX', 'symbol' => 'USD/TRY', 'name' => 'Amerikan Doları / Türk Lirası', 'base_currency' => 'TRY'],
            ['asset_class' => 'FX', 'symbol' => 'EUR/TRY', 'name' => 'Euro / Türk Lirası',            'base_currency' => 'TRY'],
            ['asset_class' => 'FX', 'symbol' => 'EUR/USD', 'name' => 'Euro / Amerikan Doları',         'base_currency' => 'USD'],
            ['asset_class' => 'FX', 'symbol' => 'GBP/USD', 'name' => 'İngiliz Sterlini / Dolar',       'base_currency' => 'USD'],
            ['asset_class' => 'FX', 'symbol' => 'XAU/USD', 'name' => 'Altın (Ons)',                    'base_currency' => 'USD'],
            ['asset_class' => 'FX', 'symbol' => 'XAG/USD', 'name' => 'Gümüş (Ons)',                    'base_currency' => 'USD'],
        ];

        foreach ($assets as $asset) {
            \Illuminate\Support\Facades\DB::table('assets')->insertOrIgnore([
                'id'            => \Illuminate\Support\Str::uuid()->toString(),
                'asset_class'   => $asset['asset_class'],
                'symbol'        => $asset['symbol'],
                'name'          => $asset['name'],
                'base_currency' => $asset['base_currency'],
            ]);
        }
    }
}
