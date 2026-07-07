<?php

namespace App\Http\Controllers;

use App\Models\Measurement;
use App\Models\MeasurementData;
use App\Models\Location;
use App\Models\EmissionSourceMaster;
use App\Support\JsonField;
use App\Models\EmissionFactor;
use App\Models\EmissionSourceFormField;
use App\Services\EmissionCalculationService;
use App\Services\MeasurementDocumentService;
use App\Services\MeasurementService;
use App\Services\QuickInputFormBuilder;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class QuickInputController extends Controller
{
    protected $calculationService;
    protected $measurementService;
    protected $formBuilder;
    protected $subscriptionService;
    protected $documentService;

    public function __construct(
        EmissionCalculationService $calculationService,
        MeasurementService $measurementService,
        QuickInputFormBuilder $formBuilder,
        SubscriptionService $subscriptionService,
        MeasurementDocumentService $documentService,
    ) {
        $this->calculationService = $calculationService;
        $this->measurementService = $measurementService;
        $this->formBuilder = $formBuilder;
        $this->subscriptionService = $subscriptionService;
        $this->documentService = $documentService;
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
            ->whereHas('measurement', function ($q) use ($company) {
                $q->whereHas('location', function ($locQuery) use ($company) {
                    $locQuery->where('company_id', $company->id);
                });
            });

        // Apply filters
        if ($request->filled('scope')) {
            $query->where('scope', $request->scope);
        }

        if ($request->filled('location_id')) {
            $query->whereHas('measurement', function ($q) use ($request) {
                $q->where('location_id', $request->location_id);
            });
        }

        if ($request->filled('fiscal_year')) {
            $query->whereHas('measurement', function ($q) use ($request) {
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
        $yearsWithEntries = MeasurementData::whereHas('measurement', function ($q) use ($company) {
            $q->whereHas('location', function ($locQuery) use ($company) {
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
            ->map(function ($source) use ($company, $request) {
                // Count entries for this source
                $entryCountQuery = MeasurementData::where('emission_source_id', $source->id)
                    ->whereHas('measurement', function ($q) use ($company) {
                    $q->whereHas('location', function ($locQuery) use ($company) {
                        $locQuery->where('company_id', $company->id);
                    });
                });

                // Apply same filters as main query
                if ($request->filled('scope')) {
                    $entryCountQuery->where('scope', $request->scope);
                }
                if ($request->filled('location_id')) {
                    $entryCountQuery->whereHas('measurement', function ($q) use ($request) {
                        $q->where('location_id', $request->location_id);
                    });
                }
                if ($request->filled('fiscal_year')) {
                    $entryCountQuery->whereHas('measurement', function ($q) use ($request) {
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

        $canAddEntries = $user->isAdmin()
            || $user->isCompanyAdmin($company->id)
            || $user->hasPermission('measurements.add', $company->id)
            || $user->hasPermission('measurements.*', $company->id)
            || $user->hasPermission('manage_measurements', $company->id)
            || $user->hasModulePermission('measurements', 'add', $company->id);

        return view('quick-input.index', compact('entries', 'locations', 'sources', 'summary', 'yearsWithEntries', 'canAddEntries'));
    }

    /**
     * Layman-friendly Scope 1 & 2 data help guide (what to enter, units, where to find UAE bills).
     */
    public function helpGuide()
    {
        $this->requirePermission('measurements.view', null, ['measurements.*', 'manage_measurements']);

        $user = Auth::user();
        $company = $user->getActiveCompany();
        if (!$company) {
            abort(403, 'No active company found.');
        }

        $this->requireHelpGuide($company->id);

        $intro = \App\Data\Scope12HelpGuide::intro();
        $categories = \App\Data\Scope12HelpGuide::categories();
        $columns = \App\Data\Scope12HelpGuide::columnHelp();

        $locations = Location::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name');

        return view('quick-input.help-guide', compact('intro', 'categories', 'columns', 'locations'));
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

        if ($emissionSource->scope === 'Scope 3') {
            $this->requireScope3Access($company->id);
        }

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
        $yearsWithMeasurements = Measurement::whereHas('location', function ($q) use ($company) {
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
                ->whereHas('measurement.location', function ($q) use ($company) {
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

        // Scope 3 free-plan limit info (used to show an upgrade prompt instead of the
        // add form once the per-form record limit is reached).
        $scope3Limit = -1;
        $scope3LimitReached = false;
        if ($emissionSource->scope === 'Scope 3') {
            $scope3Check = $this->subscriptionService->canAddScope3Record($company->id, $emissionSource->id);
            $scope3Limit = $scope3Check['limit'];
            $scope3LimitReached = !$scope3Check['allowed'];
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
            'editEntry',
            'scope3Limit',
            'scope3LimitReached'
        ));
    }

    public function getFormFieldsForVehicle(Request $request)
    {
        
        $knowAmountOfFuel = $request->input('knowAmountOfFuel');

        if ($knowAmountOfFuel == "true") {
            $fieldNames = [
                'vehicle_fuel_type',
                'unit_of_measure',
                'distance'
            ];
        } else {
            $fieldNames = [
                'vehicle_category',
                'vehicle_type',
                'vehicle_fuel_type',
                'unit_of_measure',
                'distance'
            ];
        }

        $vehicleSource = EmissionSourceMaster::where('quick_input_slug', 'vehicle')
            ->where('scope', 'Scope 1')
            ->where('is_quick_input', true)
            ->first();
        $vehicleSourceId = $vehicleSource?->id ?? 0;

        $fields = EmissionSourceFormField::where('emission_source_id', $vehicleSourceId)
            ->whereIn('field_name', $fieldNames)
            ->orderBy('field_order')
            ->get();
        
        if(isset($request->edit) && $request->edit != null && $request->edit > 0) {
            $data = MeasurementData::where('id', $request->edit)->first();
        }

        $html = '';

        foreach ($fields as $field) {
            $inputName = $field->field_name;
            if ($knowAmountOfFuel == 'true' && $field->field_name === 'distance') {
                $inputName = 'amount';
            }

            if ($knowAmountOfFuel == 'true' && $field->field_label == 'Distance') {
                $field->field_label = 'Amount';
                $field->field_placeholder = 'Enter the amount of fuel used';
            }
        // normalize options safely (field_options may be array-cast by Eloquent)
        $options = [];
        if ($field->field_type === 'select') {
            $options = JsonField::decode($field->field_options);
        }

        $html .= '<div class="form-group-horizontal">';

        /* ---------- LABEL + HELP TEXT ---------- */
        $html .= '<div class="form-label-wrapper">';
        $html .= '<label for="'.e($inputName).'" class="form-label-horizontal">';
        $html .= e($field->field_label ?? ucwords(str_replace('_', ' ', $field->field_name)));

        if ($field->is_required) {
            $html .= ' <span class="text-red-500">*</span>';
        }

        $html .= '</label>';

        $helpContext = [];
        if ($knowAmountOfFuel == 'true' && $field->field_name === 'distance') {
            $helpContext['variant'] = 'amount_fuel';
        }
        $helpText = $field->resolvedHelpText('vehicle', $helpContext);
        if ($helpText !== null) {
            $html .= '<p class="form-help-text-under-label">'.e($helpText).'</p>';
        }

        $html .= '</div>'; // label wrapper

        /* ---------- INPUT WRAPPER ---------- */
        $html .= '<div class="form-input-wrapper">';

        /* ---------- SELECT ---------- */
        if ($field->field_type === 'select') {

            $html .= '<select name="'.e($field->field_name).'" '
                . 'id="'.e($field->field_name).'" '
                . 'data-field-name="'.e($field->field_name).'" '
                . 'data-depends-on="'.e($field->depends_on_field ?? '').'" '
                . ($field->is_required ? 'required ' : '')
                . 'class="form-input-select">';

            $html .= '<option value="">Select an option</option>';

            if($knowAmountOfFuel == "true") {
                if($field->field_name == 'vehicle_fuel_type'){
                    $options = [
                        ['value' => 'Diesel (average biofuel blend)', 'label' => 'Diesel (average biofuel blend)'],
                        ['value' => 'Petrol (average biofuel blend)', 'label' => 'Petrol (average biofuel blend)'],
                    ];
                }
                if($field->field_name == 'unit_of_measure'){
                    $options = [
                        ['value' => 'litres', 'label' => 'litres'],
                        ['value' => 'tonnes', 'label' => 'tonnes'],
                    ];
                }
                
            }
            foreach ($options as $option) {
                $value = is_array($option) ? ($option['value'] ?? '') : $option;
                $label = is_array($option) ? ($option['label'] ?? $value) : $option;
                // if($field->field_name == 'vehicle_fuel_type' && $knowAmountOfFuel == "true" && !in_array($value,['Diesel','Petrol'])) {
                //     continue;
                // }
                // dd($data->vehicle_fuel_type);
                if(isset($data) && $data != null && $data->fuel_type == $value && $field->field_name == 'vehicle_fuel_type') {
                    $html .= '<option value="'.e($value).'" selected>'.e($label).'</option>';
                }else if(isset($data) && $data != null && $data->unit == $value && $field->field_name == 'unit_of_measure') {
                    $html .= '<option value="'.e($value).'" selected>'.e($label).'</option>';
                }else{
                    $html .= '<option value="'.e($value).'">'.e($label).'</option>';
                }
            }

            $html .= '</select>';
        }

        /* ---------- NUMBER ---------- */
        elseif ($field->field_type === 'number') {

            $html .= '<input type="number" '
                . 'name="'.e($inputName).'" '
                . 'id="'.e($inputName).'" '
                . 'step="any" min="0" '
                . ($field->is_required ? 'required ' : '')
                . 'placeholder="'.e($field->field_placeholder ?? 'Enter '.strtolower($field->field_label ?? $field->field_name)).'" '
                . 'class="form-input" value="'.(isset($data) && $data != null && $data->quantity ? $data->quantity : '').'">';
        }

        /* ---------- TEXT / TEXTAREA ---------- */
        else {

            if ($field->field_type === 'textarea') {
                $html .= '<textarea name="'.e($field->field_name).'" '
                    . 'id="'.e($field->field_name).'" '
                    . 'rows="3" '
                    . ($field->is_required ? 'required ' : '')
                    . 'placeholder="'.e($field->field_placeholder ?? '').'" '
                    . 'class="form-input form-textarea"></textarea>';
            } else {
                $html .= '<input type="'.e($field->field_type).'" '
                    . 'name="'.e($field->field_name).'" '
                    . 'id="'.e($field->field_name).'" '
                    . ($field->is_required ? 'required ' : '')
                    . 'placeholder="'.e($field->field_placeholder ?? '').'" '
                    . 'class="form-input">';
            }
        }

        $html .= '</div>'; // input wrapper
        $html .= '</div>'; // form-group-horizontal
    }

    return response()->json([
        'html' => $html
    ]);
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

        // Enforce Scope 3 per-form record limit (free plan allows 1 record per form).
        // Scope 1 & 2 are unlimited; only new records are gated (edits go through update()).
        if ($emissionSource->scope === 'Scope 3') {
            $scope3Check = $this->subscriptionService->canAddScope3Record($company->id, $emissionSource->id);
            if (!$scope3Check['allowed']) {
                $this->denyEntitlement($scope3Check['message']);
            }
        }

        $this->validateQuickInputRequest($request, $emissionSource);

        $this->requireReportingYearWrite($company->id, (int) $request->fiscal_year);

        // Verify location belongs to company
        $location = Location::where('id', $request->location_id)
            ->where('company_id', $company->id)
            ->firstOrFail();

        DB::beginTransaction();
        try {
            // Get or create measurement record
            $measurement = $this->measurementService->getOrCreateMeasurement(
                $request->location_id,
                $request->fiscal_year,
                $user->id
            );

            // Get quantity and unit from request (handle both 'amount' and 'quantity' fields)
            $quantity = ($request->input('amount') ?? $request->input('quantity')) ?? $request->input('distance');
            $unit = $request->input('unit_of_measure') ?? $request->input('unit');
            $entryDate = $this->resolveEntryDate($request);
            $supportingDocs = $this->documentService->storeForCompany(
                $company->id,
                $request->file('supporting_documents', []) ?? []
            );

            $conditions = $this->buildFactorConditions($request, $emissionSource, $unit);

            // Select emission factor
            $emissionFactor = $this->calculationService->selectEmissionFactor(
                $emissionSource->id,
                $conditions
            );
            if (!$emissionFactor) {
                \Log::warning('Quick Input: no emission factor found on store', [
                    'emission_source_id' => $emissionSource->id,
                    'emission_source_name' => $emissionSource->name,
                    'emission_type' => $emissionSource->emission_type,
                    'conditions' => $conditions,
                ]);
                return back()
                    ->withErrors(['quantity' => 'No suitable emission factor found for the selected criteria. Please contact support.'])
                    ->withInput();
            }

            // Debug: Log emission factor details
            \Log::info('Emission Factor Selected', [
                'factor_id' => $emissionFactor->id,
                'calculation_method' => $emissionFactor->calculation_method,
                'has_calculation_method' => !empty($emissionFactor->calculation_method),
            ]);

            // Calculate CO2e
            try {
                $calculation = $this->resolveCo2eCalculation(
                    $request,
                    $emissionSource,
                    (float) $quantity,
                    $unit,
                    $emissionFactor
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
                if (in_array($field->field_name, ['unit_of_measure', 'amount', 'quantity', 'unit', 'comments', 'link', 'fuel_category', 'fuel_type', 'energy_type', 'refrigerant_type', 'process_type', 'scope2_method', 'supplier_emission_factor', 'renewable_percent', 'is_biogenic', 'emission_factor_methodology', 'methodology_reference'])) {
                    continue;
                }
                if ($request->has($field->field_name) && $request->input($field->field_name) !== null && $request->input($field->field_name) !== '') {
                    $additionalData[$field->field_name] = $request->input($field->field_name);
                }
            }

            $additionalData = $this->withEvidenceAdditionalData($additionalData, $request);
            $additionalData = $this->withMethodologyAdditionalData($additionalData, $request, $emissionSource);

            // Get notes/comments (handle both 'comments' from form fields and 'notes' for backward compatibility)
            $notes = $request->input('comments') ?? $request->input('notes') ?? null;

            // Ensure calculation result has the expected structure
            $co2e = $calculation['co2e'] ?? $calculation['total_co2e'] ?? 0;
            if (!is_numeric($co2e)) {
                $co2e = 0;
            }

            $storedSupplierFactor = $this->resolveCustomEmissionFactor($request, $emissionSource);

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
                'entry_date' => $entryDate,
                'supporting_docs' => !empty($supportingDocs) ? $supportingDocs : null,
                'emission_factor_id' => $emissionFactor->id,
                'gwp_version_used' => $emissionFactor->gwp_version ?? 'AR6',
                'calculation_method' => $emissionFactor->calculation_method ?? null, // Save calculation method from emission factor
                'supplier_emission_factor' => $storedSupplierFactor,
                'scope2_method' => $this->resolveScope2Method($request, $emissionSource),
                'is_biogenic' => $request->boolean('is_biogenic'),
                'fuel_type' => $this->determineFuelType($request, $emissionSource),
                'additional_data' => !empty($additionalData) ? $additionalData : null,
                'notes' => $notes,
                'created_by' => $user->id,
            ]);

            // Store fuel_category, energy_type, and refrigerant_type in additional_data if provided
            $additionalDataToUpdate = [];
            if ($request->input('fuel_category')) {
                $additionalDataToUpdate['fuel_category'] = $request->input('fuel_category');
            }
            if ($request->input('energy_type')) {
                $additionalDataToUpdate['energy_type'] = $request->input('energy_type');
            }
            if ($request->input('refrigerant_type')) {
                $additionalDataToUpdate['refrigerant_type'] = $request->input('refrigerant_type');
            }
            if (!empty($additionalDataToUpdate)) {
                $existingAdditionalData = $measurementData->additional_data ?? [];
                $mergedAdditionalData = array_merge($existingAdditionalData, $additionalDataToUpdate);
                $measurementData->update(['additional_data' => $mergedAdditionalData]);
            }

            // Update measurement totals
            $this->measurementService->updateMeasurementTotals($measurement->id);

            DB::commit();

            // Stay on the same form so the user can keep adding entries for this
            // source; the newly saved entry will now appear in the Results list below.
            return redirect()->route('quick-input.show', [
                'scope' => $scope,
                'slug' => $slug,
                'location_id' => $request->location_id,
                'fiscal_year' => $request->fiscal_year,
            ])->with('success', 'Emission data added successfully!');

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
                'quantity' => 'required_without:distance|numeric|min:0',
                'distance' => 'required_without:quantity|numeric|min:0',
                'unit' => 'required|string',
            ]);

            $emissionSource = EmissionSourceMaster::findOrFail($request->emission_source_id);

            $unit = $request->input('unit') ?? $request->input('unit_of_measure');
            $conditions = $this->buildFactorConditions($request, $emissionSource, $unit);

            // Select emission factor
            $emissionFactor = $this->calculationService->selectEmissionFactor(
                $emissionSource->id,
                $conditions
            );
            if (!$emissionFactor) {
                // Debug: Check what factors exist for this source
                $availableFactors = \App\Models\EmissionFactor::where('emission_source_id', $emissionSource->id)
                    ->where('is_active', true)
                    ->get(['id', 'fuel_type', 'unit', 'region', 'factor_value']);

                \Log::warning('No emission factor found', [
                    'emission_source_id' => $emissionSource->id,
                    'emission_source_name' => $emissionSource->name,
                    'emission_type' => $emissionSource->emission_type,
                    'conditions' => $conditions,
                    'available_factors' => $availableFactors->toArray(),
                    'request_data' => $request->all()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'No suitable emission factor found for the selected criteria.',
                    'debug' => [
                        'conditions' => $conditions,
                        'available_factors_count' => $availableFactors->count(),
                        'emission_type' => $emissionSource->emission_type
                    ]
                ], 400);
            }

            // Debug: Log selected factor details
            \Log::info('Emission Factor Selected for Calculation', [
                'factor_id' => $emissionFactor->id,
                'factor_value' => $emissionFactor->factor_value,
                'total_co2e_factor' => $emissionFactor->total_co2e_factor,
                'fuel_type' => $emissionFactor->fuel_type,
                'fuel_category' => $emissionFactor->fuel_category,
                'unit' => $emissionFactor->unit,
                'conditions' => $conditions
            ]);

            // Get quantity (handle both 'amount' and 'quantity' fields)
            $quantity = $request->input('quantity') ?? $request->input('amount') ?? $request->input('distance');

            $calculation = $this->resolveCo2eCalculation(
                $request,
                $emissionSource,
                (float) $quantity,
                $unit,
                $emissionFactor
            );

            return response()->json([
                'success' => true,
                'calculation' => $calculation,
                'factor' => [
                    'id' => $emissionFactor->id,
                    'factor_value' => $emissionFactor->factor_value ?? $emissionFactor->total_co2e_factor ?? 0,
                    'unit' => $emissionFactor->unit ?? '',
                    'region' => $emissionFactor->region ?? '',
                    'source_standard' => $emissionFactor->source_standard ?? '',
                    'fuel_type' => $emissionFactor->fuel_type ?? '',
                    'fuel_category' => $emissionFactor->fuel_category ?? '',
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
            ->whereHas('measurement.location', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->findOrFail($id);

        return view('quick-input.view', compact('entry'));
    }

    /**
     * Download a supporting document (bill/PDF) attached to a quick-input entry.
     * Route: GET /quick-input/entries/{id}/documents/{index}/download
     */
    public function downloadSupportingDocument(int $id, int $index)
    {
        $this->requirePermission('measurements.view', null, ['measurements.*', 'manage_measurements']);

        $user = Auth::user();
        $company = $user->getActiveCompany();

        if (!$company) {
            abort(403, 'No active company found.');
        }

        $entry = MeasurementData::with('measurement.location')
            ->whereHas('measurement.location', fn ($q) => $q->where('company_id', $company->id))
            ->findOrFail($id);

        $resolved = $this->documentService->resolveDownload(
            $entry->supporting_docs ?? [],
            $index,
            $company->id
        );

        return Storage::disk('local')->download(
            $resolved['doc']['path'],
            $resolved['downloadName']
        );
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
            ->whereHas('measurement.location', function ($q) use ($company) {
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
            ->whereHas('measurement.location', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->findOrFail($id);

        $emissionSource = $entry->emissionSource;

        $this->validateQuickInputRequest($request, $emissionSource);

        $this->requireReportingYearWrite($company->id, (int) $request->fiscal_year);

        // Verify location belongs to company
        $location = Location::where('id', $request->location_id)
            ->where('company_id', $company->id)
            ->firstOrFail();

        DB::beginTransaction();
        try {
            // Get or create measurement record (might be different location/year)
            $measurement = $this->measurementService->getOrCreateMeasurement(
                $request->location_id,
                $request->fiscal_year,
                $user->id
            );
            // Get quantity and unit from request (handle both 'amount' and 'quantity' fields)
            $quantity = ($request->input('amount') ?? $request->input('quantity')) ?? $request->input('distance');
            $unit = $request->input('unit_of_measure') ?? $request->input('unit');
            $entryDate = $this->resolveEntryDate($request);
            $supportingDocs = $this->documentService->mergeDocuments(
                $entry->supporting_docs,
                $request->file('supporting_documents', []) ?? [],
                $company->id
            );

            $conditions = $this->buildFactorConditions($request, $emissionSource, $unit);

            $emissionFactor = $this->calculationService->selectEmissionFactor(
                $emissionSource->id,
                $conditions
            );

            if (!$emissionFactor) {
                return back()->withErrors(['quantity' => 'No suitable emission factor found for the selected criteria.'])->withInput();
            }

            $calculation = $this->resolveCo2eCalculation(
                $request,
                $emissionSource,
                (float) $quantity,
                $unit,
                $emissionFactor
            );

            // Prepare additional data
            $additionalData = [];
            $formFields = $this->formBuilder->buildForm($emissionSource->id);
            foreach ($formFields as $field) {
                // Skip main fields that are stored directly
                if (in_array($field->field_name, ['unit_of_measure', 'amount', 'quantity', 'unit', 'comments', 'link', 'fuel_category', 'fuel_type', 'energy_type', 'refrigerant_type', 'process_type', 'scope2_method', 'supplier_emission_factor', 'renewable_percent', 'is_biogenic', 'emission_factor_methodology', 'methodology_reference'])) {
                    continue;
                }
                if ($request->has($field->field_name) && $request->input($field->field_name) !== null && $request->input($field->field_name) !== '') {
                    $additionalData[$field->field_name] = $request->input($field->field_name);
                }
            }

            $additionalData = $this->withEvidenceAdditionalData($additionalData, $request);
            $additionalData = $this->withMethodologyAdditionalData($additionalData, $request, $emissionSource);

            $storedSupplierFactor = $this->resolveCustomEmissionFactor($request, $emissionSource);

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
                'entry_date' => $entryDate,
                'supporting_docs' => !empty($supportingDocs) ? $supportingDocs : null,
                'emission_factor_id' => $emissionFactor->id,
                'gwp_version_used' => $emissionFactor->gwp_version ?? 'AR6',
                'calculation_method' => $emissionFactor->calculation_method ?? null, // Save calculation method from emission factor
                'supplier_emission_factor' => $storedSupplierFactor,
                'scope2_method' => $this->resolveScope2Method($request, $emissionSource),
                'is_biogenic' => $request->boolean('is_biogenic'),
                'fuel_type' => $this->determineFuelType($request, $emissionSource),
                'additional_data' => !empty($additionalData) ? $additionalData : null,
                'notes' => $request->input('comments') ?? $request->input('notes') ?? null,
                'updated_by' => $user->id,
            ];

            $entry->update($updateData);

            // Store fuel_category and energy_type in additional_data if provided
            $additionalDataToUpdate = [];
            if ($request->input('fuel_category')) {
                $additionalDataToUpdate['fuel_category'] = $request->input('fuel_category');
            }
            if ($request->input('energy_type')) {
                $additionalDataToUpdate['energy_type'] = $request->input('energy_type');
            }
            if ($request->input('refrigerant_type')) {
                $additionalDataToUpdate['refrigerant_type'] = $request->input('refrigerant_type');
            }
            if (!empty($additionalDataToUpdate)) {
                $existingAdditionalData = $entry->additional_data ?? [];
                $mergedAdditionalData = array_merge($existingAdditionalData, $additionalDataToUpdate);
                $entry->update(['additional_data' => $mergedAdditionalData]);
            }

            // Update measurement totals (both old and new measurements)
            $this->measurementService->updateMeasurementTotals($entry->measurement_id);
            if ($entry->measurement_id != $measurement->id) {
                $this->measurementService->updateMeasurementTotals($measurement->id);
            }

            DB::commit();

            // Stay on the same form so the user sees the updated entry in the
            // Results list below. Derive scope + slug from the entry itself.
            $scopeNumber = str_replace('Scope ', '', $entry->scope);
            $slug = $entry->emissionSource->quick_input_slug;

            return redirect()->route('quick-input.show', [
                'scope' => $scopeNumber,
                'slug' => $slug,
                'location_id' => $request->location_id,
                'fiscal_year' => $request->fiscal_year,
            ])->with('success', 'Entry updated successfully!');

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
            ->whereHas('measurement.location', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->findOrFail($id);

        $this->requireReportingYearWrite($company->id, (int) $entry->measurement->fiscal_year);

        $measurementId = $entry->measurement_id;

        DB::beginTransaction();
        try {
            $entry->delete();

            // Update measurement totals
            $this->measurementService->updateMeasurementTotals($measurementId);

            DB::commit();

            // Stay on whichever page the user deleted from (entries list OR the source form)
            return back()->with('success', 'Entry deleted successfully!');

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

        $this->requireBulkExport($company->id);

        // Get entries with same filters as index
        $query = MeasurementData::with(['measurement.location', 'emissionSource'])
            ->whereHas('measurement.location', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            });

        // Apply filters
        if ($request->filled('scope')) {
            $query->where('scope', $request->scope);
        }
        if ($request->filled('location_id')) {
            $query->whereHas('measurement', function ($q) use ($request) {
                $q->where('location_id', $request->location_id);
            });
        }
        if ($request->filled('fiscal_year')) {
            $query->whereHas('measurement', function ($q) use ($request) {
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

        $callback = function () use ($entries) {
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

    public function getVehicleTypes($sourceId, Request $request)
    {
        try {
            $query = EmissionFactor::where('emission_source_id', $sourceId)
                ->where('is_active', true)
                ->whereNotNull('vehicle_type');

            if ($request->has('vehicle_category') && $request->vehicle_category) {
                $query->where('vehicle_category', $request->vehicle_category);
            }

            $fuelTypes = $query->select('vehicle_type')
                ->distinct()
                // ->orderBy('vehicle_type')
                ->pluck('vehicle_type')
                ->toArray();

            return response()->json([
                'success' => true,
                'vehicle_types' => $fuelTypes
            ]);
        } catch (\Exception $e) {
            \Log::error('Get Fuel Types Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch fuel types'
            ], 500);
        }
    }

    public function getVehicleFuelTypes($sourceId, Request $request)
    {
        try {
            $query = EmissionFactor::where('emission_source_id', operator: $sourceId)
                ->where('is_active', true)
                ->whereNotNull('fuel_type');

            if ($request->has('vehicle_category') && $request->vehicle_category) {
                $query->where('vehicle_category', $request->vehicle_category);
            }

            if ($request->has('vehicle_type') && $request->vehicle_type) {
                $query->where('vehicle_type', $request->vehicle_type);
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

    public function getVehicleUoms($sourceId, Request $request)
    {
        try {
            $query = EmissionFactor::where('emission_source_id', operator: $sourceId)
                ->where('is_active', true)
                ->whereNotNull('unit');

            if ($request->has('vehicle_category') && $request->vehicle_category) {
                $query->where('vehicle_category', $request->vehicle_category);
            }

            if ($request->has('vehicle_type') && $request->vehicle_type) {
                $query->where('vehicle_type', $request->vehicle_type);
            }

            $fuelTypes = $query->select('unit')
                ->distinct()
                ->orderBy('unit')
                ->pluck('unit')
                ->toArray();

            return response()->json([
                'success' => true,
                'units' => $fuelTypes
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
     * Whether the vehicle form is in "known fuel amount" mode (vs distance-based).
     */
    private function vehicleKnowsFuelAmount(Request $request): bool
    {
        if ($request->input('knowAmountOfFuel') === 'true') {
            return true;
        }

        $answer = $request->input('has_already_amount_of_fuel');

        return in_array($answer, ['Yes', 'yes', 'true', '1', 1, true], true);
    }

    /**
     * Normalize vehicle request: sync knowAmountOfFuel and map legacy field names.
     */
    private function normalizeVehicleRequest(Request $request): void
    {
        $knowsFuel = $this->vehicleKnowsFuelAmount($request);
        $request->merge(['knowAmountOfFuel' => $knowsFuel ? 'true' : 'false']);

        if ($knowsFuel && !$request->filled('amount') && $request->filled('distance')) {
            $request->merge(['amount' => $request->input('distance')]);
        }
    }

    /**
     * Determine fuel_type based on request and emission source
     */
    private function validateQuickInputRequest(Request $request, EmissionSourceMaster $emissionSource): void
    {
        if ($emissionSource->quick_input_slug === 'vehicle') {
            $this->normalizeVehicleRequest($request);
        }

        $formFields = $this->formBuilder->buildForm($emissionSource->id);
        $vehicleKnowsFuel = $emissionSource->quick_input_slug === 'vehicle'
            && $request->input('knowAmountOfFuel') === 'true';

        $formFieldsForValidation = $formFields->filter(function ($field) use ($request, $emissionSource, $vehicleKnowsFuel) {
            if ($field->depends_on_field && !$request->filled($field->depends_on_field)) {
                return false;
            }

            if ($emissionSource->quick_input_slug === 'vehicle' && $vehicleKnowsFuel) {
                return !in_array($field->field_name, ['vehicle_category', 'vehicle_type', 'distance'], true);
            }

            if ($emissionSource->quick_input_slug === 'vehicle' && !$vehicleKnowsFuel) {
                return $field->field_name !== 'amount';
            }

            return true;
        });

        $formValidator = $this->formBuilder->validateForm($request->all(), $formFieldsForValidation);
        if ($formValidator->fails()) {
            throw ValidationException::withMessages($formValidator->errors()->toArray());
        }

        $validationRules = [
            'location_id' => 'required|exists:locations,id',
            'fiscal_year' => 'required|integer|min:2000|max:2100',
            'entry_date' => 'nullable|date|before_or_equal:today',
            'evidence_link' => 'nullable|url|max:2048',
            'supporting_documents' => 'nullable|array|max:' . MeasurementDocumentService::MAX_FILES,
            'supporting_documents.*' => 'file|mimes:pdf,jpg,jpeg,png,webp|max:' . MeasurementDocumentService::MAX_SIZE_KB,
        ];

        $hasAmountField = $formFields->contains(fn ($field) => $field->field_name === 'amount');
        $hasUnitOfMeasure = $formFields->contains(fn ($field) => $field->field_name === 'unit_of_measure');

        if ($emissionSource->quick_input_slug === 'vehicle') {
            $unitField = $hasUnitOfMeasure ? 'unit_of_measure' : 'unit';
            $validationRules[$unitField] = 'required|string';

            if ($vehicleKnowsFuel) {
                $validationRules['amount'] = 'required|numeric|min:0';
            } else {
                $validationRules['distance'] = 'required|numeric|min:0';
            }
        } elseif ($request->filled('distance')) {
            $validationRules['distance'] = 'required_without:amount,quantity|nullable|numeric|min:0';
            $validationRules['amount'] = 'required_without:distance,quantity|nullable|numeric|min:0';
            $validationRules['quantity'] = 'required_without:distance,amount|nullable|numeric|min:0';
            $validationRules[$hasUnitOfMeasure ? 'unit_of_measure' : 'unit'] = 'required|string';
        } elseif ($hasAmountField) {
            $validationRules['amount'] = 'required|numeric|min:0';
            $validationRules[$hasUnitOfMeasure ? 'unit_of_measure' : 'unit'] = 'required|string';
        } elseif ($hasUnitOfMeasure) {
            $validationRules['unit_of_measure'] = 'required|string';
            $validationRules['amount'] = 'nullable|numeric|min:0';
            $validationRules['quantity'] = 'nullable|numeric|min:0';
        } else {
            $validationRules['quantity'] = 'required|numeric|min:0';
            $validationRules['unit'] = 'required|string';
        }

        $request->validate($validationRules);
    }

    private function resolveEntryDate(Request $request): ?string
    {
        if (!$request->filled('entry_date')) {
            return null;
        }

        return Carbon::parse($request->input('entry_date'))->toDateString();
    }

    /**
     * @param  array<string, mixed>  $additionalData
     * @return array<string, mixed>
     */
    private function withEvidenceAdditionalData(array $additionalData, Request $request): array
    {
        if (!$request->has('evidence_link')) {
            return $additionalData;
        }

        unset($additionalData['link']);

        $link = trim((string) $request->input('evidence_link'));
        if ($link !== '') {
            $additionalData['evidence_link'] = $link;
        } else {
            unset($additionalData['evidence_link']);
        }

        return $additionalData;
    }

    private function resolveDefaultRegion(EmissionSourceMaster $emissionSource): string
    {
        if ($emissionSource->emission_type === 'fugitive') {
            return 'Global';
        }

        if ($emissionSource->scope === 'Scope 3') {
            return 'Global';
        }

        return 'UAE';
    }

    private function buildFactorConditions(Request $request, EmissionSourceMaster $emissionSource, ?string $unit): array
    {
        return [
            'region' => $request->input('region', $this->resolveDefaultRegion($emissionSource)),
            'fuel_category' => $request->input('fuel_category'),
            'fuel_type' => $this->determineFuelType($request, $emissionSource),
            'unit' => $unit ?? $request->input('unit') ?? $request->input('unit_of_measure'),
            'vehicle_category' => $request->input('vehicle_category'),
            'vehicle_type' => $request->input('vehicle_type'),
        ];
    }

    /**
     * Scope 2 overrides: market-based electricity, custom heat/steam/cooling factors.
     */
    private function resolveCo2eCalculation(
        Request $request,
        EmissionSourceMaster $emissionSource,
        float $quantity,
        ?string $unit,
        EmissionFactor $emissionFactor
    ): array {
        $customFactor = $this->resolveCustomEmissionFactor($request, $emissionSource);

        if ($customFactor !== null && $customFactor > 0) {
            $co2e = round($quantity * $customFactor, 6);

            return [
                'co2e' => number_format($co2e, 6, '.', ''),
                'co2' => null,
                'ch4' => null,
                'n2o' => null,
            ];
        }

        return $this->calculationService->calculateCO2e($quantity, $emissionFactor, $unit);
    }

    /**
     * User-supplied kg CO2e per activity unit (kWh, litres, RT, etc.).
     */
    private function resolveCustomEmissionFactor(Request $request, EmissionSourceMaster $emissionSource): ?float
    {
        if ($emissionSource->quick_input_slug === 'electricity') {
            return $this->resolveSupplierEmissionFactor($request, $emissionSource);
        }

        if ($emissionSource->quick_input_slug === 'heat-steam-cooling') {
            return $this->resolveHeatSteamCoolingEmissionFactor($request);
        }

        return null;
    }

    private function resolveHeatSteamCoolingEmissionFactor(Request $request): ?float
    {
        $methodology = $request->input('emission_factor_methodology', 'default');

        if (!in_array($methodology, ['supplier', 'custom', 'dewa_grid'], true)) {
            return null;
        }

        $factor = $request->input('supplier_emission_factor');
        if ($factor !== null && $factor !== '') {
            return (float) $factor;
        }

        if ($methodology === 'dewa_grid') {
            // DEWA Sustainability Report 2023: 0.3979 tCO2e/MWh = 0.3979 kg CO2e/kWh
            return 0.3979;
        }

        return null;
    }

    private function resolveScope2Method(Request $request, EmissionSourceMaster $emissionSource): ?string
    {
        if ($emissionSource->scope !== 'Scope 2') {
            return null;
        }

        $method = $request->input('scope2_method', 'location');

        return in_array($method, ['location', 'market'], true) ? $method : 'location';
    }

    private function withMethodologyAdditionalData(array $additionalData, Request $request, EmissionSourceMaster $emissionSource): array
    {
        if ($emissionSource->quick_input_slug !== 'heat-steam-cooling') {
            return $additionalData;
        }

        if ($request->filled('emission_factor_methodology')) {
            $additionalData['emission_factor_methodology'] = $request->input('emission_factor_methodology');
        }

        if ($request->filled('methodology_reference')) {
            $additionalData['methodology_reference'] = trim((string) $request->input('methodology_reference'));
        }

        return $additionalData;
    }

    private function resolveSupplierEmissionFactor(Request $request, EmissionSourceMaster $emissionSource): ?float
    {
        if ($emissionSource->quick_input_slug !== 'electricity') {
            return $request->input('supplier_emission_factor')
                ? (float) $request->input('supplier_emission_factor')
                : null;
        }

        if ($this->resolveScope2Method($request, $emissionSource) !== 'market') {
            return null;
        }

        $factor = $request->input('supplier_emission_factor');
        if ($factor !== null && $factor !== '') {
            return (float) $factor;
        }

        // Blend grid factor with zero-emission renewable share when only % is given.
        $renewablePercent = (float) $request->input('renewable_percent', 0);
        if ($renewablePercent > 0) {
            $gridFactor = 0.424; // UAE default kg CO2e/kWh
            $effective = $gridFactor * (1 - min($renewablePercent, 100) / 100);

            return round($effective, 6);
        }

        return null;
    }

    private function determineFuelType(Request $request, EmissionSourceMaster $emissionSource)
    {
        // Handle vehicle-specific fields (simplified: only distance-based)
        if ($emissionSource->quick_input_slug === 'vehicle') {
            $vehicleFuelType = $request->input('vehicle_fuel_type');
            if ($vehicleFuelType) {
                return $vehicleFuelType;
            }
        }

        // Handle process emissions - use process_type as fuel_type
        if ($emissionSource->quick_input_slug === 'process') {
            $processType = $request->input('process_type');
            if ($processType) {
                return $processType;
            }
        }

        // For other sources, use standard mapping
        return $request->input('fuel_type') ?: $request->input('energy_type') ?: $request->input('refrigerant_type');
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

