<?php

namespace App\Services;

use App\Models\CarbonEmission;
use App\Models\CarbonCalculation;
use App\Models\EmissionFactor;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CarbonCalculationService
{
    /**
     * Calculate total emissions for a company by scope and time period.
     */
    public function calculateEmissions(Company $company, string $scope, int $year, ?int $quarter = null, ?int $month = null): array
    {
        $query = $company->carbonEmissions()
            ->where('scope', $scope)
            ->whereYear('activity_date', $year);

        if ($quarter) {
            $query->whereRaw('QUARTER(activity_date) = ?', [$quarter]);
        }

        if ($month) {
            $query->whereMonth('activity_date', $month);
        }

        $emissions = $query->get();

        $totalEmissions = $emissions->sum('total_emissions');
        $breakdown = $this->getBreakdownByCategory($emissions);
        $trends = $this->getTrends($company, $scope, $year, $quarter, $month);

        return [
            'total_emissions' => $totalEmissions,
            'emissions_per_employee' => $this->calculatePerEmployee($totalEmissions, $company),
            'emissions_per_revenue' => $this->calculatePerRevenue($totalEmissions, $company),
            'breakdown' => $breakdown,
            'trends' => $trends,
        ];
    }

    /**
     * Calculate emissions for all scopes.
     */
    public function calculateAllScopes(Company $company, int $year, ?int $quarter = null, ?int $month = null): array
    {
        $scopes = ['scope_1', 'scope_2', 'scope_3'];
        $results = [];

        foreach ($scopes as $scope) {
            $results[$scope] = $this->calculateEmissions($company, $scope, $year, $quarter, $month);
        }

        // Calculate total
        $totalEmissions = array_sum(array_column($results, 'total_emissions'));
        $results['total'] = [
            'total_emissions' => $totalEmissions,
            'emissions_per_employee' => $this->calculatePerEmployee($totalEmissions, $company),
            'emissions_per_revenue' => $this->calculatePerRevenue($totalEmissions, $company),
            'breakdown' => $this->mergeBreakdowns($results),
            'trends' => $this->mergeTrends($results),
        ];

        return $results;
    }

    /**
     * Save calculation results to database.
     */
    public function saveCalculation(Company $company, string $scope, int $year, ?int $quarter, ?int $month, array $data, int $calculatedBy): CarbonCalculation
    {
        return CarbonCalculation::create([
            'company_id' => $company->id,
            'scope' => $scope,
            'year' => $year,
            'quarter' => $quarter,
            'month' => $month,
            'total_emissions' => $data['total_emissions'],
            'emissions_per_employee' => $data['emissions_per_employee'],
            'emissions_per_revenue' => $data['emissions_per_revenue'],
            'breakdown' => $data['breakdown'],
            'trends' => $data['trends'],
            'calculated_by' => $calculatedBy,
            'calculated_at' => now(),
        ]);
    }

    /**
     * Calculate reduction targets and achievements.
     */
    public function calculateReduction(Company $company, int $currentYear, int $baseYear, string $scope = 'total'): array
    {
        $currentEmissions = $this->getTotalEmissions($company, $scope, $currentYear);
        $baseEmissions = $this->getTotalEmissions($company, $scope, $baseYear);

        if ($baseEmissions == 0) {
            return [
                'reduction_achieved' => 0,
                'reduction_percentage' => 0,
                'base_year_emissions' => $baseEmissions,
                'current_year_emissions' => $currentEmissions,
            ];
        }

        $reductionAchieved = $baseEmissions - $currentEmissions;
        $reductionPercentage = ($reductionAchieved / $baseEmissions) * 100;

        return [
            'reduction_achieved' => $reductionAchieved,
            'reduction_percentage' => $reductionPercentage,
            'base_year_emissions' => $baseEmissions,
            'current_year_emissions' => $currentEmissions,
        ];
    }

    /**
     * Get emission factor for a specific category and unit.
     */
    public function getEmissionFactor(string $category, string $unit, ?string $subcategory = null, ?string $region = 'US'): ?EmissionFactor
    {
        $query = EmissionFactor::active()
            ->forCategory($category)
            ->forUnit($unit)
            ->forRegion($region);

        if ($subcategory) {
            $query->forSubcategory($subcategory);
        }

        return $query->latestForCategoryAndUnit($category, $unit)->first();
    }

    /**
     * Calculate emissions for a single activity.
     */
    public function calculateActivityEmissions(float $quantity, string $unit, string $category, ?string $subcategory = null): array
    {
        $emissionFactor = $this->getEmissionFactor($category, $unit, $subcategory);

        if (!$emissionFactor) {
            throw new \Exception("No emission factor found for category: {$category}, unit: {$unit}");
        }

        $totalEmissions = $quantity * $emissionFactor->total_gwp;

        return [
            'quantity' => $quantity,
            'unit' => $unit,
            'emission_factor' => $emissionFactor->total_gwp,
            'total_emissions' => $totalEmissions,
            'co2_emissions' => $quantity * $emissionFactor->co2_factor,
            'ch4_emissions' => $quantity * $emissionFactor->ch4_factor,
            'n2o_emissions' => $quantity * $emissionFactor->n2o_factor,
        ];
    }

    /**
     * Get breakdown by category.
     */
    private function getBreakdownByCategory($emissions): array
    {
        return $emissions->groupBy('category')
            ->map(function ($categoryEmissions) {
                return [
                    'total_emissions' => $categoryEmissions->sum('total_emissions'),
                    'count' => $categoryEmissions->count(),
                    'subcategories' => $categoryEmissions->groupBy('subcategory')
                        ->map(function ($subcategoryEmissions) {
                            return [
                                'total_emissions' => $subcategoryEmissions->sum('total_emissions'),
                                'count' => $subcategoryEmissions->count(),
                            ];
                        }),
                ];
            });
    }

    /**
     * Get trends data.
     */
    private function getTrends(Company $company, string $scope, int $year, ?int $quarter, ?int $month): array
    {
        $previousYear = $year - 1;
        $currentEmissions = $this->getTotalEmissions($company, $scope, $year, $quarter, $month);
        $previousEmissions = $this->getTotalEmissions($company, $scope, $previousYear, $quarter, $month);

        $change = $previousEmissions > 0 ? (($currentEmissions - $previousEmissions) / $previousEmissions) * 100 : 0;

        return [
            'current_year' => $year,
            'previous_year' => $previousYear,
            'current_emissions' => $currentEmissions,
            'previous_emissions' => $previousEmissions,
            'change_percentage' => $change,
            'change_amount' => $currentEmissions - $previousEmissions,
        ];
    }

    /**
     * Calculate emissions per employee.
     */
    private function calculatePerEmployee(float $totalEmissions, Company $company): ?float
    {
        if (!$company->employee_count || $company->employee_count == 0) {
            return null;
        }

        return $totalEmissions / $company->employee_count;
    }

    /**
     * Calculate emissions per revenue unit.
     */
    private function calculatePerRevenue(float $totalEmissions, Company $company): ?float
    {
        if (!$company->annual_revenue || $company->annual_revenue == 0) {
            return null;
        }

        return $totalEmissions / $company->annual_revenue;
    }

    /**
     * Get total emissions for a specific scope and time period.
     */
    private function getTotalEmissions(Company $company, string $scope, int $year, ?int $quarter = null, ?int $month = null): float
    {
        $query = $company->carbonEmissions()
            ->where('scope', $scope)
            ->whereYear('activity_date', $year);

        if ($quarter) {
            $query->whereRaw('QUARTER(activity_date) = ?', [$quarter]);
        }

        if ($month) {
            $query->whereMonth('activity_date', $month);
        }

        return $query->sum('total_emissions');
    }

    /**
     * Merge breakdowns from multiple scopes.
     */
    private function mergeBreakdowns(array $results): array
    {
        $merged = [];
        foreach ($results as $scope => $data) {
            if ($scope === 'total') continue;
            foreach ($data['breakdown'] as $category => $breakdown) {
                if (!isset($merged[$category])) {
                    $merged[$category] = [
                        'total_emissions' => 0,
                        'count' => 0,
                        'subcategories' => [],
                    ];
                }
                $merged[$category]['total_emissions'] += $breakdown['total_emissions'];
                $merged[$category]['count'] += $breakdown['count'];
            }
        }
        return $merged;
    }

    /**
     * Merge trends from multiple scopes.
     */
    private function mergeTrends(array $results): array
    {
        $totalCurrent = 0;
        $totalPrevious = 0;

        foreach ($results as $scope => $data) {
            if ($scope === 'total') continue;
            $totalCurrent += $data['trends']['current_emissions'];
            $totalPrevious += $data['trends']['previous_emissions'];
        }

        $change = $totalPrevious > 0 ? (($totalCurrent - $totalPrevious) / $totalPrevious) * 100 : 0;

        return [
            'current_year' => $results['scope_1']['trends']['current_year'],
            'previous_year' => $results['scope_1']['trends']['previous_year'],
            'current_emissions' => $totalCurrent,
            'previous_emissions' => $totalPrevious,
            'change_percentage' => $change,
            'change_amount' => $totalCurrent - $totalPrevious,
        ];
    }
}
