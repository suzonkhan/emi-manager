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
        'error_message',
        'sent_at',
        'sent_by',
    ];

    protected function casts(): array
    {
        return [
            'command_data' => 'array',
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
}
