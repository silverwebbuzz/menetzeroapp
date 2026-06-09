<?php

namespace App\Http\Controllers\Disclosure;

use App\Services\EsgDashboardService;
use Illuminate\Http\Request;

class EsgDashboardController extends DisclosureBaseController
{
    public function __construct(
        protected EsgDashboardService $dashboardService,
    ) {
    }

    public function index(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        return view('disclosures.esg-dashboard', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'dashboard' => $this->dashboardService->build($company, $fiscalYear),
        ]);
    }
}
