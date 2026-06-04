<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Workbook container for the GHG results export.
 *  Sheet 1: Results breakdown by scope/source (always present).
 *  Sheet 2: Scope 3 by GHG Protocol category + data quality (only when Scope 3 data exists).
 *
 * Backward compatible: the first two constructor args are unchanged, so existing
 * callers keep working; pass $scope3Categories to enable the second sheet.
 */
class ResultsBreakdownExport implements WithMultipleSheets
{
    protected $resultsBreakdown;
    protected $grandTotal;
    protected $scope3Categories;

    public function __construct($resultsBreakdown, ?float $grandTotal = null, ?Collection $scope3Categories = null)
    {
        $this->resultsBreakdown = $resultsBreakdown;
        $this->grandTotal = $grandTotal;
        $this->scope3Categories = $scope3Categories ?? collect();
    }

    public function sheets(): array
    {
        $sheets = [
            new ResultsBreakdownSheet($this->resultsBreakdown, $this->grandTotal),
        ];

        if ($this->scope3Categories->isNotEmpty()) {
            $sheets[] = new Scope3CategorySheet($this->scope3Categories);
        }

        return $sheets;
    }
}
