<?php

namespace App\Console\Commands;

use App\Services\BinanceStreamService;
use Illuminate\Console\Command;

/**
 * StartBinanceStream — Binance Fiyat Akışı Artisan Komutu
 *
 * Docker container'da sürekli çalışan process olarak başlatılır.
 * Kripto varlıkların fiyatlarını gerçek zamanlı takip eder.
 *
 * Kullanım: php artisan binance:stream
 * Docker:  compose.yaml'deki reverb container'ında çalışır.
 *
 * @see TASKS.md — Görev M-16
 */
class StartBinanceStream extends Command
{
    /**
     * Komut adı ve argümanları.
     */
    protected $signature = 'binance:stream
                            {--symbols=BTCUSDT,ETHUSDT,SOLUSDT,BNBUSDT,XRPUSDT : Takip edilecek semboller}';

    /**
     * Komut açıklaması.
     */
    protected $description = 'Binance fiyat akışını başlat ve gerçek zamanlı fiyat güncellemelerini yayınla';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $symbolsInput = $this->option('symbols');
        $symbols = array_map('trim', explode(',', $symbolsInput));

        $this->info('🚀 Binance fiyat akışı başlatılıyor...');
        $this->info('📊 Semboller: ' . implode(', ', $symbols));
        $this->info('Press Ctrl+C to stop.');
        $this->newLine();

        $streamService = new BinanceStreamService();
        $streamService->startStream($symbols);
    }
}
