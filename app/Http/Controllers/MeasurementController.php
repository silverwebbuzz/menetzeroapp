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
            $availablePeriods = $this->getAvailablePeriods($location);
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
     * Get available measurement periods for a location
     */
    private function getAvailablePeriods(Location $location)
    {
        $periods = [];
        $currentYear = date('Y');
        $fiscalYearStart = $location->fiscal_year_start_month;
        $measurementFrequency = $location->measurement_frequency;

        // Get fiscal year start month number
        $monthMap = [
            'JAN' => 1, 'FEB' => 2, 'MAR' => 3, 'APR' => 4,
            'MAY' => 5, 'JUN' => 6, 'JUL' => 7, 'AUG' => 8,
            'SEP' => 9, 'OCT' => 10, 'NOV' => 11, 'DEC' => 12
        ];
        $startMonth = $monthMap[$fiscalYearStart];

        // Generate periods based on frequency
        switch ($measurementFrequency) {
            case 'annually':
                $periods[] = [
                    'start' => Carbon::create($currentYear, $startMonth, 1),
                    'end' => Carbon::create($currentYear, $startMonth, 1)->addYear()->subDay(),
                    'label' => "FY {$currentYear} (Annual)"
                ];
                break;

            case 'half_yearly':
                for ($i = 0; $i < 2; $i++) {
                    $periodStart = Carbon::create($currentYear, $startMonth, 1)->addMonths($i * 6);
                    $periodEnd = $periodStart->copy()->addMonths(6)->subDay();
                    $periods[] = [
                        'start' => $periodStart,
                        'end' => $periodEnd,
                        'label' => $periodStart->format('M Y') . ' - ' . $periodEnd->format('M Y')
                    ];
                }
                break;

            case 'quarterly':
                for ($i = 0; $i < 4; $i++) {
                    $periodStart = Carbon::create($currentYear, $startMonth, 1)->addMonths($i * 3);
                    $periodEnd = $periodStart->copy()->addMonths(3)->subDay();
                    $periods[] = [
                        'start' => $periodStart,
                        'end' => $periodEnd,
                        'label' => $periodStart->format('M Y') . ' - ' . $periodEnd->format('M Y')
                    ];
                }
                break;

            case 'monthly':
                for ($i = 0; $i < 12; $i++) {
                    $periodStart = Carbon::create($currentYear, $startMonth, 1)->addMonths($i);
                    $periodEnd = $periodStart->copy()->addMonth()->subDay();
                    $periods[] = [
                        'start' => $periodStart,
                        'end' => $periodEnd,
                        'label' => $periodStart->format('M Y')
                    ];
                }
                break;
        }

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
