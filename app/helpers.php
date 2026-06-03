<?php

use App\Services\GhgReportService;

if (! function_exists('co2e_t')) {
    /**
     * Format stored kg CO₂e for display as metric tonnes (tCO₂e).
     */
    function co2e_t(float|int|string|null $kg, int $decimals = 2): string
    {
        return GhgReportService::formatTonnes($kg, $decimals);
    }
}

if (! function_exists('co2e_tonne')) {
    /**
     * Convert stored kg CO₂e to metric tonnes (float).
     */
    function co2e_tonne(float|int|string|null $kg): float
    {
        return GhgReportService::kgToTonnes($kg);
    }
}
