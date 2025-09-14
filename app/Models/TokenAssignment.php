<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenAssignment extends Model
{
    protected $fillable = [
        'token_id',
        'from_user_id',
        'to_user_id',
        'action',
        'from_role',
        'to_role',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function token(): BelongsTo
    {
        return $this->belongsTo(Token::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function scopeForToken($query, $tokenId)
    {
        return $query->where('token_id', $tokenId)->orderBy('created_at');
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }
}
