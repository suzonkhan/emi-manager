<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Installment extends Model
{
    protected $fillable = [
        'customer_id',
        'installment_number',
        'amount',
        'due_date',
        'paid_date',
        'paid_amount',
        'status',
        'notes',
        'collected_by',
        'payment_method',
        'transaction_reference',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    /**
     * Check if installment is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date->isPast();
    }

    /**
     * Get remaining amount
     */
    public function getRemainingAmountAttribute(): float
    {
        return $this->amount - $this->paid_amount;
    }
}
