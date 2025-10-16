<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceCommandLog extends Model
{
    protected $fillable = [
        'customer_id',
        'command',
        'command_data',
        'status',
        'fcm_response',
        'metadata',
        'error_message',
        'sent_at',
        'sent_by',
    ];

    protected function casts(): array
    {
        return [
            'command_data' => 'array',
            'metadata' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Get location data from metadata if available
     */
    public function getLocationData(): ?array
    {
        if ($this->command === 'REQUEST_LOCATION' && $this->metadata) {
            return $this->metadata;
        }

        return null;
    }

    /**
     * Check if this command has received a location response
     */
    public function hasLocationResponse(): bool
    {
        return $this->command === 'REQUEST_LOCATION'
            && $this->status === 'delivered'
            && ! empty($this->metadata);
    }
}
