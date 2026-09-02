<?php

namespace App\Http\Controllers;

use App\Models\CompanyReportingSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyReportingSettingsController extends Controller
{
    public function edit(Request $request)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            abort(403, 'No active company found.');
        }

        $fiscalYear = (int) $request->input('fiscal_year', now()->year);

        $settings = CompanyReportingSetting::firstOrCreate(
            ['company_id' => $company->id, 'fiscal_year' => $fiscalYear],
            [
                'organisational_boundary' => 'operational_control',
                'consolidation_approach' => 'operational_control',
                'gwp_version' => 'AR6',
                'scope3_category_policy' => CompanyReportingSetting::defaultScope3Policy(),
            ]
        );

        // The base-year nudge is shown only when the question is answerable:
        // there is data to measure against, and no base year set yet. Prompting
        // before either is true asks the user to guess.
        $entryCount = $company->carbonEmissions()->count();
        $earliestEntry = $entryCount > 0
            ? $company->carbonEmissions()->min('created_at')
            : null;

        return view('settings.reporting', [
            'company' => $company,
            'settings' => $settings,
            'entryCount' => $entryCount,
            'earliestEntry' => $earliestEntry,
            'fiscalYear' => $fiscalYear,
            'boundaries' => CompanyReportingSetting::BOUNDARIES,
            'scope3Categories' => CompanyReportingSetting::SCOPE3_CATEGORIES,
        ]);
    }

    public function update(Request $request)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            abort(403, 'No active company found.');
        }

        $validated = $request->validate([
            'fiscal_year' => 'required|integer|min:2000|max:2100',
            'organisational_boundary' => 'required|in:operational_control,equity_share,financial_control',
            'consolidation_approach' => 'required|in:operational_control,equity_share,financial_control',
            'base_year' => 'nullable|integer|min:1990|max:2100',
            'base_year_rationale' => 'nullable|string|max:2000',
            'recalculation_policy' => 'nullable|string|max:2000',
            'recalculation_threshold_percent' => 'nullable|numeric|min:0|max:100',
            'intensity_denominator_type' => 'nullable|in:revenue,floor_area,employees,production',
            'intensity_denominator_value' => 'nullable|numeric|min:0',
            'intensity_denominator_unit' => 'nullable|string|max:40',
            'gwp_version' => 'required|in:AR4,AR5,AR6',
            'scope3_included' => 'nullable|array',
            'scope3_included.*' => 'integer|min:1|max:15',
            'scope3_reason' => 'nullable|array',
            'scope3_reason.*' => 'nullable|string|max:500',
        ]);

        $policy = [];
        foreach (CompanyReportingSetting::SCOPE3_CATEGORIES as $cat => $label) {
            $included = in_array($cat, $validated['scope3_included'] ?? [], true);
            $policy[] = [
                'category' => $cat,
                'label' => $label,
                'included' => $included,
                'reason' => $included ? null : ($validated['scope3_reason'][$cat] ?? 'Not material / no data'),
            ];
        }

        CompanyReportingSetting::updateOrCreate(
            ['company_id' => $company->id, 'fiscal_year' => $validated['fiscal_year']],
            [
                'organisational_boundary' => $validated['organisational_boundary'],
                'consolidation_approach' => $validated['consolidation_approach'],
                'base_year' => $validated['base_year'],
                'base_year_rationale' => $validated['base_year_rationale'],
                'recalculation_policy' => $validated['recalculation_policy'],
                'recalculation_threshold_percent' => $validated['recalculation_threshold_percent'] ?? 5,
                'intensity_denominator_type' => $validated['intensity_denominator_type'] ?? null,
                'intensity_denominator_value' => $validated['intensity_denominator_value'] ?? null,
                // Default the unit label from the chosen denominator so the
                // dashboard always has something readable to print.
                'intensity_denominator_unit' => $validated['intensity_denominator_unit']
                    ?: (CompanyReportingSetting::INTENSITY_DENOMINATORS[$validated['intensity_denominator_type'] ?? '']['unit'] ?? null),
                'gwp_version' => $validated['gwp_version'],
                'scope3_category_policy' => $policy,
            ]
        );

        return redirect()
            ->route('settings.reporting', ['fiscal_year' => $validated['fiscal_year']])
            ->with('success', 'Reporting settings saved.');
    }
}
