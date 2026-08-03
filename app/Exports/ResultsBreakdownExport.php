<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Workbook container for the GHG results export.
 *  Sheet 1 (optional): Free trial watermark notice.
 *  Next: Results breakdown by scope/source.
 *  Optional: Scope 3 category sheet when data exists.
 */
class ResultsBreakdownExport implements WithMultipleSheets
{
    protected $resultsBreakdown;
    protected $grandTotal;
    protected $scope3Categories;
    protected bool $watermark;

    public function __construct(
        $resultsBreakdown,
        ?float $grandTotal = null,
        ?Collection $scope3Categories = null,
        bool $watermark = false
    ) {
        $this->resultsBreakdown = $resultsBreakdown;
        $this->grandTotal = $grandTotal;
        $this->scope3Categories = $scope3Categories ?? collect();
        $this->watermark = $watermark;
    }

    public function sheets(): array
    {
        $sheets = [];

        if ($this->watermark) {
            $sheets[] = new TrialWatermarkNoticeSheet();
        }

        $sheets[] = new ResultsBreakdownSheet($this->resultsBreakdown, $this->grandTotal, $this->watermark);

        if ($this->scope3Categories->isNotEmpty()) {
            $sheets[] = new Scope3CategorySheet($this->scope3Categories);
        }

        return $sheets;
    }
}
