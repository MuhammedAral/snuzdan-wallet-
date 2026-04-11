<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'email',
        'password_hash',
        'display_name',
        'base_currency',
        'theme',
        'status',
        'email_verified',
        'avatar_url',
        'current_workspace_id',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    // Laravel Auth expects 'password' column — we use password_hash
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    // ── Relationships ──

    public function currentWorkspace()
    {
        return $this->belongsTo(Workspace::class, 'current_workspace_id');
    }

    public function workspaces()
    {
        return $this->belongsToMany(Workspace::class, 'workspace_user')
                    ->withPivot('role')
                    ->withTimestamps(false);
    }

    public function ownedWorkspaces()
    {
        return $this->hasMany(Workspace::class, 'created_by');
    }

    public function oauthAccounts()
    {
        return $this->hasMany(OauthAccount::class, 'user_id');
    }

    public function verificationTokens()
    {
        return $this->hasMany(VerificationToken::class, 'user_id');
    }
}
