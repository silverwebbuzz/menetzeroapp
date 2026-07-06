<?php

namespace App\Services;

use App\Models\MeasurementData;
use Illuminate\Support\Collection;

/**
 * Derives GRI 302-1 energy (GJ) from Scope 1 & 2 Quick Input activity — read-only.
 */
class EnergyFromActivityService
{
    public function totalGj(int $companyId, int $fiscalYear): ?float
    {
        $breakdown = $this->breakdown($companyId, $fiscalYear);
        if ($breakdown->isEmpty()) {
            return null;
        }

        return round((float) $breakdown->sum('gj'), 4);
    }

    /**
     * @return Collection<int, array{source: string, slug: string|null, quantity: float, unit: string, gj: float}>
     */
    public function breakdown(int $companyId, int $fiscalYear): Collection
    {
        $rows = MeasurementData::query()
            ->whereHas('measurement.location', fn ($q) => $q->where('company_id', $companyId))
            ->whereHas('measurement', fn ($q) => $q->where('fiscal_year', $fiscalYear))
            ->where('quantity', '>', 0)
            ->with('emissionSource:id,name,scope,quick_input_slug')
            ->get();

        $items = collect();

        foreach ($rows as $row) {
            $source = $row->emissionSource;
            if (!$source || !$this->isEnergyActivity($source->scope, $source->quick_input_slug)) {
                continue;
            }

            $gj = $this->quantityToGj(
                (float) $row->quantity,
                (string) ($row->unit ?? ''),
                (string) ($row->fuel_type ?? ''),
                (string) ($source->quick_input_slug ?? ''),
            );

            if ($gj === null || $gj <= 0) {
                continue;
            }

            $items->push([
                'source' => $source->name,
                'slug' => $source->quick_input_slug,
                'quantity' => (float) $row->quantity,
                'unit' => $row->unit ?? '',
                'gj' => round($gj, 4),
            ]);
        }

        return $items;
    }

    protected function isEnergyActivity(?string $scope, ?string $slug): bool
    {
        if (!in_array($scope, ['Scope 1', 'Scope 2'], true)) {
            return false;
        }

        $slug = strtolower((string) $slug);
        $excluded = config('energy_from_activity.excluded_slugs', []);

        return !in_array($slug, $excluded, true);
    }

    protected function quantityToGj(float $quantity, string $unit, string $fuelType, string $slug): ?float
    {
        if ($quantity <= 0) {
            return null;
        }

        $unitKey = strtolower(trim($unit));
        $unitMap = config('energy_from_activity.unit_to_gj', []);

        if (isset($unitMap[$unitKey])) {
            return $quantity * (float) $unitMap[$unitKey];
        }

        if (in_array($unitKey, ['litre', 'litres', 'l', 'liter', 'liters'], true)) {
            $factor = $this->litreGjFactor($fuelType, $slug);

            return $quantity * $factor;
        }

        return null;
    }

    protected function litreGjFactor(string $fuelType, string $slug): float
    {
        $factors = config('energy_from_activity.litre_gj_factors', []);
        $haystack = strtolower($fuelType . ' ' . $slug);

        foreach ($factors as $key => $factor) {
            if ($key === 'default') {
                continue;
            }
            if (str_contains($haystack, str_replace('-', '_', $key)) || str_contains($haystack, $key)) {
                return (float) $factor;
            }
        }

        return (float) ($factors['default'] ?? 0.036);
    }
}
