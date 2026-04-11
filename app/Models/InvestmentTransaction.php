<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InvestmentTransaction — Yatırım İşlemi Modeli
 *
 * Append-only ledger: ASLA DELETE veya UPDATE yapılmaz.
 * İptal = is_void = true.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $created_by_user_id
 * @property string $asset_id
 * @property string $side              BUY veya SELL
 * @property float  $quantity
 * @property float  $unit_price
 * @property float  $total_amount      = quantity × unit_price
 * @property float  $commission
 * @property float  $fx_rate_to_base
 * @property string|null $note
 * @property \Carbon\Carbon $transaction_date
 * @property \Carbon\Carbon $created_at
 * @property bool   $is_void
 * @property string|null $void_reason
 * @property \Carbon\Carbon|null $voided_at
 */
class InvestmentTransaction extends Model
{
    use HasUuids;

    /**
     * Tablo adı
     */
    protected $table = 'investment_transactions';

    /**
     * Sadece created_at var, updated_at yok (append-only).
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'workspace_id',
        'created_by_user_id',
        'asset_id',
        'side',
        'quantity',
        'unit_price',
        'total_amount',
        'commission',
        'fx_rate_to_base',
        'note',
        'transaction_date',
        'is_void',
        'void_reason',
        'voided_at',
    ];

    /**
     * Attribute casts.
     */
    protected function casts(): array
    {
        return [
            'quantity'         => 'float',
            'unit_price'       => 'float',
            'total_amount'     => 'float',
            'commission'       => 'float',
            'fx_rate_to_base'  => 'float',
            'is_void'          => 'boolean',
            'transaction_date' => 'datetime',
            'created_at'       => 'datetime',
            'voided_at'        => 'datetime',
        ];
    }

    /**
     * Bu işlemi yapan kullanıcı.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Bu işlemin ait olduğu varlık.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
