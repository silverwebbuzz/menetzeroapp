<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class Scope12DataGuideSheet implements FromArray, WithTitle
{
    public function array(): array
    {
        return \App\Data\Scope12HelpGuide::excelGuideRows();
    }

    public function title(): string
    {
        return 'Data Guide';
    }
}
