<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CarbonEmission;
use App\Services\CarbonCalculationService;

class DashboardController extends Controller
{
    protected $carbonCalculationService;

    public function __construct(CarbonCalculationService $carbonCalculationService)
    {
        $this->carbonCalculationService = $carbonCalculationService;
    }

    public function index()
    {
        $user = auth()->user();
        $company = $user->company;

        // Get recent emissions
        $recentEmissions = $company->carbonEmissions()
            ->with('user')
            ->latest()
            ->limit(10)
            ->get();

        // Get current year calculations
        $currentYear = now()->year;
        $calculations = $this->carbonCalculationService->calculateAllScopes($company, $currentYear);

        return view('dashboard.index', compact('recentEmissions', 'calculations'));
    }
}
