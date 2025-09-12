<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Upazilla extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'bn_name',
        'code',
        'district_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * District this upazilla belongs to
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Division through district
     */
    public function division(): BelongsTo
    {
        return $this->district()->division();
    }

    /**
     * Addresses in this upazilla
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Scope for active upazillas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
