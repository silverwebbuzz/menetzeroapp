<?php

namespace App\Exports;

use App\Services\Scope3BulkImportService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Scope3InstructionsSheet implements FromArray, WithTitle, WithStyles
{
    public function array(): array
    {
        return Scope3BulkImportService::instructionsRows();
    }

    public function title(): string
    {
        return 'Instructions';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 14]]);
        $sheet->getColumnDimension('A')->setWidth(105);

        return [];
    }
}
