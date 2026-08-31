<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyDisclosure;
use App\Models\SustainabilityRisk;

/**
 * The Governance pillar dashboard (/governance).
 *
 * Third and last of the pillar dashboards; /governance pointed at
 * EsgDashboardController like the other two, so a Governance URL rendered
 * whole-ESG content.
 *
 * STANDARD: IFRS S1/S2 governance plus GRI 2 and 205/418.
 *   GRI 2-9   governance structure and composition (oversight body)
 *   GRI 2-12  role of the highest governance body in overseeing impacts
 *   GRI 2-13  delegation of responsibility
 *   GRI 2-19  remuneration policies
 *   GRI 205-2 anti-corruption training      205-3 confirmed incidents
 *   GRI 418-1 substantiated privacy complaints / breaches
 *
 * BOARD INDEPENDENCE IS DELIBERATELY ABSENT. The app stores
 * board_diversity_percent -- WOMEN on the board. Independence is
 * NON-EXECUTIVE directors. They are different measures, and presenting one
 * under the other's label would be a misstatement in a regulated disclosure.
 * It needs its own field before it can be shown.
 *
 * THE POLICIES REGISTER IS NOT BUILT. It needs an entity with owner, review
 * date and approval status per policy -- a feature with its own schema, not a
 * dashboard query. What IS shown is the governance DISCLOSURE completeness,
 * which is the same question the register answers ("what is still missing")
 * against data that exists.
 */
class GovernanceDashboardService
{
    /**
     * Governance disclosures shown in the table, with the framework each
     * belongs to. Every field verified present in config/disclosure.php.
     */
    protected const DISCLOSURES = [
        ['framework' => 'ifrs_s2', 'section' => 'governance', 'field' => 'board_oversight_body',         'label' => 'Board / committee responsible',      'code' => 'GRI 2-9'],
        ['framework' => 'ifrs_s2', 'section' => 'governance', 'field' => 'management_accountable_role',  'label' => 'Management accountability',          'code' => 'GRI 2-13'],
        ['framework' => 'ifrs_s2', 'section' => 'governance', 'field' => 'oversight_frequency',          'label' => 'Board oversight frequency',          'code' => 'GRI 2-12'],
        ['framework' => 'ifrs_s2', 'section' => 'governance', 'field' => 'board_climate_integration',    'label' => 'Climate in strategy & decisions',    'code' => 'IFRS S2 §6'],
        ['framework' => 'ifrs_s2', 'section' => 'governance', 'field' => 'target_oversight',             'label' => 'Board oversight of targets',         'code' => 'IFRS S2 §6'],
        ['framework' => 'ifrs_s2', 'section' => 'governance', 'field' => 'board_climate_expertise',      'label' => 'Climate expertise on the board',     'code' => 'IFRS S2 §6'],
        ['framework' => 'ifrs_s2', 'section' => 'governance', 'field' => 'remuneration_linked',          'label' => 'Remuneration linked to climate',     'code' => 'GRI 2-19'],
        ['framework' => 'ifrs_s1', 'section' => 'governance', 'field' => 'sustainability_integration',   'label' => 'Sustainability in strategy',         'code' => 'IFRS S1'],
        ['framework' => 'ifrs_s1', 'section' => 'governance', 'field' => 'material_topics_oversight',    'label' => 'Oversight of material topics',       'code' => 'IFRS S1'],
    ];

    /**
     * Ethics and conduct KPIs, all GRI-coded.
     */
    protected const CONDUCT = [
        ['field' => 'ethics_incidents',              'label' => 'Confirmed corruption incidents',  'code' => 'GRI 205-3', 'format' => 'int'],
        ['field' => 'ethics_training_percent',       'label' => 'Employees given ethics training', 'code' => 'GRI 205-2', 'format' => 'percent'],
        ['field' => 'data_breaches',                 'label' => 'Data privacy breaches',           'code' => 'GRI 418-1', 'format' => 'int'],
        ['field' => 'collective_bargaining_percent', 'label' => 'Covered by collective bargaining','code' => 'GRI 2-30',  'format' => 'percent'],
    ];

    public function __construct(
        protected DisclosureService $disclosureService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(Company $company, int $fiscalYear): array
    {
        $s2 = $this->sections($company->id, 'ifrs_s2', $fiscalYear);
        $s1 = $this->sections($company->id, 'ifrs_s1', $fiscalYear);
        $gri = $this->disclosureService->griSectionsContent($company->id, $fiscalYear);
        $conductData = $gri['governance_metrics'] ?? [];

        return [
            'fiscal_year' => $fiscalYear,
            'kpis' => $this->kpis($s2, $s1, $conductData, $company, $fiscalYear),
            'disclosures' => $this->disclosures($s2, $s1),
            'conduct' => $this->conduct($conductData),
            'remuneration' => $this->remuneration($s2, $s1),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function sections(int $companyId, string $framework, int $fiscalYear): array
    {
        return CompanyDisclosure::where('company_id', $companyId)
            ->where('framework', $framework)
            ->where('fiscal_year', $fiscalYear)
            ->get()
            ->keyBy('section')
            ->map(fn ($r) => $r->content ?? [])
            ->all();
    }

    /**
     * Four headline tiles.
     *
     * Oversight frequency and body come from IFRS S2 governance, falling back
     * to S1 -- a company may complete either first, and both ask the same
     * question of the same board.
     *
     * @return list<array<string, mixed>>
     */
    protected function kpis(array $s2, array $s1, array $conduct, Company $company, int $fiscalYear): array
    {
        $frequency = $this->text($s2, 'governance', 'oversight_frequency')
            ?? $this->text($s1, 'governance', 'oversight_frequency');

        $body = $this->text($s2, 'governance', 'board_oversight_body')
            ?? $this->text($s1, 'governance', 'board_oversight_body');

        $incidents = $this->number($conduct, 'ethics_incidents');
        $breaches = $this->number($conduct, 'data_breaches');

        $riskCount = SustainabilityRisk::where('company_id', $company->id)
            ->where('fiscal_year', $fiscalYear)
            ->count();

        return [
            [
                'label' => 'Board oversight',
                'code' => 'GRI 2-12',
                'display' => $frequency,
                'meta' => $body ?: 'oversight body not stated',
                'meta_missing' => $body === null,
            ],
            [
                'label' => 'Corruption incidents',
                'code' => 'GRI 205-3',
                // 0 is a real, reportable answer -- not "missing".
                'display' => $incidents !== null ? number_format($incidents) : null,
                'meta' => $incidents !== null ? 'confirmed this year' : null,
                'meta_missing' => false,
            ],
            [
                'label' => 'Data breaches',
                'code' => 'GRI 418-1',
                'display' => $breaches !== null ? number_format($breaches) : null,
                'meta' => $breaches !== null ? 'substantiated complaints' : null,
                'meta_missing' => false,
            ],
            [
                'label' => 'Sustainability risks',
                'code' => 'IFRS S1',
                'display' => number_format($riskCount),
                'meta' => $riskCount > 0 ? 'on the register' : 'register empty',
                'meta_missing' => $riskCount === 0,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function disclosures(array $s2, array $s1): array
    {
        $rows = [];
        $done = 0;

        foreach (self::DISCLOSURES as $spec) {
            $source = $spec['framework'] === 'ifrs_s2' ? $s2 : $s1;
            $value = $this->text($source, $spec['section'], $spec['field']);

            $rows[] = [
                'label' => $spec['label'],
                'code' => $spec['code'],
                'framework' => $spec['framework'] === 'ifrs_s2' ? 'IFRS S2' : 'IFRS S1',
                'value' => $value,
                'complete' => $value !== null,
            ];

            $done += $value !== null ? 1 : 0;
        }

        $total = count(self::DISCLOSURES);

        return [
            'rows' => $rows,
            'complete' => $done,
            'total' => $total,
            'percent' => $total > 0 ? (int) round(($done / $total) * 100) : 0,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function conduct(array $data): array
    {
        $out = [];

        foreach (self::CONDUCT as $spec) {
            $value = $this->number($data, $spec['field']);

            $out[] = [
                'label' => $spec['label'],
                'code' => $spec['code'],
                'display' => $value === null
                    ? null
                    : ($spec['format'] === 'percent'
                        ? number_format($value, 1) . '%'
                        : number_format($value)),
            ];
        }

        return $out;
    }

    /**
     * Remuneration linkage (GRI 2-19 / IFRS S1). A free-text field, so this
     * reports only whether it has been ANSWERED -- it deliberately does not
     * parse the text for a yes/no, which would be unreliable and could
     * misstate whether pay is linked to ESG performance.
     *
     * @return array<string, mixed>
     */
    protected function remuneration(array $s2, array $s1): array
    {
        $climate = $this->text($s2, 'governance', 'remuneration_linked');
        $sustainability = $this->text($s1, 'governance', 'remuneration_linked');

        return [
            'climate' => $climate,
            'sustainability' => $sustainability,
            'answered' => $climate !== null || $sustainability !== null,
        ];
    }

    protected function text(array $sections, string $section, string $field): ?string
    {
        $raw = $sections[$section][$field] ?? null;

        return (is_string($raw) && trim($raw) !== '') ? trim($raw) : null;
    }

    protected function number(array $data, string $field): ?float
    {
        $raw = $data[$field] ?? null;

        return ($raw === null || $raw === '' || ! is_numeric($raw)) ? null : (float) $raw;
    }
}
