<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\Token;
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
            $isSuperAdmin = $user->hasRole('super_admin');

            // Get current month start and end
            $currentMonthStart = now()->startOfMonth();
            $currentMonthEnd = now()->endOfMonth();

            // User statistics based on role hierarchy
            if ($isSuperAdmin) {
                $dealerStats = $this->getRoleStats('dealer');
                $subDealerStats = $this->getRoleStats('sub_dealer');
                $salesmanStats = $this->getRoleStats('salesman');
            } else {
                // Get subordinate statistics
                $subordinates = $user->children()->get();
                $dealerStats = $this->getSubordinateStatsByRole($subordinates, 'dealer', $currentMonthStart);
                $subDealerStats = $this->getSubordinateStatsByRole($subordinates, 'sub_dealer', $currentMonthStart);
                $salesmanStats = $this->getSubordinateStatsByRole($subordinates, 'salesman', $currentMonthStart);
            }

            // Token statistics
            $tokenStats = $this->getTokenStats($user, $isSuperAdmin, $currentMonthStart);

            // Customer statistics
            $customerStats = $this->getCustomerStats($user, $isSuperAdmin, $currentMonthStart);

            // Sales statistics
            $salesStats = $this->getSalesStats($user, $isSuperAdmin);

            // Installment statistics
            $installmentStats = $this->getInstallmentStats($user, $isSuperAdmin, $currentMonthStart);

            // Device statistics
            $deviceStats = $this->getDeviceStats($user, $isSuperAdmin, $currentMonthStart);

            // Chart data
            $chartData = $this->getChartData($user, $isSuperAdmin);

            $stats = [
                'dealers' => $dealerStats,
                'sub_dealers' => $subDealerStats,
                'salesmen' => $salesmanStats,
                'tokens' => $tokenStats,
                'customers' => $customerStats,
                'sales' => $salesStats,
                'installments' => $installmentStats,
                'devices' => $deviceStats,
                'charts' => $chartData,
                'user_info' => [
                    'role' => $user->getRoleNames()->first(),
                    'hierarchy_level' => $user->getHierarchyLevel(),
                ],
            ];

            return $this->success($stats);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch dashboard statistics: '.$e->getMessage());
        }
    }

    private function getRoleStats(string $role): array
    {
        $currentMonthStart = now()->startOfMonth();
        $total = User::role($role)->count();
        $newThisMonth = User::role($role)
            ->where('created_at', '>=', $currentMonthStart)
            ->count();

        return [
            'total' => $total,
            'new_this_month' => $newThisMonth,
        ];
    }

    private function getSubordinateStatsByRole($subordinates, string $role, $currentMonthStart): array
    {
        $total = $subordinates->filter(function ($user) use ($role) {
            return $user->hasRole($role);
        })->count();

        $newThisMonth = $subordinates->filter(function ($user) use ($role, $currentMonthStart) {
            return $user->hasRole($role) && $user->created_at >= $currentMonthStart;
        })->count();

        return [
            'total' => $total,
            'new_this_month' => $newThisMonth,
        ];
    }

    private function getTokenStats(User $user, bool $isSuperAdmin, $currentMonthStart): array
    {
        if ($isSuperAdmin) {
            // For Super Admin, show tokens they CREATED (generated)
            $createdByUser = Token::where('created_by', $user->id);
            
            $totalTokens = (clone $createdByUser)->count();
            
            // Used tokens - tokens with status 'used'
            $usedTokens = (clone $createdByUser)
                ->where('status', 'used')
                ->count();
            
            // Available tokens - unassigned + tokens assigned to super admin
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
            
            // New used tokens this month
            $newUsedThisMonth = (clone $createdByUser)
                ->where('status', 'used')
                ->whereHas('customer', function ($query) use ($currentMonthStart) {
                    $query->where('created_at', '>=', $currentMonthStart);
                })
                ->count();
        } else {
            // For other roles, use hierarchy-based logic
            $assignedTokenIds = $user->assignedTokens()->pluck('id');
            $totalTokens = $assignedTokenIds->count();
            $usedTokens = Token::whereIn('id', $assignedTokenIds)
                ->whereHas('customer')
                ->count();
            $availableTokens = Token::whereIn('id', $assignedTokenIds)
                ->where('status', 'assigned')
                ->doesntHave('customer')
                ->count();
            $newUsedThisMonth = Token::whereIn('id', $assignedTokenIds)
                ->whereHas('customer', function ($query) use ($currentMonthStart) {
                    $query->where('created_at', '>=', $currentMonthStart);
                })
                ->count();
        }

        return [
            'total' => $totalTokens,
            'used' => $usedTokens,
            'available' => $availableTokens,
            'new_used_this_month' => $newUsedThisMonth,
        ];
    }

    private function getCustomerStats(User $user, bool $isSuperAdmin, $currentMonthStart): array
    {
        if ($isSuperAdmin) {
            $total = Customer::count();
            $newThisMonth = Customer::where('created_at', '>=', $currentMonthStart)->count();
        } else {
            $total = Customer::where('created_by', $user->id)
                ->orWhereHas('token', function ($query) use ($user) {
                    $query->where('assigned_to', $user->id);
                })
                ->count();

            $newThisMonth = Customer::where('created_at', '>=', $currentMonthStart)
                ->where(function ($query) use ($user) {
                    $query->where('created_by', $user->id)
                        ->orWhereHas('token', function ($q) use ($user) {
                            $q->where('assigned_to', $user->id);
                        });
                })
                ->count();
        }

        return [
            'total' => $total,
            'new_this_month' => $newThisMonth,
        ];
    }

    private function getSalesStats(User $user, bool $isSuperAdmin): array
    {
        if ($isSuperAdmin) {
            $totalSales = Customer::sum('product_price');
        } else {
            $totalSales = Customer::where('created_by', $user->id)
                ->orWhereHas('token', function ($query) use ($user) {
                    $query->where('assigned_to', $user->id);
                })
                ->sum('product_price');
        }

        return [
            'total' => $totalSales ?? 0,
        ];
    }

    private function getInstallmentStats(User $user, bool $isSuperAdmin, $currentMonthStart): array
    {
        $currentMonthEnd = now()->endOfMonth();

        if ($isSuperAdmin) {
            $thisMonthTotal = Installment::whereBetween('due_date', [$currentMonthStart, $currentMonthEnd])
                ->sum('amount');
            $thisMonthPaid = Installment::whereBetween('due_date', [$currentMonthStart, $currentMonthEnd])
                ->where('status', 'paid')
                ->sum('paid_amount');
            // Pending includes: pending, partial, and overdue installments
            $pending = Installment::whereIn('status', ['pending', 'partial', 'overdue'])
                ->sum('amount');
        } else {
            $customerIds = Customer::where('created_by', $user->id)
                ->orWhereHas('token', function ($query) use ($user) {
                    $query->where('assigned_to', $user->id);
                })
                ->pluck('id');

            $thisMonthTotal = Installment::whereIn('customer_id', $customerIds)
                ->whereBetween('due_date', [$currentMonthStart, $currentMonthEnd])
                ->sum('amount');
            $thisMonthPaid = Installment::whereIn('customer_id', $customerIds)
                ->whereBetween('due_date', [$currentMonthStart, $currentMonthEnd])
                ->where('status', 'paid')
                ->sum('paid_amount');
            // Pending includes: pending, partial, and overdue installments
            $pending = Installment::whereIn('customer_id', $customerIds)
                ->whereIn('status', ['pending', 'partial', 'overdue'])
                ->sum('amount');
        }

        return [
            'this_month_total' => $thisMonthTotal ?? 0,
            'this_month_paid' => $thisMonthPaid ?? 0,
            'pending' => $pending ?? 0,
        ];
    }

    private function getDeviceStats(User $user, bool $isSuperAdmin, $currentMonthStart): array
    {
        // Note: Assuming you have a devices table or device_commands table
        // For now, returning placeholder data
        // TODO: Implement actual device locking statistics when device management is complete

        return [
            'locked_this_month' => 0,
        ];
    }

    private function getChartData(User $user, bool $isSuperAdmin): array
    {
        $last6Months = [];
        for ($i = 5; $i >= 0; $i--) {
            $last6Months[] = now()->subMonths($i)->format('Y-m');
        }

        // User signup chart data
        $userSignupData = [];
        foreach ($last6Months as $month) {
            $monthStart = \Carbon\Carbon::parse($month.'-01')->startOfMonth();
            $monthEnd = \Carbon\Carbon::parse($month.'-01')->endOfMonth();

            if ($isSuperAdmin) {
                $dealers = User::role('dealer')
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count();
                $subDealers = User::role('sub_dealer')
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count();
                $salesmen = User::role('salesman')
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count();
            } else {
                $subordinates = $user->children()
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->get();

                $dealers = $subordinates->filter(fn ($u) => $u->hasRole('dealer'))->count();
                $subDealers = $subordinates->filter(fn ($u) => $u->hasRole('sub_dealer'))->count();
                $salesmen = $subordinates->filter(fn ($u) => $u->hasRole('salesman'))->count();
            }

            $userSignupData[] = [
                'month' => $month,
                'dealers' => $dealers,
                'sub_dealers' => $subDealers,
                'salesmen' => $salesmen,
            ];
        }

        // Customer signup chart data
        $customerSignupData = [];
        foreach ($last6Months as $month) {
            $monthStart = \Carbon\Carbon::parse($month.'-01')->startOfMonth();
            $monthEnd = \Carbon\Carbon::parse($month.'-01')->endOfMonth();

            if ($isSuperAdmin) {
                $count = Customer::whereBetween('created_at', [$monthStart, $monthEnd])->count();
            } else {
                $count = Customer::whereBetween('created_at', [$monthStart, $monthEnd])
                    ->where(function ($query) use ($user) {
                        $query->where('created_by', $user->id)
                            ->orWhereHas('token', function ($q) use ($user) {
                                $q->where('assigned_to', $user->id);
                            });
                    })
                    ->count();
            }

            $customerSignupData[] = [
                'month' => $month,
                'customers' => $count,
            ];
        }

        // Sales chart data
        $salesChartData = [];
        foreach ($last6Months as $month) {
            $monthStart = \Carbon\Carbon::parse($month.'-01')->startOfMonth();
            $monthEnd = \Carbon\Carbon::parse($month.'-01')->endOfMonth();

            if ($isSuperAdmin) {
                $amount = Customer::whereBetween('created_at', [$monthStart, $monthEnd])
                    ->sum('product_price');
            } else {
                $amount = Customer::whereBetween('created_at', [$monthStart, $monthEnd])
                    ->where(function ($query) use ($user) {
                        $query->where('created_by', $user->id)
                            ->orWhereHas('token', function ($q) use ($user) {
                                $q->where('assigned_to', $user->id);
                            });
                    })
                    ->sum('product_price');
            }

            $salesChartData[] = [
                'month' => $month,
                'sales' => $amount ?? 0,
            ];
        }

        // Installment chart data
        $installmentChartData = [];
        foreach ($last6Months as $month) {
            $monthStart = \Carbon\Carbon::parse($month.'-01')->startOfMonth();
            $monthEnd = \Carbon\Carbon::parse($month.'-01')->endOfMonth();

            if ($isSuperAdmin) {
                $paid = Installment::whereBetween('paid_date', [$monthStart, $monthEnd])
                    ->where('status', 'paid')
                    ->sum('paid_amount');
            } else {
                $customerIds = Customer::where('created_by', $user->id)
                    ->orWhereHas('token', function ($query) use ($user) {
                        $query->where('assigned_to', $user->id);
                    })
                    ->pluck('id');

                $paid = Installment::whereIn('customer_id', $customerIds)
                    ->whereBetween('paid_date', [$monthStart, $monthEnd])
                    ->where('status', 'paid')
                    ->sum('paid_amount');
            }

            $installmentChartData[] = [
                'month' => $month,
                'paid' => $paid ?? 0,
            ];
        }

        return [
            'user_signups' => $userSignupData,
            'customer_signups' => $customerSignupData,
            'sales' => $salesChartData,
            'installments' => $installmentChartData,
        ];
    }
}
