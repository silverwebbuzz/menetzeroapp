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
        // Get user from web guard
        $user = auth('web')->user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Reload relationships to ensure fresh data (important after invitation acceptance)
        // Only load activeContext if table exists
        try {
            $user->load('companyRoles');
            // Try to load activeContext, but don't fail if table doesn't exist
            if (\Illuminate\Support\Facades\Schema::hasTable('user_active_contexts')) {
                $user->load('activeContext');
            }
        } catch (\Exception $e) {
            // If table doesn't exist, just load companyRoles
            $user->load('companyRoles');
        }
        
        // Check if user has multiple company access - show workspace selector
        if ($user->hasMultipleCompanyAccess()) {
            return redirect()->route('account.selector');
        }
        
        // Get active company (owned or staff)
        $company = $user->getActiveCompany();
        
        // STEP 3: If no company, check if user has any company access
        if (!$company) {
            // Check if user has any company access (owned or staff)
            $hasAnyCompany = $user->ownsCompany() || $user->isStaffInAnyCompany();
            
            // Debug logging
            \Log::info('Dashboard - No active company found', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'owns_company' => $user->ownsCompany(),
                'is_staff_in_any' => $user->isStaffInAnyCompany(),
                'has_any_company' => $hasAnyCompany,
                'company_roles_count' => $user->companyRoles()->where('is_active', true)->count(),
                'staff_companies_count' => $user->getStaffCompanies()->count(),
            ]);
            
            if (!$hasAnyCompany) {
                // User has no company access - show message
                return view('dashboard.no-company-access');
            }
            
            // User has company access but no active context - redirect to selector
            if ($user->hasMultipleCompanyAccess()) {
                return redirect()->route('account.selector');
            }
            
            // Single company but no active context - try to set it
            $accessibleCompanies = $user->getAccessibleCompanies();
            if ($accessibleCompanies->isNotEmpty()) {
                $firstCompany = $accessibleCompanies->first();
                $user->switchToCompany($firstCompany['id']);
                $company = $user->getActiveCompany();
            }
        }
        
        // If still no company after trying to set context
        if (!$company) {
            // Check if user is staff (not owner) - they shouldn't see company setup
            $isStaff = $user->isStaffInAnyCompany() && !$user->ownsCompany();
            
            if ($isStaff) {
                // Staff user with no active company - show no access message
                return view('dashboard.no-company-access');
            }
            
            // Owner with no company - show company setup form
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
                    'monthly_labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    'monthly_emissions' => [0, 0, 0, 0, 0, 0],
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
        
        // STEP 4: Check if company information is incomplete
        // Only ask for company setup if user is the OWNER (not staff)
        // Staff users don't need to add company information - they're just accessing an existing company
        $isOwner = $user->ownsCompany() && $user->getOwnedCompany()?->id === $company->id;
        
        if ($isOwner) {
            // Only check if company name is missing or very basic - other fields are optional
            // Also check if company name is just a placeholder or empty string
            $companyName = trim($company->name ?? '');
            if (empty($companyName) || $companyName === 'New Company' || $companyName === '') {
                return view('dashboard.index', [
                    'needsCompanySetup' => true,
                    'company' => $company,
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
                        'monthly_labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                        'monthly_emissions' => [0, 0, 0, 0, 0, 0],
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
        }
        // If user is staff, skip company setup check - they don't need to add company info
        
        // Get all measurements for the user's active company
        $measurements = Measurement::whereHas('location', function($query) use ($company) {
                $query->where('company_id', $company->id);
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
        // Get current year data
        $currentYear = now()->year;
        $currentYearMeasurements = $measurements->filter(function($measurement) use ($currentYear) {
            return $measurement->period_start->year == $currentYear;
        });

        $totalEmissions = $currentYearMeasurements->sum('total_co2e') ?? 0;
        $scope1Total = $currentYearMeasurements->sum('scope_1_co2e') ?? 0;
        $scope2Total = $currentYearMeasurements->sum('scope_2_co2e') ?? 0;
        $scope3Total = $currentYearMeasurements->sum('scope_3_co2e') ?? 0;
        
        // Verify calculation: total should equal scope1 + scope2 + scope3
        $calculatedTotal = $scope1Total + $scope2Total + $scope3Total;
        if (abs($totalEmissions - $calculatedTotal) > 0.01) {
            // If there's a discrepancy, use the calculated total
            $totalEmissions = $calculatedTotal;
        }
        
        // Calculate month-over-month change based on measurement periods
        $currentMonth = now()->format('Y-m');
        $currentMonthEmissions = $measurements
            ->filter(function($measurement) use ($currentMonth) {
                return $measurement->period_start->format('Y-m') === $currentMonth;
            })
            ->sum('total_co2e') ?? 0;
            
        $lastMonth = now()->subMonth()->format('Y-m');
        $lastMonthEmissions = $measurements
            ->filter(function($measurement) use ($lastMonth) {
                return $measurement->period_start->format('Y-m') === $lastMonth;
            })
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
            'period' => $currentYear, // Add period context
        ];
    }

    private function getChartData($measurements)
    {
        // Monthly emissions trend - get last 6 months based on measurement periods
        $monthlyData = collect();
        
        // Get the range of months from the measurements
        $measurementMonths = $measurements->map(function($measurement) {
            return $measurement->period_start->format('Y-m');
        })->unique()->sort()->values();
        
        // If we have measurements, use their months, otherwise use last 6 months
        if ($measurementMonths->isNotEmpty()) {
            $monthsToShow = $measurementMonths->take(6);
        } else {
            $monthsToShow = collect();
            for ($i = 5; $i >= 0; $i--) {
                $monthsToShow->push(now()->subMonths($i)->format('Y-m'));
            }
        }
        
        foreach ($monthsToShow as $monthKey) {
            $month = \Carbon\Carbon::createFromFormat('Y-m', $monthKey);
            $monthLabel = $month->format('M');
            
            $monthlyEmissions = $measurements
                ->filter(function($measurement) use ($month) {
                    return $measurement->period_start->format('Y-m') === $month->format('Y-m');
                })
                ->sum('total_co2e') ?? 0;
                
            $monthlyData->put($monthKey, [
                'label' => $monthLabel,
                'emissions' => round($monthlyEmissions, 2)
            ]);
        }

        // Prepare chart data
        $monthlyLabels = $monthlyData->pluck('label')->toArray();
        $monthlyEmissions = $monthlyData->pluck('emissions')->toArray();

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
            'monthly_labels' => $monthlyLabels,
            'monthly_emissions' => $monthlyEmissions,
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
