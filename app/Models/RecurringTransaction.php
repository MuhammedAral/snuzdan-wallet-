<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecurringTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // We only have created_at handled manually or by DB default

    protected $fillable = [
        'workspace_id',
        'created_by_user_id',
        'account_id',
        'category_id',
        'direction',
        'amount',
        'currency',
        'period',
        'note',
        'next_run_date',
        'is_active',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'next_run_date' => 'date',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
