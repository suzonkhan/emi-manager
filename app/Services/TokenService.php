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
        if (! $this->roleHierarchyService->canAssignRole($fromUser->role, $toUser->role)) {
            throw new Exception('You cannot assign tokens to this user role');
        }

        return DB::transaction(function () use ($fromUser, $toUser, $tokenCode) {
            // Find available token assigned to fromUser
            $token = $this->tokenRepository->findByCode($tokenCode);

            if (! $token || $token->assigned_to !== $fromUser->id || $token->status !== 'assigned') {
                throw new Exception('Token not found or not available for assignment');
            }

            // Transfer token
            $this->tokenRepository->updateToken($token, [
                'assigned_to' => $toUser->id,
                'assigned_at' => now(),
            ]);

            // Record assignment history
            $this->tokenAssignmentRepository->recordAssignment($token, $fromUser, $toUser);

            return $token->fresh();
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
     * Get tokens created by user
     */
    public function getCreatedTokens(User $user): Collection
    {
        return $this->tokenRepository->getCreatedTokensByUser($user);
    }

    /**
     * Use token for customer creation (salesman function)
     */
    public function useTokenForCustomer(User $user, string $tokenCode): Token
    {
        if (! $user->hasRole('salesman')) {
            throw new Exception('Only salesman can use tokens for customers');
        }

        $token = $this->tokenRepository->findByCode($tokenCode);

        if (! $token || $token->assigned_to !== $user->id || $token->status !== 'assigned') {
            throw new Exception('Token not found or not available for use');
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
