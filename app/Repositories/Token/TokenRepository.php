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

    public function getCreatedTokensByUser(User $user): Collection
    {
        return Token::where('created_by', $user->id)
            ->with(['assignedTo', 'usedBy'])
            ->latest()
            ->get();
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

    public function markTokenAsUsed(Token $token): bool
    {
        return $token->update([
            'status' => 'used',
            'used_at' => now(),
        ]);
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
        $createdQuery = Token::where('created_by', $user->id);
        $accessibleQuery = $this->getTokensQueryForUser($user);

        return [
            'created_total' => (clone $createdQuery)->count(),
            'created_available' => (clone $createdQuery)->where('status', 'available')->count(),
            'created_assigned' => (clone $createdQuery)->where('status', 'assigned')->count(),
            'created_used' => (clone $createdQuery)->where('status', 'used')->count(),
            'accessible_total' => (clone $accessibleQuery)->count(),
            'accessible_available' => (clone $accessibleQuery)->where('status', 'available')->count(),
            'assigned_to_me' => Token::where('assigned_to', $user->id)->count(),
            'used_by_me' => Token::where('used_by', $user->id)->count(),
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
            return $this->roleHierarchyService->canAssignRole($user->role, $token->creator->role);
        }

        return false;
    }

    public function getTokensWithAssignmentChain(User $user): Collection
    {
        return $this->getTokensQueryForUser($user)
            ->with([
                'creator:id,name,email,role',
                'assignedTo:id,name,email,role',
                'usedBy:id,name,email,role'
            ])
            ->get();
    }

    public function bulkCreateTokens(array $tokensData): Collection
    {
        $tokens = new Collection();

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

        // If user has no role, return empty query
        if (!$user->role) {
            return $query->whereRaw('1 = 0'); // Returns no results
        }

        // Super admin can see all tokens
        if ($user->role === 'super_admin') {
            return $query;
        }

        // Get assignable roles for the user
        $assignableRoles = $this->roleHierarchyService->getAssignableRolesByRole($user->role);

        // User can see tokens created by users in their hierarchy or assigned to them
        return $query->where(function (Builder $q) use ($user, $assignableRoles) {
            $q->where('created_by', $user->id)
              ->orWhere('assigned_to', $user->id)
              ->orWhereHas('creator', function (Builder $creatorQuery) use ($assignableRoles) {
                  $creatorQuery->whereIn('role', $assignableRoles);
              });
        });
    }
}