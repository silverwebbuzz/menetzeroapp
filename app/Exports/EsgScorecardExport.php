<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class EsgScorecardExport implements FromArray, WithHeadings, WithTitle
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  int[]  $years
     */
    public function __construct(
        protected array $rows,
        protected array $years,
        protected string $companyName,
    ) {
    }

    public function array(): array
    {
        return array_map(function (array $row) {
            return [
                $row['category'],
                $row['metric'],
                $row['unit'],
                $row['source'],
                $row[(string) $this->years[0]] ?? '—',
                $row[(string) $this->years[1]] ?? '—',
                $row[(string) $this->years[2]] ?? '—',
            ];
        }, $this->rows);
    }

    public function headings(): array
    {
        return [
            'Category',
            'Metric',
            'Unit',
            'Data source',
            (string) $this->years[0],
            (string) $this->years[1],
            (string) $this->years[2],
        ];
    }

    public function title(): string
    {
        return 'ESG Scorecard';
    }
}
