<?php

namespace App\Exports;

use App\Services\Scope12BulkImportService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class Scope12ReferenceSheet implements FromArray, WithTitle
{
    public function array(): array
    {
        return Scope12BulkImportService::referenceRows();
    }

    public function title(): string
    {
        return 'Reference';
    }
}
