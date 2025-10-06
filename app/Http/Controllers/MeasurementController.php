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
        \Log::info('Measurement store called with data:', $request->all());
        
        $request->validate([
            'location_id' => 'required|exists:locations,id',
            'measurement_id' => 'required|exists:measurements,id',
        ]);
        
        \Log::info('Validation passed');

        $user = Auth::user();
        
        // Check if user has access to this location
        $location = Location::where('id', $request->location_id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        // Get the selected measurement
        $measurement = Measurement::where('id', $request->measurement_id)
            ->where('location_id', $request->location_id)
            ->firstOrFail();

        \Log::info('Redirecting to existing measurement. ID: ' . $measurement->id . ', Location: ' . $measurement->location_id);
        
        $redirectUrl = route('measurements.show', $measurement);
        \Log::info('Redirecting to: ' . $redirectUrl);
        
        return redirect()->route('measurements.show', $measurement)
            ->with('success', 'Measurement selected. You can now add emission data.');
    }

    /**
     * Display the specified measurement
     */
    public function show(Measurement $measurement)
    {
        try {
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
            $emissionBoundaries = collect();
            
            try {
                $emissionBoundaries = $measurement->location->emissionBoundaries()
                    ->get()
                    ->groupBy('scope');
                    
                \Log::info('Emission boundaries loaded successfully', [
                    'count' => $emissionBoundaries->count(),
                    'scopes' => $emissionBoundaries->keys()->toArray()
                ]);
            } catch (\Exception $e) {
                \Log::warning('Could not load emission boundaries: ' . $e->getMessage());
                // Continue with empty collection
            }

            \Log::info('About to return measurements.show view', [
                'measurement_id' => $measurement->id,
                'emission_boundaries_count' => $emissionBoundaries->count()
            ]);

            return view('measurements.show', compact('measurement', 'emissionBoundaries'));
        } catch (\Exception $e) {
            \Log::error('Error in MeasurementController@show: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to load measurement details: ' . $e->getMessage()]);
        }
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

            // Use the new service to get measurements
            $service = app(\App\Services\MeasurementPeriodService::class);
            $measurements = $service->getMeasurementsForLocation($location);
            
            \Log::info('Getting measurements for location: ' . $location->name, [
                'count' => $measurements->count()
            ]);
            
            return response()->json([
                'measurements' => $measurements,
                'location' => [
                    'name' => $location->name,
                    'fiscal_year_start' => $location->fiscal_year_start,
                    'measurement_frequency' => $location->measurement_frequency
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting measurements: ' . $e->getMessage(), [
                'location_id' => $location->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to load measurements: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Show form to calculate emissions for a specific source
     */
    public function calculateSource(Measurement $measurement, $sourceId)
    {
        $user = Auth::user();
        
        // Check if user has access to this measurement
        if ($measurement->location->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this measurement.');
        }

        $emissionSource = \App\Models\EmissionSource::findOrFail($sourceId);
        $existingData = $measurement->measurementData()
            ->where('emission_source_id', $sourceId)
            ->first();

        return view('measurements.calculate-source', compact('measurement', 'emissionSource', 'existingData'));
    }

    /**
     * Store emission data for a specific source
     */
    public function storeSourceData(Request $request, Measurement $measurement, $sourceId)
    {
        $user = Auth::user();
        
        // Check if user has access to this measurement
        if ($measurement->location->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this measurement.');
        }

        $emissionSource = \App\Models\EmissionSource::findOrFail($sourceId);
        
        $request->validate([
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'calculation_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Get emission factor for this source
        $emissionFactor = \App\Models\EmissionFactor::where('emission_source_id', $sourceId)
            ->where('scope', $emissionSource->scope)
            ->where('is_active', true)
            ->first();

        if (!$emissionFactor) {
            return back()->withErrors(['error' => 'No emission factor found for this source.']);
        }

        // Calculate CO2e
        $calculatedCo2e = $request->quantity * $emissionFactor->factor_value;

        DB::beginTransaction();
        try {
            \App\Models\MeasurementData::create([
                'measurement_id' => $measurement->id,
                'emission_source_id' => $sourceId,
                'quantity' => $request->quantity,
                'unit' => $request->unit,
                'calculated_co2e' => $calculatedCo2e,
                'scope' => $emissionSource->scope,
                'calculation_method' => $request->calculation_method,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return redirect()->route('measurements.show', $measurement)
                ->with('success', 'Emission data saved successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to save emission data. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Show form to edit emission data for a specific source
     */
    public function editSource(Measurement $measurement, $sourceId)
    {
        $user = Auth::user();
        
        // Check if user has access to this measurement
        if ($measurement->location->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this measurement.');
        }

        $emissionSource = \App\Models\EmissionSource::findOrFail($sourceId);
        $existingData = $measurement->measurementData()
            ->where('emission_source_id', $sourceId)
            ->firstOrFail();

        return view('measurements.edit-source', compact('measurement', 'emissionSource', 'existingData'));
    }

    /**
     * Update emission data for a specific source
     */
    public function updateSourceData(Request $request, Measurement $measurement, $sourceId)
    {
        $user = Auth::user();
        
        // Check if user has access to this measurement
        if ($measurement->location->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this measurement.');
        }

        $emissionSource = \App\Models\EmissionSource::findOrFail($sourceId);
        $existingData = $measurement->measurementData()
            ->where('emission_source_id', $sourceId)
            ->firstOrFail();
        
        $request->validate([
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'calculation_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Get emission factor for this source
        $emissionFactor = \App\Models\EmissionFactor::where('emission_source_id', $sourceId)
            ->where('scope', $emissionSource->scope)
            ->where('is_active', true)
            ->first();

        if (!$emissionFactor) {
            return back()->withErrors(['error' => 'No emission factor found for this source.']);
        }

        // Calculate CO2e
        $calculatedCo2e = $request->quantity * $emissionFactor->factor_value;

        DB::beginTransaction();
        try {
            $existingData->update([
                'quantity' => $request->quantity,
                'unit' => $request->unit,
                'calculated_co2e' => $calculatedCo2e,
                'calculation_method' => $request->calculation_method,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return redirect()->route('measurements.show', $measurement)
                ->with('success', 'Emission data updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update emission data. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Delete emission data for a specific source
     */
    public function deleteSourceData(Measurement $measurement, $sourceId)
    {
        $user = Auth::user();
        
        // Check if user has access to this measurement
        if ($measurement->location->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this measurement.');
        }

        $existingData = $measurement->measurementData()
            ->where('emission_source_id', $sourceId)
            ->first();

        if ($existingData) {
            $existingData->delete();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'No data found to delete.']);
    }


}
