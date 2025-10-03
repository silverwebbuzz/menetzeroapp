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
        $selectedBoundaries = [];
        $boundaries = $location->emissionBoundaries;
        
        foreach ($boundaries as $boundary) {
            $selectedBoundaries = array_merge($selectedBoundaries, $boundary->selected_sources ?? []);
        }

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

        // Group selected sources by scope
        $scope1Sources = [];
        $scope2Sources = [];
        $scope3Sources = [];

        foreach ($selectedSources as $sourceId) {
            $source = EmissionSourceMaster::find($sourceId);
            if ($source) {
                switch ($source->scope) {
                    case 'Scope 1':
                        $scope1Sources[] = $sourceId;
                        break;
                    case 'Scope 2':
                        $scope2Sources[] = $sourceId;
                        break;
                    case 'Scope 3':
                        $scope3Sources[] = $sourceId;
                        break;
                }
            }
        }

        // Update or create boundaries for each scope
        $scopes = [
            'Scope 1' => $scope1Sources,
            'Scope 2' => $scope2Sources,
            'Scope 3' => $scope3Sources,
        ];

        foreach ($scopes as $scope => $sources) {
            LocationEmissionBoundary::updateOrCreate(
                [
                    'location_id' => $location->id,
                    'scope' => $scope,
                ],
                [
                    'selected_sources' => $sources,
                ]
            );
        }

        // Handle different actions
        $action = $request->input('action', 'save');
        
        if ($action === 'next') {
            // Get the current tab from the form
            $currentTab = $request->input('current_tab', 'scope1');
            
            // Determine next tab based on current tab
            if ($currentTab === 'scope1') {
                return redirect()->route('emission-boundaries.index', $location)
                    ->with('success', 'Scope 1 selections saved! Please continue with Scope 2.')
                    ->with('active_tab', 'scope2');
            } elseif ($currentTab === 'scope2') {
                return redirect()->route('emission-boundaries.index', $location)
                    ->with('success', 'Scope 2 selections saved! Please continue with Scope 3.')
                    ->with('active_tab', 'scope3');
            } else {
                // If somehow on scope3, just redirect back to scope3
                return redirect()->route('emission-boundaries.index', $location)
                    ->with('success', 'Selections saved!')
                    ->with('active_tab', 'scope3');
            }
        } else {
            // Save and close - redirect to locations listing
            return redirect()->route('locations.index')
                ->with('success', 'Emission boundaries updated successfully!');
        }
    }
}
