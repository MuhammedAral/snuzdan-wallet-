<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OauthAccount extends Model
{
    use HasUuids;

    protected $fillable = [
        'workspace_id',
        'created_by_user_id',
        'provider',
        'provider_id',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
