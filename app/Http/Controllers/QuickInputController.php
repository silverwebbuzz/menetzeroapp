<?php

namespace App\Http\Controllers;

use App\Models\Measurement;
use App\Models\MeasurementData;
use App\Models\Location;
use App\Models\EmissionSourceMaster;
use App\Models\EmissionFactor;
use App\Models\EmissionSourceFormField;
use App\Models\EmissionIndustryLabel;
use App\Models\EmissionFactorSelectionRule;
use App\Models\EmissionGwpValue;
use App\Models\EmissionUnitConversion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QuickInputController extends Controller
{
    /**
     * Display a listing of Quick Input entries
     */
    public function index(Request $request)
    {
        $this->requirePermission('measurements.view', null, ['measurements.*', 'manage_measurements']);
        
        $user = Auth::user();
        $company = $user->getActiveCompany();
        
        if (!$company) {
            abort(403, 'No active company found.');
        }
        
        // Get all measurement_data entries for user's company
        $query = MeasurementData::with(['measurement.location', 'emissionSource'])
            ->whereHas('measurement', function($q) use ($company) {
                $q->whereHas('location', function($locQuery) use ($company) {
                    $locQuery->where('company_id', $company->id);
                });
            });
        
        // Apply filters
        if ($request->filled('scope')) {
            $query->where('scope', $request->scope);
        }
        
        if ($request->filled('location_id')) {
            $query->whereHas('measurement', function($q) use ($request) {
                $q->where('location_id', $request->location_id);
            });
        }
        
        if ($request->filled('fiscal_year')) {
            $query->whereHas('measurement', function($q) use ($request) {
                $q->where('fiscal_year', $request->fiscal_year);
            });
        }
        
        if ($request->filled('source_id')) {
            $query->where('emission_source_id', $request->source_id);
        }
        
        $entries = $query->orderBy('entry_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);
        
        // Get locations for filter dropdown
        $locations = Location::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        // Get emission sources for filter dropdown
        $sources = EmissionSourceMaster::where('is_quick_input', true)
            ->orderBy('scope')
            ->orderBy('quick_input_order')
            ->get();
        
        // Calculate summary statistics
        $summary = $this->calculateSummary($company->id, $request);
        
        return view('quick-input.index', compact('entries', 'locations', 'sources', 'summary'));
    }
    
    /**
     * Show Quick Input form for a specific emission source
     */
    public function show($scope, $slug)
    {
        $this->requirePermission('measurements.add', null, ['measurements.*', 'manage_measurements']);
        
        $user = Auth::user();
        $company = $user->getActiveCompany();
        
        if (!$company) {
            abort(403, 'No active company found.');
        }
        
        // Get emission source by slug
        $emissionSource = EmissionSourceMaster::where('quick_input_slug', $slug)
            ->where('scope', 'Scope ' . $scope)
            ->where('is_quick_input', true)
            ->firstOrFail();
        
        // Get form fields
        $formFields = EmissionSourceFormField::where('emission_source_id', $emissionSource->id)
            ->orderBy('display_order')
            ->get();
        
        // Get user-friendly name based on company's industry
        $userFriendlyName = $this->getUserFriendlyName($emissionSource->id, $company->industry_category_id ?? null);
        
        // Get available locations
        $locations = Location::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        // Get available units from emission factors
        $availableUnits = EmissionFactor::where('emission_source_id', $emissionSource->id)
            ->distinct()
            ->pluck('unit')
            ->toArray();
        
        return view('quick-input.show', compact(
            'emissionSource',
            'formFields',
            'userFriendlyName',
            'locations',
            'availableUnits',
            'scope',
            'slug'
        ));
    }
    
    /**
     * Store a new Quick Input entry
     */
    public function store(Request $request, $scope, $slug)
    {
        $this->requirePermission('measurements.add', null, ['measurements.*', 'manage_measurements']);
        
        $user = Auth::user();
        $company = $user->getActiveCompany();
        
        if (!$company) {
            abort(403, 'No active company found.');
        }
        
        // Get emission source
        $emissionSource = EmissionSourceMaster::where('quick_input_slug', $slug)
            ->where('scope', 'Scope ' . $scope)
            ->where('is_quick_input', true)
            ->firstOrFail();
        
        // Validate basic required fields
        $request->validate([
            'location_id' => 'required|exists:locations,id',
            'fiscal_year' => 'required|integer|min:2000|max:2100',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string',
            'entry_date' => 'required|date',
        ]);
        
        // Verify location belongs to company
        $location = Location::where('id', $request->location_id)
            ->where('company_id', $company->id)
            ->firstOrFail();
        
        DB::beginTransaction();
        try {
            // Get or create measurement record
            $measurement = $this->getOrCreateMeasurement($request->location_id, $request->fiscal_year);
            
            // Select emission factor
            $emissionFactor = $this->selectEmissionFactor($emissionSource->id, $request->all());
            
            if (!$emissionFactor) {
                return back()->withErrors(['quantity' => 'No suitable emission factor found for the selected criteria. Please contact support.'])->withInput();
            }
            
            // Calculate CO2e
            $calculation = $this->calculateCO2e($request->quantity, $emissionFactor, $request->unit);
            
            // Prepare additional data (form field values)
            $additionalData = [];
            $formFields = EmissionSourceFormField::where('emission_source_id', $emissionSource->id)->get();
            foreach ($formFields as $field) {
                if ($request->has($field->field_name)) {
                    $additionalData[$field->field_name] = $request->input($field->field_name);
                }
            }
            
            // Create measurement_data record
            $measurementData = MeasurementData::create([
                'measurement_id' => $measurement->id,
                'emission_source_id' => $emissionSource->id,
                'quantity' => $request->quantity,
                'unit' => $request->unit,
                'calculated_co2e' => $calculation['co2e'],
                'co2_emissions' => $calculation['co2'] ?? null,
                'ch4_emissions' => $calculation['ch4'] ?? null,
                'n2o_emissions' => $calculation['n2o'] ?? null,
                'scope' => $emissionSource->scope,
                'entry_date' => $request->entry_date,
                'emission_factor_id' => $emissionFactor->id,
                'gwp_version_used' => $emissionFactor->gwp_version ?? 'AR6',
                'additional_data' => !empty($additionalData) ? json_encode($additionalData) : null,
                'notes' => $request->notes ?? null,
                'created_by' => $user->id,
            ]);
            
            // Update measurement totals
            $this->updateMeasurementTotals($measurement->id);
            
            DB::commit();
            
            return redirect()->route('quick-input.index')
                ->with('success', 'Emission data added successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Quick Input Store Error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'An error occurred while saving. Please try again.'])->withInput();
        }
    }
    
    /**
     * Calculate CO2e emissions (AJAX endpoint)
     */
    public function calculate(Request $request)
    {
        $request->validate([
            'emission_source_id' => 'required|exists:emission_sources_master,id',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string',
        ]);
        
        $emissionSource = EmissionSourceMaster::findOrFail($request->emission_source_id);
        
        // Select emission factor
        $emissionFactor = $this->selectEmissionFactor($emissionSource->id, $request->all());
        
        if (!$emissionFactor) {
            return response()->json([
                'success' => false,
                'message' => 'No suitable emission factor found for the selected criteria.'
            ], 400);
        }
        
        // Calculate CO2e
        $calculation = $this->calculateCO2e($request->quantity, $emissionFactor, $request->unit);
        
        return response()->json([
            'success' => true,
            'calculation' => $calculation,
            'factor' => [
                'id' => $emissionFactor->id,
                'unit' => $emissionFactor->unit,
                'region' => $emissionFactor->region,
                'source_standard' => $emissionFactor->source_standard,
            ]
        ]);
    }
    
    /**
     * Get or create measurement record
     */
    private function getOrCreateMeasurement($locationId, $fiscalYear)
    {
        $measurement = Measurement::where('location_id', $locationId)
            ->where('fiscal_year', $fiscalYear)
            ->first();
        
        if (!$measurement) {
            $measurement = Measurement::create([
                'location_id' => $locationId,
                'fiscal_year' => $fiscalYear,
                'period_start' => Carbon::create($fiscalYear, 1, 1)->startOfYear(),
                'period_end' => Carbon::create($fiscalYear, 12, 31)->endOfYear(),
                'frequency' => 'annually',
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);
        }
        
        return $measurement;
    }
    
    /**
     * Select appropriate emission factor
     */
    private function selectEmissionFactor($emissionSourceId, $conditions)
    {
        // Get selection rules for this source
        $rules = EmissionFactorSelectionRule::where('emission_source_id', $emissionSourceId)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();
        
        // Try to match rules
        foreach ($rules as $rule) {
            if ($this->matchRuleConditions($rule, $conditions)) {
                return EmissionFactor::find($rule->emission_factor_id);
            }
        }
        
        // If no rule matches, get default factor
        return EmissionFactor::where('emission_source_id', $emissionSourceId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first() ?? EmissionFactor::where('emission_source_id', $emissionSourceId)
                ->where('is_active', true)
                ->orderBy('priority', 'desc')
                ->first();
    }
    
    /**
     * Match rule conditions
     */
    private function matchRuleConditions($rule, $conditions)
    {
        if (!$rule->conditions) {
            return true; // No conditions = match all
        }
        
        $ruleConditions = is_array($rule->conditions) ? $rule->conditions : json_decode($rule->conditions, true);
        
        if (!is_array($ruleConditions)) {
            return true;
        }
        
        foreach ($ruleConditions as $key => $value) {
            if ($value !== null && (!isset($conditions[$key]) || $conditions[$key] != $value)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Calculate CO2e emissions
     */
    private function calculateCO2e($quantity, $emissionFactor, $userUnit)
    {
        // Convert unit if needed
        $convertedQuantity = $this->convertUnit($quantity, $userUnit, $emissionFactor->unit);
        
        // If factor has separate gas factors, calculate multi-gas
        if ($emissionFactor->co2_factor || $emissionFactor->ch4_factor || $emissionFactor->n2o_factor) {
            $co2 = $convertedQuantity * ($emissionFactor->co2_factor ?? 0);
            $ch4 = $convertedQuantity * ($emissionFactor->ch4_factor ?? 0);
            $n2o = $convertedQuantity * ($emissionFactor->n2o_factor ?? 0);
            
            // Get GWP values (AR6)
            $gwpVersion = $emissionFactor->gwp_version ?? 'AR6';
            $gwpCh4 = EmissionGwpValue::where('gas_code', 'CH4_FOSSIL')
                ->where('gwp_version', $gwpVersion)
                ->first()->gwp_100_year ?? 27.2;
            $gwpN2O = EmissionGwpValue::where('gas_code', 'N2O')
                ->where('gwp_version', $gwpVersion)
                ->first()->gwp_100_year ?? 273;
            
            $co2e = $co2 + ($ch4 * $gwpCh4) + ($n2o * $gwpN2O);
            
            return [
                'co2e' => round($co2e, 6),
                'co2' => round($co2, 6),
                'ch4' => round($ch4, 6),
                'n2o' => round($n2o, 6),
            ];
        }
        
        // Single gas factor
        $factor = $emissionFactor->total_co2e_factor ?? $emissionFactor->factor_value;
        $co2e = $convertedQuantity * $factor;
        
        return [
            'co2e' => round($co2e, 6),
            'co2' => null,
            'ch4' => null,
            'n2o' => null,
        ];
    }
    
    /**
     * Convert unit
     */
    private function convertUnit($value, $fromUnit, $toUnit)
    {
        if ($fromUnit === $toUnit) {
            return $value;
        }
        
        $conversion = EmissionUnitConversion::where('from_unit', $fromUnit)
            ->where('to_unit', $toUnit)
            ->where('is_active', true)
            ->first();
        
        if ($conversion) {
            return $value * $conversion->conversion_factor;
        }
        
        // If no conversion found, return original value
        return $value;
    }
    
    /**
     * Get user-friendly name based on industry
     */
    private function getUserFriendlyName($emissionSourceId, $industryCategoryId)
    {
        if (!$industryCategoryId) {
            return null;
        }
        
        $label = EmissionIndustryLabel::where('emission_source_id', $emissionSourceId)
            ->where('industry_category_id', $industryCategoryId)
            ->where('is_active', true)
            ->first();
        
        return $label ? $label->user_friendly_name : null;
    }
    
    /**
     * Update measurement totals
     */
    private function updateMeasurementTotals($measurementId)
    {
        $measurement = Measurement::findOrFail($measurementId);
        
        $totals = MeasurementData::where('measurement_id', $measurementId)
            ->selectRaw('
                SUM(calculated_co2e) as total_co2e,
                SUM(CASE WHEN scope = "Scope 1" THEN calculated_co2e ELSE 0 END) as scope_1_co2e,
                SUM(CASE WHEN scope = "Scope 2" THEN calculated_co2e ELSE 0 END) as scope_2_co2e,
                SUM(CASE WHEN scope = "Scope 3" THEN calculated_co2e ELSE 0 END) as scope_3_co2e
            ')
            ->first();
        
        $measurement->update([
            'total_co2e' => $totals->total_co2e ?? 0,
            'scope_1_co2e' => $totals->scope_1_co2e ?? 0,
            'scope_2_co2e' => $totals->scope_2_co2e ?? 0,
            'scope_3_co2e' => $totals->scope_3_co2e ?? 0,
            'co2e_calculated_at' => now(),
        ]);
    }
    
    /**
     * Calculate summary statistics
     */
    private function calculateSummary($companyId, $request)
    {
        $query = MeasurementData::with('measurement.location')
            ->whereHas('measurement', function($q) use ($companyId) {
                $q->whereHas('location', function($locQuery) use ($companyId) {
                    $locQuery->where('company_id', $companyId);
                });
            });
        
        // Apply same filters as main query
        if ($request->filled('scope')) {
            $query->where('scope', $request->scope);
        }
        
        if ($request->filled('location_id')) {
            $query->whereHas('measurement', function($q) use ($request) {
                $q->where('location_id', $request->location_id);
            });
        }
        
        if ($request->filled('fiscal_year')) {
            $query->whereHas('measurement', function($q) use ($request) {
                $q->where('fiscal_year', $request->fiscal_year);
            });
        }
        
        $summary = $query->selectRaw('
            COUNT(*) as total_entries,
            SUM(calculated_co2e) as total_co2e,
            SUM(CASE WHEN scope = "Scope 1" THEN calculated_co2e ELSE 0 END) as scope_1_co2e,
            SUM(CASE WHEN scope = "Scope 2" THEN calculated_co2e ELSE 0 END) as scope_2_co2e,
            SUM(CASE WHEN scope = "Scope 3" THEN calculated_co2e ELSE 0 END) as scope_3_co2e
        ')->first();
        
        return $summary;
    }
}

