<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Upazilla extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
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
    public function division()
    {
        return $this->hasOneThrough(Division::class, District::class, 'id', 'id', 'district_id', 'division_id');
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
