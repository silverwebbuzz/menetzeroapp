<?php

namespace App\Http\Controllers;

use App\Models\Measurement;
use App\Models\MeasurementData;
use App\Models\Location;
use App\Models\EmissionSourceMaster;
use App\Models\EmissionFactor;
use App\Models\EmissionSourceFormField;
use App\Services\EmissionCalculationService;
use App\Services\MeasurementService;
use App\Services\QuickInputFormBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuickInputController extends Controller
{
    protected $calculationService;
    protected $measurementService;
    protected $formBuilder;

    public function __construct(
        EmissionCalculationService $calculationService,
        MeasurementService $measurementService,
        QuickInputFormBuilder $formBuilder
    ) {
        $this->calculationService = $calculationService;
        $this->measurementService = $measurementService;
        $this->formBuilder = $formBuilder;
    }
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
        $summary = $this->measurementService->calculateSummary($company->id, $request->all());
        
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
        $formFields = $this->formBuilder->buildForm($emissionSource->id);
        
        // Get user-friendly name based on company's industry
        $userFriendlyName = $this->calculationService->getUserFriendlyName(
            $emissionSource->id,
            $company->industry_category_id ?? null
        );
        
        // Get available locations
        $locations = Location::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        // Get available units from emission factors
        $availableUnits = $this->calculationService->getAvailableUnits($emissionSource->id);
        
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
            $measurement = $this->measurementService->getOrCreateMeasurement(
                $request->location_id,
                $request->fiscal_year
            );
            
            // Select emission factor
            $emissionFactor = $this->calculationService->selectEmissionFactor(
                $emissionSource->id,
                $request->all()
            );
            
            if (!$emissionFactor) {
                return back()->withErrors(['quantity' => 'No suitable emission factor found for the selected criteria. Please contact support.'])->withInput();
            }
            
            // Calculate CO2e
            $calculation = $this->calculationService->calculateCO2e(
                $request->quantity,
                $emissionFactor,
                $request->unit
            );
            
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
            $this->measurementService->updateMeasurementTotals($measurement->id);
            
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
        $emissionFactor = $this->calculationService->selectEmissionFactor(
            $emissionSource->id,
            $request->all()
        );
        
        if (!$emissionFactor) {
            return response()->json([
                'success' => false,
                'message' => 'No suitable emission factor found for the selected criteria.'
            ], 400);
        }
        
        // Calculate CO2e
        $calculation = $this->calculationService->calculateCO2e(
            $request->quantity,
            $emissionFactor,
            $request->unit
        );
        
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
    
}

