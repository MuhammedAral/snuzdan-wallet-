<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseTransaction extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // We use created_at/updated_at but handles it raw/mostly read. Let's enable timestamps if needed. Wait, we have updated_at. Let's keep it simple.

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    // Enable standard timestamps since schema has them.
    public $timestamps = true;

    protected $fillable = [
        'workspace_id',
        'created_by_user_id',
        'account_id',
        'category_id',
        'amount',
        'currency',
        'expense_date',
        'notes',
        'is_void',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'datetime',
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
