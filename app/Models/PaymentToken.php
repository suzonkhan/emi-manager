<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PaymentToken extends Model
{
    protected $fillable = [
        'token',
        'customer_id',
        'installment_id',
        'created_by',
        'amount',
        'status',
        'expires_at',
        'payment_data',
        'customer_notes',
        'admin_notes',
        'submitted_at',
        'processed_at',
        'processed_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'submitted_at' => 'datetime',
        'processed_at' => 'datetime',
        'payment_data' => 'array',
        'amount' => 'decimal:2',
    ];

    /**
     * Generate a unique payment token
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(32);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    /**
     * Check if token is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if token can be used
     */
    public function canBeUsed(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Mark token as submitted
     */
    public function markAsSubmitted(array $paymentData, string $notes = null): void
    {
        $this->update([
            'status' => 'submitted',
            'payment_data' => $paymentData,
            'customer_notes' => $notes,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Approve payment
     */
    public function approve(int $processedBy, string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'admin_notes' => $notes,
            'processed_at' => now(),
            'processed_by' => $processedBy,
        ]);
    }

    /**
     * Reject payment
     */
    public function reject(int $processedBy, string $notes = null): void
    {
        $this->update([
            'status' => 'rejected',
            'admin_notes' => $notes,
            'processed_at' => now(),
            'processed_by' => $processedBy,
        ]);
    }

    /**
     * Relationships
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
