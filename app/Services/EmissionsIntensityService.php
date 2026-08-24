<?php

namespace App\Services;

use App\Models\CompanyReportingSetting;
use App\Models\Company;
use App\Models\Measurement;
use App\Models\StructuralChange;

/**
 * GHG intensity and year-on-year comparability (GHG Protocol Chapter 5).
 *
 * Absolute emissions alone mislead when the organisational boundary changes:
 * a company running three branches in 2026 and one in 2024 shows an increase
 * even if every branch improved. This service supplies the intensity figure
 * that normalises for that, and flags when two years are not like-for-like.
 */
class EmissionsIntensityService
{
    /**
     * Intensity for one year, or null when no denominator has been recorded.
     *
     * @return array<string, mixed>|null
     */
    public function forYear(Company $company, int $fiscalYear, float $tonnes): ?array
    {
        $settings = CompanyReportingSetting::where('company_id', $company->id)
            ->where('fiscal_year', $fiscalYear)
            ->first();

        $denominator = $settings?->intensity_denominator_value !== null
            ? (float) $settings->intensity_denominator_value
            : null;

        if (!$denominator || $denominator <= 0) {
            return null;
        }

        $type = $settings->intensity_denominator_type;
        $meta = CompanyReportingSetting::INTENSITY_DENOMINATORS[$type] ?? null;

        return [
            'value' => round($tonnes / $denominator, 4),
            'denominator_value' => $denominator,
            'denominator_type' => $type,
            'denominator_label' => $meta['label'] ?? $type,
            'unit' => $settings->intensity_denominator_unit ?: ($meta['unit'] ?? 'unit'),
        ];
    }

    /**
     * Intensity series across several years, for the dashboard comparison.
     *
     * @param  array<int, float>  $tonnesByYear  fiscal year => tCO2e
     * @return array<int, float|null>            fiscal year => intensity or null
     */
    public function series(Company $company, array $tonnesByYear): array
    {
        $out = [];

        foreach ($tonnesByYear as $year => $tonnes) {
            $intensity = $this->forYear($company, (int) $year, (float) $tonnes);
            $out[(int) $year] = $intensity['value'] ?? null;
        }

        return $out;
    }

    /**
     * Whether two years can be compared on absolute emissions alone.
     *
     * Two independent signals: a differing count of reporting locations, and
     * any logged structural change in between. Either means the absolute
     * numbers describe different organisations.
     *
     * @return array<string, mixed>
     */
    public function comparability(Company $company, int $fromYear, int $toYear): array
    {
        if ($fromYear === $toYear) {
            return $this->comparable();
        }

        [$earlier, $later] = $fromYear < $toYear ? [$fromYear, $toYear] : [$toYear, $fromYear];

        $fromCount = $this->locationCount($company, $earlier);
        $toCount = $this->locationCount($company, $later);

        $changes = StructuralChange::where('company_id', $company->id)
            ->whereBetween('fiscal_year', [$earlier + 1, $later])
            ->get();

        $recalcChanges = $changes->where('triggers_recalculation', true);

        $countDiffers = $fromCount > 0 && $toCount > 0 && $fromCount !== $toCount;

        if (!$countDiffers && $changes->isEmpty()) {
            return $this->comparable();
        }

        $reasons = [];

        if ($countDiffers) {
            $reasons[] = sprintf(
                '%d reporting location%s in %d vs %d in %d',
                $fromCount,
                $fromCount === 1 ? '' : 's',
                $earlier,
                $toCount,
                $later
            );
        }

        foreach ($changes as $change) {
            $reasons[] = $change->typeLabel() . ' (' . $change->fiscal_year . '): ' . $change->title;
        }

        return [
            'comparable' => false,
            'requires_recalculation' => $recalcChanges->isNotEmpty(),
            'reasons' => $reasons,
            'from_locations' => $fromCount,
            'to_locations' => $toCount,
            'message' => $recalcChanges->isNotEmpty()
                ? 'The organisational boundary changed between these years. Under the GHG Protocol the base year should be recalculated before comparing absolute emissions.'
                : 'The organisational boundary changed between these years, so absolute emissions are not like-for-like. Compare intensity instead.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function comparable(): array
    {
        return [
            'comparable' => true,
            'requires_recalculation' => false,
            'reasons' => [],
            'from_locations' => null,
            'to_locations' => null,
            'message' => null,
        ];
    }

    /**
     * Locations that actually reported in a year — not every location the
     * company owns, since a site added late may have no data yet.
     */
    private function locationCount(Company $company, int $fiscalYear): int
    {
        return Measurement::whereHas(
            'location',
            fn ($q) => $q->where('company_id', $company->id)
        )
            ->where('fiscal_year', $fiscalYear)
            ->distinct()
            ->count('location_id');
    }
}
