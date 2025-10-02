<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        $types = ['MOCCAE', 'GRI', 'Internal'];
        $type = $this->faker->randomElement($types);

        $periodStart = $this->faker->dateTimeBetween('-2 years', '-6 months');
        $periodEnd = $this->faker->dateTimeBetween($periodStart, 'now');

        $fileNames = [
            'MOCCAE' => [
                'moccae_annual_report_2023.pdf',
                'carbon_footprint_assessment_2024.pdf',
                'sustainability_report_moccae_2023.pdf'
            ],
            'GRI' => [
                'gri_sustainability_report_2023.pdf',
                'gri_standards_compliance_2024.pdf',
                'global_reporting_initiative_2023.pdf'
            ],
            'Internal' => [
                'internal_carbon_audit_2024.pdf',
                'quarterly_sustainability_report.pdf',
                'environmental_impact_assessment.pdf'
            ]
        ];

        return [
            'company_id' => Company::factory(),
            'type' => $type,
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'file_path' => '/reports/' . $this->faker->randomElement($fileNames[$type]),
        ];
    }
}

