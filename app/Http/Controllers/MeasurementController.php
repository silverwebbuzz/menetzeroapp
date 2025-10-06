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

        $measurements = $query->orderBy('created_at', 'desc')->paginate(50);
        
        // Get locations for filter dropdown
        $locations = Location::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Group and sort measurements for display
        $groupedMeasurements = $this->groupAndSortMeasurements($measurements, $user);

        return view('measurements.index', compact('measurements', 'locations', 'groupedMeasurements'));
    }

    /**
     * Group and sort measurements for display
     */
    private function groupAndSortMeasurements($measurements, $user)
    {
        // Get current fiscal year (assuming calendar year for simplicity)
        $currentYear = date('Y');
        
        // Group by location first
        $groupedByLocation = $measurements->groupBy(function($measurement) {
            return $measurement->location->name;
        });
        
        // Sort locations: Head office first, then alphabetically
        $sortedLocations = $groupedByLocation->sortBy(function($locationMeasurements, $locationName) use ($user) {
            $location = $locationMeasurements->first()->location;
            
            // Head office gets priority (0), others get alphabetical order (1+)
            if ($location->is_head_office) {
                return 0; // Head office first
            }
            
            // For non-head offices, use alphabetical order
            return 1;
        });
        
        // For each location, group by year and sort
        $finalGrouped = $sortedLocations->map(function($locationMeasurements) use ($currentYear) {
            $groupedByYear = $locationMeasurements->groupBy('fiscal_year');
            
            // Sort years: current year first, then descending
            return $groupedByYear->sortByDesc(function($yearMeasurements, $year) use ($currentYear) {
                if ($year == $currentYear) {
                    return 0; // Current year first
                }
                return $year; // Then by year descending
            });
        });
        
        return $finalGrouped;
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
            'staff_count' => 'nullable|integer|min:1|max:10000',
            'staff_work_from_home' => 'nullable|boolean',
            'work_from_home_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $oldValues = $measurement->toArray();

        // Prepare update data
        $updateData = [];
        
        if ($request->has('notes')) {
            $updateData['notes'] = $request->notes;
        }
        
        if ($request->has('staff_count')) {
            $updateData['staff_count'] = $request->staff_count;
        }
        
        if ($request->has('staff_work_from_home')) {
            $updateData['staff_work_from_home'] = $request->boolean('staff_work_from_home');
            // If work from home is disabled, set percentage to 0
            if (!$request->boolean('staff_work_from_home')) {
                $updateData['work_from_home_percentage'] = 0.00;
            }
        }
        
        if ($request->has('work_from_home_percentage')) {
            $updateData['work_from_home_percentage'] = $request->work_from_home_percentage;
        }

        $measurement->update($updateData);

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

        // Determine what was updated for the success message
        $updatedFields = [];
        if (isset($updateData['notes'])) $updatedFields[] = 'notes';
        if (isset($updateData['staff_count'])) $updatedFields[] = 'staff information';
        if (isset($updateData['staff_work_from_home'])) $updatedFields[] = 'work from home settings';
        
        $message = !empty($updatedFields) 
            ? 'Measurement ' . implode(', ', $updatedFields) . ' updated successfully.'
            : 'Measurement updated successfully.';

        return redirect()->route('measurements.show', $measurement)
            ->with('success', $message);
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
