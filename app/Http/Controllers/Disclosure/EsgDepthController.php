<?php

namespace App\Http\Controllers\Disclosure;

use App\Models\EsgSustainabilityTarget;
use App\Models\StakeholderEngagement;
use App\Models\SupplyChainSupplier;
use App\Services\DisclosureService;
use Illuminate\Http\Request;

class EsgDepthController extends DisclosureBaseController
{
    public function __construct(
        protected DisclosureService $disclosureService,
    ) {
    }

    public function overview(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        $topics = $this->disclosureService->materialTopicsForCompany($company->id, $fiscalYear);

        return view('disclosures.esg-depth.overview', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'stakeholderCount' => StakeholderEngagement::where('company_id', $company->id)->where('fiscal_year', $fiscalYear)->count(),
            'supplierCount' => SupplyChainSupplier::where('company_id', $company->id)->where('fiscal_year', $fiscalYear)->count(),
            'esgTargetCount' => EsgSustainabilityTarget::where('company_id', $company->id)->where('status', 'active')->count(),
            'materialTopicCount' => collect($topics)->where('is_material', true)->count(),
            'matrixComplete' => collect($topics)->filter(fn ($t) => $t['impact_materiality'] && $t['financial_materiality'])->count(),
        ]);
    }
}
