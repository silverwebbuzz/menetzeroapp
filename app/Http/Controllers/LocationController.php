<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;

class LocationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $company = $user->company;
        
        if (!$company) {
            return redirect()->route('company.setup')->with('error', 'Please complete your business profile first.');
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
        $user = Auth::user();
        $company = $user->company;
        
        if (!$company) {
            return redirect()->route('company.setup')->with('error', 'Please complete your business profile first.');
        }

        return view('locations.create', compact('company'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $company = $user->company;
        
        if (!$company) {
            return redirect()->route('company.setup')->with('error', 'Please complete your business profile first.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'location_type' => 'nullable|string|max:100',
            'staff_count' => 'required|integer|min:1',
            'staff_work_from_home' => 'boolean',
            'fiscal_year_start' => 'nullable|string|max:20',
            'is_head_office' => 'boolean',
            'receives_utility_bills' => 'boolean',
            'pays_electricity_proportion' => 'boolean',
            'shared_building_services' => 'boolean',
            'reporting_period' => 'nullable|integer|min:2020|max:2030',
            'measurement_frequency' => 'nullable|string|max:20',
        ]);

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
            'fiscal_year_start' => $request->fiscal_year_start ?? 'January',
            'is_head_office' => $shouldBeHeadOffice,
            'receives_utility_bills' => $request->boolean('receives_utility_bills'),
            'pays_electricity_proportion' => $request->boolean('pays_electricity_proportion'),
            'shared_building_services' => $request->boolean('shared_building_services'),
            'reporting_period' => $request->reporting_period,
            'measurement_frequency' => $request->measurement_frequency ?? 'Annually',
            'is_active' => true,
        ]);

        return redirect()->route('locations.index')->with('success', 'Location created successfully!');
    }

    public function storeStep(Request $request, $step)
    {
        $user = Auth::user();
        $company = $user->company;
        
        if (!$company) {
            return redirect()->route('company.setup')->with('error', 'Please complete your business profile first.');
        }

        // Get or create location from session
        $locationId = session('location_id');
        $location = null;
        
        if ($locationId) {
            $location = Location::find($locationId);
        }
        
        if (!$location) {
            // Check if this is the first location for the company
            $isFirstLocation = $company->locations()->count() === 0;
            
            // Create new location with basic info
            $location = $company->locations()->create([
                'name' => $request->name ?? 'New Location',
                'is_active' => true,
                'is_head_office' => $isFirstLocation, // First location is automatically head office
            ]);
            session(['location_id' => $location->id]);
        }

        // Update location based on step
        switch ($step) {
            case 'step1':
                $request->validate([
                    'name' => 'required|string|max:255',
                    'address' => 'nullable|string|max:500',
                    'city' => 'nullable|string|max:100',
                    'country' => 'nullable|string|max:100',
                    'location_type' => 'nullable|string|max:100',
                    'fiscal_year_start' => 'nullable|string|max:20',
                    'receives_utility_bills' => 'boolean',
                    'pays_electricity_proportion' => 'boolean',
                    'shared_building_services' => 'boolean',
                ]);
                
                $location->update([
                    'name' => $request->name,
                    'address' => $request->address,
                    'city' => $request->city,
                    'country' => $request->country,
                    'location_type' => $request->location_type,
                    'fiscal_year_start' => $request->fiscal_year_start ?? 'January',
                    'receives_utility_bills' => $request->boolean('receives_utility_bills'),
                    'pays_electricity_proportion' => $request->boolean('pays_electricity_proportion'),
                    'shared_building_services' => $request->boolean('shared_building_services'),
                ]);
                break;
                
            case 'step2':
                $request->validate([
                    'staff_count' => 'required|integer|min:1',
                    'staff_work_from_home' => 'boolean',
                ]);
                
                $location->update([
                    'staff_count' => $request->staff_count,
                    'staff_work_from_home' => $request->boolean('staff_work_from_home'),
                ]);
                break;
                
            case 'step3':
                $request->validate([
                    'reporting_period' => 'nullable|integer|min:2020|max:2030',
                    'measurement_frequency' => 'nullable|string|max:20',
                ]);
                
                $location->update([
                    'reporting_period' => $request->reporting_period,
                    'measurement_frequency' => $request->measurement_frequency ?? 'Annually',
                ]);
                
                // Clear session after final step
                session()->forget('location_id');
                return redirect()->route('locations.index')->with('success', 'Location created successfully!');
        }

        return response()->json(['success' => true, 'location_id' => $location->id]);
    }

    public function show(Location $location)
    {
        $user = Auth::user();
        if ($location->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this location.');
        }
        return view('locations.show', compact('location'));
    }

    public function edit(Location $location)
    {
        $user = Auth::user();
        if ($location->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this location.');
        }
        return view('locations.edit', compact('location'));
    }

    public function update(Request $request, Location $location)
    {
        \Log::info('LocationController update method called', [
            'location_id' => $location->id,
            'request_data' => $request->all(),
            'method' => $request->method(),
            'url' => $request->url()
        ]);

        try {
            $user = Auth::user();
            if ($location->company_id !== $user->company_id) {
                abort(403, 'Unauthorized access to this location.');
            }

            // Minimal validation - only required fields
            $request->validate([
                'name' => 'required|string|max:255',
                'staff_count' => 'required|integer|min:1',
            ]);

            \Log::info('Validation passed, proceeding with update');

            // Minimal update - only essential fields
            $location->update([
                'name' => $request->name,
                'staff_count' => $request->staff_count,
                'measurement_frequency' => $request->measurement_frequency ?? 'Annually',
                'reporting_period' => $request->reporting_period,
                'fiscal_year_start' => $request->fiscal_year_start ?? 'January',
            ]);

            \Log::info('Location updated successfully', [
                'location_id' => $location->id,
                'new_measurement_frequency' => $location->measurement_frequency,
                'new_fiscal_year_start' => $location->fiscal_year_start,
                'new_reporting_period' => $location->reporting_period
            ]);

            return redirect()->route('locations.index')->with('success', 'Location updated successfully!');
            
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
        if ($location->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this location.');
        }
        $location->delete();
        return redirect()->route('locations.index')->with('success', 'Location deleted successfully!');
    }

    public function toggleStatus(Location $location)
    {
        $user = Auth::user();
        if ($location->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this location.');
        }
        
        $location->update(['is_active' => !$location->is_active]);
        
        $status = $location->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Location {$status} successfully!");
    }

    public function toggleHeadOffice(Location $location)
    {
        $user = Auth::user();
        if ($location->company_id !== $user->company_id) {
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
