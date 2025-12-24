<?php

namespace App\Services;

use App\Models\Measurement;
use App\Models\MeasurementData;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class MeasurementService
{
    /**
     * Get or create measurement record for a location and fiscal year
     *
     * @param int $locationId
     * @param int $fiscalYear
     * @return Measurement
     */
    public function getOrCreateMeasurement($locationId, $fiscalYear)
    {
        $measurement = Measurement::where('location_id', $locationId)
            ->where('fiscal_year', $fiscalYear)
            ->first();

        if (!$measurement) {
            $measurement = Measurement::create([
                'location_id' => $locationId,
                'fiscal_year' => $fiscalYear,
                'period_start' => Carbon::create($fiscalYear, 1, 1)->startOfYear(),
                'period_end' => Carbon::create($fiscalYear, 12, 31)->endOfYear(),
                'frequency' => 'annually',
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);
        }

        return $measurement;
    }

    /**
     * Update measurement totals (scope totals and total CO2e)
     *
     * @param int $measurementId
     * @return void
     */
    public function updateMeasurementTotals($measurementId)
    {
        $measurement = Measurement::findOrFail($measurementId);

        // Check if calculated_co2e column exists
        $hasCalculatedCo2e = Schema::hasColumn('measurement_data', 'calculated_co2e');
        
        if ($hasCalculatedCo2e) {
            $totals = MeasurementData::where('measurement_id', $measurementId)
                ->selectRaw('
                    SUM(calculated_co2e) as total_co2e,
                    SUM(CASE WHEN scope = "Scope 1" THEN calculated_co2e ELSE 0 END) as scope_1_co2e,
                    SUM(CASE WHEN scope = "Scope 2" THEN calculated_co2e ELSE 0 END) as scope_2_co2e,
                    SUM(CASE WHEN scope = "Scope 3" THEN calculated_co2e ELSE 0 END) as scope_3_co2e
                ')
                ->first();
        } else {
            // Fallback if column doesn't exist yet
            $totals = (object) [
                'total_co2e' => 0,
                'scope_1_co2e' => 0,
                'scope_2_co2e' => 0,
                'scope_3_co2e' => 0,
            ];
        }

        // Check if columns exist before updating
        $updateData = [];
        
        if (Schema::hasColumn('measurements', 'total_co2e')) {
            $updateData['total_co2e'] = $totals->total_co2e ?? 0;
        }
        if (Schema::hasColumn('measurements', 'scope_1_co2e')) {
            $updateData['scope_1_co2e'] = $totals->scope_1_co2e ?? 0;
        }
        if (Schema::hasColumn('measurements', 'scope_2_co2e')) {
            $updateData['scope_2_co2e'] = $totals->scope_2_co2e ?? 0;
        }
        if (Schema::hasColumn('measurements', 'scope_3_co2e')) {
            $updateData['scope_3_co2e'] = $totals->scope_3_co2e ?? 0;
        }
        if (Schema::hasColumn('measurements', 'co2e_calculated_at')) {
            $updateData['co2e_calculated_at'] = now();
        }
        
        if (!empty($updateData)) {
            $measurement->update($updateData);
        }
    }

    /**
     * Get measurement data entries by scope
     *
     * @param int $measurementId
     * @param string $scope
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMeasurementDataByScope($measurementId, $scope)
    {
        return MeasurementData::where('measurement_id', $measurementId)
            ->where('scope', $scope)
            ->with('emissionSource')
            ->get();
    }

    /**
     * Get all measurement data for a measurement
     *
     * @param int $measurementId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMeasurementData($measurementId)
    {
        return MeasurementData::where('measurement_id', $measurementId)
            ->with('emissionSource')
            ->orderBy('entry_date', 'desc')
            ->get();
    }

    /**
     * Calculate summary statistics for a company
     *
     * @param int $companyId
     * @param array $filters
     * @return object
     */
    public function calculateSummary($companyId, $filters = [])
    {
        $query = MeasurementData::with('measurement.location')
            ->whereHas('measurement', function($q) use ($companyId) {
                $q->whereHas('location', function($locQuery) use ($companyId) {
                    $locQuery->where('company_id', $companyId);
                });
            });

        // Apply filters
        if (isset($filters['scope'])) {
            $query->where('scope', $filters['scope']);
        }

        if (isset($filters['location_id'])) {
            $query->whereHas('measurement', function($q) use ($filters) {
                $q->where('location_id', $filters['location_id']);
            });
        }

        if (isset($filters['fiscal_year'])) {
            $query->whereHas('measurement', function($q) use ($filters) {
                $q->where('fiscal_year', $filters['fiscal_year']);
            });
        }

        // Check if calculated_co2e column exists, otherwise use 0
        $hasCalculatedCo2e = Schema::hasColumn('measurement_data', 'calculated_co2e');
        
        if ($hasCalculatedCo2e) {
            return $query->selectRaw('
                COUNT(*) as total_entries,
                SUM(calculated_co2e) as total_co2e,
                SUM(CASE WHEN scope = "Scope 1" THEN calculated_co2e ELSE 0 END) as scope_1_co2e,
                SUM(CASE WHEN scope = "Scope 2" THEN calculated_co2e ELSE 0 END) as scope_2_co2e,
                SUM(CASE WHEN scope = "Scope 3" THEN calculated_co2e ELSE 0 END) as scope_3_co2e
            ')->first();
        } else {
            // Fallback if column doesn't exist yet
            return (object) [
                'total_entries' => $query->count(),
                'total_co2e' => 0,
                'scope_1_co2e' => 0,
                'scope_2_co2e' => 0,
                'scope_3_co2e' => 0,
            ];
        }
    }
}

