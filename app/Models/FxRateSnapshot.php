<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * FxRateSnapshot — Döviz Kuru Geçmişi Modeli
 *
 * Yahoo ve AlphaVantage'dan çekilen döviz kuru verileri.
 *
 * @property string $id
 * @property string $base_currency    'USD', 'EUR'
 * @property string $quote_currency   'TRY', 'USD'
 * @property float  $rate
 * @property string $source           'yahoo', 'alphavantage'
 * @property \Carbon\Carbon $fetched_at
 */
class FxRateSnapshot extends Model
{
    use HasUuids;

    /**
     * Bu tabloda Laravel timestamps yok, fetched_at kullanıyoruz.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'base_currency',
        'quote_currency',
        'rate',
        'source',
        'fetched_at',
    ];

    /**
     * Attribute casts.
     */
    protected $casts = [
        'rate'       => 'float',
        'fetched_at' => 'datetime',
    ];
}
