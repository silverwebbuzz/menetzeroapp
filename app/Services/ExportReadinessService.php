<?php

namespace App\Services;

use App\Models\Company;
use App\Models\EmissionSourceMaster;
use App\Models\Location;
use App\Models\Measurement;
use App\Models\MeasurementData;
use App\Support\JsonField;
use App\Support\UaeUtilityContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExportReadinessService
{
    /**
     * @return array{errors: list<string>, warnings: list<string>, is_ready: bool}
     */
    public function assess(Measurement $measurement, bool $moccaeOnly = false): array
    {
        $measurement->loadMissing('location.company');

        return $this->assessLocationMeasurement(
            $measurement->location,
            $measurement->location?->company,
            $measurement,
            $moccaeOnly
        );
    }

    /**
     * Company-wide readiness for disclosure previews (all active locations).
     *
     * @return array{errors: list<string>, warnings: list<string>, is_ready: bool, locations_checked: int}
     */
    /**
     * @param  bool  $strict  When false (disclosure previews), electricity gaps are warnings only.
     */
    public function assessCompany(Company $company, int $fiscalYear, bool $moccaeOnly = false, bool $strict = false): array
    {
        $errors = [];
        $warnings = [];
        $checked = 0;

        $locations = $company->locations()->where('is_active', true)->get();

        foreach ($locations as $location) {
            $measurement = Measurement::query()
                ->where('location_id', $location->id)
                ->where('fiscal_year', $fiscalYear)
                ->first();

            if (!$measurement) {
                $warnings[] = sprintf(
                    'No emission data for %s (%s). Disclosure GHG sections will be incomplete until data is entered.',
                    $location->name,
                    $fiscalYear
                );

                continue;
            }

            $checked++;
            $result = $this->assessLocationMeasurement($location, $company, $measurement, $moccaeOnly);
            $errors = array_merge($errors, $result['errors']);
            $warnings = array_merge($warnings, $result['warnings']);
        }

        if ($checked === 0 && $locations->isNotEmpty()) {
            $warnings[] = sprintf(
                'No locations have emission data for %s. Enter Scope 1 & 2 data in Quick Input before publishing disclosures.',
                $fiscalYear
            );
        }

        if (!$strict && $errors !== []) {
            $warnings = array_merge($warnings, $errors);
            $errors = [];
        }

        return [
            'errors' => array_values(array_unique($errors)),
            'warnings' => array_values(array_unique($warnings)),
            'is_ready' => $errors === [],
            'locations_checked' => $checked,
        ];
    }

    /**
     * Redirect back to the report page with flash messages when MOCCAE export is blocked.
     */
    public function redirectIfBlocked(Measurement $measurement, bool $moccaeOnly, Request $request): ?RedirectResponse
    {
        $readiness = $this->assess($measurement, $moccaeOnly);

        if ($readiness['is_ready']) {
            return null;
        }

        return redirect()
            ->route('reports.show', array_filter([
                'fiscal_year' => $measurement->fiscal_year,
                'location_id' => $measurement->location_id,
                'moccae_only' => $moccaeOnly ? 1 : null,
            ]))
            ->with('error', 'Export could not be completed. Resolve the data gaps below, then try again.')
            ->with('export_errors', $readiness['errors'])
            ->with('export_warnings', $readiness['warnings']);
    }

    /**
     * @return array{errors: list<string>, warnings: list<string>, is_ready: bool}
     */
    protected function assessLocationMeasurement(
        ?Location $location,
        ?Company $company,
        Measurement $measurement,
        bool $moccaeOnly
    ): array {
        $errors = [];
        $warnings = [];

        if ($location) {
            $utility = UaeUtilityContext::resolve($location, $company);

            if ($utility) {
                $this->assessUaeElectricity(
                    $location,
                    $measurement,
                    $utility,
                    $errors,
                    $warnings
                );
            }
        }

        if (!$moccaeOnly) {
            $scope3Count = MeasurementData::query()
                ->where('measurement_id', $measurement->id)
                ->where('scope', 'Scope 3')
                ->where('quantity', '>', 0)
                ->count();

            if ($scope3Count === 0) {
                $warnings[] = sprintf(
                    'No Scope 3 entries for %s (%s). Full GHG inventory exports will show Scope 3 as zero.',
                    $location?->name ?? 'this location',
                    $measurement->fiscal_year
                );
            }
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'is_ready' => $errors === [],
        ];
    }

    /**
     * @param  array{key: string, label: string, provider: string}  $utility
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     */
    protected function assessUaeElectricity(
        Location $location,
        Measurement $measurement,
        array $utility,
        array &$errors,
        array &$warnings
    ): void {
        if (!UaeUtilityContext::locationReceivesUtilityBills($location)) {
            $warnings[] = sprintf(
                '%s is marked as not receiving utility bills directly. Confirm Scope 2 electricity is captured elsewhere or update the location profile.',
                $location->name
            );

            return;
        }

        $electricityEntries = $this->electricityEntriesForMeasurement($measurement->id);

        if ($electricityEntries->isEmpty()) {
            $errors[] = sprintf(
                'Missing %s electricity data for %s (%s). Add a Scope 2 electricity entry with kWh from your %s bill before MOCCAE / IEQT export.',
                $utility['provider'],
                $location->name,
                $measurement->fiscal_year,
                $utility['provider']
            );

            return;
        }

        $withoutEvidence = $electricityEntries->filter(fn (MeasurementData $entry) => !$this->entryHasEvidence($entry));

        if ($withoutEvidence->isNotEmpty()) {
            $warnings[] = sprintf(
                '%d electricity %s for %s (%s) have no supporting file or link. MOCCAE audits may request %s documentation.',
                $withoutEvidence->count(),
                $withoutEvidence->count() === 1 ? 'entry' : 'entries',
                $location->name,
                $utility['label'],
                $utility['provider']
            );
        }
    }

    protected function entryHasEvidence(MeasurementData $entry): bool
    {
        if (!empty($entry->supporting_docs)) {
            return true;
        }

        $additional = JsonField::decode($entry->additional_data ?? []);

        return !empty($additional['evidence_link']) || !empty($additional['link']);
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
