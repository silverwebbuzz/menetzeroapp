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

        return view('settings.reporting', [
            'company' => $company,
            'settings' => $settings,
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
                'gwp_version' => $validated['gwp_version'],
                'scope3_category_policy' => $policy,
            ]
        );

        return redirect()
            ->route('settings.reporting', ['fiscal_year' => $validated['fiscal_year']])
            ->with('success', 'Reporting settings saved.');
    }
}
