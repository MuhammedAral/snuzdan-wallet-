<?php

use App\Models\Asset;

$assets = [
    ['asset_class' => 'CRYPTO', 'symbol' => 'BTC', 'name' => 'Bitcoin', 'base_currency' => 'USD'],
    ['asset_class' => 'CRYPTO', 'symbol' => 'ETH', 'name' => 'Ethereum', 'base_currency' => 'USD'],
    ['asset_class' => 'CRYPTO', 'symbol' => 'SOL', 'name' => 'Solana', 'base_currency' => 'USD'],
    ['asset_class' => 'CRYPTO', 'symbol' => 'BNB', 'name' => 'Binance Coin', 'base_currency' => 'USD'],
    ['asset_class' => 'CRYPTO', 'symbol' => 'XRP', 'name' => 'Ripple', 'base_currency' => 'USD'],
    ['asset_class' => 'CRYPTO', 'symbol' => 'DOGE', 'name' => 'Dogecoin', 'base_currency' => 'USD'],
    ['asset_class' => 'STOCK', 'symbol' => 'AAPL', 'name' => 'Apple Inc.', 'base_currency' => 'USD'],
    ['asset_class' => 'STOCK', 'symbol' => 'TSLA', 'name' => 'Tesla Inc.', 'base_currency' => 'USD'],
    ['asset_class' => 'STOCK', 'symbol' => 'MSFT', 'name' => 'Microsoft', 'base_currency' => 'USD'],
    ['asset_class' => 'STOCK', 'symbol' => 'NVDA', 'name' => 'NVIDIA', 'base_currency' => 'USD'],
    ['asset_class' => 'STOCK', 'symbol' => 'THYAO.IS', 'name' => 'Türk Hava Yolları', 'base_currency' => 'TRY'],
    ['asset_class' => 'STOCK', 'symbol' => 'TUPRS.IS', 'name' => 'Tüpraş', 'base_currency' => 'TRY'],
    ['asset_class' => 'FX', 'symbol' => 'USDTRY=X', 'name' => 'Dolar/TL', 'base_currency' => 'TRY'],
    ['asset_class' => 'FX', 'symbol' => 'EURTRY=X', 'name' => 'Euro/TL', 'base_currency' => 'TRY']
];

foreach($assets as $a) {
    Asset::firstOrCreate(['symbol' => $a['symbol']], $a);
}
echo "Popüler varlıklar başarıyla eklendi.\n";
