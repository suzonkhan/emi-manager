<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'nid_no',
        'name',
        'email',
        'mobile',
        'present_address_id',
        'permanent_address_id',
        'token_id',
        'emi_duration_months',
        'product_type',
        'product_model',
        'product_price',
        'emi_per_month',
        'imei_1',
        'imei_2',
        'created_by',
        'documents',
        'status',
    ];

    protected $casts = [
        'product_price' => 'decimal:2',
        'emi_per_month' => 'decimal:2',
        'documents' => 'array',
    ];

    public function presentAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'present_address_id');
    }

    public function permanentAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'permanent_address_id');
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(Token::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    public function getTotalPayableAmount(): float
    {
        return $this->emi_per_month * $this->emi_duration_months;
    }

    public function getServiceChargeAmount(): float
    {
        return $this->getTotalPayableAmount() - $this->product_price;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isDefaulted(): bool
    {
        return $this->status === 'defaulted';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
