<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
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
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function ($user) {
            if (empty($user->unique_id)) {
                $user->unique_id = self::generateUniqueId();
            }
        });
    }

    /**
     * Generate unique ID for user
     */
    public static function generateUniqueId(): string
    {
        do {
            $uniqueId = 'EMI-' . strtoupper(Str::random(8));
        } while (self::where('unique_id', $uniqueId)->exists());
        
        return $uniqueId;
    }

    /**
     * Parent user (supervisor)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * Child users (subordinates)
     */
    public function children(): HasMany
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    /**
     * Get all descendants recursively
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Present address
     */
    public function presentAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'present_address_id');
    }

    /**
     * Permanent address
     */
    public function permanentAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'permanent_address_id');
    }

    /**
     * Check if user can create other users
     */
    public function canCreateUser(string $role): bool
    {
        $userRole = $this->getRoleNames()->first();
        
        $hierarchy = [
            'super_admin' => ['dealer', 'sub_dealer', 'salesman', 'customer'],
            'dealer' => ['sub_dealer', 'salesman', 'customer'],
            'sub_dealer' => ['salesman', 'customer'],
            'salesman' => ['customer'],
            'customer' => [],
        ];

        return in_array($role, $hierarchy[$userRole] ?? []);
    }

    /**
     * Check if user can change password
     */
    public function canChangeOwnPassword(): bool
    {
        return $this->can_change_password || $this->hasRole('super_admin');
    }

    /**
     * Get user's hierarchy level
     */
    public function getHierarchyLevel(): int
    {
        $levels = [
            'super_admin' => 1,
            'dealer' => 2,
            'sub_dealer' => 3,
            'salesman' => 4,
            'customer' => 5,
        ];

        $role = $this->getRoleNames()->first();
        return $levels[$role] ?? 5;
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for users by role
     */
    public function scopeByRole($query, string $role)
    {
        return $query->role($role);
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }
}
