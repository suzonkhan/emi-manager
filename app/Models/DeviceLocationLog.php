<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceLocationLog extends Model
{
    protected $fillable = [
        'customer_id',
        'latitude',
        'longitude',
        'accuracy',
        'altitude',
        'speed',
        'provider',
        'address',
        'metadata',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'accuracy' => 'decimal:2',
            'altitude' => 'decimal:2',
            'speed' => 'decimal:2',
            'metadata' => 'array',
            'recorded_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get Google Maps URL for this location
     */
    public function getGoogleMapsUrlAttribute(): string
    {
        return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
    }

    /**
     * Get formatted location string
     */
    public function getFormattedLocationAttribute(): string
    {
        return "{$this->latitude}, {$this->longitude}";
    }
}
