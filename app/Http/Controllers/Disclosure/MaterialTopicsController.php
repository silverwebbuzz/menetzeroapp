<?php

namespace App\Http\Controllers\Disclosure;

use App\Services\DisclosureService;
use Illuminate\Http\Request;

class MaterialTopicsController extends DisclosureBaseController
{
    public function __construct(
        protected DisclosureService $disclosureService,
    ) {
    }

    public function edit(Request $request)
    {
        return $this->show($request, 'ifrs_s1');
    }

    public function editGri(Request $request)
    {
        return $this->show($request, 'gri');
    }

    protected function show(Request $request, string $context)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        return view('disclosures.material-topics', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'framework' => $context === 'gri' ? 'gri' : 'ifrs_s1',
            'topics' => $this->disclosureService->materialTopicsForCompany($company->id, $fiscalYear),
        ]);
    }

    public function update(Request $request)
    {
        return $this->save($request, 'disclosures.s1.material-topics');
    }

    public function updateGri(Request $request)
    {
        return $this->save($request, 'disclosures.gri.material-topics');
    }

    protected function save(Request $request, string $redirectRoute)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);

        $this->disclosureService->syncMaterialTopics(
            $company->id,
            $fiscalYear,
            $request->input('topics', [])
        );

        return $this->fiscalRedirect($redirectRoute, $fiscalYear, 'Material topics saved.');
    }
}
