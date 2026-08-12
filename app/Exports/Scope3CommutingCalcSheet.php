<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Optional working sheet — NOT imported.
 *
 * measurement_data stores one aggregate per category, so per-employee detail has
 * nowhere to live in the database. Rather than throw that detail away, the user keeps
 * it here and the sheet totals it into the single number they paste into Data Entry.
 * Next year they update the rows instead of rebuilding the calculation.
 *
 * The sheet name is prefixed "Calc:" so Scope3BulkImportService::extractDataSheet()
 * never mistakes it for the data sheet.
 */
class Scope3CommutingCalcSheet implements FromArray, WithTitle, WithStyles
{
    /** Header row is written by array(), not WithHeadings, so notes can sit above it. */
    private const HEADER_ROW = 8;

    private const SAMPLE_ROWS = 6;

    public function array(): array
    {
        $rows = [
            ['CALCULATOR — EMPLOYEE COMMUTING (this sheet is NOT imported)'],
            [''],
            ['List one row per employee, or one row per group of employees who travel the same way.'],
            ['Round-trip km = one-way km × 2. Days per year is typically 220 after leave and weekends.'],
            [''],
            ['Copy each mode total from column F into the Data Entry sheet as a separate row,'],
            ['using Category "Cat 7" and the matching activity_type / unit from Reference.'],
            ['Employee / group', 'Mode (see Reference)', 'One-way km', 'Days per year', 'People', 'Total km'],
        ];

        $first = self::HEADER_ROW + 1;

        $samples = [
            ['e.g. Head office — car drivers', 'Average car', 18, 220, 12, null],
            ['e.g. Head office — metro users', 'Light rail / Tram', 11, 220, 8, null],
            ['e.g. Warehouse — company bus', 'Local bus', 24, 240, 30, null],
            ['', '', null, null, null, null],
            ['', '', null, null, null, null],
            ['', '', null, null, null, null],
        ];

        foreach ($samples as $i => $sample) {
            $r = $first + $i;
            // Round trip: one-way × 2 × days × people.
            $sample[5] = "=IF(COUNT(C{$r}:E{$r})=3,C{$r}*2*D{$r}*E{$r},\"\")";
            $rows[] = $sample;
        }

        $last = $first + self::SAMPLE_ROWS - 1;

        $rows[] = ['', '', '', '', '', ''];
        $rows[] = ['TOTAL (all modes)', '', '', '', '', "=SUM(F{$first}:F{$last})"];
        $rows[] = [''];
        $rows[] = ['Per-mode totals — paste these into Data Entry as separate rows:'];
        $rows[] = ['Average car', '', '', '', 'km', "=SUMIF(B{$first}:B{$last},\"Average car\",F{$first}:F{$last})"];
        $rows[] = ['Motorbike', '', '', '', 'km', "=SUMIF(B{$first}:B{$last},\"Motorbike\",F{$first}:F{$last})"];
        $rows[] = ['Local bus', '', '', '', 'passenger.km', "=SUMIF(B{$first}:B{$last},\"Local bus\",F{$first}:F{$last})"];
        $rows[] = ['Coach', '', '', '', 'passenger.km', "=SUMIF(B{$first}:B{$last},\"Coach\",F{$first}:F{$last})"];
        $rows[] = ['Rail - National', '', '', '', 'passenger.km', "=SUMIF(B{$first}:B{$last},\"Rail - National\",F{$first}:F{$last})"];
        $rows[] = ['Light rail / Tram', '', '', '', 'passenger.km', "=SUMIF(B{$first}:B{$last},\"Light rail / Tram\",F{$first}:F{$last})"];

        return $rows;
    }

    public function title(): string
    {
        return 'Calc: Commuting';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 12]]);

        $sheet->getStyle('A' . self::HEADER_ROW . ':F' . self::HEADER_ROW)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F766E'],
            ],
        ]);

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }
}
