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
use Carbon\Carbon;

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
    public function show($scope, $slug, Request $request)
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
        
        // Get selected location and year from request or session
        $selectedLocationId = $request->input('location_id', session('quick_input.location_id'));
        $selectedFiscalYear = $request->input('fiscal_year', session('quick_input.fiscal_year', Carbon::now()->year));
        
        $measurement = null;
        $existingEntries = collect();
        
        if ($selectedLocationId && $selectedFiscalYear) {
            $measurement = $this->measurementService->getOrCreateMeasurement($selectedLocationId, $selectedFiscalYear);
            session(['quick_input.location_id' => $selectedLocationId, 'quick_input.fiscal_year' => $selectedFiscalYear]);
            
            // Get existing entries for this emission source
            $existingEntries = MeasurementData::where('measurement_id', $measurement->id)
                ->where('emission_source_id', $emissionSource->id)
                ->orderBy('entry_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        }
        
        return view('quick-input.show', compact(
            'emissionSource',
            'formFields',
            'userFriendlyName',
            'locations',
            'availableUnits',
            'scope',
            'slug',
            'selectedLocationId',
            'selectedFiscalYear',
            'measurement',
            'existingEntries'
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
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Quick Input Store Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'request_data' => $request->except(['_token'])
            ]);
            return back()
                ->withErrors(['error' => 'An unexpected error occurred while saving. Please try again or contact support if the problem persists.'])
                ->withInput()
                ->with('error', 'Failed to save entry. Please check your inputs and try again.');
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

    /**
     * View a specific entry
     */
    public function view($id)
    {
        $this->requirePermission('measurements.view', null, ['measurements.*', 'manage_measurements']);
        
        $user = Auth::user();
        $company = $user->getActiveCompany();
        
        if (!$company) {
            abort(403, 'No active company found.');
        }
        
        $entry = MeasurementData::with(['measurement.location', 'emissionSource'])
            ->whereHas('measurement.location', function($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->findOrFail($id);
        
        return view('quick-input.view', compact('entry'));
    }

    /**
     * Show edit form for an entry
     */
    public function edit($id)
    {
        $this->requirePermission('measurements.edit', null, ['measurements.*', 'manage_measurements']);
        
        $user = Auth::user();
        $company = $user->getActiveCompany();
        
        if (!$company) {
            abort(403, 'No active company found.');
        }
        
        $entry = MeasurementData::with(['measurement.location', 'emissionSource'])
            ->whereHas('measurement.location', function($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->findOrFail($id);
        
        $emissionSource = $entry->emissionSource;
        $formFields = $this->formBuilder->buildForm($emissionSource->id);
        $userFriendlyName = $this->calculationService->getUserFriendlyName(
            $emissionSource->id,
            $company->industry_category_id ?? null
        );
        
        $locations = Location::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $availableUnits = $this->calculationService->getAvailableUnits($emissionSource->id);
        
        // Parse scope to get number
        $scope = str_replace('Scope ', '', $entry->scope);
        $slug = $emissionSource->quick_input_slug;
        
        return view('quick-input.edit', compact(
            'entry',
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
     * Update an entry
     */
    public function update(Request $request, $id)
    {
        $this->requirePermission('measurements.edit', null, ['measurements.*', 'manage_measurements']);
        
        $user = Auth::user();
        $company = $user->getActiveCompany();
        
        if (!$company) {
            abort(403, 'No active company found.');
        }
        
        $entry = MeasurementData::with(['measurement.location', 'emissionSource'])
            ->whereHas('measurement.location', function($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->findOrFail($id);
        
        $emissionSource = $entry->emissionSource;
        
        // Validate
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
            // Get or create measurement record (might be different location/year)
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
                return back()->withErrors(['quantity' => 'No suitable emission factor found for the selected criteria.'])->withInput();
            }
            
            // Calculate CO2e
            $calculation = $this->calculationService->calculateCO2e(
                $request->quantity,
                $emissionFactor,
                $request->unit
            );
            
            // Prepare additional data
            $additionalData = [];
            $formFields = $this->formBuilder->buildForm($emissionSource->id);
            foreach ($formFields as $field) {
                if ($request->has($field->field_name)) {
                    $additionalData[$field->field_name] = $request->input($field->field_name);
                }
            }
            
            // Update entry
            $entry->update([
                'measurement_id' => $measurement->id,
                'quantity' => $request->quantity,
                'unit' => $request->unit,
                'calculated_co2e' => $calculation['co2e'],
                'co2_emissions' => $calculation['co2'] ?? null,
                'ch4_emissions' => $calculation['ch4'] ?? null,
                'n2o_emissions' => $calculation['n2o'] ?? null,
                'entry_date' => $request->entry_date,
                'emission_factor_id' => $emissionFactor->id,
                'gwp_version_used' => $emissionFactor->gwp_version ?? 'AR6',
                'additional_data' => !empty($additionalData) ? json_encode($additionalData) : null,
                'notes' => $request->notes ?? null,
            ]);
            
            // Update measurement totals (both old and new measurements)
            $this->measurementService->updateMeasurementTotals($entry->measurement_id);
            if ($entry->measurement_id != $measurement->id) {
                $this->measurementService->updateMeasurementTotals($measurement->id);
            }
            
            DB::commit();
            
            return redirect()->route('quick-input.index')
                ->with('success', 'Entry updated successfully!');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Quick Input Update Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'entry_id' => $id,
                'request_data' => $request->except(['_token', '_method'])
            ]);
            return back()
                ->withErrors(['error' => 'An unexpected error occurred while updating. Please try again or contact support if the problem persists.'])
                ->withInput()
                ->with('error', 'Failed to update entry. Please check your inputs and try again.');
        }
    }

    /**
     * Delete an entry
     */
    public function destroy($id)
    {
        $this->requirePermission('measurements.delete', null, ['measurements.*', 'manage_measurements']);
        
        $user = Auth::user();
        $company = $user->getActiveCompany();
        
        if (!$company) {
            abort(403, 'No active company found.');
        }
        
        $entry = MeasurementData::with('measurement.location')
            ->whereHas('measurement.location', function($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->findOrFail($id);
        
        $measurementId = $entry->measurement_id;
        
        DB::beginTransaction();
        try {
            $entry->delete();
            
            // Update measurement totals
            $this->measurementService->updateMeasurementTotals($measurementId);
            
            DB::commit();
            
            return redirect()->route('quick-input.index')
                ->with('success', 'Entry deleted successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Quick Input Delete Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'entry_id' => $id
            ]);
            return back()
                ->withErrors(['error' => 'Failed to delete entry. Please try again or contact support if the problem persists.'])
                ->with('error', 'Unable to delete entry. Please try again.');
        }
    }

    /**
     * Export entries to CSV
     */
    public function export(Request $request)
    {
        $this->requirePermission('measurements.view', null, ['measurements.*', 'manage_measurements']);
        
        $user = Auth::user();
        $company = $user->getActiveCompany();
        
        if (!$company) {
            abort(403, 'No active company found.');
        }
        
        // Get entries with same filters as index
        $query = MeasurementData::with(['measurement.location', 'emissionSource'])
            ->whereHas('measurement.location', function($q) use ($company) {
                $q->where('company_id', $company->id);
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
        
        $entries = $query->orderBy('entry_date', 'desc')->get();
        
        // Generate CSV
        $filename = 'quick-input-entries-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($entries) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, [
                'Date',
                'Emission Source',
                'Location',
                'Year',
                'Quantity',
                'Unit',
                'CO2e (kg)',
                'CO2 (kg)',
                'CH4 (kg)',
                'N2O (kg)',
                'Scope',
                'Notes'
            ]);
            
            // Data rows
            foreach ($entries as $entry) {
                fputcsv($file, [
                    $entry->entry_date ? $entry->entry_date->format('Y-m-d') : '',
                    $entry->emissionSource->name ?? '',
                    $entry->measurement->location->name ?? '',
                    $entry->measurement->fiscal_year ?? '',
                    $entry->quantity,
                    $entry->unit,
                    $entry->calculated_co2e,
                    $entry->co2_emissions ?? '',
                    $entry->ch4_emissions ?? '',
                    $entry->n2o_emissions ?? '',
                    $entry->scope,
                    $entry->notes ?? '',
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
}

