<?php

namespace App\Http\Controllers\Disclosure;

use App\Services\EnvironmentalDashboardService;
use Illuminate\Http\Request;

/**
 * /environmental -- the Environmental pillar dashboard.
 *
 * Replaces EsgDashboardController on this route only. That controller answers
 * "how is my whole ESG programme doing" and still serves its own pages
 * unchanged; this one answers "what are my emissions".
 *
 * Extends DisclosureBaseController so resolveContext() applies the same
 * permission check and fiscal-year resolution as every other disclosure page.
 */
class EnvironmentalDashboardController extends DisclosureBaseController
{
    public function __construct(
        protected EnvironmentalDashboardService $environmental,
    ) {
    }

    public function index(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        return view('environmental.index', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'env' => $this->environmental->build($company, $fiscalYear),
        ]);
    }
}
