<?php

namespace App\Exports;

use App\Services\Scope12BulkImportService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class Scope12InstructionsSheet implements FromArray, WithTitle
{
    public function array(): array
    {
        return Scope12BulkImportService::instructionsRows();
    }

    public function title(): string
    {
        return 'Instructions';
    }
}
