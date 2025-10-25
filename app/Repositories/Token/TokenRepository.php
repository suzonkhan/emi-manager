<?php

namespace App\Repositories\Token;

use App\Models\Token;
use App\Models\User;
use App\Services\RoleHierarchyService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TokenRepository implements TokenRepositoryInterface
{
    public function __construct(
        protected RoleHierarchyService $roleHierarchyService
    ) {}

    public function createToken(array $tokenData): Token
    {
        return Token::create($tokenData);
    }

    public function findByCode(string $code): ?Token
    {
        return Token::where('code', $code)->first();
    }

    public function getAvailableTokensForUser(User $user): Collection
    {
        return $this->getTokensQueryForUser($user)
            ->where('status', 'available')
            ->with(['creator'])
            ->get();
    }

    public function getAvailableTokensForUserPaginated(User $user, int $perPage = 15, string $search = ''): LengthAwarePaginator
    {
        $query = $this->getTokensQueryForUser($user)
            ->where(function ($q) use ($user) {
                // Include tokens that are available and unassigned
                $q->where(function ($subQuery) {
                    $subQuery->where('status', 'available')
                        ->whereNull('assigned_to');
                })
                // OR tokens that are assigned to this user
                ->orWhere(function ($subQuery) use ($user) {
                    $subQuery->where('status', 'assigned')
                        ->where('assigned_to', $user->id);
                });
            })
            ->whereNull('used_by')
            ->with(['creator', 'assignedTo']);

        if (! empty($search)) {
            $query->where('code', 'like', "%{$search}%");
        }

        return $query->paginate($perPage);
    }

    public function getCreatedTokensByUser(User $user): Collection
    {
        return Token::where('created_by', $user->id)
            ->with(['assignedTo', 'usedBy'])
            ->latest()
            ->get();
    }

    public function getCreatedTokensByUserPaginated(User $user, int $perPage = 15, string $search = ''): LengthAwarePaginator
    {
        $query = Token::where('created_by', $user->id)
            ->with(['assignedTo', 'usedBy'])
            ->latest();

        if (! empty($search)) {
            $query->where('code', 'like', "%{$search}%");
        }

        return $query->paginate($perPage);
    }

    /**
     * Get complete token history for user (all tokens they created, assigned to, or used)
     */
    public function getTokenHistoryForUserPaginated(User $user, int $perPage = 15, string $search = ''): LengthAwarePaginator
    {
        // Get user's children IDs (users they assigned tokens to)
        $childrenIds = $user->children()->pluck('id')->toArray();
        
        $query = Token::where(function ($q) use ($user, $childrenIds) {
            // Tokens created by the user
            $q->where('created_by', $user->id)
                // OR tokens assigned TO the user
                ->orWhere('assigned_to', $user->id)
                // OR tokens used BY the user
                ->orWhere('used_by', $user->id);
            
            // OR tokens assigned to user's children (tokens they distributed to their team)
            if (! empty($childrenIds)) {
                $q->orWhereIn('assigned_to', $childrenIds);
            }
        })
            ->with(['creator', 'assignedTo', 'usedBy', 'customer'])
            ->latest();

        if (! empty($search)) {
            $query->where('code', 'like', "%{$search}%");
        }

        return $query->paginate($perPage);
    }

    public function getAssignedTokensByUser(User $user): Collection
    {
        return Token::where('assigned_to', $user->id)
            ->with(['creator', 'usedBy'])
            ->latest()
            ->get();
    }

    public function getUsedTokensByUser(User $user): Collection
    {
        return Token::where('used_by', $user->id)
            ->with(['creator', 'assignedTo'])
            ->latest()
            ->get();
    }

    public function assignTokenToUser(Token $token, User $user): bool
    {
        return $token->update([
            'assigned_to' => $user->id,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);
    }

    public function markTokenAsUsed(Token $token, ?User $user = null): bool
    {
        $data = [
            'status' => 'used',
            'used_at' => now(),
        ];

        if ($user) {
            $data['used_by'] = $user->id;
        }

        return $token->update($data);
    }

    public function getTokensByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return Token::where('status', $status)
            ->with(['creator', 'assignedTo', 'usedBy'])
            ->latest()
            ->paginate($perPage);
    }

    public function getTokenStatistics(User $user): array
    {
        // For Super Admin, show tokens they CREATED (generated)
        if ($user->hasRole('super_admin')) {
            $createdByUser = Token::where('created_by', $user->id);
            
            $totalTokens = (clone $createdByUser)->count();
            
            // Available: Unassigned tokens + tokens assigned to super admin
            $availableTokens = (clone $createdByUser)
                ->where(function ($query) use ($user) {
                    $query->where('status', 'available')
                        ->whereNull('assigned_to')
                        ->orWhere(function ($q) use ($user) {
                            $q->where('assigned_to', $user->id)
                                ->where('status', 'assigned');
                        });
                })
                ->whereNull('used_by')
                ->count();
            
            // Distributed: All tokens assigned to anyone (distributed to the system)
            $assignedTokens = (clone $createdByUser)
                ->where('status', 'assigned')
                ->whereNotNull('assigned_to')
                ->count();
            
            // Used: All tokens that have been used
            $usedTokens = (clone $createdByUser)
                ->where('status', 'used')
                ->count();
        } else {
            // For Dealers, Sub-Dealers, Salesmen - use hierarchy-based logic
            $accessibleQuery = $this->getTokensQueryForUser($user);

            // Get tokens the user can actually use (available or assigned to them)
            $availableTokens = (clone $accessibleQuery)
                ->where(function ($query) use ($user) {
                    $query->where('status', 'available')
                        ->whereNull('assigned_to')
                        ->orWhere(function ($q) use ($user) {
                            $q->where('assigned_to', $user->id)
                                ->where('status', 'assigned');
                        });
                })
                ->whereNull('used_by')
                ->count();

            // Get all tokens user has access to (total)
            $totalTokens = (clone $accessibleQuery)->count();

            // Get tokens that the user has assigned TO OTHERS (their children/sub-dealers)
            // This shows how many tokens they've distributed to their team
            $childrenIds = $user->children()->pluck('id')->toArray();
            $assignedTokens = Token::whereIn('assigned_to', $childrenIds)
                ->where('status', 'assigned')
                ->whereNull('used_by')
                ->count();

            // Get tokens used by user
            $usedTokens = Token::where('used_by', $user->id)
                ->where('status', 'used')
                ->count();
        }

        return [
            'total_tokens' => $totalTokens,
            'available_tokens' => $availableTokens,
            'assigned_tokens' => $assignedTokens,
            'used_tokens' => $usedTokens,
            
            // Keep old keys for backward compatibility
            'created_total' => Token::where('created_by', $user->id)->count(),
            'created_available' => Token::where('created_by', $user->id)->where('status', 'available')->count(),
            'created_assigned' => Token::where('created_by', $user->id)->where('status', 'assigned')->count(),
            'created_used' => Token::where('created_by', $user->id)->where('status', 'used')->count(),
            'accessible_total' => $totalTokens,
            'accessible_available' => $availableTokens,
            'assigned_to_children' => $assignedTokens,
            'used_by_me' => $usedTokens,
        ];
    }

    public function updateToken(Token $token, array $data): bool
    {
        return $token->update($data);
    }

    public function canUserAccessToken(User $user, Token $token): bool
    {
        // User can access tokens they created
        if ($token->created_by === $user->id) {
            return true;
        }

        // User can access tokens assigned to them
        if ($token->assigned_to === $user->id) {
            return true;
        }

        // User can access tokens if they are in the hierarchy below the creator
        if ($token->creator) {
            $creatorRole = $token->creator->getRoleNames()->first();
            if ($creatorRole) {
                return $this->roleHierarchyService->canAssignRole($user, $creatorRole);
            }
        }

        return false;
    }

    public function getTokensWithAssignmentChain(User $user): Collection
    {
        return $this->getTokensQueryForUser($user)
            ->with([
                'creator:id,name,email',
                'assignedTo:id,name,email',
                'usedBy:id,name,email',
            ])
            ->get();
    }

    public function bulkCreateTokens(array $tokensData): Collection
    {
        $tokens = new Collection;

        DB::transaction(function () use ($tokensData, &$tokens) {
            foreach ($tokensData as $tokenData) {
                $tokens->push(Token::create($tokenData));
            }
        });

        return $tokens;
    }

    protected function getTokensQueryForUser(User $user): Builder
    {
        $query = Token::query();

        // Get user's role
        $userRole = $user->getRoleNames()->first();

        // If user has no role, return empty query
        if (! $userRole) {
            return $query->whereRaw('1 = 0'); // Returns no results
        }

        // Super admin can see all tokens
        if ($userRole === 'super_admin') {
            return $query;
        }

        // Get assignable roles for the user
        $assignableRoles = $this->roleHierarchyService->getAssignableRolesByRole($userRole);

        // Get user's children IDs for tokens assigned to them
        $childrenIds = $user->children()->pluck('id')->toArray();

        // User can see tokens:
        // 1. Created by them
        // 2. Assigned to them
        // 3. Assigned to their children (sub-dealers, salesmen)
        // 4. Created by users in their hierarchy
        return $query->where(function (Builder $q) use ($user, $assignableRoles, $childrenIds) {
            $q->where('created_by', $user->id)
                ->orWhere('assigned_to', $user->id)
                ->when(! empty($childrenIds), function (Builder $query) use ($childrenIds) {
                    $query->orWhereIn('assigned_to', $childrenIds);
                })
                ->orWhereHas('creator', function (Builder $creatorQuery) use ($assignableRoles) {
                    $creatorQuery->role($assignableRoles);
                });
        });
    }
}
