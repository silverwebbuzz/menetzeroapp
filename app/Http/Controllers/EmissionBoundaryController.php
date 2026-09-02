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

        // getActiveCompany() resolves the workspace a consultant is acting in.
        // $user->company_id is the consultant's own agency org, so comparing
        // against it 403s on every managed-client location.
        $company = $user->getActiveCompany();

        if (!$company || $location->company_id !== $company->id) {
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

        // getActiveCompany() resolves the workspace a consultant is acting in.
        // $user->company_id is the consultant's own agency org, so comparing
        // against it 403s on every managed-client location.
        $company = $user->getActiveCompany();

        if (!$company || $location->company_id !== $company->id) {
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

        // The page shows all three scopes at once and posts them together,
        // so there is no tab sequence left to advance through. The old
        // action/current_tab branch drove Next between tabs; it went out with
        // the tabs. Scopes with nothing ticked are still written above as an
        // empty array, which is what makes unticking everything persist.
        return redirect()->route('locations.index')
            ->with('success', 'Emission boundaries updated for ' . $location->name . '.');
    }
}
