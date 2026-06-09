<?php

namespace App\Http\Controllers\Disclosure;

use App\Services\DisclosureService;
use Illuminate\Http\Request;

class OverviewController extends DisclosureBaseController
{
    public function __construct(
        protected DisclosureService $disclosureService,
    ) {
    }

    public function index(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        $completeness = $this->disclosureService->completeness($company->id, $fiscalYear);

        return view('disclosures.overview', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'completeness' => $completeness,
        ]);
    }
}
