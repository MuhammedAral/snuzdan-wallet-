<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\FetchPriceSnapshots;
use App\Jobs\FetchFxRates;
use App\Jobs\ProcessRecurringTransactions;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks (Laravel Scheduler)
|--------------------------------------------------------------------------
|
| FetchPriceSnapshots: Her 5 dakikada bir tüm asset fiyatlarını günceller
| FetchFxRates: Her saat başı USD/TRY, EUR/TRY, EUR/USD kurlarını günceller
|
*/
Schedule::job(new FetchPriceSnapshots())->everyFiveMinutes();
Schedule::job(new FetchFxRates())->hourly();
Schedule::job(new ProcessRecurringTransactions())->dailyAt('00:01');
