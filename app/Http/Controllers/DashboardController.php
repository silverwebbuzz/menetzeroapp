<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmissionSourceMaster;
use App\Models\Company;
use App\Models\Facility;
use App\Models\Measurement;
use App\Models\MasterIndustryCategory;
use App\Services\GhgReportService;
use App\Services\OnboardingService;
use App\Services\DashboardInsightsService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(protected OnboardingService $onboarding)
    {
    }

    public function index()
    {
        // Get user from web guard
        $user = auth('web')->user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        $consultantOrgWorkspace = app(\App\Services\ConsultantAgencyWorkspaceService::class);
        if ($consultantOrgWorkspace->isConsultantOrgUser($user) && !$consultantOrgWorkspace->isActingAsManagedClient($user)) {
            return redirect()->route('consultant.dashboard');
        }
        
        // Reload relationships to ensure fresh data (important after invitation acceptance)
        // Only load activeContext if table exists
        try {
            $user->load('companyRoles');
            // Try to load activeContext, but don't fail if table doesn't exist
            if (\Illuminate\Support\Facades\Schema::hasTable('user_active_context')) {
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
            
            // Check if user is a company_admin without a company (new registration)
            // They should be allowed to create a company, not see "No Company Access"
            $isNewCompanyAdmin = $user->role === 'company_admin' && !$hasAnyCompany;
            
            // Debug logging
            \Log::info('Dashboard - No active company found', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role,
                'owns_company' => $user->ownsCompany(),
                'is_staff_in_any' => $user->isStaffInAnyCompany(),
                'has_any_company' => $hasAnyCompany,
                'is_new_company_admin' => $isNewCompanyAdmin,
                'company_roles_count' => $user->companyRoles()->where('is_active', true)->count(),
                'staff_companies_count' => $user->getStaffCompanies()->count(),
            ]);
            
            if (!$hasAnyCompany && !$isNewCompanyAdmin) {
                // User has no company access and is not a new company_admin - show message
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
            $sectors = MasterIndustryCategory::getSectors();
            return view('dashboard.index', [
                'needsCompanySetup' => true,
                'sectors' => $sectors,
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
            $onboardingStep = $this->onboarding->currentStep($user);

            if ($onboardingStep === 'business') {
                $sectors = MasterIndustryCategory::getSectors();
                return view('dashboard.index', [
                    'needsCompanySetup' => true,
                    'company' => $company,
                    'sectors' => $sectors,
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

            if ($onboardingStep === 'location') {
                return redirect()->route('locations.create', ['onboarding' => 1])
                    ->with('info', 'Add at least one business location before entering emission data.');
            }
        }
        // If user is staff, skip company setup check - they don't need to add company info
        
        // Every year the company has measurements for — drives the year filter.
        $availableYears = Measurement::whereHas('location', function ($query) use ($company) {
                $query->where('company_id', $company->id);
            })
            ->distinct()
            ->orderByDesc('fiscal_year')
            ->pluck('fiscal_year')
            ->map(fn ($y) => (int) $y)
            ->filter()
            ->values()
            ->all();

        // Selected year: an explicit ?fiscal_year=, else the newest year with
        // data, else the current calendar year. Only years that actually exist
        // are honoured, so a stale bookmark cannot show an empty dashboard.
        $requestedYear = (int) request()->input('fiscal_year', 0);
        $selectedYear = in_array($requestedYear, $availableYears, true)
            ? $requestedYear
            : ($availableYears[0] ?? (int) now()->year);

        $allMeasurements = Measurement::whereHas('location', function ($query) use ($company) {
                $query->where('company_id', $company->id);
            })
            ->with('location')
            ->orderBy('created_at', 'desc')
            ->get();

        // Scoped to the selected year so KPIs describe one reporting period
        // rather than summing every year the company has ever recorded.
        $measurements = $allMeasurements->where('fiscal_year', $selectedYear)->values();

        // Calculate KPIs
        $kpis = $this->calculateKPIs($measurements, $selectedYear);
        
        // Get chart data
        $chartData = $this->getChartData($measurements);
        
        // Get UAE Net Zero progress
        $netZeroProgress = $this->calculateNetZeroProgress($kpis['total_emissions'], $company, $selectedYear, $kpis);
        
        // Get top emission sources
        $topSources = $this->getTopEmissionSources($measurements);
        
        // Get recent activity
        $recentActivity = $measurements->take(5);

        $insights = app(DashboardInsightsService::class);
        $fiscalYear = $selectedYear;
        $twelveMonth = $insights->twelveMonthTrend($measurements);
        // Year-on-year comparison across ALL years, not just the selected one.
        $yearlyTrend = $insights->yearlyTrend($allMeasurements);
        $yearOverYear = $insights->yearOverYear($measurements);
        $compliance = $insights->complianceStatus(
            $company->id,
            $fiscalYear,
            ($kpis['total_emissions'] ?? 0) > 0
        );
        $recommendations = $insights->recommendations($company, $measurements, $kpis);

        $chartData['monthly_labels'] = $twelveMonth['labels'];
        $chartData['monthly_emissions'] = $twelveMonth['values'];
        $chartData['sparklines'] = $twelveMonth['sparklines'];

        $reductionPct = $netZeroProgress['baseline'] > 0
            ? round((1 - ($netZeroProgress['current'] / $netZeroProgress['baseline'])) * 100, 1)
            : 0;
        $netZeroProgress['reduction_pct'] = max(0, $reductionPct);
        // target_year comes from the company's own target when one exists —
        // don't overwrite it with the 2050 default.
        $netZeroProgress['projected_achievement'] = $netZeroProgress['progress'] >= 80
            ? 'On track'
            : ($netZeroProgress['progress'] >= 40 ? 'Needs acceleration' : 'Early stage');

        return view('dashboard.index', compact(
            'kpis',
            'chartData',
            'netZeroProgress',
            'availableYears',
            'selectedYear',
            'yearlyTrend',
            'topSources',
            'recentActivity',
            'yearOverYear',
            'compliance',
            'recommendations',
            'company',
        ));
    }

    private function calculateKPIs($measurements, ?int $selectedYear = null)
    {
        // $measurements is already scoped to the selected year by index(); the
        // filter below is a safety net for any other caller. Filtering on
        // now()->year here would zero the KPIs whenever a past year is selected.
        $currentYear = $selectedYear ?? now()->year;
        $currentYearMeasurements = $measurements->filter(function($measurement) use ($currentYear) {
            return (int) $measurement->fiscal_year === (int) $currentYear;
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
                'emissions' => round($monthlyEmissions, 2),
            ]);
        }

        // Prepare chart data
        $monthlyLabels = $monthlyData->pluck('label')->toArray();
        $monthlyEmissions = $monthlyData->pluck('emissions')->toArray();

        // Emissions by scope (stored kg — converted to tCO₂e in views/charts)
        $scopeBreakdown = [
            'Scope 1' => $measurements->sum('scope_1_co2e') ?? 0,
            'Scope 2' => $measurements->sum('scope_2_co2e') ?? 0,
            'Scope 3' => $measurements->sum('scope_3_co2e') ?? 0,
        ];

        // Top locations
        $locationBreakdown = $measurements
            ->groupBy('location.name')
            ->map(fn ($group) => $group->sum('total_co2e'))
            ->sortDesc()
            ->take(5);

        return [
            'monthly_labels' => $monthlyLabels,
            'monthly_emissions' => $monthlyEmissions,
            'scope_breakdown' => $scopeBreakdown,
            'location_breakdown' => $locationBreakdown,
        ];
    }

    /**
     * Net zero progress for the selected year.
     *
     * Prefers the company's own active ReductionTarget (Disclosures → Targets)
     * so this card agrees with the ESG dashboard. Falls back to the UAE Net Zero
     * 2050 pathway with a nominal baseline only when no target has been set —
     * without that fallback a company with no targets would show nothing.
     */
    private function calculateNetZeroProgress($totalEmissions, $company = null, ?int $selectedYear = null, array $kpis = [])
    {
        $current = GhgReportService::kgToTonnes($totalEmissions);

        $target = null;

        if ($company) {
            $target = \App\Models\ReductionTarget::where('company_id', $company->id)
                ->where('status', 'active')
                ->whereNotNull('baseline_tco2e')
                ->orderBy('target_year')
                ->first();
        }

        if ($target) {
            $baseline = (float) $target->baseline_tco2e;

            // Compare like with like: a Scope 1 & 2 target must be measured
            // against Scope 1 + 2 actuals, not a total that includes Scope 3.
            $current = GhgReportService::kgToTonnes(
                $this->emissionsForCoverage($target->scope_coverage, $kpis, $totalEmissions)
            );

            $targetTonnes = $target->target_tco2e !== null
                ? (float) $target->target_tco2e
                : ($target->reduction_percent !== null
                    ? $baseline * (1 - ((float) $target->reduction_percent / 100))
                    : 0.0);

            $targetYear = (int) $target->target_year;
            $required = $baseline - $targetTonnes;

            // A target at or above baseline implies no reduction — treat as
            // unset rather than dividing by zero or a negative.
            $progress = $required > 0
                ? max(0, min(100, (($baseline - $current) / $required) * 100))
                : 0;

            return [
                'current' => round($current, 2),
                'baseline' => round($baseline, 2),
                'target' => round($targetTonnes, 2),
                'target_name' => $target->name,
                'scope_label' => \App\Models\ReductionTarget::SCOPE_COVERAGE[$target->scope_coverage] ?? null,
                'has_target' => true,
                'progress' => round($progress, 1),
                'target_year' => $targetYear,
                'years_remaining' => max(0, $targetYear - ($selectedYear ?? (int) now()->year)),
            ];
        }

        // No target set — UAE Net Zero 2050 pathway against a nominal baseline.
        $baseline = 1000; // tonnes CO2e
        $progress = max(0, min(100, (($baseline - $current) / $baseline) * 100));

        return [
            'current' => round($current, 2),
            'baseline' => $baseline,
            'target' => 0,
            'target_name' => null,
            'has_target' => false,
            'progress' => round($progress, 1),
            'target_year' => 2050,
            'years_remaining' => max(0, 2050 - ($selectedYear ?? (int) now()->year)),
        ];
    }

    /**
     * Emissions (kg) for the scopes a reduction target covers.
     */
    private function emissionsForCoverage(?string $coverage, array $kpis, $totalEmissions)
    {
        $scope1 = (float) ($kpis['scope1_total'] ?? 0);
        $scope2 = (float) ($kpis['scope2_total'] ?? 0);
        $scope3 = (float) ($kpis['scope3_total'] ?? 0);

        return match ($coverage) {
            'scope1' => $scope1,
            'scope2' => $scope2,
            'scope12' => $scope1 + $scope2,
            'scope3' => $scope3,
            'scope123' => $scope1 + $scope2 + $scope3,
            default => (float) $totalEmissions,
        };
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
