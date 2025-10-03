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

        // Handle different actions
        $action = $request->input('action', 'save');
        
        if ($action === 'next') {
            // Determine next tab based on current selections
            $scope1Count = count(array_filter($selectedSources, function($id) {
                $source = \App\Models\EmissionSourceMaster::find($id);
                return $source && $source->scope === 'Scope 1';
            }));
            
            $scope2Count = count(array_filter($selectedSources, function($id) {
                $source = \App\Models\EmissionSourceMaster::find($id);
                return $source && $source->scope === 'Scope 2';
            }));
            
            // Redirect to next scope or back to form
            if ($scope1Count > 0 && $scope2Count === 0) {
                return redirect()->route('emission-boundaries.index', $location)
                    ->with('success', 'Scope 1 selections saved! Please continue with Scope 2.')
                    ->with('active_tab', 'scope2');
            } elseif ($scope2Count > 0) {
                return redirect()->route('emission-boundaries.index', $location)
                    ->with('success', 'Scope 2 selections saved! Please continue with Scope 3.')
                    ->with('active_tab', 'scope3');
            } else {
                return redirect()->route('emission-boundaries.index', $location)
                    ->with('success', 'Selections saved! Please continue with the next scope.')
                    ->with('active_tab', 'scope2');
            }
        } else {
            // Save and close - redirect to locations listing
            return redirect()->route('locations.index')
                ->with('success', 'Emission boundaries updated successfully!');
        }
    }
}
