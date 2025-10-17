<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommandPresetMessage extends Model
{
    protected $fillable = [
        'user_id',
        'command',
        'title',
        'message',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user who owns this preset message
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get active preset messages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get preset for a specific command
     */
    public function scopeForCommand($query, string $command)
    {
        return $query->where('command', $command);
    }
}
