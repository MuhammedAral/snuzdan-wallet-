<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiInteraction extends Model
{
    use HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'prompt',
        'response',
        'action_type',
        'was_accepted',
    ];

    protected function casts(): array
    {
        return [
            'response' => 'array',
            'was_accepted' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
