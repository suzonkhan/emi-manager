<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Token extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'created_by',
        'assigned_to',
        'status',
        'assigned_at',
        'used_at',
        'metadata',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'used_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($token) {
            if (empty($token->code)) {
                $token->code = $token->generateUniqueCode();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(12));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function assignTo(User $user): bool
    {
        if ($this->status !== 'available') {
            return false;
        }

        return $this->update([
            'assigned_to' => $user->id,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);
    }

    public function markAsUsed(): bool
    {
        if ($this->status !== 'assigned') {
            return false;
        }

        return $this->update([
            'status' => 'used',
            'used_at' => now(),
        ]);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function isAssigned(): bool
    {
        return $this->status === 'assigned';
    }

    public function isUsed(): bool
    {
        return $this->status === 'used';
    }
}
