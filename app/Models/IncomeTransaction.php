<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class IncomeTransaction extends Model
{
    use HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $timestamps = true;

    protected $fillable = [
        'workspace_id',
        'created_by_user_id',
        'account_id',
        'category_id',
        'amount',
        'currency',
        'income_date',
        'notes',
        'is_void',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'income_date' => 'datetime',
            'is_void' => 'boolean',
        ];
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Scope to only include active/valid transactions (not voided).
     */
    public function scopeActive($query)
    {
        return $query->where('is_void', false);
    }
}
