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
        $this->requirePermission('locations.*', null, ['manage_locations']);

        $user = Auth::user();
        $company = $user->getActiveCompany();

        if (!$company || $location->company_id !== $company->id) {
            abort(403, 'Unauthorized access to this location.');
        }

        // Same required set as store(). The edit form posts 15 fields; this
        // method used to validate 7 and write 7, so country, city, address,
        // location_type and the three utility toggles were silently dropped —
        // the form reported success while discarding half of what was typed.
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'country' => 'required|string|max:100',
            'location_type' => 'nullable|string|max:100',
            'staff_count' => 'required|integer|min:1',
            'staff_work_from_home' => 'nullable|boolean',
            'work_from_home_percentage' => 'nullable|numeric|min:0|max:100',
            'fiscal_year_start' => 'required|string|max:20',
            'is_head_office' => 'nullable|boolean',
            'receives_utility_bills' => 'nullable|boolean',
            'pays_electricity_proportion' => 'nullable|boolean',
            'shared_building_services' => 'nullable|boolean',
            'reporting_period' => 'required|integer|min:2020|max:2030',
            'measurement_frequency' => 'required|string|max:20',
        ], [
            'country.required' => 'Select a country — it determines which emission factors apply to this location.',
            'reporting_period.required' => 'Choose the year you are reporting on.',
            'measurement_frequency.required' => 'Choose how often you will record data.',
        ]);

        $oldFrequency       = $location->measurement_frequency;
        $oldReportingPeriod = $location->reporting_period;
        $oldFiscalYearStart = $location->fiscal_year_start;

        // Promoting this location to head office demotes the previous one.
        // Demoting the last head office is refused: reports assume one exists.
        $wantsHeadOffice = $request->boolean('is_head_office');

        if ($wantsHeadOffice && !$location->is_head_office) {
            $company->locations()->where('id', '!=', $location->id)
                ->update(['is_head_office' => false]);
        }

        if (!$wantsHeadOffice && $location->is_head_office) {
            $otherHeadOffice = $company->locations()
                ->where('id', '!=', $location->id)
                ->where('is_head_office', true)
                ->exists();

            if (!$otherHeadOffice) {
                return back()->withInput()->withErrors([
                    'is_head_office' => 'This is your only head office. Make another location the head office first.',
                ]);
            }
        }

        $location->update([
            'name' => $request->name,
            'address' => $request->address,
            'city' => $request->city,
            'country' => $request->country,
            'location_type' => $request->location_type,
            'staff_count' => $request->staff_count,
            'staff_work_from_home' => $request->boolean('staff_work_from_home'),
            'work_from_home_percentage' => $request->boolean('staff_work_from_home')
                ? ($request->work_from_home_percentage ?? 100)
                : null,
            'fiscal_year_start' => $request->fiscal_year_start,
            'is_head_office' => $wantsHeadOffice,
            'receives_utility_bills' => $request->boolean('receives_utility_bills'),
            'pays_electricity_proportion' => $request->boolean('pays_electricity_proportion'),
            'shared_building_services' => $request->boolean('shared_building_services'),
            'reporting_period' => $request->reporting_period,
            'measurement_frequency' => $request->measurement_frequency,
        ]);

        // Only regenerate periods when the settings that define them changed.
        // syncMeasurementPeriods() adds what is missing and keeps what exists,
        // so entered data survives; it never deletes periods.
        if ($oldFrequency !== $location->measurement_frequency
            || (int) $oldReportingPeriod !== (int) $location->reporting_period
            || $oldFiscalYearStart !== $location->fiscal_year_start) {
            app(\App\Services\MeasurementPeriodService::class)
                ->syncMeasurementPeriods($location, $user->id);
        }

        return back()->with('success', 'Location updated successfully!');
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
