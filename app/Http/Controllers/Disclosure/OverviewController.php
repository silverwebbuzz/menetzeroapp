<?php

namespace App\Http\Controllers\Disclosure;

use App\Services\DisclosureService;
use App\Services\UaeEsgReportService;
use Illuminate\Http\Request;

class OverviewController extends DisclosureBaseController
{
    public function __construct(
        protected DisclosureService $disclosureService,
        protected UaeEsgReportService $uaeEsgReportService,
    ) {
    }

    public function hub(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        return view('disclosures.hub', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            's2Completeness' => $this->disclosureService->completenessS2($company->id, $fiscalYear),
            's1Completeness' => $this->disclosureService->completenessS1($company->id, $fiscalYear),
            'griCompleteness' => $this->disclosureService->completenessGri($company->id, $fiscalYear),
            'uaeEsgCompleteness' => $this->uaeEsgReportService->build($company, $fiscalYear)['completeness'],
        ]);
    }

    public function s2(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        return view('disclosures.overview', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'framework' => 'ifrs_s2',
            'completeness' => $this->disclosureService->completenessS2($company->id, $fiscalYear),
        ]);
    }

    public function s1(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        return view('disclosures.s1-overview', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'framework' => 'ifrs_s1',
            'completeness' => $this->disclosureService->completenessS1($company->id, $fiscalYear),
        ]);
    }

    public function gri(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        return view('disclosures.gri-overview', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'framework' => 'gri',
            'completeness' => $this->disclosureService->completenessGri($company->id, $fiscalYear),
        ]);
    }

    public function uaeEsg(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);
        $report = $this->uaeEsgReportService->build($company, $fiscalYear);

        return view('disclosures.uae-esg-overview', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'framework' => 'esg_report',
            'completeness' => $report['completeness'],
            'sectionConfig' => config('esg_report.sections', []),
            'assuranceDocument' => $report['assurance_document'],
        ]);
    }
}
