<?php

namespace App\Services;

use App\Models\Company;
use App\Models\EmissionSourceMaster;
use App\Models\Location;
use App\Models\Measurement;
use App\Models\MeasurementData;

class ExportReadinessService
{
    /**
     * @return array{errors: list<string>, warnings: list<string>, is_ready: bool}
     */
    public function assess(Measurement $measurement, bool $moccaeOnly = false): array
    {
        $measurement->loadMissing('location.company');
        $location = $measurement->location;
        $company = $location?->company;

        $errors = [];
        $warnings = [];

        if ($location && $this->locationIsDubai($location, $company)) {
            $electricityEntries = $this->electricityEntriesForMeasurement($measurement->id);

            if ($electricityEntries->isEmpty()) {
                $errors[] = sprintf(
                    'Missing DEWA electricity data for %s (%s). Add a Scope 2 electricity entry with kWh from your DEWA bill before MOCCAE / IEQT export.',
                    $location->name,
                    $measurement->fiscal_year
                );
            } else {
                $withoutDocs = $electricityEntries->filter(function (MeasurementData $entry) {
                    if (!empty($entry->supporting_docs)) {
                        return false;
                    }

                    $additional = $entry->additional_data ?? [];
                    if (is_string($additional)) {
                        $additional = json_decode($additional, true) ?? [];
                    }

                    return empty($additional['evidence_link']) && empty($additional['link']);
                });

                if ($withoutDocs->isNotEmpty()) {
                    $warnings[] = sprintf(
                        '%d electricity %s for %s have no supporting file or reference link. MOCCAE audits may request DEWA documentation.',
                        $withoutDocs->count(),
                        $withoutDocs->count() === 1 ? 'entry' : 'entries',
                        $location->name
                    );
                }
            }
        }

        if (!$moccaeOnly) {
            $scope3Count = MeasurementData::query()
                ->where('measurement_id', $measurement->id)
                ->where('scope', 'Scope 3')
                ->where('quantity', '>', 0)
                ->count();

            if ($scope3Count === 0) {
                $warnings[] = 'No Scope 3 entries recorded for this location and year. Full GHG inventory exports will show Scope 3 as zero.';
            }
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'is_ready' => $errors === [],
        ];
    }

    public function locationIsDubai(Location $location, ?Company $company = null): bool
    {
        $haystack = strtolower(implode(' ', array_filter([
            $location->city,
            $location->address,
            $location->name,
            $company?->emirate,
            $company?->city,
        ])));

        return str_contains($haystack, 'dubai');
    }

    /**
     * @return \Illuminate\Support\Collection<int, MeasurementData>
     */
    protected function electricityEntriesForMeasurement(int $measurementId)
    {
        $sourceIds = EmissionSourceMaster::query()
            ->where('quick_input_slug', 'electricity')
            ->where('scope', 'Scope 2')
            ->pluck('id');

        if ($sourceIds->isEmpty()) {
            return collect();
        }

        return MeasurementData::query()
            ->where('measurement_id', $measurementId)
            ->whereIn('emission_source_id', $sourceIds)
            ->where('quantity', '>', 0)
            ->get();
    }
}
