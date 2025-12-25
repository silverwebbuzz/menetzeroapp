<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmissionSourceMaster;
use App\Models\EmissionFactor;
use App\Models\EmissionGwpValue;
use App\Models\EmissionUnitConversion;
use App\Models\EmissionIndustryLabel;
use App\Models\EmissionFactorSelectionRule;
use App\Models\EmissionSourceFormField;
use App\Models\MasterIndustryCategory;

class EmissionManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Dashboard/Overview
     */
    public function index()
    {
        $stats = [
            'sources' => EmissionSourceMaster::count(),
            'factors' => EmissionFactor::count(),
            'gwp_values' => EmissionGwpValue::count(),
            'unit_conversions' => EmissionUnitConversion::count(),
            'industry_labels' => EmissionIndustryLabel::count(),
            'selection_rules' => EmissionFactorSelectionRule::count(),
        ];

        return view('admin.emissions.index', compact('stats'));
    }

    /**
     * Emission Sources Management
     */
    public function sources(Request $request)
    {
        $query = EmissionSourceMaster::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('scope')) {
            $query->where('scope', $request->scope);
        }

        $sources = $query->orderBy('name')->paginate(20);
        return view('admin.emissions.sources.index', compact('sources'));
    }

    public function createSource()
    {
        return view('admin.emissions.sources.create');
    }

    public function storeSource(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scope' => 'required|in:1,2,3',
            'category' => 'nullable|string',
            'quick_input_slug' => 'nullable|string|unique:emission_sources_master,quick_input_slug',
            'is_quick_input' => 'boolean',
            'quick_input_order' => 'nullable|integer',
        ]);

        EmissionSourceMaster::create($request->all());

        return redirect()->route('admin.emissions.sources')
            ->with('success', 'Emission source created successfully');
    }

    public function editSource($id)
    {
        $source = EmissionSourceMaster::findOrFail($id);
        return view('admin.emissions.sources.edit', compact('source'));
    }

    public function updateSource(Request $request, $id)
    {
        $source = EmissionSourceMaster::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scope' => 'required|in:1,2,3',
            'category' => 'nullable|string',
            'quick_input_slug' => 'nullable|string|unique:emission_sources_master,quick_input_slug,' . $id,
            'is_quick_input' => 'boolean',
            'quick_input_order' => 'nullable|integer',
        ]);

        $source->update($request->all());

        return redirect()->route('admin.emissions.sources')
            ->with('success', 'Emission source updated successfully');
    }

    public function destroySource($id)
    {
        $source = EmissionSourceMaster::findOrFail($id);
        $source->delete();

        return redirect()->route('admin.emissions.sources')
            ->with('success', 'Emission source deleted successfully');
    }

    /**
     * Emission Factors Management
     */
    public function factors(Request $request)
    {
        $query = EmissionFactor::with('emissionSource');

        if ($request->filled('source_id')) {
            $query->where('emission_source_id', $request->source_id);
        }

        $factors = $query->orderBy('created_at', 'desc')->paginate(20);
        $sources = EmissionSourceMaster::orderBy('name')->get();

        return view('admin.emissions.factors.index', compact('factors', 'sources'));
    }

    public function createFactor()
    {
        $sources = EmissionSourceMaster::orderBy('name')->get();
        return view('admin.emissions.factors.create', compact('sources'));
    }

    public function storeFactor(Request $request)
    {
        $request->validate([
            'emission_source_id' => 'required|exists:emission_sources_master,id',
            'factor_value' => 'required|numeric',
            'unit' => 'required|string',
            'region' => 'nullable|string',
            'co2_factor' => 'nullable|numeric',
            'ch4_factor' => 'nullable|numeric',
            'n2o_factor' => 'nullable|numeric',
            'total_co2e_factor' => 'nullable|numeric',
            'gwp_version' => 'nullable|string',
            'fuel_category' => 'nullable|string|max:100',
            'fuel_type' => 'nullable|string|max:100',
        ]);

        EmissionFactor::create($request->all());

        return redirect()->route('admin.emissions.factors')
            ->with('success', 'Emission factor created successfully');
    }

    public function editFactor($id)
    {
        $factor = EmissionFactor::with('emissionSource')->findOrFail($id);
        $sources = EmissionSourceMaster::orderBy('name')->get();
        return view('admin.emissions.factors.edit', compact('factor', 'sources'));
    }

    public function updateFactor(Request $request, $id)
    {
        $factor = EmissionFactor::findOrFail($id);

        $request->validate([
            'emission_source_id' => 'required|exists:emission_sources_master,id',
            'factor_value' => 'required|numeric',
            'unit' => 'required|string',
            'region' => 'nullable|string',
            'co2_factor' => 'nullable|numeric',
            'ch4_factor' => 'nullable|numeric',
            'n2o_factor' => 'nullable|numeric',
            'total_co2e_factor' => 'nullable|numeric',
            'gwp_version' => 'nullable|string',
            'fuel_category' => 'nullable|string|max:100',
            'fuel_type' => 'nullable|string|max:100',
        ]);

        $factor->update($request->all());

        return redirect()->route('admin.emissions.factors')
            ->with('success', 'Emission factor updated successfully');
    }

    public function destroyFactor($id)
    {
        $factor = EmissionFactor::findOrFail($id);
        $factor->delete();

        return redirect()->route('admin.emissions.factors')
            ->with('success', 'Emission factor deleted successfully');
    }

    /**
     * GWP Values Management
     */
    public function gwpValues(Request $request)
    {
        $query = EmissionGwpValue::query();

        if ($request->filled('gwp_version')) {
            $query->where('gwp_version', $request->gwp_version);
        }

        $gwpValues = $query->orderBy('gas_name')->paginate(20);
        return view('admin.emissions.gwp-values.index', compact('gwpValues'));
    }

    public function createGwpValue()
    {
        return view('admin.emissions.gwp-values.create');
    }

    public function storeGwpValue(Request $request)
    {
        $request->validate([
            'gas_name' => 'required|string|max:100',
            'gas_code' => 'nullable|string|max:50',
            'gwp_version' => 'required|in:AR4,AR5,AR6',
            'gwp_100_year' => 'required|numeric',
            'gwp_20_year' => 'nullable|numeric',
            'gwp_500_year' => 'nullable|numeric',
            'is_kyoto_protocol' => 'boolean',
        ]);

        EmissionGwpValue::create($request->all());

        return redirect()->route('admin.emissions.gwp-values')
            ->with('success', 'GWP value created successfully');
    }

    public function editGwpValue($id)
    {
        $gwpValue = EmissionGwpValue::findOrFail($id);
        return view('admin.emissions.gwp-values.edit', compact('gwpValue'));
    }

    public function updateGwpValue(Request $request, $id)
    {
        $gwpValue = EmissionGwpValue::findOrFail($id);

        $request->validate([
            'gas_name' => 'required|string|max:100',
            'gas_code' => 'nullable|string|max:50',
            'gwp_version' => 'required|in:AR4,AR5,AR6',
            'gwp_100_year' => 'required|numeric',
            'gwp_20_year' => 'nullable|numeric',
            'gwp_500_year' => 'nullable|numeric',
            'is_kyoto_protocol' => 'boolean',
        ]);

        $gwpValue->update($request->all());

        return redirect()->route('admin.emissions.gwp-values')
            ->with('success', 'GWP value updated successfully');
    }

    public function destroyGwpValue($id)
    {
        $gwpValue = EmissionGwpValue::findOrFail($id);
        $gwpValue->delete();

        return redirect()->route('admin.emissions.gwp-values')
            ->with('success', 'GWP value deleted successfully');
    }

    /**
     * Unit Conversions Management
     */
    public function unitConversions(Request $request)
    {
        $query = EmissionUnitConversion::query();

        if ($request->filled('from_unit')) {
            $query->where('from_unit', 'like', '%' . $request->from_unit . '%');
        }

        $conversions = $query->orderBy('from_unit')->paginate(20);
        return view('admin.emissions.unit-conversions.index', compact('conversions'));
    }

    public function createUnitConversion()
    {
        return view('admin.emissions.unit-conversions.create');
    }

    public function storeUnitConversion(Request $request)
    {
        $request->validate([
            'from_unit' => 'required|string|max:50',
            'to_unit' => 'required|string|max:50',
            'conversion_factor' => 'required|numeric',
            'fuel_type' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active');

        EmissionUnitConversion::create($data);

        return redirect()->route('admin.emissions.unit-conversions')
            ->with('success', 'Unit conversion created successfully');
    }

    public function editUnitConversion($id)
    {
        $conversion = EmissionUnitConversion::findOrFail($id);
        return view('admin.emissions.unit-conversions.edit', compact('conversion'));
    }

    public function updateUnitConversion(Request $request, $id)
    {
        $conversion = EmissionUnitConversion::findOrFail($id);

        $request->validate([
            'from_unit' => 'required|string|max:50',
            'to_unit' => 'required|string|max:50',
            'conversion_factor' => 'required|numeric',
            'fuel_type' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active');

        $conversion->update($data);

        return redirect()->route('admin.emissions.unit-conversions')
            ->with('success', 'Unit conversion updated successfully');
    }

    public function destroyUnitConversion($id)
    {
        $conversion = EmissionUnitConversion::findOrFail($id);
        $conversion->delete();

        return redirect()->route('admin.emissions.unit-conversions')
            ->with('success', 'Unit conversion deleted successfully');
    }

    /**
     * Industry Labels Management
     */
    public function industryLabels(Request $request)
    {
        $query = EmissionIndustryLabel::with(['emissionSource', 'industryCategory']);

        if ($request->filled('source_id')) {
            $query->where('emission_source_id', $request->source_id);
        }

        $labels = $query->orderBy('display_order')->paginate(20);
        $sources = EmissionSourceMaster::orderBy('name')->get();
        $categories = MasterIndustryCategory::orderBy('name')->get();

        return view('admin.emissions.industry-labels.index', compact('labels', 'sources', 'categories'));
    }

    public function createIndustryLabel()
    {
        $sources = EmissionSourceMaster::orderBy('name')->get();
        $categories = MasterIndustryCategory::orderBy('name')->get();
        return view('admin.emissions.industry-labels.create', compact('sources', 'categories'));
    }

    public function storeIndustryLabel(Request $request)
    {
        $request->validate([
            'emission_source_id' => 'required|exists:emission_sources_master,id',
            'industry_category_id' => 'nullable|exists:master_industry_categories,id',
            'user_friendly_name' => 'required|string|max:255',
            'match_level' => 'nullable|in:1,2,3',
            'unit_type' => 'nullable|string|max:255',
            'display_order' => 'nullable|integer',
            'also_match_children' => 'boolean',
            'is_active' => 'boolean',
            'user_friendly_description' => 'nullable|string',
            'typical_units' => 'nullable|string|max:255',
        ]);

        $data = $request->all();
        $data['also_match_children'] = $request->has('also_match_children');
        $data['is_active'] = $request->has('is_active');

        EmissionIndustryLabel::create($data);

        return redirect()->route('admin.emissions.industry-labels')
            ->with('success', 'Industry label created successfully');
    }

    public function editIndustryLabel($id)
    {
        $label = EmissionIndustryLabel::with(['emissionSource', 'industryCategory'])->findOrFail($id);
        $sources = EmissionSourceMaster::orderBy('name')->get();
        $categories = MasterIndustryCategory::orderBy('name')->get();
        return view('admin.emissions.industry-labels.edit', compact('label', 'sources', 'categories'));
    }

    public function updateIndustryLabel(Request $request, $id)
    {
        $label = EmissionIndustryLabel::findOrFail($id);

        $request->validate([
            'emission_source_id' => 'required|exists:emission_sources_master,id',
            'industry_category_id' => 'nullable|exists:master_industry_categories,id',
            'user_friendly_name' => 'required|string|max:255',
            'match_level' => 'nullable|in:1,2,3',
            'unit_type' => 'nullable|string|max:255',
            'display_order' => 'nullable|integer',
            'also_match_children' => 'boolean',
            'is_active' => 'boolean',
            'user_friendly_description' => 'nullable|string',
            'typical_units' => 'nullable|string|max:255',
        ]);

        $data = $request->all();
        $data['also_match_children'] = $request->has('also_match_children');
        $data['is_active'] = $request->has('is_active');

        $label->update($data);

        return redirect()->route('admin.emissions.industry-labels')
            ->with('success', 'Industry label updated successfully');
    }

    public function destroyIndustryLabel($id)
    {
        $label = EmissionIndustryLabel::findOrFail($id);
        $label->delete();

        return redirect()->route('admin.emissions.industry-labels')
            ->with('success', 'Industry label deleted successfully');
    }

    /**
     * Selection Rules Management
     */
    public function selectionRules(Request $request)
    {
        $query = EmissionFactorSelectionRule::with(['emissionSource', 'emissionFactor']);

        if ($request->filled('source_id')) {
            $query->where('emission_source_id', $request->source_id);
        }

        $rules = $query->orderBy('priority', 'desc')->paginate(20);
        $sources = EmissionSourceMaster::orderBy('name')->get();

        return view('admin.emissions.selection-rules.index', compact('rules', 'sources'));
    }

    public function createSelectionRule()
    {
        $sources = EmissionSourceMaster::orderBy('name')->get();
        $factors = EmissionFactor::orderBy('created_at', 'desc')->get();
        return view('admin.emissions.selection-rules.create', compact('sources', 'factors'));
    }

    public function storeSelectionRule(Request $request)
    {
        $request->validate([
            'emission_source_id' => 'required|exists:emission_sources_master,id',
            'rule_name' => 'required|string|max:255',
            'priority' => 'nullable|integer',
            'conditions' => 'nullable|string',
            'emission_factor_id' => 'nullable|exists:emission_factors,id',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active');
        
        // Parse JSON conditions if provided
        if ($request->filled('conditions')) {
            $decoded = json_decode($request->conditions, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['conditions'] = $decoded;
            } else {
                $data['conditions'] = [];
            }
        } else {
            $data['conditions'] = [];
        }

        EmissionFactorSelectionRule::create($data);

        return redirect()->route('admin.emissions.selection-rules')
            ->with('success', 'Selection rule created successfully');
    }

    public function editSelectionRule($id)
    {
        $rule = EmissionFactorSelectionRule::with(['emissionSource', 'emissionFactor'])->findOrFail($id);
        $sources = EmissionSourceMaster::orderBy('name')->get();
        $factors = EmissionFactor::orderBy('created_at', 'desc')->get();
        return view('admin.emissions.selection-rules.edit', compact('rule', 'sources', 'factors'));
    }

    public function updateSelectionRule(Request $request, $id)
    {
        $rule = EmissionFactorSelectionRule::findOrFail($id);

        $request->validate([
            'emission_source_id' => 'required|exists:emission_sources_master,id',
            'rule_name' => 'required|string|max:255',
            'priority' => 'nullable|integer',
            'conditions' => 'nullable|string',
            'emission_factor_id' => 'nullable|exists:emission_factors,id',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active');
        
        // Parse JSON conditions if provided
        if ($request->filled('conditions')) {
            $decoded = json_decode($request->conditions, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['conditions'] = $decoded;
            } else {
                $data['conditions'] = $rule->conditions ?? [];
            }
        } else {
            $data['conditions'] = $rule->conditions ?? [];
        }

        $rule->update($data);

        return redirect()->route('admin.emissions.selection-rules')
            ->with('success', 'Selection rule updated successfully');
    }

    public function destroySelectionRule($id)
    {
        $rule = EmissionFactorSelectionRule::findOrFail($id);
        $rule->delete();

        return redirect()->route('admin.emissions.selection-rules')
            ->with('success', 'Selection rule deleted successfully');
    }
}

