<?php

namespace App\Exports;

use App\Services\Scope3BulkImportService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Scope 3 bulk-import workbook.
 *
 * Sheet order is deliberate — a user reads left to right: what to do, where to type,
 * what the valid values are, then the optional calculators for detail they already hold.
 *
 * Only "Data Entry" is imported. The calculator sheets are prefixed "Calc:" so
 * Scope3BulkImportService::extractDataSheet() skips them.
 */
class Scope3BulkTemplateExport implements WithMultipleSheets
{
    public function __construct(
        protected array $locationNames = []
    ) {}

    public function sheets(): array
    {
        $sheets = [
            new Scope3InstructionsSheet(),
            // Data Entry ships EMPTY and the samples live on their own sheet, matching
            // Scope12BulkTemplateExport. Pre-filling Data Entry meant a user who
            // downloaded the template and uploaded it back re-imported the examples —
            // and since they reference a location ("Dubai Head Office") that most
            // companies do not have, every row failed with "Location not found".
            new Scope3DataSheet('Data Entry', []),
            new Scope3DataSheet('Examples', Scope3BulkImportService::sampleRows()),
            new Scope3ReferenceSheet(),
        ];

        if (!empty($this->locationNames)) {
            $sheets[] = new Scope3LocationsSheet($this->locationNames);
        }

        $sheets[] = new Scope3CommutingCalcSheet();
        $sheets[] = new Scope3FlightsCalcSheet();

        return $sheets;
    }
}
