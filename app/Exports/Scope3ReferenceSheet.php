<?php

namespace App\Exports;

use App\Services\Scope3BulkImportService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Every valid Category + Activity Type + Unit combination (66 rows).
 *
 * This is the contract between the template and the importer: a row whose
 * activity_type / unit pair is not listed here has no emission factor and will be
 * rejected. Copy-paste accuracy matters, so the values are printed verbatim.
 */
class Scope3ReferenceSheet implements FromArray, WithHeadings, WithTitle, WithStyles
{
    public function array(): array
    {
        return array_map(
            fn (array $row) => [
                'Cat ' . $row[0],
                $row[1],
                $row[2],
                $row[3],
                $row[4],
            ],
            Scope3BulkImportService::referenceCombinations()
        );
    }

    public function headings(): array
    {
        return [
            'Category',
            'category (slug — either form works)',
            'activity_type (copy exactly)',
            'unit (copy exactly)',
            'Where to find this number',
        ];
    }

    public function title(): string
    {
        return 'Reference';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F766E'],
            ],
        ]);

        $sheet->freezePane('A2');

        // The two copy-exactly columns carry the risk of typos — highlight them.
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle('C2:D' . $highestRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0FDFA'],
            ],
        ]);

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }
}
