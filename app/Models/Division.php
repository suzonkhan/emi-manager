<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Division extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'bn_name',
        'code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Districts in this division
     */
    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    /**
     * Addresses in this division
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Scope for active divisions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
