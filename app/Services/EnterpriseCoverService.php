<?php

namespace App\Services;

use App\Models\Company;

class EnterpriseCoverService
{
    /**
     * White-label cover payload for enterprise UAE ESG PDF.
     *
     * @param  array<string, mixed>  $report  UaeEsgReportService::build() output
     * @return array<string, mixed>
     */
    public function build(Company $company, int $fiscalYear, array $report): array
    {
        $config = config('esg_report_enterprise.cover', []);
        $about = $report['narrative']['about_report']['content'] ?? [];

        $frameworks = trim((string) ($about['frameworks_used'] ?? ''));
        if ($frameworks === '') {
            $frameworks = implode(' · ', $report['frameworks_disclosed'] ?? []);
        }

        return [
            'title' => (string) ($config['default_title'] ?? 'Sustainability & ESG Report'),
            'company_name' => $company->name,
            'tagline' => $this->shortTagline((string) ($about['report_purpose'] ?? '')),
            'fiscal_year' => $fiscalYear,
            'generated_at' => $report['generated_at'] ?? now()->format('d M Y'),
            'frameworks' => $frameworks,
            'approval' => trim((string) ($about['report_approval'] ?? '')),
            'contact' => trim((string) ($about['contact_feedback'] ?? '')),
            'accent_color' => (string) ($config['accent_color'] ?? '#0d9488'),
            'accent_dark' => (string) ($config['accent_dark'] ?? '#115e59'),
            'confidentiality' => (string) ($config['confidentiality_line'] ?? ''),
            'logo' => $company->logoDataUri(),
        ];
    }

    protected function shortTagline(string $purpose): ?string
    {
        $purpose = trim(preg_replace('/\s+/', ' ', $purpose));
        if ($purpose === '') {
            return null;
        }

        if (strlen($purpose) <= 180) {
            return $purpose;
        }

        return substr($purpose, 0, 177) . '...';
    }
}
