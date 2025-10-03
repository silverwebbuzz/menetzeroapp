<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\EmissionSourceMaster;
use App\Models\LocationEmissionBoundary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmissionBoundaryController extends Controller
{
    /**
     * Show the emission boundaries page for a location
     */
    public function index(Location $location)
    {
        $user = Auth::user();
        
        // Check if user has access to this location
        if ($location->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this location.');
        }

        // Get all emission sources grouped by scope
        $scope1Sources = EmissionSourceMaster::byScope('Scope 1')->where('is_active', true)->get();
        $scope2Sources = EmissionSourceMaster::byScope('Scope 2')->where('is_active', true)->get();
        $scope3Sources = EmissionSourceMaster::byScope('Scope 3')->where('is_active', true)->get();

        // Get selected boundaries for this location
        $selectedBoundaries = $location->emissionBoundaries()
            ->where('is_selected', true)
            ->pluck('emission_source_id')
            ->toArray();

        return view('emission-boundaries.index', compact(
            'location',
            'scope1Sources',
            'scope2Sources', 
            'scope3Sources',
            'selectedBoundaries'
        ));
    }

    /**
     * Store or update emission boundaries for a location
     */
    public function store(Request $request, Location $location)
    {
        $user = Auth::user();
        
        // Check if user has access to this location
        if ($location->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this location.');
        }

        $request->validate([
            'emission_sources' => 'array',
            'emission_sources.*' => 'exists:emission_sources_master,id',
        ]);

        // Get the selected emission source IDs
        $selectedSources = $request->input('emission_sources', []);

        // Delete all existing boundaries for this location
        $location->emissionBoundaries()->delete();

        // Create new boundaries for selected sources
        foreach ($selectedSources as $sourceId) {
            LocationEmissionBoundary::create([
                'location_id' => $location->id,
                'emission_source_id' => $sourceId,
                'is_selected' => true,
            ]);
        }

        return redirect()->route('locations.index')
            ->with('success', 'Emission boundaries updated successfully!');
    }
}
