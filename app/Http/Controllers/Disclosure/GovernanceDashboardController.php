<?php

namespace App\Http\Controllers\Disclosure;

use App\Services\GovernanceDashboardService;
use Illuminate\Http\Request;

/**
 * /governance -- the Governance pillar dashboard.
 *
 * Last of the three pillar roots to be repointed away from
 * EsgDashboardController, which answers a different question (whole-ESG
 * programme health) and is unchanged -- it still serves
 * /disclosures/esg-dashboard, linked from the Disclosure hub and the ESG
 * scorecard in both themes.
 */
class GovernanceDashboardController extends DisclosureBaseController
{
    public function __construct(
        protected GovernanceDashboardService $governance,
    ) {
    }

    public function index(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        return view('governance.index', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'gov' => $this->governance->build($company, $fiscalYear),
        ]);
    }
}
