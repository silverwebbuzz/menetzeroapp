<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmissionSource;
use App\Models\Company;
use App\Models\Facility;
use App\Services\CarbonCalculatorService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected $carbonCalculator;

    public function __construct(CarbonCalculatorService $carbonCalculator)
    {
        $this->carbonCalculator = $carbonCalculator;
    }

    public function index()
    {
        $user = auth()->user();
        
        // Check if user has a company
        if (!$user->company_id) {
            return view('dashboard.index', [
                'needsCompanySetup' => true,
                'kpis' => [],
                'chartData' => [],
                'netZeroProgress' => [],
                'topSources' => collect([]),
                'recentActivity' => collect([])
            ]);
        }
        
        // Get all emission sources for the user's company
        $emissionSources = EmissionSource::where('company_name', $user->company->name ?? 'Unknown')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate KPIs
        $kpis = $this->calculateKPIs($emissionSources);
        
        // Get chart data
        $chartData = $this->getChartData($emissionSources);
        
        // Get UAE Net Zero progress
        $netZeroProgress = $this->calculateNetZeroProgress($kpis['total_emissions']);
        
        // Get top emission sources
        $topSources = $this->getTopEmissionSources($emissionSources);
        
        // Get recent activity
        $recentActivity = $emissionSources->take(5);

        return view('dashboard.index', compact(
            'kpis', 
            'chartData', 
            'netZeroProgress', 
            'topSources', 
            'recentActivity'
        ));
    }

    private function calculateKPIs($emissionSources)
    {
        $totalEmissions = $emissionSources->sum('grand_total') ?? 0;
        $scope1Total = $emissionSources->sum('scope1_total') ?? 0;
        $scope2Total = $emissionSources->sum('scope2_total') ?? 0;
        $scope3Total = $emissionSources->sum('scope3_total') ?? 0;
        
        // Calculate month-over-month change
        $currentMonth = now()->month;
        $lastMonth = $currentMonth - 1;
        
        $currentMonthEmissions = $emissionSources
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('grand_total') ?? 0;
            
        $lastMonthEmissions = $emissionSources
            ->whereBetween('created_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth()
            ])
            ->sum('grand_total') ?? 0;

        $monthlyChange = $lastMonthEmissions > 0 
            ? (($currentMonthEmissions - $lastMonthEmissions) / $lastMonthEmissions) * 100 
            : 0;

        return [
            'total_emissions' => round($totalEmissions, 2),
            'scope1_total' => round($scope1Total, 2),
            'scope2_total' => round($scope2Total, 2),
            'scope3_total' => round($scope3Total, 2),
            'monthly_change' => round($monthlyChange, 1),
            'reports_count' => $emissionSources->count(),
            'draft_reports' => $emissionSources->where('status', 'draft')->count(),
            'submitted_reports' => $emissionSources->where('status', 'submitted')->count(),
        ];
    }

    private function getChartData($emissionSources)
    {
        // Monthly emissions trend
        $monthlyTrend = $emissionSources
            ->groupBy(function($item) {
                return $item->created_at->format('Y-m');
            })
            ->map(function($group) {
                return $group->sum('grand_total');
            })
            ->sortKeys();

        // Emissions by scope
        $scopeBreakdown = [
            'Scope 1' => $emissionSources->sum('scope1_total') ?? 0,
            'Scope 2' => $emissionSources->sum('scope2_total') ?? 0,
            'Scope 3' => $emissionSources->sum('scope3_total') ?? 0,
        ];

        // Top facilities/companies
        $facilityBreakdown = $emissionSources
            ->groupBy('company_name')
            ->map(function($group) {
                return $group->sum('grand_total');
            })
            ->sortDesc()
            ->take(5);

        return [
            'monthly_trend' => $monthlyTrend,
            'scope_breakdown' => $scopeBreakdown,
            'facility_breakdown' => $facilityBreakdown,
        ];
    }

    private function calculateNetZeroProgress($totalEmissions)
    {
        // UAE Net Zero 2050 target: Assume baseline of 1000 tonnes CO2e per company
        $baseline = 1000; // tonnes CO2e
        $target = 0; // Net zero
        $current = $totalEmissions / 1000; // Convert kg to tonnes
        
        $progress = max(0, min(100, (($baseline - $current) / $baseline) * 100));
        
        return [
            'current' => round($current, 2),
            'baseline' => $baseline,
            'target' => $target,
            'progress' => round($progress, 1),
            'years_remaining' => 2050 - now()->year,
        ];
    }

    private function getTopEmissionSources($emissionSources)
    {
        return $emissionSources
            ->sortByDesc('grand_total')
            ->take(5)
            ->map(function($source) {
                return [
                    'company' => $source->company_name,
                    'year' => $source->reporting_year,
                    'emissions' => round($source->grand_total ?? 0, 2),
                    'scope1' => round($source->scope1_total ?? 0, 2),
                    'scope2' => round($source->scope2_total ?? 0, 2),
                    'scope3' => round($source->scope3_total ?? 0, 2),
                    'status' => $source->status,
                ];
            });
    }
}
