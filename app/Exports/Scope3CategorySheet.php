<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet 2 — Scope 3 by GHG Protocol value-chain category, with a data-quality
 * column (Activity-based / Spend-based / Mixed). Mirrors the PDF "3a" section.
 */
class Scope3CategorySheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    /** @var \Illuminate\Support\Collection */
    protected $categories;

    public function __construct(?Collection $categories = null)
    {
        $this->categories = $categories ?? collect();
    }

    public function title(): string
    {
        return 'Scope 3 by Category';
    }

    public function collection()
    {
        $data = collect();
        $total = $this->categories->sum('tonnes');

        foreach ($this->categories as $cat) {
            $tonnes = $cat['tonnes'] ?? 0;
            $data->push([
                'GHG Protocol Category' => $cat['category'] ?? '—',
                'Emissions (tCO₂e)' => number_format($tonnes, 2, '.', ''),
                '% of Scope 3' => $total > 0 ? number_format(($tonnes / $total) * 100, 1, '.', '') : '0.0',
                'Data Quality' => $cat['data_quality'] ?? '—',
            ]);
        }

        // Total row
        $data->push([
            'GHG Protocol Category' => 'Total Scope 3',
            'Emissions (tCO₂e)' => number_format($total, 2, '.', ''),
            '% of Scope 3' => '100.0',
            'Data Quality' => '',
        ]);

        return $data;
    }

    public function headings(): array
    {
        return ['GHG Protocol Category', 'Emissions (tCO₂e)', '% of Scope 3', 'Data Quality'];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        // Header
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '000000'], 'size' => 12],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9D9D9'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
            ],
        ]);

        for ($row = 2; $row <= $highestRow; $row++) {
            $label = $sheet->getCell('A' . $row)->getValue();
            $isTotal = $label === 'Total Scope 3';

            $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray([
                'font' => ['bold' => $isTotal, 'color' => ['rgb' => '000000']],
                'fill' => $isTotal ? [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9D9D9'],
                ] : [],
                'borders' => [
                    'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                ],
            ]);

            // Right-align numeric columns
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        }

        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
