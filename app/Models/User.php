<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'unique_id',
        'name',
        'email',
        'phone',
        'password',
        'parent_id',
        'present_address_id',
        'permanent_address_id',
        'bkash_merchant_number',
        'nagad_merchant_number',
        'can_change_password',
        'is_active',
        'metadata',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'can_change_password' => 'boolean',
            'last_login_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the user's role name
     */
    public function getRoleAttribute(): ?string
    {
        $role = $this->getRoleNames()->first();

        return $role ?: null;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->unique_id)) {
                $user->unique_id = self::generateUniqueId();
            }
        });
    }

    public static function generateUniqueId(): string
    {
        do {
            $uniqueId = 'EMI-'.strtoupper(Str::random(8));
        } while (self::where('unique_id', $uniqueId)->exists());

        return $uniqueId;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    public function presentAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'present_address_id');
    }

    public function permanentAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'permanent_address_id');
    }

    public function createdTokens(): HasMany
    {
        return $this->hasMany(Token::class, 'created_by');
    }

    public function assignedTokens(): HasMany
    {
        return $this->hasMany(Token::class, 'assigned_to');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'created_by');
    }

    public function canCreateUser(string $role): bool
    {
        $userRole = $this->getRoleNames()->first();

        $hierarchy = [
            'super_admin' => ['dealer', 'sub_dealer', 'salesman'],
            'dealer' => ['sub_dealer', 'salesman'],
            'sub_dealer' => ['salesman'],
            'salesman' => [],
        ];

        return in_array($role, $hierarchy[$userRole] ?? []);
    }

    public function canChangeOwnPassword(): bool
    {
        return $this->can_change_password || $this->hasRole('super_admin');
    }

    public function getHierarchyLevel(): int
    {
        $levels = [
            'super_admin' => 1,
            'dealer' => 2,
            'sub_dealer' => 3,
            'salesman' => 4,
        ];

        $role = $this->getRoleNames()->first();

        return $levels[$role] ?? 4;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->role($role);
    }

    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }
}
