<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Division;
use App\Models\Upazilla;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get all active divisions
     */
    public function getDivisions(): JsonResponse
    {
        try {
            $divisions = Division::active()->get();

            return $this->success([
                'divisions' => $divisions,
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch divisions', null, 500);
        }
    }

    /**
     * Get districts by division ID
     */
    public function getDistricts(int $divisionId): JsonResponse
    {
        try {
            $districts = District::where('division_id', $divisionId)->active()->get();

            return $this->success([
                'districts' => $districts,
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch districts', null, 500);
        }
    }

    /**
     * Get upazillas by district ID
     */
    public function getUpazillas(int $districtId): JsonResponse
    {
        try {
            $upazillas = Upazilla::where('district_id', $districtId)->active()->get();

            return $this->success([
                'upazillas' => $upazillas,
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch upazillas', null, 500);
        }
    }
}
