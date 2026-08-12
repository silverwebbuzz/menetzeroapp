<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Optional working sheet — NOT imported.
 *
 * Business travel is reported as one passenger.km total per travel class, but users
 * hold a trip list. This sheet keeps the trip list and sums it per class.
 *
 * Prefixed "Calc:" so extractDataSheet() skips it.
 */
class Scope3FlightsCalcSheet implements FromArray, WithTitle, WithStyles
{
    private const HEADER_ROW = 9;

    private const SAMPLE_ROWS = 8;

    /** Indicative one-way great-circle distances from Dubai, in km. */
    private const DISTANCE_HINTS = [
        ['Dubai → Abu Dhabi', 120, 'Flight - Domestic'],
        ['Dubai → Doha', 380, 'Flight - Short-haul Economy'],
        ['Dubai → Riyadh', 870, 'Flight - Short-haul Economy'],
        ['Dubai → Mumbai', 1930, 'Flight - Short-haul Economy'],
        ['Dubai → Cairo', 2410, 'Flight - Short-haul Economy'],
        ['Dubai → London', 5500, 'Flight - Long-haul Economy'],
        ['Dubai → Singapore', 5840, 'Flight - Long-haul Economy'],
        ['Dubai → New York', 11000, 'Flight - Long-haul Economy'],
    ];

    public function array(): array
    {
        $rows = [
            ['CALCULATOR — BUSINESS TRAVEL BY AIR (this sheet is NOT imported)'],
            [''],
            ['List one row per trip, or one row per route flown repeatedly.'],
            ['passenger.km = one-way km × passengers × legs. A return trip is 2 legs.'],
            ['Short-haul is roughly under 3,700 km; long-haul above it. Match the class you booked.'],
            [''],
            ['Copy each class total from the bottom of this sheet into Data Entry as a separate row,'],
            ['using Category "Cat 6" and unit "passenger.km".'],
            ['Route / description', 'Class (see Reference)', 'One-way km', 'Passengers', 'Legs (1 or 2)', 'passenger.km'],
        ];

        $first = self::HEADER_ROW + 1;

        $samples = [
            ['e.g. Dubai → London (return)', 'Flight - Long-haul Economy', 5500, 2, 2, null],
            ['e.g. Dubai → Riyadh (return)', 'Flight - Short-haul Economy', 870, 3, 2, null],
            ['e.g. Dubai → Abu Dhabi (one way)', 'Flight - Domestic', 120, 1, 1, null],
            ['', '', null, null, null, null],
            ['', '', null, null, null, null],
            ['', '', null, null, null, null],
            ['', '', null, null, null, null],
            ['', '', null, null, null, null],
        ];

        foreach ($samples as $i => $sample) {
            $r = $first + $i;
            $sample[5] = "=IF(COUNT(C{$r}:E{$r})=3,C{$r}*D{$r}*E{$r},\"\")";
            $rows[] = $sample;
        }

        $last = $first + self::SAMPLE_ROWS - 1;

        $rows[] = ['', '', '', '', '', ''];
        $rows[] = ['TOTAL (all classes)', '', '', '', '', "=SUM(F{$first}:F{$last})"];
        $rows[] = [''];
        $rows[] = ['Per-class totals — paste these into Data Entry as separate rows (unit: passenger.km):'];

        $classes = [
            'Flight - Domestic',
            'Flight - Short-haul Economy',
            'Flight - Short-haul Business',
            'Flight - Long-haul Economy',
            'Flight - Long-haul Premium Economy',
            'Flight - Long-haul Business',
            'Flight - Long-haul First',
            'Flight - International Economy',
        ];

        foreach ($classes as $class) {
            $rows[] = [$class, '', '', '', 'passenger.km', "=SUMIF(B{$first}:B{$last},\"{$class}\",F{$first}:F{$last})"];
        }

        $rows[] = [''];
        $rows[] = ['DISTANCE REFERENCE — indicative one-way km from Dubai'];
        $rows[] = ['Route', 'Approx. one-way km', 'Typical class'];

        foreach (self::DISTANCE_HINTS as $hint) {
            $rows[] = [$hint[0], $hint[1], $hint[2]];
        }

        $rows[] = [''];
        $rows[] = ['Use your airline or travel-agent report where available — these are estimates only.'];

        return $rows;
    }

    public function title(): string
    {
        return 'Calc: Flights';
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
