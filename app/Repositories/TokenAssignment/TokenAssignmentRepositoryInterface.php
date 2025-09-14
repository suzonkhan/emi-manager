<?php

namespace App\Repositories\TokenAssignment;

use App\Models\Token;
use App\Models\TokenAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface TokenAssignmentRepositoryInterface
{
    /**
     * Create a new token assignment record
     */
    public function create(array $data): TokenAssignment;

    /**
     * Get assignment history for a token
     */
    public function getTokenHistory(Token $token): Collection;

    /**
     * Get assignment history for a user
     */
    public function getUserAssignmentHistory(User $user): Collection;

    /**
     * Record token generation
     */
    public function recordGeneration(Token $token, User $generator, array $metadata = []): TokenAssignment;

    /**
     * Record token assignment
     */
    public function recordAssignment(Token $token, User $fromUser, User $toUser, array $metadata = []): TokenAssignment;

    /**
     * Record token usage
     */
    public function recordUsage(Token $token, User $user, array $metadata = []): TokenAssignment;

    /**
     * Get assignment chain for a token
     */
    public function getAssignmentChain(Token $token): Collection;

    /**
     * Get latest assignment for a token
     */
    public function getLatestAssignment(Token $token): ?TokenAssignment;

    /**
     * Get assignments by action type
     */
    public function getAssignmentsByAction(string $action): Collection;

    /**
     * Get assignments between date range
     */
    public function getAssignmentsByDateRange(\DateTime $startDate, \DateTime $endDate): Collection;
}
