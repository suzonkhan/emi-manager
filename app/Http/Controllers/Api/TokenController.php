<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Token\AssignTokenRequest;
use App\Http\Requests\Token\GenerateTokenRequest;
use App\Http\Resources\TokenResource;
use App\Models\User;
use App\Services\RoleHierarchyService;
use App\Services\TokenService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private TokenService $tokenService,
        private RoleHierarchyService $roleHierarchyService
    ) {}

    /**
     * Generate tokens (Super Admin only)
     */
    public function generate(GenerateTokenRequest $request): JsonResponse
    {
        try {
            $tokens = $this->tokenService->generateTokens(
                $request->user(),
                $request->validated('quantity')
            );

            return $this->success([
                'tokens' => TokenResource::collection($tokens),
                'message' => "Generated {$tokens->count()} tokens successfully",
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), null, 400);
        }
    }

    /**
     * Get user's tokens
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->integer('per_page', 15);
            $search = $request->string('search', '');

            $availableTokens = $this->tokenService->getAvailableTokensPaginated($user, $perPage, $search);
            $createdTokens = collect();

            if ($user->hasRole('super_admin')) {
                $createdTokens = $this->tokenService->getCreatedTokensPaginated($user, $perPage, $search);
            }

            return $this->success([
                'available_tokens' => TokenResource::collection($availableTokens->items()),
                'created_tokens' => TokenResource::collection($createdTokens instanceof \Illuminate\Pagination\LengthAwarePaginator ? $createdTokens->items() : $createdTokens),
                'pagination' => [
                    'current_page' => $availableTokens->currentPage(),
                    'last_page' => $availableTokens->lastPage(),
                    'per_page' => $availableTokens->perPage(),
                    'total' => $availableTokens->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Assign token to user
     */
    public function assign(AssignTokenRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $toUser = User::findOrFail($validated['assignee_id']);

            // Prevent assigning tokens to salesmen
            if ($toUser->hasRole('salesman')) {
                return $this->error('Salesmen cannot receive token assignments. They automatically use tokens from their parent (dealer or sub-dealer).', null, 400);
            }

            $token = $this->tokenService->assignToken(
                $request->user(),
                $toUser,
                $validated['token_code']
            );

            return $this->success([
                'token' => new TokenResource($token->load(['creator', 'assignedTo'])),
                'message' => "Token assigned to {$toUser->name} successfully",
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), null, 400);
        }
    }

    /**
     * Assign multiple tokens to user (bulk assignment)
     */
    public function assignBulk(Request $request): JsonResponse
    {
        $request->validate([
            'assignee_id' => 'required|integer|exists:users,id',
            'quantity' => 'required|integer|min:1|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $toUser = User::findOrFail($request->assignee_id);

            // Prevent assigning tokens to salesmen
            if ($toUser->hasRole('salesman')) {
                return $this->error('Salesmen cannot receive token assignments. They automatically use tokens from their parent (dealer or sub-dealer).', null, 400);
            }

            // Check role hierarchy
            $roleHierarchyService = app(RoleHierarchyService::class);
            $assigneeRole = $toUser->getRoleNames()->first();

            if (! $roleHierarchyService->canAssignRole($request->user(), $assigneeRole)) {
                return $this->error('You cannot assign tokens to this user role', null, 403);
            }

            $tokens = $this->tokenService->assignTokens(
                $request->user(),
                $toUser,
                $request->quantity
            );

            return $this->success([
                'tokens' => TokenResource::collection($tokens->load(['creator', 'assignedTo'])),
                'message' => "{$tokens->count()} tokens assigned to {$toUser->name} successfully",
                'assigned_count' => $tokens->count(),
                'token_codes' => $tokens->pluck('code')->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), null, 400);
        }
    }

    /**
     * Distribute tokens to dealers (Super Admin only)
     */
    public function distribute(Request $request): JsonResponse
    {
        $request->validate([
            'distributions' => 'required|array',
            'distributions.*.dealer_id' => 'required|exists:users,id',
            'distributions.*.token_codes' => 'required|array',
            'distributions.*.token_codes.*' => 'string|size:12',
        ]);

        try {
            $dealerTokens = [];
            foreach ($request->input('distributions') as $distribution) {
                $dealerTokens[$distribution['dealer_id']] = $distribution['token_codes'];
            }

            $results = $this->tokenService->distributeTokensToDealers(
                $request->user(),
                $dealerTokens
            );

            return $this->success([
                'distributions' => collect($results)->map(function ($tokens, $dealerId) {
                    $dealer = User::find($dealerId);

                    return [
                        'dealer' => [
                            'id' => $dealer->id,
                            'name' => $dealer->name,
                            'email' => $dealer->email,
                        ],
                        'tokens_assigned' => count($tokens),
                        'tokens' => TokenResource::collection($tokens),
                    ];
                }),
                'message' => 'Tokens distributed successfully',
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), null, 400);
        }
    }

    /**
     * Get token statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $stats = $this->tokenService->getTokenStatistics($request->user());

            return $this->success([
                'statistics' => $stats,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get assignable users for tokens
     */
    public function assignableUsers(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $assignableRoles = $this->roleHierarchyService->getAssignableRoles($user);

            // Remove 'salesman' from assignable roles if present
            // Salesmen use their parent's tokens and don't receive assignments
            $assignableRoles = array_filter($assignableRoles, fn ($role) => $role !== 'salesman');

            $assignableUsers = User::role($assignableRoles)
                ->select(['id', 'name', 'email'])
                ->get();

            return $this->success([
                'users' => $assignableUsers->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->getRoleNames()->first(),
                ]),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Show specific token details
     */
    public function show(Request $request, string $tokenCode): JsonResponse
    {
        try {
            $token = \App\Models\Token::where('code', $tokenCode)
                ->with(['creator', 'assignedTo', 'usedBy'])
                ->first();

            if (! $token) {
                return $this->error('Token not found', null, 404);
            }

            // Check if user can access this token
            if (! $this->tokenService->canUserAccessToken($request->user(), $token)) {
                return $this->error('Access denied', null, 403);
            }

            return $this->success([
                'token' => new TokenResource($token),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get available tokens for customer creation (assigned and unused)
     */
    public function availableForCustomer(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // For Super Admin: Get all available tokens (not assigned to anyone) or assigned to them
            if ($user->hasRole('super_admin')) {
                $availableTokens = \App\Models\Token::where(function ($query) use ($user) {
                    $query->where('status', 'available')
                        ->whereNull('assigned_to')
                        ->orWhere(function ($q) use ($user) {
                            $q->where('assigned_to', $user->id)
                                ->where('status', 'assigned');
                        });
                })
                    ->whereNull('used_by')
                    ->select(['id', 'code', 'status', 'assigned_to', 'created_at'])
                    ->orderBy('created_at', 'desc')
                    ->get();
            } else {
                // For other users: Get tokens that are assigned to this user and not yet used
                $availableTokens = \App\Models\Token::where('assigned_to', $user->id)
                    ->where('status', 'assigned')
                    ->whereNull('used_by')
                    ->select(['id', 'code', 'status', 'assigned_to', 'created_at'])
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            return $this->success([
                'tokens' => $availableTokens->map(fn ($token) => [
                    'id' => $token->id,
                    'code' => $token->code,
                    'status' => $token->status,
                ]),
                'count' => $availableTokens->count(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
