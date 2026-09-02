<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\Company;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Auth;

class LocationController extends Controller
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function index()
    {
        $user = Auth::user();
        $company = $user->getActiveCompany();
        
        if (!$company) {
            abort(403, 'No active company found.');
        }

        $locations = $company->locations()
            ->when(request('search'), function($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%")
                      ->orWhere('country', 'like', "%{$search}%");
            })
            ->when(request('filter'), function($query, $filter) {
                if ($filter === 'active') {
                    $query->where('is_active', true);
                } elseif ($filter === 'inactive') {
                    $query->where('is_active', false);
                } elseif ($filter === 'head_office') {
                    $query->where('is_head_office', true);
                }
            })
            ->when(request('sort'), function($query, $sort) {
                if ($sort === 'name') {
                    $query->orderBy('name');
                } elseif ($sort === 'created') {
                    $query->orderBy('created_at', 'desc');
                } elseif ($sort === 'staff') {
                    $query->orderBy('staff_count', 'desc');
                }
            })
            ->orderBy('is_head_office', 'desc')
            ->orderBy('name')
            ->paginate(10);

        return view('locations.index', compact('locations', 'company'));
    }

    public function create()
    {
        $this->requirePermission('locations.*', null, ['manage_locations']);
        
        $user = Auth::user();
        $company = $user->getActiveCompany();
        
        if (!$company) {
            abort(403, 'No active company found.');
        }

        return view('locations.create', compact('company'));
    }

    public function store(Request $request)
    {
        $this->requirePermission('locations.*', null, ['manage_locations']);
        
        $user = Auth::user();
        $company = $user->getActiveCompany();
        
        if (!$company) {
            abort(403, 'No active company found.');
        }

        // Required set is narrow by design: a field is required only where the
        // calculation engine is wrong without it. country drives region-matched
        // emission factors; the three period fields decide whether any
        // measurement can be attached to this location at all. They were
        // nullable, which is how locations with zero periods were created.
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'country' => 'required|string|max:100',
            'location_type' => 'nullable|string|max:100',
            'staff_count' => 'required|integer|min:1',
            'staff_work_from_home' => 'boolean',
            'work_from_home_percentage' => 'nullable|numeric|min:0|max:100',
            'fiscal_year_start' => 'required|string|max:20',
            'is_head_office' => 'boolean',
            'receives_utility_bills' => 'boolean',
            'pays_electricity_proportion' => 'boolean',
            'shared_building_services' => 'boolean',
            'reporting_period' => 'required|integer|min:2020|max:2030',
            'measurement_frequency' => 'required|string|max:20',
        ], [
            'country.required' => 'Select a country — it determines which emission factors apply to this location.',
            'reporting_period.required' => 'Choose the year you are reporting on.',
            'measurement_frequency.required' => 'Choose how often you will record data.',
        ]);

        // Check location limit before creating. When the plan limit is hit we send the
        // user to the upgrade page so they can pick a paid plan with more locations.
        $limitCheck = $this->subscriptionService->canPerformAction($company->id, 'locations', 1);
        if (!$limitCheck['allowed']) {
            $this->denyEntitlement($limitCheck['message']);
        }

        // Check if this is the first location for the company
        $isFirstLocation = $company->locations()->count() === 0;
        $shouldBeHeadOffice = $request->boolean('is_head_office') || $isFirstLocation;
        
        // If this is set as head office, unset any existing head office
        if ($shouldBeHeadOffice) {
            $company->locations()->update(['is_head_office' => false]);
        }

        $location = $company->locations()->create([
            'name' => $request->name,
            'address' => $request->address,
            'city' => $request->city,
            'country' => $request->country,
            'location_type' => $request->location_type,
            'staff_count' => $request->staff_count,
            'staff_work_from_home' => $request->boolean('staff_work_from_home'),
            'work_from_home_percentage' => $request->work_from_home_percentage,
            'fiscal_year_start' => $request->fiscal_year_start ?? 'January',
            'is_head_office' => $shouldBeHeadOffice,
            'receives_utility_bills' => $request->boolean('receives_utility_bills'),
            'pays_electricity_proportion' => $request->boolean('pays_electricity_proportion'),
            'shared_building_services' => $request->boolean('shared_building_services'),
            'reporting_period' => $request->reporting_period,
            'measurement_frequency' => $request->measurement_frequency ?? 'Annually',
            'is_active' => true,
        ]);

        // Generate the measurement periods this location's settings imply.
        // Previously only the AJAX step path did this, so a location saved
        // through this method had nowhere to put data.
        app(\App\Services\MeasurementPeriodService::class)
            ->syncMeasurementPeriods($location, $user->id);

        if ($isFirstLocation || $request->boolean('onboarding')) {
            // setup_complete drives the one-time panel on the dashboard. Flashed
            // rather than persisted: it is a moment, not a state, and the panel
            // must not reappear on every later visit.
            return redirect()->route('client.dashboard')
                ->with('setup_complete', true);
        }

        return redirect()->route('locations.index')->with('success', 'Location created successfully!');
    }

    public function show(Location $location)
    {
        $user = Auth::user();
        $company = $user->getActiveCompany();
        
        if (!$company || $location->company_id !== $company->id) {
            abort(403, 'Unauthorized access to this location.');
        }
        return view('locations.show', compact('location'));
    }

    public function edit(Location $location)
    {
        $user = Auth::user();
        $company = $user->getActiveCompany();
        if (!$company || $location->company_id !== $company->id) {
            abort(403, 'Unauthorized access to this location.');
        }
        return view('locations.edit', compact('location'));
    }

    public function update(Request $request, Location $location)
    {
        \Log::info('=== LOCATION CONTROLLER UPDATE METHOD CALLED ===', [
            'location_id' => $location->id,
            'request_data' => $request->all(),
            'method' => $request->method(),
            'url' => $request->url()
        ]);

        try {
            $user = Auth::user();
            $company = $user->getActiveCompany();
        if (!$company || $location->company_id !== $company->id) {
                abort(403, 'Unauthorized access to this location.');
            }

            // Validation
            $request->validate([
                'name' => 'required|string|max:255',
                'staff_count' => 'required|integer|min:1',
                'staff_work_from_home' => 'nullable|boolean',
                'work_from_home_percentage' => 'nullable|numeric|min:0|max:100',
                'measurement_frequency' => 'nullable|string|max:20',
                'reporting_period' => 'nullable|integer|min:2020|max:2030',
                'fiscal_year_start' => 'nullable|string|max:20',
            ]);

            \Log::info('Validation passed, proceeding with update');

            // Store old values for comparison
            $oldFrequency = $location->measurement_frequency;
            $oldReportingPeriod = $location->reporting_period;
            $oldFiscalYearStart = $location->fiscal_year_start;

                // Update location
                $location->update([
                    'name' => $request->name,
                    'staff_count' => $request->staff_count,
                    'staff_work_from_home' => $request->boolean('staff_work_from_home'),
                    'work_from_home_percentage' => $request->work_from_home_percentage,
                    'measurement_frequency' => $request->measurement_frequency ?? 'Annually',
                    'reporting_period' => $request->reporting_period,
                    'fiscal_year_start' => $request->fiscal_year_start ?? 'January',
                ]);

            \Log::info('Location updated successfully', [
                'location_id' => $location->id,
                'old_frequency' => $oldFrequency,
                'new_frequency' => $location->measurement_frequency,
                'old_reporting_period' => $oldReportingPeriod,
                'new_reporting_period' => $location->reporting_period,
                'old_fiscal_year_start' => $oldFiscalYearStart,
                'new_fiscal_year_start' => $location->fiscal_year_start
            ]);

            // Check if measurement settings changed and sync measurements
            if ($oldFrequency != $location->measurement_frequency || 
                $oldReportingPeriod != $location->reporting_period || 
                $oldFiscalYearStart != $location->fiscal_year_start) {
                
                \Log::info('Measurement settings changed, syncing measurements');
                
                $service = app(\App\Services\MeasurementPeriodService::class);
                $service->syncMeasurementPeriods($location, $user->id);
                
                \Log::info('Measurement sync completed');
            }

            return back()->with('success', 'Location updated successfully!');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error in location update', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('Error updating location: ' . $e->getMessage(), [
                'location_id' => $location->id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Failed to update location: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy(Location $location)
    {
        $user = Auth::user();
        $company = $user->getActiveCompany();
        if (!$company || $location->company_id !== $company->id) {
            abort(403, 'Unauthorized access to this location.');
        }
        $location->delete();
        return redirect()->route('locations.index')->with('success', 'Location deleted successfully!');
    }

    public function toggleStatus(Location $location)
    {
        $user = Auth::user();
        $company = $user->getActiveCompany();
        if (!$company || $location->company_id !== $company->id) {
            abort(403, 'Unauthorized access to this location.');
        }
        
        $location->update(['is_active' => !$location->is_active]);
        
        $status = $location->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Location {$status} successfully!");
    }

    public function toggleHeadOffice(Location $location)
    {
        $user = Auth::user();
        $company = $user->getActiveCompany();
        if (!$company || $location->company_id !== $company->id) {
            abort(403, 'Unauthorized access to this location.');
        }
        
        if (!$location->is_head_office) {
            // Unset any existing head office
            $location->company->locations()->update(['is_head_office' => false]);
            $location->update(['is_head_office' => true]);
        } else {
            $location->update(['is_head_office' => false]);
        }
        
        return back()->with('success', 'Head office status updated successfully!');
    }
}
