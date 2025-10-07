<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Token;
use App\Models\User;
use App\Repositories\Token\TokenRepositoryInterface;
use App\Repositories\TokenAssignment\TokenAssignmentRepositoryInterface;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TokenService
{
    public function __construct(
        private RoleHierarchyService $roleHierarchyService,
        private TokenRepositoryInterface $tokenRepository,
        private TokenAssignmentRepositoryInterface $tokenAssignmentRepository
    ) {}

    /**
     * Generate tokens for super admin (quantity: 12 characters each)
     */
    public function generateTokens(User $user, int $quantity): Collection
    {
        // Validate user object completeness
        if (! $user || ! $user->name || ! $user->email) {
            throw new Exception('User authentication error: incomplete user data. Please log out and log back in.');
        }

        if (! $user->hasRole('super_admin')) {
            throw new Exception('Only super admin can generate tokens');
        }

        return DB::transaction(function () use ($user, $quantity) {
            $tokens = collect();
            $tokensData = [];

            for ($i = 0; $i < $quantity; $i++) {
                $tokensData[] = [
                    'created_by' => $user->id,
                    'status' => 'available',
                    'metadata' => [
                        'generated_at' => now()->toISOString(),
                        'batch_size' => $quantity,
                    ],
                ];
            }

            $tokens = $this->tokenRepository->bulkCreateTokens($tokensData);

            // Record generation history for each token
            foreach ($tokens as $token) {
                $this->tokenAssignmentRepository->recordGeneration($token, $user, [
                    'batch_size' => $quantity,
                    'total_generated' => $quantity,
                ]);
            }

            return $tokens;
        });
    }

    /**
     * Assign token from current user to target user (following hierarchy)
     */
    public function assignToken(User $fromUser, User $toUser, string $tokenCode): Token
    {
        // Check if fromUser can assign to toUser based on hierarchy
        $toUserRole = $toUser->getRoleNames()->first();
        if (! $this->roleHierarchyService->canAssignRole($fromUser, $toUserRole)) {
            throw new Exception('You cannot assign tokens to this user role');
        }

        return DB::transaction(function () use ($fromUser, $toUser, $tokenCode) {
            // Find available token
            $token = $this->tokenRepository->findByCode($tokenCode);

            if (! $token) {
                throw new Exception('Token not found');
            }

            // Check if token is available or assigned to fromUser
            if ($token->status === 'used') {
                throw new Exception('Token has already been used');
            }

            // For available tokens, anyone with permission can assign
            // For assigned tokens, only the current holder can reassign
            if ($token->status === 'assigned' && $token->assigned_to !== $fromUser->id) {
                throw new Exception('You do not have permission to assign this token');
            }

            // Transfer token
            $this->tokenRepository->updateToken($token, [
                'assigned_to' => $toUser->id,
                'assigned_at' => now(),
                'status' => 'assigned',
            ]);

            // Record assignment history
            $this->tokenAssignmentRepository->recordAssignment($token, $fromUser, $toUser);

            return $token->fresh();
        });
    }

    /**
     * Assign multiple available tokens to a user (bulk assignment)
     */
    public function assignTokens(User $fromUser, User $toUser, int $quantity): Collection
    {
        // Check if fromUser can assign to toUser based on hierarchy
        $toUserRole = $toUser->getRoleNames()->first();
        if (! $this->roleHierarchyService->canAssignRole($fromUser, $toUserRole)) {
            throw new Exception('You cannot assign tokens to this user role');
        }

        return DB::transaction(function () use ($fromUser, $toUser, $quantity) {
            // Get available tokens for the current user
            $availableTokens = Token::where(function ($query) use ($fromUser) {
                $query->where('status', 'available')
                    ->where(function ($q) use ($fromUser) {
                        // Super admin can assign any available token
                        if ($fromUser->hasRole('super_admin')) {
                            $q->whereNull('assigned_to')
                                ->orWhere('assigned_to', $fromUser->id);
                        } else {
                            // Others can only assign tokens assigned to them
                            $q->where('assigned_to', $fromUser->id);
                        }
                    });
            })
                ->orWhere(function ($query) use ($fromUser) {
                    $query->where('status', 'assigned')
                        ->where('assigned_to', $fromUser->id);
                })
                ->limit($quantity)
                ->get();

            if ($availableTokens->count() < $quantity) {
                throw new Exception("Not enough available tokens. You have {$availableTokens->count()} available, but requested {$quantity}");
            }

            $assignedTokens = collect();

            foreach ($availableTokens as $token) {
                // Transfer token
                $this->tokenRepository->updateToken($token, [
                    'assigned_to' => $toUser->id,
                    'assigned_at' => now(),
                    'status' => 'assigned',
                ]);

                // Record assignment history
                $this->tokenAssignmentRepository->recordAssignment($token, $fromUser, $toUser);

                $assignedTokens->push($token->fresh());
            }

            return $assignedTokens;
        });
    }

    /**
     * Distribute initial tokens to dealers (super admin function)
     */
    public function distributeTokensToDealers(User $superAdmin, array $dealerTokens): array
    {
        if (! $superAdmin->hasRole('super_admin')) {
            throw new Exception('Only super admin can distribute tokens');
        }

        $results = [];

        DB::transaction(function () use ($superAdmin, $dealerTokens, &$results) {
            foreach ($dealerTokens as $dealerId => $tokenCodes) {
                $dealer = User::findOrFail($dealerId);

                if (! $dealer->hasRole('dealer')) {
                    throw new Exception("User {$dealer->name} is not a dealer");
                }

                foreach ($tokenCodes as $tokenCode) {
                    $token = $this->tokenRepository->findByCode($tokenCode);

                    if (! $token || $token->created_by !== $superAdmin->id || $token->status !== 'available') {
                        throw new Exception("Token {$tokenCode} not found or not available");
                    }

                    // Assign token to dealer
                    $this->tokenRepository->assignTokenToUser($token, $dealer);

                    // Record assignment history
                    $this->tokenAssignmentRepository->recordAssignment($token, $superAdmin, $dealer, [
                        'distribution_batch' => true,
                    ]);

                    $results[$dealerId][] = $token;
                }
            }
        });

        return $results;
    }

    /**
     * Get available tokens for a user
     */
    public function getAvailableTokens(User $user): Collection
    {
        return $this->tokenRepository->getAvailableTokensForUser($user);
    }

    /**
     * Get available tokens for a user with pagination
     */
    public function getAvailableTokensPaginated(User $user, int $perPage = 15, string $search = ''): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->tokenRepository->getAvailableTokensForUserPaginated($user, $perPage, $search);
    }

    /**
     * Get tokens created by user
     */
    public function getCreatedTokens(User $user): Collection
    {
        return $this->tokenRepository->getCreatedTokensByUser($user);
    }

    /**
     * Get tokens created by user with pagination
     */
    public function getCreatedTokensPaginated(User $user, int $perPage = 15, string $search = ''): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->tokenRepository->getCreatedTokensByUserPaginated($user, $perPage, $search);
    }

    /**
     * Use token for customer creation
     */
    public function useTokenForCustomer(User $user, string $tokenCode): Token
    {
        // Allow super_admin, dealer, sub_dealer, and salesman to use tokens
        $allowedRoles = ['super_admin', 'dealer', 'sub_dealer', 'salesman'];
        if (! $user->hasAnyRole($allowedRoles)) {
            throw new Exception('You do not have permission to use tokens for customers');
        }

        $token = $this->tokenRepository->findByCode($tokenCode);

        if (! $token) {
            throw new Exception('Token not found');
        }

        // Super Admin can use available tokens or tokens assigned to them
        if ($user->hasRole('super_admin')) {
            $canUse = ($token->status === 'available' && $token->assigned_to === null) ||
                      ($token->status === 'assigned' && $token->assigned_to === $user->id);

            if (! $canUse || $token->used_by !== null) {
                throw new Exception('Token not available for use');
            }
        } else {
            // Other users can only use tokens assigned to them
            if ($token->assigned_to !== $user->id || $token->status !== 'assigned' || $token->used_by !== null) {
                throw new Exception('Token not found or not available for use');
            }
        }

        // Don't mark as used here - will be done when customer is created
        return $token;
    }

    /**
     * Complete token usage for customer creation
     */
    public function completeTokenUsage(Token $token, Customer $customer, User $user): void
    {
        DB::transaction(function () use ($token, $customer, $user) {
            // Update token to used status
            $this->tokenRepository->markTokenAsUsed($token, $customer);

            // Record token usage in assignment history
            $this->tokenAssignmentRepository->recordUsage($token, $user, [
                'customer_id' => $customer->id,
                'customer_name' => $customer->full_name,
                'customer_phone' => $customer->phone,
                'financed_amount' => $customer->financed_amount,
            ]);
        });
    }

    /**
     * Get token statistics for user
     */
    public function getTokenStatistics(User $user): array
    {
        return $this->tokenRepository->getTokenStatistics($user);
    }

    /**
     * Validate token hierarchy assignment
     */
    public function canAssignToken(User $fromUser, User $toUser): bool
    {
        return $this->roleHierarchyService->canAssignRole($fromUser->role, $toUser->role);
    }

    /**
     * Get token assignment chain
     */
    public function getTokenChain(Token $token): array
    {
        return $token->metadata['assignment_chain'] ?? [];
    }

    /**
     * Mark token as used
     */
    public function markTokenAsUsed(Token $token): bool
    {
        return $this->tokenRepository->markTokenAsUsed($token);
    }

    /**
     * Check if user can access token
     */
    public function canUserAccessToken(User $user, Token $token): bool
    {
        return $this->tokenRepository->canUserAccessToken($user, $token);
    }
}
