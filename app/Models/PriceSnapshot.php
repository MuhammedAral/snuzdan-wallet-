<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PriceSnapshot — Fiyat Geçmişi Modeli
 *
 * Binance, Yahoo, AlphaVantage gibi kaynaklardan çekilen fiyat verileri.
 * Her çekme işlemi yeni bir satır oluşturur (append-only).
 *
 * @property string $id
 * @property string $asset_id
 * @property float  $price
 * @property string $currency
 * @property string $source     'binance', 'yahoo', 'alphavantage'
 * @property \Carbon\Carbon $fetched_at
 */
class PriceSnapshot extends Model
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
        'asset_id',
        'price',
        'currency',
        'source',
        'fetched_at',
    ];

    /**
     * Attribute casts.
     */
    protected $casts = [
        'price'      => 'float',
        'fetched_at' => 'datetime',
    ];

    /**
     * Bu snapshot'ın ait olduğu varlık.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
