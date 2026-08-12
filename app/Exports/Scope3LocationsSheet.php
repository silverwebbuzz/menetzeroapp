<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The company's active location names. Import matches on exact name, so this sheet
 * exists to be copied from — a mistyped location is the second most common row error
 * after a wrong unit.
 */
class Scope3LocationsSheet implements FromArray, WithHeadings, WithTitle, WithStyles
{
    public function __construct(protected array $locationNames) {}

    public function array(): array
    {
        return array_map(fn ($name) => [$name], $this->locationNames);
    }

    public function headings(): array
    {
        return ['Your Location Names (copy exactly into the location_name column)'];
    }

    public function title(): string
    {
        return 'Your Locations';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F766E'],
            ],
        ]);

        $sheet->getColumnDimension('A')->setAutoSize(true);

        return [];
    }
}
