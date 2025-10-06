<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Measurement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MeasurementPeriodService
{
    /**
     * Sync measurement periods for a location based on its settings
     */
    public function syncMeasurementPeriods(Location $location, $createdBy = null)
    {
        Log::info('Syncing measurement periods for location', [
            'location_id' => $location->id,
            'location_name' => $location->name,
            'frequency' => $location->measurement_frequency,
            'fiscal_year_start' => $location->fiscal_year_start,
            'reporting_period' => $location->reporting_period
        ]);

        // Get all possible periods that should exist
        $requiredPeriods = $this->calculateRequiredPeriods($location);
        
        // Get existing measurements
        $existingMeasurements = Measurement::where('location_id', $location->id)
            ->get()
            ->keyBy(function($measurement) {
                return $measurement->period_start->format('Y-m-d') . '_' . $measurement->period_end->format('Y-m-d');
            });

        $createdCount = 0;
        $keptCount = 0;

        DB::beginTransaction();
        try {
            foreach ($requiredPeriods as $period) {
                $key = $period['start'] . '_' . $period['end'];
                
                if ($existingMeasurements->has($key)) {
                    // Period already exists, keep it
                    $keptCount++;
                    Log::info('Keeping existing measurement', [
                        'period' => $period['label'],
                        'start' => $period['start'],
                        'end' => $period['end']
                    ]);
                } else {
                    // Create new measurement
                    Measurement::create([
                        'location_id' => $location->id,
                        'period_start' => $period['start'],
                        'period_end' => $period['end'],
                        'frequency' => $period['frequency'],
                        'status' => 'draft',
                        'fiscal_year' => $period['fiscal_year'],
                        'fiscal_year_start_month' => $period['fiscal_start'],
                        'created_by' => $createdBy,
                        'notes' => 'Auto-generated measurement period'
                    ]);
                    $createdCount++;
                    
                    Log::info('Created new measurement', [
                        'period' => $period['label'],
                        'start' => $period['start'],
                        'end' => $period['end']
                    ]);
                }
            }

            DB::commit();
            
            Log::info('Measurement period sync completed', [
                'location_id' => $location->id,
                'created' => $createdCount,
                'kept' => $keptCount,
                'total_required' => count($requiredPeriods)
            ]);

            return [
                'created' => $createdCount,
                'kept' => $keptCount,
                'total_required' => count($requiredPeriods)
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error syncing measurement periods', [
                'location_id' => $location->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Calculate all required periods for a location
     */
    private function calculateRequiredPeriods(Location $location)
    {
        $periods = [];
        $currentYear = $location->reporting_period ?? date('Y');
        $fiscalYearStart = $location->fiscal_year_start ?? 'JAN';
        $measurementFrequency = $location->measurement_frequency ?? 'monthly';

        // Get fiscal year start month number
        $monthMap = [
            'JAN' => 1, 'FEB' => 2, 'MAR' => 3, 'APR' => 4,
            'MAY' => 5, 'JUN' => 6, 'JUL' => 7, 'AUG' => 8,
            'SEP' => 9, 'OCT' => 10, 'NOV' => 11, 'DEC' => 12,
            'January' => 1, 'February' => 2, 'March' => 3, 'April' => 4,
            'May' => 5, 'June' => 6, 'July' => 7, 'August' => 8,
            'September' => 9, 'October' => 10, 'November' => 11, 'December' => 12
        ];
        $startMonth = $monthMap[$fiscalYearStart] ?? 1;

        // Normalize frequency
        $normalizedFrequency = strtolower(str_replace(' ', '_', $measurementFrequency));

        Log::info('Calculating required periods', [
            'frequency' => $normalizedFrequency,
            'currentYear' => $currentYear,
            'startMonth' => $startMonth
        ]);

        switch ($normalizedFrequency) {
            case 'annually':
                $periods[] = [
                    'start' => Carbon::create($currentYear, $startMonth, 1)->format('Y-m-d'),
                    'end' => Carbon::create($currentYear, $startMonth, 1)->addYear()->subDay()->format('Y-m-d'),
                    'label' => "FY {$currentYear} (Annual)",
                    'frequency' => 'annually',
                    'fiscal_year' => $currentYear,
                    'fiscal_start' => $fiscalYearStart
                ];
                break;

            case 'half_yearly':
                for ($i = 0; $i < 2; $i++) {
                    $periodStart = Carbon::create($currentYear, $startMonth, 1)->addMonths($i * 6);
                    $periodEnd = $periodStart->copy()->addMonths(6)->subDay();
                    $periods[] = [
                        'start' => $periodStart->format('Y-m-d'),
                        'end' => $periodEnd->format('Y-m-d'),
                        'label' => $periodStart->format('M Y') . ' - ' . $periodEnd->format('M Y'),
                        'frequency' => 'half_yearly',
                        'fiscal_year' => $currentYear,
                        'fiscal_start' => $fiscalYearStart
                    ];
                }
                break;

            case 'quarterly':
                for ($i = 0; $i < 4; $i++) {
                    $periodStart = Carbon::create($currentYear, $startMonth, 1)->addMonths($i * 3);
                    $periodEnd = $periodStart->copy()->addMonths(3)->subDay();
                    $periods[] = [
                        'start' => $periodStart->format('Y-m-d'),
                        'end' => $periodEnd->format('Y-m-d'),
                        'label' => $periodStart->format('M Y') . ' - ' . $periodEnd->format('M Y'),
                        'frequency' => 'quarterly',
                        'fiscal_year' => $currentYear,
                        'fiscal_start' => $fiscalYearStart
                    ];
                }
                break;

            case 'monthly':
                for ($i = 0; $i < 12; $i++) {
                    $periodStart = Carbon::create($currentYear, $startMonth, 1)->addMonths($i);
                    $periodEnd = $periodStart->copy()->addMonth()->subDay();
                    $periods[] = [
                        'start' => $periodStart->format('Y-m-d'),
                        'end' => $periodEnd->format('Y-m-d'),
                        'label' => $periodStart->format('M Y'),
                        'frequency' => 'monthly',
                        'fiscal_year' => $currentYear,
                        'fiscal_start' => $fiscalYearStart
                    ];
                }
                break;

            default:
                Log::warning('Unknown frequency, defaulting to monthly', [
                    'frequency' => $measurementFrequency,
                    'normalized' => $normalizedFrequency
                ]);
                // Fallback to monthly
                for ($i = 0; $i < 12; $i++) {
                    $periodStart = Carbon::create($currentYear, $startMonth, 1)->addMonths($i);
                    $periodEnd = $periodStart->copy()->addMonth()->subDay();
                    $periods[] = [
                        'start' => $periodStart->format('Y-m-d'),
                        'end' => $periodEnd->format('Y-m-d'),
                        'label' => $periodStart->format('M Y'),
                        'frequency' => 'monthly',
                        'fiscal_year' => $currentYear,
                        'fiscal_start' => $fiscalYearStart
                    ];
                }
                break;
        }

        Log::info('Generated required periods', [
            'count' => count($periods),
            'periods' => array_map(function($p) { return $p['label']; }, $periods)
        ]);

        return $periods;
    }

    /**
     * Get all measurements for a location with their status
     */
    public function getMeasurementsForLocation(Location $location)
    {
        return Measurement::where('location_id', $location->id)
            ->orderBy('period_start')
            ->get()
            ->map(function($measurement) {
                return [
                    'id' => $measurement->id,
                    'start' => $measurement->period_start->format('Y-m-d'),
                    'end' => $measurement->period_end->format('Y-m-d'),
                    'label' => $measurement->period_start->format('M Y') . ($measurement->frequency !== 'monthly' ? ' - ' . $measurement->period_end->format('M Y') : ''),
                    'frequency' => $measurement->frequency,
                    'status' => $measurement->status,
                    'has_data' => $measurement->measurementData()->exists(),
                    'created_at' => $measurement->created_at->format('Y-m-d H:i:s')
                ];
            });
    }
}
