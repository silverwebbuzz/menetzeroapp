<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class Scope12LocationsSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(protected array $locationNames) {}

    public function array(): array
    {
        return array_map(fn ($name) => [$name], $this->locationNames);
    }

    public function headings(): array
    {
        return ['Your Location Names (use exactly in location_name column)'];
    }

    public function title(): string
    {
        return 'Your Locations';
    }
}
