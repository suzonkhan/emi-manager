<?php

namespace App\Repositories\Token;

use App\Models\Token;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TokenRepositoryInterface
{
    public function createToken(array $tokenData): Token;

    public function findByCode(string $code): ?Token;

    public function getAvailableTokensForUser(User $user): Collection;

    public function getCreatedTokensByUser(User $user): Collection;

    public function getAssignedTokensByUser(User $user): Collection;

    public function getUsedTokensByUser(User $user): Collection;

    public function assignTokenToUser(Token $token, User $user): bool;

    public function markTokenAsUsed(Token $token): bool;

    public function getTokensByStatus(string $status, int $perPage = 15): LengthAwarePaginator;

    public function getTokenStatistics(User $user): array;

    public function updateToken(Token $token, array $data): bool;

    public function canUserAccessToken(User $user, Token $token): bool;

    public function getTokensWithAssignmentChain(User $user): Collection;

    public function bulkCreateTokens(array $tokensData): Collection;
}
