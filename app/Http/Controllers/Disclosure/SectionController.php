<?php

namespace App\Http\Controllers\Disclosure;

use App\Services\DisclosureService;
use Illuminate\Http\Request;

class SectionController extends DisclosureBaseController
{
    public function __construct(
        protected DisclosureService $disclosureService,
    ) {
    }

    public function edit(Request $request, string $section)
    {
        if (!in_array($section, $this->disclosureService->validSections(), true)) {
            abort(404);
        }

        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);
        $config = $this->disclosureService->sectionConfig($section);
        $record = $this->disclosureService->getSection($company->id, $fiscalYear, $section);

        return view('disclosures.section', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'section' => $section,
            'config' => $config,
            'record' => $record,
            'content' => $record->content ?? [],
        ]);
    }

    public function update(Request $request, string $section)
    {
        if (!in_array($section, $this->disclosureService->validSections(), true)) {
            abort(404);
        }

        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);

        $this->disclosureService->saveSection(
            $company->id,
            $fiscalYear,
            $section,
            $request->input('content', [])
        );

        return $this->fiscalRedirect(
            'disclosures.sections.edit',
            $fiscalYear,
            'Disclosure section saved.',
            ['section' => $section]
        );
    }
}
