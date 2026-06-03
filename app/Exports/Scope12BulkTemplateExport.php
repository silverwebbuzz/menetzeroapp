<?php

namespace App\Exports;

use App\Services\Scope12BulkImportService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class Scope12BulkTemplateExport implements WithMultipleSheets
{
    public function __construct(
        protected array $locationNames = []
    ) {}

    public function sheets(): array
    {
        $sheets = [
            new Scope12InstructionsSheet(),
            new Scope12DataGuideSheet(),
            new Scope12DataSheet('Data Entry', []),
            new Scope12DataSheet('Examples', Scope12BulkImportService::sampleRows()),
            new Scope12ReferenceSheet(),
        ];

        if (!empty($this->locationNames)) {
            $sheets[] = new Scope12LocationsSheet($this->locationNames);
        }

        return $sheets;
    }
}
