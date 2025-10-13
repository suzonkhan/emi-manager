<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Installment;
use App\Models\Token;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Generate sales report
     */
    public function generateSalesReport(array $filters, User $user): array
    {
        $query = Customer::query()
            ->with(['creator', 'dealer', 'token'])
            ->whereBetween('created_at', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay(),
            ]);

        // Apply hierarchy filtering
        $query = $this->applyHierarchyFilter($query, $user, $filters);

        $customers = $query->get();

        $reportData = $customers->map(function ($customer) {
            return [
                'date' => $customer->created_at->format('Y-m-d'),
                'dealer_name' => $customer->dealer?->name ?? 'N/A',
                'product_name' => $customer->product_model ?? $customer->product_type,
                'price' => $customer->product_price,
            ];
        });

        return [
            'report_type' => 'Sales Report',
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
            'data' => $reportData,
            'total' => $reportData->sum('price'),
        ];
    }

    /**
     * Generate installments report
     */
    public function generateInstallmentsReport(array $filters, User $user): array
    {
        $query = Customer::query()
            ->with(['token', 'installments'])
            ->whereBetween('created_at', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay(),
            ]);

        $query = $this->applyHierarchyFilter($query, $user, $filters);

        $customers = $query->get();

        $reportData = $customers->map(function ($customer) {
            $totalPaid = $customer->installments->where('status', 'paid')->sum('amount');
            $remaining = $customer->product_price - $customer->down_payment - $totalPaid;

            return [
                'date' => $customer->created_at->format('Y-m-d'),
                'token' => $customer->token?->code ?? 'N/A',
                'product_type' => $customer->product_type,
                'product_name' => $customer->product_model ?? $customer->product_type,
                'duration' => $customer->emi_duration_months,
                'price' => $customer->product_price,
                'paid' => $totalPaid,
                'remaining' => max(0, $remaining),
            ];
        });

        return [
            'report_type' => 'Installment Report',
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
            'data' => $reportData,
            'total_price' => $reportData->sum('price'),
            'total_paid' => $reportData->sum('paid'),
            'total_remaining' => $reportData->sum('remaining'),
        ];
    }

    /**
     * Generate collections report
     */
    public function generateCollectionsReport(array $filters, User $user): array
    {
        $query = Installment::query()
            ->with(['customer.token'])
            ->where('status', 'paid')
            ->whereBetween('paid_date', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay(),
            ]);

        // Apply hierarchy filtering through customer
        $query->whereHas('customer', function ($q) use ($user, $filters) {
            $q = $this->applyHierarchyFilter($q, $user, $filters);
        });

        $installments = $query->get();

        $reportData = $installments->map(function ($installment) {
            return [
                'date' => $installment->paid_date?->format('Y-m-d') ?? 'N/A',
                'token' => $installment->customer->token?->code ?? 'N/A',
                'product_type' => $installment->customer->product_type,
                'product_name' => $installment->customer->product_model ?? $installment->customer->product_type,
                'installment_no' => $this->getInstallmentPosition($installment),
                'paid' => $installment->amount,
            ];
        });

        return [
            'report_type' => 'Collection Report',
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
            'data' => $reportData,
            'total' => $reportData->sum('paid'),
        ];
    }

    /**
     * Generate products report
     */
    public function generateProductsReport(array $filters, User $user): array
    {
        $query = Customer::query()
            ->select('product_type', DB::raw('COUNT(*) as sales_qty'), DB::raw('SUM(product_price) as total_price'))
            ->whereBetween('created_at', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay(),
            ])
            ->groupBy('product_type');

        $query = $this->applyHierarchyFilter($query, $user, $filters);

        $products = $query->get();

        $reportData = $products->map(function ($product) {
            return [
                'product_type' => $product->product_type,
                'sales_qty' => $product->sales_qty,
                'price' => $product->total_price,
            ];
        });

        return [
            'report_type' => 'Product Report',
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
            'data' => $reportData,
            'total_qty' => $reportData->sum('sales_qty'),
            'total_price' => $reportData->sum('price'),
        ];
    }

    /**
     * Generate customers report
     */
    public function generateCustomersReport(array $filters, User $user): array
    {
        $query = Customer::query()
            ->with(['presentAddress.district', 'presentAddress.upazilla', 'installments'])
            ->whereBetween('created_at', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay(),
            ]);

        $query = $this->applyHierarchyFilter($query, $user, $filters);

        $customers = $query->get();

        $reportData = $customers->map(function ($customer) {
            $totalPaid = $customer->installments->where('status', 'paid')->sum('amount') + $customer->down_payment;
            $due = $customer->product_price - $totalPaid;

            return [
                'name' => $customer->name,
                'mobile' => $customer->mobile,
                'district' => $customer->presentAddress?->district?->name ?? 'N/A',
                'upazila' => $customer->presentAddress?->upazilla?->name ?? 'N/A',
                'product_name' => $customer->product_model ?? $customer->product_type,
                'price' => $customer->product_price,
                'paid' => $totalPaid,
                'due' => max(0, $due),
            ];
        });

        return [
            'report_type' => 'Customer Report',
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
            'data' => $reportData,
            'total_price' => $reportData->sum('price'),
            'total_paid' => $reportData->sum('paid'),
            'total_due' => $reportData->sum('due'),
        ];
    }

    /**
     * Generate dealers report
     */
    public function generateDealersReport(array $filters, User $user): array
    {
        $query = User::query()
            ->role('dealer')
            ->with(['presentAddress.district', 'presentAddress.upazilla', 'assignedTokens']);

        // Super admin can see all dealers
        if (! $user->hasRole('super_admin')) {
            return [
                'report_type' => 'Dealer Report',
                'start_date' => $filters['start_date'],
                'end_date' => $filters['end_date'],
                'data' => [],
                'message' => 'Only super admin can view dealers report',
            ];
        }

        if (isset($filters['dealer_id'])) {
            $query->where('id', $filters['dealer_id']);
        }

        $dealers = $query->get();

        $reportData = $dealers->map(function ($dealer) use ($filters) {
            $usedTokens = Token::where('assigned_to', $dealer->id)
                ->where('status', 'used')
                ->whereBetween('updated_at', [
                    Carbon::parse($filters['start_date'])->startOfDay(),
                    Carbon::parse($filters['end_date'])->endOfDay(),
                ])
                ->count();

            $availableTokens = Token::where('assigned_to', $dealer->id)
                ->where('status', 'assigned')
                ->count();

            return [
                'id' => $dealer->unique_id ?? $dealer->id,
                'name' => $dealer->name,
                'mobile' => $dealer->phone,
                'district' => $dealer->presentAddress?->district?->name ?? 'N/A',
                'upazila' => $dealer->presentAddress?->upazilla?->name ?? 'N/A',
                'used_token' => $usedTokens,
                'available_token' => $availableTokens,
            ];
        });

        return [
            'report_type' => 'Dealer Report',
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
            'data' => $reportData,
        ];
    }

    /**
     * Generate sub-dealers report
     */
    public function generateSubDealersReport(array $filters, User $user): array
    {
        $query = User::query()
            ->role('sub_dealer')
            ->with(['presentAddress.district', 'presentAddress.upazilla', 'assignedTokens']);

        // Apply hierarchy filtering
        if ($user->hasRole('super_admin')) {
            // Super admin can see all sub-dealers
            if (isset($filters['dealer_id'])) {
                $query->where('parent_id', $filters['dealer_id']);
            }
        } elseif ($user->hasRole('dealer')) {
            // Dealer can only see their sub-dealers
            $query->where('parent_id', $user->id);
        } else {
            // Others cannot view this report
            return [
                'report_type' => 'Sub-Dealer Report',
                'start_date' => $filters['start_date'],
                'end_date' => $filters['end_date'],
                'data' => [],
                'message' => 'Access denied',
            ];
        }

        $subDealers = $query->get();

        $reportData = $subDealers->map(function ($subDealer) use ($filters) {
            $usedTokens = Token::where('assigned_to', $subDealer->id)
                ->where('status', 'used')
                ->whereBetween('updated_at', [
                    Carbon::parse($filters['start_date'])->startOfDay(),
                    Carbon::parse($filters['end_date'])->endOfDay(),
                ])
                ->count();

            $availableTokens = Token::where('assigned_to', $subDealer->id)
                ->where('status', 'assigned')
                ->count();

            return [
                'id' => $subDealer->unique_id ?? $subDealer->id,
                'name' => $subDealer->name,
                'mobile' => $subDealer->phone,
                'district' => $subDealer->presentAddress?->district?->name ?? 'N/A',
                'upazila' => $subDealer->presentAddress?->upazilla?->name ?? 'N/A',
                'used_token' => $usedTokens,
                'available_token' => $availableTokens,
            ];
        });

        return [
            'report_type' => 'Sub-Dealer Report',
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
            'data' => $reportData,
        ];
    }

    /**
     * Apply hierarchy filtering based on user role
     */
    private function applyHierarchyFilter($query, User $user, array $filters)
    {
        if ($user->hasRole('super_admin')) {
            // Super admin can see all, optionally filtered by dealer/sub-dealer
            if (isset($filters['dealer_id']) && $filters['dealer_id'] > 0) {
                $query->where('dealer_id', $filters['dealer_id']);
            }
            if (isset($filters['sub_dealer_id']) && $filters['sub_dealer_id'] > 0) {
                $query->where('created_by', $filters['sub_dealer_id']);
            }
        } elseif ($user->hasRole('dealer')) {
            // Dealer sees their hierarchy
            $hierarchyIds = $this->getUserHierarchyIds($user);
            $query->whereIn('created_by', $hierarchyIds);
        } elseif (in_array($user->role, ['sub_dealer', 'salesman'])) {
            // Sub-dealer and salesman see their hierarchy
            $hierarchyIds = $this->getUserHierarchyIds($user);
            $query->whereIn('created_by', $hierarchyIds);
        }

        return $query;
    }

    /**
     * Get user hierarchy IDs recursively
     */
    private function getUserHierarchyIds(User $user): array
    {
        $userIds = [$user->id];
        $children = User::where('parent_id', $user->id)->get();

        foreach ($children as $child) {
            $userIds = array_merge($userIds, $this->getUserHierarchyIds($child));
        }

        return array_unique($userIds);
    }

    /**
     * Get installment position (1st, 2nd, 3rd, etc.)
     */
    private function getInstallmentPosition(Installment $installment): string
    {
        $position = Installment::where('customer_id', $installment->customer_id)
            ->where('due_date', '<=', $installment->due_date)
            ->count();

        $suffix = match ($position % 10) {
            1 => $position === 11 ? 'th' : 'st',
            2 => $position === 12 ? 'th' : 'nd',
            3 => $position === 13 ? 'th' : 'rd',
            default => 'th',
        };

        return $position.$suffix;
    }
}
