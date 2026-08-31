<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\SustainabilityRisk;
use Illuminate\Database\Seeder;

/**
 * IFRS S1 sustainability risks for a demo company.
 *
 * ConsultantFullDemoSeeder imports SustainabilityRisk but never writes any, so
 * /disclosures/ifrs-s1/sustainability-risks renders empty on a fully seeded
 * demo while the S2 climate register is populated. This fills that gap.
 *
 * Every row carries likelihood, owner and financial_impact. Those columns have
 * always existed on sustainability_risks and the controller has always saved
 * them; until recently the form simply never asked, so the four completeness
 * tiles on the register would have read 0 quantified / all unassigned no
 * matter what was seeded.
 *
 * Deliberately NOT climate risks. Climate belongs to the IFRS S2 register
 * (ClimateRisk, seeded by ConsultantFullDemoSeeder) and duplicating it here
 * would put the same exposure in two registers with no single owner. The one
 * climate row below is a cross-reference topic only.
 *
 * Two rows are intentionally incomplete — one with no financial_impact, one
 * with no owner — so the "Quantified" and "Without owner" tiles show a real
 * gap rather than a perfect score. A register where every field is filled is
 * not what an actual first-year filer looks like.
 *
 * Demo data only — never wire this into a migration or DatabaseSeeder::run().
 * Run explicitly:
 *   php artisan db:seed --class=FalconSustainabilityRiskSeeder
 */
class FalconSustainabilityRiskSeeder extends Seeder
{
    /**
     * Company to seed. Overridable via FALCON_DEMO_COMPANY env var, matching
     * FalconHistoricalDataSeeder so both target the same demo client.
     */
    private const DEFAULT_COMPANY = 'Falcon Industrial Parks';

    public function run(): void
    {
        $companyName = env('FALCON_DEMO_COMPANY', self::DEFAULT_COMPANY);

        $company = Company::where('name', $companyName)->first();

        if (!$company) {
            $this->command?->error("Company \"{$companyName}\" not found. Run ConsultantFullDemoSeeder first.");

            return;
        }

        // Seed the current reporting year plus the two backfilled by
        // FalconHistoricalDataSeeder, so switching the year selector does not
        // drop the register to empty.
        $currentYear = (int) date('Y');
        $years = [$currentYear - 2, $currentYear - 1, $currentYear];

        $seeded = 0;

        foreach ($years as $year) {
            foreach ($this->risksForYear($year, $currentYear) as $risk) {
                SustainabilityRisk::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'fiscal_year' => $year,
                        'name' => $risk['name'],
                    ],
                    $risk
                );
                $seeded++;
            }

            $this->command?->info("Seeded {$year} sustainability risks for {$companyName}");
        }

        $this->command?->info("Done — {$seeded} sustainability risk rows.");
    }

    /**
     * Risks for one fiscal year.
     *
     * Earlier years carry fewer rows and less quantification: a register grows
     * as a company matures, and showing 2024 already complete would make the
     * year-on-year view meaningless. Topic keys are the ten defined in
     * config/disclosure.php ifrs_s1.material_topics — an unknown key would
     * fall back to the raw string in topicLabel() and read as a bug.
     *
     * @return array<int, array<string, string>>
     */
    private function risksForYear(int $year, int $currentYear): array
    {
        $risks = [
            [
                'name' => 'Groundwater stress at inland industrial sites',
                'topic' => 'water',
                'time_horizon' => 'medium',
                'description' => 'Warehouse washdown, dust suppression and landscaping draw on municipal supply in a water-scarce jurisdiction. Tariff reform or abstraction limits would raise operating costs across the inland sites.',
                'financial_impact' => 'AED 180,000–320,000 a year if industrial tariffs rise to the announced band.',
                'likelihood' => 'medium',
                'mitigation' => 'Sub-metering installed at two sites; greywater reuse for landscaping under evaluation.',
                'owner' => 'Facilities Director',
                'status' => 'monitoring',
            ],
            [
                'name' => 'Heat stress exposure for outdoor loading crews',
                'topic' => 'health_safety',
                'time_horizon' => 'short',
                'description' => 'Loading bays and yard operations are open-air. The midday break rule shortens the productive day in summer, and heat illness remains the most likely cause of a lost-time injury.',
                'financial_impact' => 'AED 240,000 a year in shift premiums and shaded-bay retrofit; excludes any injury claim.',
                'likelihood' => 'high',
                'mitigation' => 'Enforced midday break, rotation schedule, shaded bays at the two largest yards, mandatory hydration stations.',
                'owner' => 'HSE Manager',
                'status' => 'open',
            ],
            [
                'name' => 'Labour standards in contracted logistics workforce',
                'topic' => 'workforce',
                'time_horizon' => 'medium',
                'description' => 'A significant share of yard and driver headcount is supplied through third-party manpower agencies, so accommodation standards, wage timeliness and passport-retention practices sit outside direct control.',
                // Left unquantified on purpose: no audit has been run, so any
                // figure here would be invented. "Not quantified" is the
                // honest state and is what the register should show.
                'financial_impact' => '',
                'likelihood' => 'medium',
                'mitigation' => 'Contract clauses on wage protection added at renewal; on-site accommodation audit scheduled.',
                'owner' => 'Head of Human Resources',
                'status' => 'open',
            ],
            [
                'name' => 'Tier-1 supplier ESG screening gap',
                'topic' => 'supply_chain',
                'time_horizon' => 'medium',
                'description' => 'Client RFPs increasingly require supplier ESG screening evidence. Only a minority of tier-1 suppliers have completed any assessment, which is becoming a qualification risk on tenders.',
                'financial_impact' => 'Revenue at risk on tenders requiring supplier screening evidence; not yet sized.',
                'likelihood' => 'medium',
                'mitigation' => 'Supplier register built; screening questionnaire issued to the largest suppliers by spend.',
                // Left unassigned on purpose: procurement ownership genuinely
                // has not been allocated. Naming a plausible role would hide
                // exactly the gap the "Without owner" tile exists to show.
                'owner' => '',
                'status' => 'open',
            ],
            [
                'name' => 'Waste segregation and landfill diversion shortfall',
                'topic' => 'waste',
                'time_horizon' => 'short',
                'description' => 'Packaging, pallet and general waste streams are only partly segregated at source, so diversion rates fall short of what municipal contracts and client scorecards increasingly ask for.',
                'financial_impact' => 'AED 95,000 a year in avoidable landfill gate fees at current volumes.',
                'likelihood' => 'high',
                'mitigation' => 'Segregation points added at each dock; pallet return scheme agreed with two major clients.',
                'owner' => 'Facilities Director',
                'status' => 'monitoring',
            ],
            [
                'name' => 'Anti-bribery exposure in customs and permitting',
                'topic' => 'anti_corruption',
                'time_horizon' => 'long',
                'description' => 'Freight clearance, permits and inspections involve frequent intermediary contact, which is the recognised exposure point for facilitation payments in the sector.',
                'financial_impact' => 'Not quantified. Exposure is regulatory and reputational rather than a modelled cash figure.',
                'likelihood' => 'low',
                'mitigation' => 'Anti-bribery policy issued; annual training for customs-facing staff; whistleblowing channel live.',
                'owner' => 'General Counsel',
                'status' => 'monitoring',
            ],
        ];

        // Cross-reference row: the detail lives in the IFRS S2 climate
        // register. Present so the S1 register is not silent on climate, but
        // never a duplicate of the S2 rows.
        $risks[] = [
            'name' => 'Climate transition exposure (see IFRS S2 register)',
            'topic' => 'climate',
            'time_horizon' => 'long',
            'description' => 'Climate-related risks are identified, assessed and monitored in the IFRS S2 climate risk register. This entry cross-references that register so the S1 assessment is complete.',
            'financial_impact' => 'Quantified per risk in the IFRS S2 climate register.',
            'likelihood' => 'medium',
            'mitigation' => 'Managed through the transition plan and reduction targets recorded under IFRS S2.',
            'owner' => 'Chief Operating Officer',
            'status' => 'monitoring',
        ];

        // Earlier years: a shorter, less complete register. The two oldest
        // years predate the supply-chain and climate cross-reference work.
        if ($year < $currentYear) {
            $risks = array_filter($risks, fn ($r) => !in_array($r['topic'], ['supply_chain', 'climate'], true));
        }

        if ($year < $currentYear - 1) {
            $risks = array_filter($risks, fn ($r) => $r['topic'] !== 'anti_corruption');

            // Nothing was costed in the first year of the register.
            $risks = array_map(function (array $r) {
                $r['financial_impact'] = '';
                $r['status'] = 'open';

                return $r;
            }, $risks);
        }

        return array_values($risks);
    }
}
