<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ResultsBreakdownExport implements FromCollection, WithHeadings, WithStyles
{
    protected $resultsBreakdown;

    public function __construct($resultsBreakdown)
    {
        $this->resultsBreakdown = $resultsBreakdown;
    }

    public function collection()
    {
        $data = collect();
        $grandTotal = 0;

        foreach ($this->resultsBreakdown as $scope) {
            // Scope row
            $data->push([
                'Scope Name' => $scope['name'],
                'Emission Source' => null,
                'Results (tCO₂e)' => number_format($scope['value'], 2, '.', ''),
            ]);

            $grandTotal += $scope['value'];

            // Children rows
            foreach ($scope['children'] as $child) {
                $data->push([
                    'Scope Name' => null,
                    'Emission Source' => $child['name'],
                    'Results (tCO₂e)' => number_format($child['value'], 2, '.', ''),
                ]);
            }
        }

        // Total row
        $data->push([
            'Scope Name' => 'Total',
            'Emission Source' => null,
            'Results (tCO₂e)' => number_format($grandTotal, 2, '.', ''),
        ]);

        return $data;
    }

    public function headings(): array
    {
        return ['Scope Name', 'Emission Source', 'Results (tCO₂e)'];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        // Header row style
        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9D9D9'], // Light gray
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
            ],
        ]);

        // Loop through all rows
        for ($row = 2; $row <= $highestRow; $row++) {
            $scopeName = $sheet->getCell('A' . $row)->getValue();

            if ($scopeName && $scopeName !== 'Total') {
                // Scope rows: subtle gray fill
                $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F2F2F2'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                    ],
                ]);
            } elseif (!$scopeName) {
                // Child rows: plain white background
                $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                    ],
                ]);
            } elseif ($scopeName === 'Total') {
                // Total row: light gray fill and bold
                $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9D9D9'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                    ],
                ]);
            }

            // Right-align the Results column for readability
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        }

        // Adjust column widths
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
