<?php

namespace App\Http\Controllers\Disclosure;

use App\Services\DisclosureService;
use Illuminate\Http\Request;

class SectionController extends DisclosureBaseController
{
    private const FRAMEWORKS = ['ifrs_s2', 'ifrs_s1', 'gri'];

    public function __construct(
        protected DisclosureService $disclosureService,
    ) {
    }

    public function editS2(Request $request, string $section)
    {
        return $this->edit($request, 'ifrs_s2', $section);
    }

    public function updateS2(Request $request, string $section)
    {
        return $this->update($request, 'ifrs_s2', $section);
    }

    public function editS1(Request $request, string $section)
    {
        return $this->edit($request, 'ifrs_s1', $section);
    }

    public function updateS1(Request $request, string $section)
    {
        return $this->update($request, 'ifrs_s1', $section);
    }

    public function editGri(Request $request, string $section)
    {
        return $this->edit($request, 'gri', $section);
    }

    public function updateGri(Request $request, string $section)
    {
        return $this->update($request, 'gri', $section);
    }

    protected function edit(Request $request, string $framework, string $section)
    {
        $this->assertFramework($framework);

        if (!in_array($section, $this->disclosureService->validSections($framework), true)) {
            abort(404);
        }

        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);
        $config = $this->disclosureService->sectionConfig($framework, $section);
        $record = $this->disclosureService->getSection($company->id, $fiscalYear, $section, $framework);

        return view('disclosures.section', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'framework' => $framework,
            'section' => $section,
            'config' => $config,
            'record' => $record,
            'content' => $record->content ?? [],
        ]);
    }

    protected function update(Request $request, string $framework, string $section)
    {
        $this->assertFramework($framework);

        if (!in_array($section, $this->disclosureService->validSections($framework), true)) {
            abort(404);
        }

        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);

        $this->disclosureService->saveSection(
            $company->id,
            $fiscalYear,
            $section,
            $request->input('content', []),
            $framework
        );

        $route = match ($framework) {
            'ifrs_s1' => 'disclosures.s1.sections.edit',
            'gri' => 'disclosures.gri.sections.edit',
            default => 'disclosures.s2.sections.edit',
        };

        return $this->fiscalRedirect($route, $fiscalYear, 'Disclosure section saved.', ['section' => $section]);
    }

    protected function assertFramework(string $framework): void
    {
        if (!in_array($framework, self::FRAMEWORKS, true)) {
            abort(404);
        }
    }
}
