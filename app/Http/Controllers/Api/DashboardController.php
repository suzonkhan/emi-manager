<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get dashboard statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $stats = [
                'total_subordinates' => $user->children()->count(),
                'active_subordinates' => $user->children()->where('is_active', true)->count(),
                'my_role' => $user->getRoleNames()->first(),
                'hierarchy_level' => $user->getHierarchyLevel(),
            ];

            if ($user->hasRole('super_admin')) {
                $stats['total_users'] = User::count();
                $stats['active_users'] = User::where('is_active', true)->count();
                $stats['roles_distribution'] = User::join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->groupBy('roles.name')
                    ->selectRaw('roles.name as role, count(*) as count')
                    ->get();
            }

            return $this->success($stats);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch dashboard statistics: ' . $e->getMessage());
        }
    }
}
