<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmissionSourceMaster;
use App\Models\Company;
use App\Models\Facility;
use App\Models\Measurement;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{

    public function index()
    {
        $user = auth()->user();
        
        // Check if user has a company
        if (!$user->company_id) {
            return view('dashboard.index', [
                'needsCompanySetup' => true,
                'kpis' => [
                    'total_emissions' => 0,
                    'scope1_total' => 0,
                    'scope2_total' => 0,
                    'scope3_total' => 0,
                    'monthly_change' => 0,
                    'reports_count' => 0,
                    'draft_reports' => 0,
                    'submitted_reports' => 0,
                ],
                'chartData' => [
                    'monthly_trend' => collect([]),
                    'scope_breakdown' => [
                        'Scope 1' => 0,
                        'Scope 2' => 0,
                        'Scope 3' => 0,
                    ],
                    'location_breakdown' => collect([]),
                ],
                'netZeroProgress' => [
                    'current' => 0,
                    'baseline' => 1000,
                    'target' => 0,
                    'progress' => 0,
                    'years_remaining' => 25,
                ],
                'topSources' => collect([]),
                'recentActivity' => collect([])
            ]);
        }
        
        // Get all measurements for the user's company
        $measurements = Measurement::whereHas('location', function($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->with('location')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate KPIs
        $kpis = $this->calculateKPIs($measurements);
        
        // Get chart data
        $chartData = $this->getChartData($measurements);
        
        // Get UAE Net Zero progress
        $netZeroProgress = $this->calculateNetZeroProgress($kpis['total_emissions']);
        
        // Get top emission sources
        $topSources = $this->getTopEmissionSources($measurements);
        
        // Get recent activity
        $recentActivity = $measurements->take(5);

        return view('dashboard.index', compact(
            'kpis', 
            'chartData', 
            'netZeroProgress', 
            'topSources', 
            'recentActivity'
        ));
    }

    private function calculateKPIs($measurements)
    {
        $totalEmissions = $measurements->sum('total_co2e') ?? 0;
        $scope1Total = $measurements->sum('scope_1_co2e') ?? 0;
        $scope2Total = $measurements->sum('scope_2_co2e') ?? 0;
        $scope3Total = $measurements->sum('scope_3_co2e') ?? 0;
        
        // Calculate month-over-month change
        $currentMonthEmissions = $measurements
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('total_co2e') ?? 0;
            
        $lastMonthEmissions = $measurements
            ->whereBetween('created_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth()
            ])
            ->sum('total_co2e') ?? 0;

        $monthlyChange = $lastMonthEmissions > 0 
            ? (($currentMonthEmissions - $lastMonthEmissions) / $lastMonthEmissions) * 100 
            : 0;

        return [
            'total_emissions' => round($totalEmissions, 2),
            'scope1_total' => round($scope1Total, 2),
            'scope2_total' => round($scope2Total, 2),
            'scope3_total' => round($scope3Total, 2),
            'monthly_change' => round($monthlyChange, 1),
            'reports_count' => $measurements->count(),
            'draft_reports' => $measurements->where('status', 'draft')->count(),
            'submitted_reports' => $measurements->where('status', 'submitted')->count(),
        ];
    }

    private function getChartData($measurements)
    {
        // Monthly emissions trend
        $monthlyTrend = $measurements
            ->groupBy(function($item) {
                return $item->created_at->format('Y-m');
            })
            ->map(function($group) {
                return $group->sum('total_co2e');
            })
            ->sortKeys();

        // Emissions by scope
        $scopeBreakdown = [
            'Scope 1' => $measurements->sum('scope_1_co2e') ?? 0,
            'Scope 2' => $measurements->sum('scope_2_co2e') ?? 0,
            'Scope 3' => $measurements->sum('scope_3_co2e') ?? 0,
        ];

        // Top locations
        $locationBreakdown = $measurements
            ->groupBy('location.name')
            ->map(function($group) {
                return $group->sum('total_co2e');
            })
            ->sortDesc()
            ->take(5);

        return [
            'monthly_trend' => $monthlyTrend,
            'scope_breakdown' => $scopeBreakdown,
            'location_breakdown' => $locationBreakdown,
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

    private function getTopEmissionSources($measurements)
    {
        return $measurements
            ->sortByDesc('total_co2e')
            ->take(5)
            ->map(function($measurement) {
                return [
                    'location' => $measurement->location->name ?? 'Unknown Location',
                    'period' => $measurement->period_start->format('M Y') . ' - ' . $measurement->period_end->format('M Y'),
                    'emissions' => round($measurement->total_co2e ?? 0, 2),
                    'scope1' => round($measurement->scope_1_co2e ?? 0, 2),
                    'scope2' => round($measurement->scope_2_co2e ?? 0, 2),
                    'scope3' => round($measurement->scope_3_co2e ?? 0, 2),
                    'status' => $measurement->status,
                ];
            });
    }
}
