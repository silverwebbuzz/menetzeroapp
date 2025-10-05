<?php

namespace App\Http\Controllers;

use App\Models\Measurement;
use App\Models\MeasurementData;
use App\Models\Location;
use App\Models\EmissionSourceMaster;
use App\Models\EmissionFactor;
use App\Models\MeasurementAuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MeasurementController extends Controller
{
    /**
     * Display a listing of measurements
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get measurements for user's company locations
        $query = Measurement::with(['location', 'creator'])
            ->whereHas('location', function($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });

        // Apply filters
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('fiscal_year')) {
            $query->where('fiscal_year', $request->fiscal_year);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('location', function($locationQuery) use ($search) {
                    $locationQuery->where('name', 'like', "%{$search}%");
                })->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $measurements = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // Get locations for filter dropdown
        $locations = Location::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('measurements.index', compact('measurements', 'locations'));
    }

    /**
     * Show the form for creating a new measurement
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        
        // Get locations for the user's company
        $locations = Location::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($locations->isEmpty()) {
            return redirect()->route('locations.index')
                ->with('error', 'Please add a location first before creating measurements.');
        }

        // If location is specified, get available periods
        $availablePeriods = [];
        if ($request->filled('location_id')) {
            $location = Location::findOrFail($request->location_id);
            $availablePeriods = $this->calculateAvailablePeriods($location);
        }

        return view('measurements.create', compact('locations', 'availablePeriods'));
    }

    /**
     * Store a newly created measurement
     */
    public function store(Request $request)
    {
        $request->validate([
            'location_id' => 'required|exists:locations,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'frequency' => 'required|in:monthly,quarterly,half_yearly,annually',
            'fiscal_year' => 'required|integer|min:2020|max:2030',
            'fiscal_year_start_month' => 'required|in:JAN,FEB,MAR,APR,MAY,JUN,JUL,AUG,SEP,OCT,NOV,DEC',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        
        // Check if user has access to this location
        $location = Location::where('id', $request->location_id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        // Check if measurement period already exists
        $existingMeasurement = Measurement::where('location_id', $request->location_id)
            ->where('period_start', $request->period_start)
            ->where('period_end', $request->period_end)
            ->first();

        if ($existingMeasurement) {
            return back()->withErrors(['period_start' => 'A measurement for this period already exists.'])
                ->withInput();
        }

        DB::beginTransaction();
        try {
            $measurement = Measurement::create([
                'location_id' => $request->location_id,
                'period_start' => $request->period_start,
                'period_end' => $request->period_end,
                'frequency' => $request->frequency,
                'status' => 'draft',
                'fiscal_year' => $request->fiscal_year,
                'fiscal_year_start_month' => $request->fiscal_year_start_month,
                'created_by' => $user->id,
                'notes' => $request->notes,
            ]);

            // Create audit trail entry
            MeasurementAuditTrail::create([
                'measurement_id' => $measurement->id,
                'action' => 'created',
                'new_values' => $measurement->toArray(),
                'changed_by' => $user->id,
                'changed_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            return redirect()->route('measurements.show', $measurement)
                ->with('success', 'Measurement created successfully. You can now add emission data.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create measurement. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Display the specified measurement
     */
    public function show(Measurement $measurement)
    {
        $user = Auth::user();
        
        // Check if user has access to this measurement
        if ($measurement->location->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this measurement.');
        }

        $measurement->load([
            'location',
            'creator',
            'measurementData.emissionSource',
            'auditTrail.changedBy'
        ]);

        // Get emission boundaries for this location
        $emissionBoundaries = $measurement->location->emissionBoundaries()
            ->with('emissionSource')
            ->get()
            ->groupBy('scope');

        return view('measurements.show', compact('measurement', 'emissionBoundaries'));
    }

    /**
     * Show the form for editing the specified measurement
     */
    public function edit(Measurement $measurement)
    {
        $user = Auth::user();
        
        // Check if user has access to this measurement
        if ($measurement->location->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this measurement.');
        }

        // Check if measurement can be edited
        if (!$measurement->canBeEdited()) {
            return redirect()->route('measurements.show', $measurement)
                ->with('error', 'This measurement cannot be edited in its current status.');
        }

        $measurement->load(['location', 'measurementData.emissionSource']);

        return view('measurements.edit', compact('measurement'));
    }

    /**
     * Update the specified measurement
     */
    public function update(Request $request, Measurement $measurement)
    {
        $user = Auth::user();
        
        // Check if user has access to this measurement
        if ($measurement->location->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this measurement.');
        }

        // Check if measurement can be edited
        if (!$measurement->canBeEdited()) {
            return redirect()->route('measurements.show', $measurement)
                ->with('error', 'This measurement cannot be edited in its current status.');
        }

        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $oldValues = $measurement->toArray();

        $measurement->update([
            'notes' => $request->notes,
        ]);

        // Create audit trail entry
        MeasurementAuditTrail::create([
            'measurement_id' => $measurement->id,
            'action' => 'updated',
            'old_values' => $oldValues,
            'new_values' => $measurement->toArray(),
            'changed_by' => $user->id,
            'changed_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('measurements.show', $measurement)
            ->with('success', 'Measurement updated successfully.');
    }

    /**
     * Remove the specified measurement
     */
    public function destroy(Measurement $measurement)
    {
        $user = Auth::user();
        
        // Check if user has access to this measurement
        if ($measurement->location->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this measurement.');
        }

        // Check if measurement can be deleted
        if (!in_array($measurement->status, ['draft'])) {
            return redirect()->route('measurements.show', $measurement)
                ->with('error', 'Only draft measurements can be deleted.');
        }

        DB::beginTransaction();
        try {
            // Create audit trail entry before deletion
            MeasurementAuditTrail::create([
                'measurement_id' => $measurement->id,
                'action' => 'deleted',
                'old_values' => $measurement->toArray(),
                'changed_by' => $user->id,
                'changed_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $measurement->delete();

            DB::commit();

            return redirect()->route('measurements.index')
                ->with('success', 'Measurement deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('measurements.show', $measurement)
                ->with('error', 'Failed to delete measurement. Please try again.');
        }
    }

    /**
     * Submit measurement for review
     */
    public function submit(Measurement $measurement)
    {
        $user = Auth::user();
        
        // Check if user has access to this measurement
        if ($measurement->location->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this measurement.');
        }

        // Check if measurement can be submitted
        if (!$measurement->canBeSubmitted()) {
            return redirect()->route('measurements.show', $measurement)
                ->with('error', 'Measurement cannot be submitted. Please add emission data first.');
        }

        $oldStatus = $measurement->status;
        $measurement->update(['status' => 'submitted']);

        // Create audit trail entry
        MeasurementAuditTrail::create([
            'measurement_id' => $measurement->id,
            'action' => 'status_changed',
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => 'submitted'],
            'changed_by' => $user->id,
            'changed_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'reason' => 'Measurement submitted for review',
        ]);

        return redirect()->route('measurements.show', $measurement)
            ->with('success', 'Measurement submitted successfully.');
    }

    /**
     * Get available periods for a location (AJAX)
     */
    public function getAvailablePeriods(Location $location)
    {
        try {
            $user = Auth::user();
            
            // Check if user has access to this location
            if ($location->company_id !== $user->company_id) {
                return response()->json(['error' => 'Unauthorized access'], 403);
            }

            \Log::info('Getting periods for location: ' . $location->name, [
                'fiscal_year_start' => $location->fiscal_year_start,
                'measurement_frequency' => $location->measurement_frequency,
                'all_attributes' => $location->getAttributes()
            ]);

            $periods = $this->calculateAvailablePeriods($location);
            
            \Log::info('Generated periods:', $periods->toArray());
            
            return response()->json([
                'periods' => $periods,
                'location' => [
                    'name' => $location->name,
                    'fiscal_year_start' => $location->fiscal_year_start,
                    'measurement_frequency' => $location->measurement_frequency
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting available periods: ' . $e->getMessage(), [
                'location_id' => $location->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to load periods: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Calculate available measurement periods for a location
     */
    private function calculateAvailablePeriods(Location $location)
    {
        $periods = [];
        $currentYear = $location->reporting_period ?? date('Y'); // Use location's reporting period
        $fiscalYearStart = $location->fiscal_year_start ?? 'JAN'; // Default to January
        $measurementFrequency = $location->measurement_frequency ?? 'monthly'; // Default to monthly for testing
        
        \Log::info('Using settings:', [
            'fiscalYearStart' => $fiscalYearStart,
            'measurementFrequency' => $measurementFrequency,
            'location_name' => $location->name
        ]);

        // Get fiscal year start month number
        $monthMap = [
            'JAN' => 1, 'FEB' => 2, 'MAR' => 3, 'APR' => 4,
            'MAY' => 5, 'JUN' => 6, 'JUL' => 7, 'AUG' => 8,
            'SEP' => 9, 'OCT' => 10, 'NOV' => 11, 'DEC' => 12,
            'January' => 1, 'February' => 2, 'March' => 3, 'April' => 4,
            'May' => 5, 'June' => 6, 'July' => 7, 'August' => 8,
            'September' => 9, 'October' => 10, 'November' => 11, 'December' => 12
        ];
        $startMonth = $monthMap[$fiscalYearStart] ?? 1; // Default to January if not found

        // Generate periods based on frequency
        switch (strtolower($measurementFrequency)) {
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
                \Log::info('Generating monthly periods', [
                    'currentYear' => $currentYear,
                    'startMonth' => $startMonth
                ]);
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
                \Log::info('Generated monthly periods', ['count' => count($periods)]);
                break;
                
            default:
                \Log::warning('Unknown frequency: ' . $measurementFrequency);
                // Fallback to monthly if unknown frequency
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

        // If no periods were generated, create a simple default period
        if (empty($periods)) {
            \Log::warning('No periods generated, creating default period');
            $periods[] = [
                'start' => Carbon::create($currentYear, 1, 1)->format('Y-m-d'),
                'end' => Carbon::create($currentYear, 12, 31)->format('Y-m-d'),
                'label' => "FY {$currentYear} (Default)",
                'frequency' => 'annually',
                'fiscal_year' => $currentYear,
                'fiscal_start' => 'JAN'
            ];
        }
        
        \Log::info('Final periods generated:', [
            'count' => count($periods),
            'periods' => $periods
        ]);

        // Filter out periods that already have measurements
        $existingPeriods = Measurement::where('location_id', $location->id)
            ->get()
            ->map(function($measurement) {
                return [
                    'start' => $measurement->period_start,
                    'end' => $measurement->period_end
                ];
            });

        return collect($periods)->filter(function($period) use ($existingPeriods) {
            return !$existingPeriods->contains(function($existing) use ($period) {
                return $existing['start']->format('Y-m-d') === $period['start']->format('Y-m-d') &&
                       $existing['end']->format('Y-m-d') === $period['end']->format('Y-m-d');
            });
        })->values();
    }
}
