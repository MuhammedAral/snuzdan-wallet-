<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Category extends Model
{
    use HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'workspace_id',
        'name',
        'icon',
        'color',
        'direction',
        'cat_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    // ── Relationships ──

    public function workspace()
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function expenseTransactions()
    {
        return $this->hasMany(ExpenseTransaction::class, 'category_id');
    }

    public function incomeTransactions()
    {
        return $this->hasMany(IncomeTransaction::class, 'category_id');
    }

    // ── Scopes ──

    /**
     * Get SYSTEM categories + user's CUSTOM categories for a workspace.
     */
    public function scopeForWorkspace(Builder $query, ?string $workspaceId): Builder
    {
        return $query->where(function ($q) use ($workspaceId) {
            $q->where('cat_type', 'SYSTEM');
            if ($workspaceId) {
                $q->orWhere('workspace_id', $workspaceId);
            }
        })->where('is_active', true);
    }

    /**
     * Filter by direction: INCOME or EXPENSE.
     */
    public function scopeByDirection(Builder $query, string $direction): Builder
    {
        return $query->where('direction', $direction);
    }
}
