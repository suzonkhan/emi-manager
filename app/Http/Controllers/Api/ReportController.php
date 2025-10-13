<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\GenerateReportRequest;
use App\Services\ReportService;
use App\Traits\ApiResponseTrait;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private ReportService $reportService) {}

    /**
     * Generate and return report data
     */
    public function generate(GenerateReportRequest $request): JsonResponse|Response
    {
        try {
            $filters = $request->validated();
            $user = $request->user();

            // Generate report based on type
            $reportData = match ($filters['report_type']) {
                'sales' => $this->reportService->generateSalesReport($filters, $user),
                'installments' => $this->reportService->generateInstallmentsReport($filters, $user),
                'collections' => $this->reportService->generateCollectionsReport($filters, $user),
                'products' => $this->reportService->generateProductsReport($filters, $user),
                'customers' => $this->reportService->generateCustomersReport($filters, $user),
                'dealers' => $this->reportService->generateDealersReport($filters, $user),
                'sub_dealers' => $this->reportService->generateSubDealersReport($filters, $user),
                default => throw new \Exception('Invalid report type'),
            };

            // If format is PDF, generate and download
            if (($filters['format'] ?? 'json') === 'pdf') {
                return $this->generatePDF($reportData, $filters['report_type']);
            }

            // Return JSON response
            return $this->success([
                'report' => $reportData,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), null, 500);
        }
    }

    /**
     * Generate PDF from report data
     */
    private function generatePDF(array $reportData, string $reportType): Response
    {
        $viewName = match ($reportType) {
            'sales' => 'reports.sales',
            'installments' => 'reports.installments',
            'collections' => 'reports.collections',
            'products' => 'reports.products',
            'customers' => 'reports.customers',
            'dealers' => 'reports.dealers',
            'sub_dealers' => 'reports.sub-dealers',
            default => 'reports.generic',
        };

        $pdf = Pdf::loadView($viewName, ['report' => $reportData])
            ->setPaper('a4', 'landscape')
            ->setOption('margin-top', 10)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10);

        $filename = str_replace('_', '-', $reportType).'-report-'.date('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Get available dealers for report filtering
     */
    public function getDealers(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Only super admin can see all dealers
            if (! $user->hasRole('super_admin')) {
                return $this->error('Only super admin can view dealers list', null, 403);
            }

            $dealers = \App\Models\User::role('dealer')
                ->select('id', 'unique_id', 'name', 'phone')
                ->get();

            return $this->success($dealers);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), null, 500);
        }
    }

    /**
     * Get available sub-dealers for report filtering
     */
    public function getSubDealers(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $dealerId = $request->input('dealer_id');

            $query = \App\Models\User::role('sub_dealer')
                ->select('id', 'unique_id', 'name', 'phone', 'parent_id');

            if ($user->hasRole('super_admin') && $dealerId) {
                $query->where('parent_id', $dealerId);
            } elseif ($user->hasRole('dealer')) {
                $query->where('parent_id', $user->id);
            } else {
                return $this->error('You do not have permission to view sub-dealers', null, 403);
            }

            $subDealers = $query->get();

            return $this->success($subDealers);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), null, 500);
        }
    }
}
