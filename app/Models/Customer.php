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
        'photo',
        'present_address_id',
        'permanent_address_id',
        'token_id',
        'emi_duration_months',
        'product_type',
        'product_model',
        'product_price',
        'down_payment',
        'emi_per_month',
        'imei_1',
        'imei_2',
        'serial_number',
        'fcm_token',
        'is_device_locked',
        'is_camera_disabled',
        'is_bluetooth_disabled',
        'is_app_hidden',
        'is_call_disabled',
        'is_usb_locked',
        'is_frp_enabled',
        'has_password',
        'custom_wallpaper_url',
        'last_command_sent_at',
        'created_by',
        'dealer_id',
        'dealer_customer_id',
        'documents',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'product_price' => 'decimal:2',
            'down_payment' => 'decimal:2',
            'emi_per_month' => 'decimal:2',
            'documents' => 'array',
            'is_device_locked' => 'boolean',
            'is_camera_disabled' => 'boolean',
            'is_bluetooth_disabled' => 'boolean',
            'is_app_hidden' => 'boolean',
            'is_call_disabled' => 'boolean',
            'is_usb_locked' => 'boolean',
            'is_frp_enabled' => 'boolean',
            'has_password' => 'boolean',
            'last_command_sent_at' => 'datetime',
        ];
    }

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

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dealer_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    public function deviceCommandLogs(): HasMany
    {
        return $this->hasMany(DeviceCommandLog::class);
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

    public function hasDevice(): bool
    {
        return ! empty($this->serial_number) && ! empty($this->fcm_token);
    }

    public function canReceiveCommands(): bool
    {
        return $this->hasDevice() && $this->isActive();
    }

    /**
     * Get formatted dealer customer ID (e.g., "D-001", "D-1234")
     */
    public function getFormattedDealerCustomerId(): ?string
    {
        if (! $this->dealer_customer_id) {
            return null;
        }

        return 'D-'.str_pad($this->dealer_customer_id, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get the next dealer customer ID for a specific dealer
     */
    public static function getNextDealerCustomerId(int $dealerId): int
    {
        $lastCustomer = self::where('dealer_id', $dealerId)
            ->orderBy('dealer_customer_id', 'desc')
            ->first();

        return $lastCustomer ? $lastCustomer->dealer_customer_id + 1 : 1;
    }

    /**
     * Get total amount paid by customer
     */
    public function getTotalPaidAmount(): float
    {
        return $this->installments()
            ->where('status', 'paid')
            ->sum('paid_amount');
    }

    /**
     * Get total amount due (remaining to be paid)
     */
    public function getTotalDueAmount(): float
    {
        $totalPayable = $this->getTotalPayableAmount();
        $totalPaid = $this->getTotalPaidAmount();

        return max(0, $totalPayable - $totalPaid);
    }

    /**
     * Get last payment made by customer
     */
    public function getLastPayment(): ?Installment
    {
        return $this->installments()
            ->where('status', 'paid')
            ->latest('paid_date')
            ->first();
    }

    /**
     * Get next due installment
     */
    public function getNextDueInstallment(): ?Installment
    {
        return $this->installments()
            ->where('status', 'pending')
            ->orderBy('due_date', 'asc')
            ->first();
    }

    /**
     * Get all overdue installments
     */
    public function getOverdueInstallments()
    {
        return $this->installments()
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Check if customer has any overdue payments
     */
    public function hasOverduePayments(): bool
    {
        return $this->installments()
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->exists();
    }

    /**
     * Get total overdue amount
     */
    public function getTotalOverdueAmount(): float
    {
        return $this->installments()
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->sum('amount');
    }

    /**
     * Get payment completion percentage
     */
    public function getPaymentCompletionPercentage(): float
    {
        $totalPayable = $this->getTotalPayableAmount();
        
        if ($totalPayable <= 0) {
            return 0;
        }

        $totalPaid = $this->getTotalPaidAmount();

        return min(100, ($totalPaid / $totalPayable) * 100);
    }

    /**
     * Check if device should be locked based on payment status
     */
    public function shouldBeLocked(): bool
    {
        // If customer is not active, device should be locked
        if (!$this->isActive()) {
            return true;
        }

        // If device is already marked as locked, keep it locked
        if ($this->is_device_locked) {
            return true;
        }

        // If customer has overdue payments, should be locked
        if ($this->hasOverduePayments()) {
            return true;
        }

        return false;
    }
}
