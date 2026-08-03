<?php

namespace App\Exports;

use App\Support\ExportWatermark;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrialWatermarkNoticeSheet implements FromArray, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'TRIAL NOTICE';
    }

    public function array(): array
    {
        return [
            ['MENetZero Free Trial export'],
            [ExportWatermark::bannerText()],
            [''],
            ['This file is a draft working paper for exploring the platform.'],
            ['It is NOT for regulatory submission, auditor delivery, or client-facing final reporting.'],
            ['Request a package from MENetZero for clean official exports.'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getColumnDimension('A')->setWidth(100);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('B91C1C');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('991B1B');
        $sheet->getStyle('A1:A6')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('A1:A2')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FEE2E2'],
            ],
        ]);

        return [];
    }
}
