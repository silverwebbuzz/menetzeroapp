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
        
        // Get only years that have entries
        $yearsWithEntries = MeasurementData::whereHas('measurement', function($q) use ($company) {
                $q->whereHas('location', function($locQuery) use ($company) {
                    $locQuery->where('company_id', $company->id);
                });
            })
            ->join('measurements', 'measurement_data.measurement_id', '=', 'measurements.id')
            ->select('measurements.fiscal_year')
            ->distinct()
            ->orderBy('measurements.fiscal_year', 'desc')
            ->pluck('fiscal_year')
            ->toArray();
        
        // Get emission sources with entry counts
        $sources = EmissionSourceMaster::where('is_quick_input', true)
            ->orderBy('scope')
            ->orderBy('quick_input_order')
            ->get()
            ->map(function($source) use ($company, $request) {
                // Count entries for this source
                $entryCountQuery = MeasurementData::where('emission_source_id', $source->id)
                    ->whereHas('measurement', function($q) use ($company) {
                        $q->whereHas('location', function($locQuery) use ($company) {
                            $locQuery->where('company_id', $company->id);
                        });
                    });
                
                // Apply same filters as main query
                if ($request->filled('scope')) {
                    $entryCountQuery->where('scope', $request->scope);
                }
                if ($request->filled('location_id')) {
                    $entryCountQuery->whereHas('measurement', function($q) use ($request) {
                        $q->where('location_id', $request->location_id);
                    });
                }
                if ($request->filled('fiscal_year')) {
                    $entryCountQuery->whereHas('measurement', function($q) use ($request) {
                        $q->where('fiscal_year', $request->fiscal_year);
                    });
                }
                
                $entryCount = $entryCountQuery->count();
                
                // Get expected entry count (could be from a config or default to 1)
                $expectedCount = 1; // Default, can be customized per source if needed
                
                $source->entry_count = $entryCount;
                $source->expected_count = $expectedCount;
                
                return $source;
            });
        
        // Calculate summary statistics
        $summary = $this->measurementService->calculateSummary($company->id, $request->all());
        
        return view('quick-input.index', compact('entries', 'locations', 'sources', 'summary', 'yearsWithEntries'));
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
        
        // Get user-friendly name and industry label details based on company's industry
        $userFriendlyName = $this->calculationService->getUserFriendlyName(
            $emissionSource->id,
            $company->industry_category_id ?? null
        );
        
        // Get full industry label for additional details (description, equipment, etc.)
        $industryLabel = $this->calculationService->getIndustryLabel(
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
        
        // Get years from measurements table for this company
        $yearsWithMeasurements = Measurement::whereHas('location', function($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->select('fiscal_year')
            ->distinct()
            ->orderBy('fiscal_year', 'desc')
            ->pluck('fiscal_year')
            ->toArray();
        
        // Check if we're editing an existing entry
        $editEntry = null;
        $editEntryId = $request->input('edit');
        if ($editEntryId) {
            $editEntry = MeasurementData::with(['measurement.location', 'emissionSource'])
                ->whereHas('measurement.location', function($q) use ($company) {
                    $q->where('company_id', $company->id);
                })
                ->where('id', $editEntryId)
                ->where('emission_source_id', $emissionSource->id)
                ->first();
            
            if ($editEntry) {
                // Pre-select location and year from the entry being edited
                $selectedLocationId = $editEntry->measurement->location_id;
                $selectedFiscalYear = $editEntry->measurement->fiscal_year;
            }
        }
        
        // Get selected location and year from request, edit entry, or session
        $selectedLocationId = $request->input('location_id', $editEntry ? $editEntry->measurement->location_id : session('quick_input.location_id'));
        $selectedFiscalYear = $request->input('fiscal_year', $editEntry ? $editEntry->measurement->fiscal_year : session('quick_input.fiscal_year'));
        
        $measurement = null;
        $existingEntries = collect();
        
        if ($selectedLocationId && $selectedFiscalYear) {
            $measurement = $this->measurementService->getOrCreateMeasurement($selectedLocationId, $selectedFiscalYear);
            // Load location relationship
            $measurement->load('location');
            
            session(['quick_input.location_id' => $selectedLocationId, 'quick_input.fiscal_year' => $selectedFiscalYear]);
            
            // Get all measurement IDs for this location and fiscal year
            $measurementIds = Measurement::where('location_id', $selectedLocationId)
                ->where('fiscal_year', $selectedFiscalYear)
                ->pluck('id')
                ->toArray();
            
            // Get existing entries for this emission source from all measurements matching location and year
            $existingEntries = MeasurementData::with(['emissionSource', 'measurement.location'])
                ->whereIn('measurement_id', $measurementIds)
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
            'existingEntries',
            'yearsWithMeasurements',
            'editEntry'
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
        $validationRules = [
            'location_id' => 'required|exists:locations,id',
            'fiscal_year' => 'required|integer|min:2000|max:2100',
        ];
        
        // Check if form uses 'amount' or 'quantity'
        $formFields = EmissionSourceFormField::where('emission_source_id', $emissionSource->id)->get();
        $hasAmountField = $formFields->contains(function($field) {
            return $field->field_name === 'amount';
        });
        $hasUnitOfMeasure = $formFields->contains(function($field) {
            return $field->field_name === 'unit_of_measure';
        });
        
        if ($hasAmountField) {
            $validationRules['amount'] = 'required|numeric|min:0';
            if ($hasUnitOfMeasure) {
                $validationRules['unit_of_measure'] = 'required|string';
            } else {
                $validationRules['unit'] = 'required|string';
            }
        } else {
            $validationRules['quantity'] = 'required|numeric|min:0';
            $validationRules['unit'] = 'required|string';
        }
        
        $request->validate($validationRules);
        
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
            
            // Get quantity and unit from request (handle both 'amount' and 'quantity' fields)
            $quantity = $request->input('amount') ?? $request->input('quantity');
            $unit = $request->input('unit_of_measure') ?? $request->input('unit');
            
            // Prepare conditions for factor selection (include fuel_category, fuel_type, etc.)
            $conditions = [
                'region' => $request->input('region', 'UAE'),
                'fuel_category' => $request->input('fuel_category'),
                'fuel_type' => $request->input('fuel_type'),
                'unit' => $unit,
            ];
            
            // Select emission factor
            $emissionFactor = $this->calculationService->selectEmissionFactor(
                $emissionSource->id,
                $conditions
            );
            
            if (!$emissionFactor) {
                return back()->withErrors(['quantity' => 'No suitable emission factor found for the selected criteria. Please contact support.'])->withInput();
            }
            
            // Debug: Log emission factor details
            \Log::info('Emission Factor Selected', [
                'factor_id' => $emissionFactor->id,
                'calculation_method' => $emissionFactor->calculation_method,
                'has_calculation_method' => !empty($emissionFactor->calculation_method),
            ]);
            
            // Calculate CO2e
            try {
                $calculation = $this->calculationService->calculateCO2e(
                    $quantity,
                    $emissionFactor,
                    $unit
                );
                
                // Ensure calculation returns expected structure
                if (!is_array($calculation)) {
                    throw new \Exception('Calculation service returned invalid result');
                }
            } catch (\Exception $calcError) {
                \Log::error('Calculation error in store: ' . $calcError->getMessage());
                return back()->withErrors(['quantity' => 'Error calculating CO2e: ' . $calcError->getMessage()])->withInput();
            }
            
            // Prepare additional data (form field values)
            $additionalData = [];
            $formFields = EmissionSourceFormField::where('emission_source_id', $emissionSource->id)->get();
            foreach ($formFields as $field) {
                // Skip main fields that are stored directly or in dedicated columns
                if (in_array($field->field_name, ['unit_of_measure', 'amount', 'quantity', 'unit', 'comments', 'fuel_category', 'fuel_type'])) {
                    continue;
                }
                if ($request->has($field->field_name) && $request->input($field->field_name) !== null && $request->input($field->field_name) !== '') {
                    $additionalData[$field->field_name] = $request->input($field->field_name);
                }
            }
            
            // Get notes/comments (handle both 'comments' from form fields and 'notes' for backward compatibility)
            $notes = $request->input('comments') ?? $request->input('notes') ?? null;
            
            // Ensure calculation result has the expected structure
            $co2e = $calculation['co2e'] ?? $calculation['total_co2e'] ?? 0;
            if (!is_numeric($co2e)) {
                $co2e = 0;
            }
            
            // Create measurement_data record
            // Note: field_name and field_value are required by the database schema for backward compatibility
            // For Quick Input entries, we use 'quick_input' as field_name
            $measurementData = MeasurementData::create([
                'measurement_id' => $measurement->id,
                'emission_source_id' => $emissionSource->id,
                'field_name' => 'quick_input', // Required field for backward compatibility
                'field_value' => (string) $quantity, // Store quantity as field_value for backward compatibility
                'quantity' => $quantity,
                'unit' => $unit,
                'calculated_co2e' => $co2e,
                'co2_emissions' => isset($calculation['co2']) && is_numeric($calculation['co2']) ? $calculation['co2'] : null,
                'ch4_emissions' => isset($calculation['ch4']) && is_numeric($calculation['ch4']) ? $calculation['ch4'] : null,
                'n2o_emissions' => isset($calculation['n2o']) && is_numeric($calculation['n2o']) ? $calculation['n2o'] : null,
                'scope' => $emissionSource->scope,
                'entry_date' => Carbon::now()->toDateString(), // Automatically set to current date
                'emission_factor_id' => $emissionFactor->id,
                'gwp_version_used' => $emissionFactor->gwp_version ?? 'AR6',
                'calculation_method' => $emissionFactor->calculation_method ?? null, // Save calculation method from emission factor
                'supplier_emission_factor' => $request->input('supplier_emission_factor') ? (float) $request->input('supplier_emission_factor') : null, // Save supplier factor if provided
                'fuel_type' => $request->input('fuel_type'), // Save fuel_type if provided
                'additional_data' => !empty($additionalData) ? $additionalData : null,
                'notes' => $notes,
                'created_by' => $user->id,
            ]);
            
            // Store fuel_category in additional_data if provided (since there's no dedicated column)
            if ($request->input('fuel_category')) {
                $additionalDataWithCategory = $measurementData->additional_data ?? [];
                $additionalDataWithCategory['fuel_category'] = $request->input('fuel_category');
                $measurementData->update(['additional_data' => $additionalDataWithCategory]);
            }
            
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
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => Auth::id(),
                'request_data' => $request->except(['_token', '_method']),
                'emission_source_id' => $emissionSource->id ?? null,
            ]);
            
            // Return more detailed error in development, generic in production
            $errorMessage = config('app.debug') 
                ? 'Error: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')'
                : 'An unexpected error occurred while saving. Please try again or contact support if the problem persists.';
            
            return back()
                ->withErrors(['error' => $errorMessage])
                ->withInput()
                ->with('error', 'Failed to save entry. Please check your inputs and try again.');
        }
    }
    
    /**
     * Calculate CO2e emissions (AJAX endpoint)
     */
    public function calculate(Request $request)
    {
        try {
            $request->validate([
                'emission_source_id' => 'required|exists:emission_sources_master,id',
                'quantity' => 'required|numeric|min:0',
                'unit' => 'required|string',
            ]);
            
            $emissionSource = EmissionSourceMaster::findOrFail($request->emission_source_id);
            
            // Prepare conditions for factor selection (include fuel_category, fuel_type, etc.)
            $conditions = [
                'region' => $request->input('region', 'UAE'),
                'fuel_category' => $request->input('fuel_category'),
                'fuel_type' => $request->input('fuel_type'),
                'unit' => $request->input('unit') ?? $request->input('unit_of_measure'),
            ];
            
            // Select emission factor
            $emissionFactor = $this->calculationService->selectEmissionFactor(
                $emissionSource->id,
                $conditions
            );
            
            if (!$emissionFactor) {
                return response()->json([
                    'success' => false,
                    'message' => 'No suitable emission factor found for the selected criteria.'
                ], 400);
            }
            
            // Get quantity (handle both 'amount' and 'quantity' fields)
            $quantity = $request->input('quantity') ?? $request->input('amount');
            $unit = $request->input('unit') ?? $request->input('unit_of_measure');
            
            // Calculate CO2e
            $calculation = $this->calculationService->calculateCO2e(
                $quantity,
                $emissionFactor,
                $unit
            );
            
            return response()->json([
                'success' => true,
                'calculation' => $calculation,
                'factor' => [
                    'id' => $emissionFactor->id,
                    'unit' => $emissionFactor->unit ?? '',
                    'region' => $emissionFactor->region ?? '',
                    'source_standard' => $emissionFactor->source_standard ?? '',
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Calculate API Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Calculation failed. Please try again.'
            ], 500);
        }
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
        
        $entry = MeasurementData::with(['measurement.location', 'emissionSource', 'emissionFactor'])
            ->whereHas('measurement.location', function($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->findOrFail($id);
        
        return view('quick-input.view', compact('entry'));
    }

    /**
     * Show edit form for an entry (redirects to show page with edit parameter)
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
        $scope = str_replace('Scope ', '', $entry->scope);
        $slug = $emissionSource->quick_input_slug;
        
        // Redirect to show page with edit parameter
        return redirect()->route('quick-input.show', [
            'scope' => $scope,
            'slug' => $slug,
            'edit' => $id,
            'location_id' => $entry->measurement->location_id,
            'fiscal_year' => $entry->measurement->fiscal_year
        ]);
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
        
        // Check if form uses 'amount' or 'quantity'
        $formFields = EmissionSourceFormField::where('emission_source_id', $emissionSource->id)->get();
        $hasAmountField = $formFields->contains(function($field) {
            return $field->field_name === 'amount';
        });
        $hasUnitOfMeasure = $formFields->contains(function($field) {
            return $field->field_name === 'unit_of_measure';
        });
        
        // Validate
        $validationRules = [
            'location_id' => 'required|exists:locations,id',
            'fiscal_year' => 'required|integer|min:2000|max:2100',
        ];
        
        if ($hasAmountField) {
            $validationRules['amount'] = 'required|numeric|min:0';
            if ($hasUnitOfMeasure) {
                $validationRules['unit_of_measure'] = 'required|string';
            } else {
                $validationRules['unit'] = 'required|string';
            }
        } else {
            $validationRules['quantity'] = 'required|numeric|min:0';
            $validationRules['unit'] = 'required|string';
        }
        
        $request->validate($validationRules);
        
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
            
            // Get quantity and unit from request (handle both 'amount' and 'quantity' fields)
            $quantity = $request->input('amount') ?? $request->input('quantity');
            $unit = $request->input('unit_of_measure') ?? $request->input('unit');
            
            // Prepare conditions for factor selection (include fuel_category, fuel_type, etc.)
            $conditions = [
                'region' => $request->input('region', 'UAE'),
                'fuel_category' => $request->input('fuel_category'),
                'fuel_type' => $request->input('fuel_type'),
                'unit' => $unit,
            ];
            
            // Select emission factor
            $emissionFactor = $this->calculationService->selectEmissionFactor(
                $emissionSource->id,
                $conditions
            );
            
            if (!$emissionFactor) {
                return back()->withErrors(['quantity' => 'No suitable emission factor found for the selected criteria.'])->withInput();
            }
            
            // Calculate CO2e
            $calculation = $this->calculationService->calculateCO2e(
                $quantity,
                $emissionFactor,
                $unit
            );
            
            // Prepare additional data
            $additionalData = [];
            $formFields = $this->formBuilder->buildForm($emissionSource->id);
            foreach ($formFields as $field) {
                // Skip main fields that are stored directly
                if (in_array($field->field_name, ['unit_of_measure', 'amount', 'quantity', 'unit', 'comments'])) {
                    continue;
                }
                if ($request->has($field->field_name) && $request->input($field->field_name) !== null && $request->input($field->field_name) !== '') {
                    $additionalData[$field->field_name] = $request->input($field->field_name);
                }
            }
            
            // Update entry
            // Ensure field_name and field_value are set (required for backward compatibility)
            $updateData = [
                'measurement_id' => $measurement->id,
                'field_name' => $entry->field_name ?? 'quick_input', // Preserve existing or set default
                'field_value' => (string) $quantity, // Update field_value with new quantity
                'quantity' => $quantity,
                'unit' => $unit,
                'calculated_co2e' => $calculation['co2e'] ?? $calculation['total_co2e'] ?? 0,
                'co2_emissions' => isset($calculation['co2']) && is_numeric($calculation['co2']) ? $calculation['co2'] : null,
                'ch4_emissions' => isset($calculation['ch4']) && is_numeric($calculation['ch4']) ? $calculation['ch4'] : null,
                'n2o_emissions' => isset($calculation['n2o']) && is_numeric($calculation['n2o']) ? $calculation['n2o'] : null,
                'entry_date' => Carbon::now()->toDateString(), // Automatically set to current date
                'emission_factor_id' => $emissionFactor->id,
                'gwp_version_used' => $emissionFactor->gwp_version ?? 'AR6',
                'calculation_method' => $emissionFactor->calculation_method ?? null, // Save calculation method from emission factor
                'supplier_emission_factor' => $request->input('supplier_emission_factor') ? (float) $request->input('supplier_emission_factor') : null, // Save supplier factor if provided
                'additional_data' => !empty($additionalData) ? $additionalData : null,
                'notes' => $request->input('comments') ?? $request->input('notes') ?? null,
                'updated_by' => $user->id,
            ];
            
            $entry->update($updateData);
            
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

    /**
     * Get fuel categories for an emission source (AJAX)
     */
    public function getFuelCategories($sourceId)
    {
        try {
            $categories = EmissionFactor::where('emission_source_id', $sourceId)
                ->where('is_active', true)
                ->whereNotNull('fuel_category')
                ->select('fuel_category')
                ->distinct()
                ->orderBy('fuel_category')
                ->pluck('fuel_category')
                ->toArray();

            return response()->json([
                'success' => true,
                'categories' => $categories
            ]);
        } catch (\Exception $e) {
            \Log::error('Get Fuel Categories Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch fuel categories'
            ], 500);
        }
    }

    /**
     * Get fuel types for an emission source based on fuel category (AJAX)
     */
    public function getFuelTypes($sourceId, Request $request)
    {
        try {
            $query = EmissionFactor::where('emission_source_id', $sourceId)
                ->where('is_active', true)
                ->whereNotNull('fuel_type');

            if ($request->has('fuel_category') && $request->fuel_category) {
                $query->where('fuel_category', $request->fuel_category);
            }

            $fuelTypes = $query->select('fuel_type')
                ->distinct()
                ->orderBy('fuel_type')
                ->pluck('fuel_type')
                ->toArray();

            return response()->json([
                'success' => true,
                'fuel_types' => $fuelTypes
            ]);
        } catch (\Exception $e) {
            \Log::error('Get Fuel Types Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch fuel types'
            ], 500);
        }
    }

    /**
     * Get available units for an emission source based on fuel type (AJAX)
     */
    public function getUnits($sourceId, Request $request)
    {
        try {
            $query = EmissionFactor::where('emission_source_id', $sourceId)
                ->where('is_active', true)
                ->whereNotNull('unit');

            if ($request->has('fuel_type') && $request->fuel_type) {
                $query->where('fuel_type', $request->fuel_type);
            }

            if ($request->has('fuel_category') && $request->fuel_category) {
                $query->where('fuel_category', $request->fuel_category);
            }

            $units = $query->select('unit')
                ->distinct()
                ->orderBy('unit')
                ->pluck('unit')
                ->toArray();

            return response()->json([
                'success' => true,
                'units' => $units
            ]);
        } catch (\Exception $e) {
            \Log::error('Get Units Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch units'
            ], 500);
        }
    }
    
}

