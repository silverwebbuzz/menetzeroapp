<?php

namespace App\Http\Controllers\Disclosure;

use App\Models\StakeholderEngagement;
use Illuminate\Http\Request;

class StakeholderEngagementController extends DisclosureBaseController
{
    public function index(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        return view('disclosures.stakeholders.index', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'engagements' => StakeholderEngagement::where('company_id', $company->id)
                ->where('fiscal_year', $fiscalYear)
                ->orderBy('stakeholder_group')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);

        StakeholderEngagement::create(array_merge(
            $this->validateEngagement($request),
            ['company_id' => $company->id, 'fiscal_year' => $fiscalYear]
        ));

        return $this->fiscalRedirect('disclosures.stakeholders.index', $fiscalYear, 'Stakeholder engagement added.');
    }

    public function update(Request $request, StakeholderEngagement $stakeholderEngagement)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);
        $this->assertOwned($stakeholderEngagement, $company->id, $fiscalYear);

        $stakeholderEngagement->update($this->validateEngagement($request));

        return $this->fiscalRedirect('disclosures.stakeholders.index', $fiscalYear, 'Stakeholder engagement updated.');
    }

    public function destroy(Request $request, StakeholderEngagement $stakeholderEngagement)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);
        $this->assertOwned($stakeholderEngagement, $company->id, $fiscalYear);

        $stakeholderEngagement->delete();

        return $this->fiscalRedirect('disclosures.stakeholders.index', $fiscalYear, 'Stakeholder engagement removed.');
    }

    protected function validateEngagement(Request $request): array
    {
        return $request->validate([
            'stakeholder_group' => 'required|string|max:255',
            'engagement_method' => 'nullable|string|max:100',
            'frequency' => 'nullable|in:ongoing,quarterly,biannual,annual,ad_hoc',
            'topics_discussed' => 'nullable|string|max:5000',
            'outcomes' => 'nullable|string|max:5000',
            'last_engaged_at' => 'nullable|date',
        ]);
    }

    protected function assertOwned(StakeholderEngagement $record, int $companyId, int $fiscalYear): void
    {
        if ($record->company_id !== $companyId || $record->fiscal_year !== $fiscalYear) {
            abort(404);
        }
    }
}
