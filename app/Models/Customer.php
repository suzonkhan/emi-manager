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
}
