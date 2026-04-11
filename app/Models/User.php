<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

#[Fillable([
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
    'two_factor_confirmed_at'
])]
#[Hidden(['password_hash', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'password_hash' => 'hashed',
        ];
    }
    
    // Auth Identifier override (Laravel expects 'password' by default but we use password_hash)
    public function getAuthPassword()
    {
        return $this->password_hash;
    }
    
    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    public function oauthAccounts()
    {
        return $this->hasMany(OauthAccount::class, 'created_by_user_id');
    }

    public function verificationTokens()
    {
        return $this->hasMany(VerificationToken::class, 'created_by_user_id');
    }
}
