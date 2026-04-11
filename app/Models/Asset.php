<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\PriceService;

/**
 * Asset — Yatırım Varlığı Modeli
 *
 * Kripto, hisse senedi ve döviz varlıklarını temsil eder.
 *
 * @property string $id
 * @property string $asset_class  CRYPTO, STOCK veya FX
 * @property string $symbol       BTC, AAPL, EUR/USD
 * @property string $name         Bitcoin, Apple Inc.
 * @property string $base_currency
 * @property \Carbon\Carbon $created_at
 */
class Asset extends Model
{
    use HasUuids;

    /**
     * Timestamps: Sadece created_at var, updated_at yok.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'asset_class',
        'symbol',
        'name',
        'base_currency',
    ];

    /**
     * Attribute casts.
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Bu varlığa ait yatırım işlemleri.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(InvestmentTransaction::class);
    }

    /**
     * Bu varlığa ait fiyat snapshot'ları.
     */
    public function priceSnapshots(): HasMany
    {
        return $this->hasMany(PriceSnapshot::class);
    }

    /**
     * Bu varlığın anlık fiyatını getir (PriceService üzerinden).
     *
     * @return float
     */
    public function latestPrice(): float
    {
        $priceService = app(PriceService::class);
        return $priceService->getLatestPrice($this);
    }
}
