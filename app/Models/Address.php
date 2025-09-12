<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'street_address',
        'landmark',
        'postal_code',
        'division_id',
        'district_id',
        'upazilla_id',
    ];

    /**
     * Division relationship
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * District relationship
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Upazilla relationship
     */
    public function upazilla(): BelongsTo
    {
        return $this->belongsTo(Upazilla::class);
    }

    /**
     * Get full address string
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street_address,
            $this->landmark,
            $this->upazilla?->name,
            $this->district?->name,
            $this->division?->name,
            $this->postal_code,
        ]);

        return implode(', ', $parts);
    }
}
