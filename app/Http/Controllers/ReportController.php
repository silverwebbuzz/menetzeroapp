<?php

namespace App\Http\Controllers;

use App\Models\Measurement;
use App\Models\MeasurementData;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Exports\ResultsBreakdownExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * Show report filter form
     */
    public function index()
    {
        $user = Auth::user();
        $company = $user->getActiveCompany();

        if (!$company) {
            abort(403, 'No active company found.');
        }

        // Fetch locations and fiscal years
        $locations = $company->locations()->with('measurements')->get();
        $fiscalYears = $locations->pluck('measurements')->flatten()
            ->pluck('fiscal_year')
            ->unique()
            ->values();

        return view('reports.index', compact('locations', 'fiscalYears'));
    }

    /**
     * Show report results
     */
    public function show(Request $request)
    {
        $user = Auth::user();
        $company = $user->getActiveCompany();
        if (!$company)
            abort(403, 'No active company found.');

        $locations = $company->locations()->with('measurements')->get();
        $fiscalYears = $locations->pluck('measurements')->flatten()
            ->pluck('fiscal_year')
            ->unique()
            ->values();

        // Get selected measurement
        $measurement = Measurement::where('fiscal_year', $request->fiscal_year)
            ->where('location_id', $request->location_id)
            ->first();

        if (!$measurement) {
            return view('reports.index', compact('locations', 'fiscalYears'));
        }

        // Scope totals
        $total = $measurement->total_co2e ?? 0;
        $scopes = [
            'Scope 1' => $measurement->scope_1_co2e ?? 0,
            'Scope 2' => $measurement->scope_2_co2e ?? 0,
            'Scope 3' => $measurement->scope_3_co2e ?? 0,
        ];

        $scopeRawValues = array_map(fn($v) => number_format($v, 2), array_values($scopes));
        $scopePercentages = array_map(fn($v) => $total > 0 ? round(($v / $total) * 100, 2) : 0, array_values($scopes));

        // Emission source chart
        $emissionSourceData = MeasurementData::with('emissionSource:id,name')
            ->select('emission_source_id', DB::raw('SUM(calculated_co2e) as total_co2e'))
            ->where('measurement_id', $measurement->id)
            ->groupBy('emission_source_id')
            ->get()
            ->map(function ($row) use ($total) {
                return [
                    'label' => $row->emissionSource->name,
                    'raw' => round($row->total_co2e, 2),
                    'percent' => $total > 0 ? round(($row->total_co2e / $total) * 100, 2) : 0,
                ];
            })->values();

        // Results breakdown table
        $resultsBreakdown = $this->buildResultsBreakdown($measurement);

        return view('reports.index', compact(
            'locations',
            'fiscalYears',
            'measurement',
            'scopeRawValues',
            'scopePercentages',
            'emissionSourceData',
            'resultsBreakdown'
        ));
    }

    /**
     * Export Results Breakdown to Excel
     */
    public function exportExcel(Request $request)
    {
        $user = Auth::user();
        $company = $user->getActiveCompany();
        if (!$company)
            abort(403, 'No active company found.');

        // Get selected measurement
        $measurement = Measurement::where('fiscal_year', $request->fiscal_year)
            ->where('location_id', $request->location_id)
            ->firstOrFail();

        $resultsBreakdown = $this->buildResultsBreakdown($measurement);

        return Excel::download(new ResultsBreakdownExport($resultsBreakdown), 'results_breakdown.xlsx');
    }

    /**
     * Build results breakdown array for table or export
     */
    private function buildResultsBreakdown(Measurement $measurement): array
    {
        $breakdownByScope = MeasurementData::with('emissionSource:id,name,scope')
            ->select('emission_source_id', DB::raw('SUM(calculated_co2e) as total_co2e'))
            ->where('measurement_id', $measurement->id)
            ->groupBy('emission_source_id')
            ->get()
            ->groupBy(fn($row) => $row->emissionSource->scope);

        $resultsBreakdown = [];

        foreach (['Scope 1', 'Scope 2', 'Scope 3'] as $scope) {
            $children = [];
            $scopeTotal = 0;

            foreach ($breakdownByScope[$scope] ?? [] as $row) {
                $children[] = [
                    'name' => $row->emissionSource->name,
                    'value' => round($row->total_co2e, 2),
                ];
                $scopeTotal += $row->total_co2e;
            }

            $resultsBreakdown[] = [
                'name' => $scope,
                'value' => round($scopeTotal, 2),
                'children' => $children
            ];
        }

        return $resultsBreakdown;
    }

    public function exportPDF(Request $request)
    {
        $user = Auth::user();
        $company = $user->getActiveCompany();
        if (!$company)
            abort(403);

        $measurement = Measurement::where('fiscal_year', $request->fiscal_year)
            ->where('location_id', $request->location_id)
            ->firstOrFail();

        $resultsBreakdown = $this->buildResultsBreakdown($measurement);

        $total = $measurement->total_co2e ?? 0;

        $scopes = [
            'Scope 1' => $measurement->scope_1_co2e ?? 0,
            'Scope 2' => $measurement->scope_2_co2e ?? 0,
            'Scope 3' => $measurement->scope_3_co2e ?? 0,
        ];

        /** -------------------------------
         *  Scope Pie Chart
         * ------------------------------- */
        $scopeChart = $this->generateChartUrl([
            'type' => 'pie',
            'data' => [
                'labels' => array_keys($scopes),
                'datasets' => [
                    [
                        'data' => array_values($scopes),
                        'backgroundColor' => ['#3FAE91', '#FFA726', '#9C6ADE'],
                    ]
                ]
            ],
            'options' => [
                'plugins' => [
                    'legend' => ['display' => false],
                    'tooltip' => ['enabled' => false],
                    'datalabels' => ['display' => false],
                ],
            ]
        ]);

        /** -------------------------------
         *  Emission Source Pie Chart
         * ------------------------------- */
        $emissionSourceRows = MeasurementData::with('emissionSource:id,name')
            ->select('emission_source_id', DB::raw('SUM(calculated_co2e) as total_co2e'))
            ->where('measurement_id', $measurement->id)
            ->groupBy('emission_source_id')
            ->get();

        $emissionSourceLabels = [];
        $emissionSourceValues = [];

        foreach ($emissionSourceRows as $row) {
            if ($row->emissionSource && $row->total_co2e > 0) {
                $emissionSourceLabels[] = $row->emissionSource->name;
                $emissionSourceValues[] = round($row->total_co2e, 2);
            }
        }

        $emissionSourceChart = null;

        if (!empty($emissionSourceValues)) {
            $emissionSourceChart = $this->generateChartUrl([
                'type' => 'pie',
                'data' => [
                    'labels' => $emissionSourceLabels,
                    'datasets' => [
                        [
                            'data' => $emissionSourceValues,
                            'backgroundColor' => [
                                '#42A5F5',
                                '#66BB6A',
                                '#FFA726',
                                '#AB47BC',
                                '#EC407A',
                                '#26C6DA',
                            ],
                        ]
                    ]
                ],
                'options' => [
                    'plugins' => [
                        'legend' => ['display' => false],
                        'tooltip' => ['enabled' => false],
                        'datalabels' => ['display' => false],
                    ],
                ]
            ]);
        }

        // return view('reports.pdf', compact(
        //     'measurement',
        //     'resultsBreakdown',
        //     'total',
        //     'scopes',
        //     'company',
        //     'scopeChart',
        //     'emissionSourceChart'
        // ));
        $pdf = Pdf::loadView('reports.pdf', compact(
            'measurement',
            'resultsBreakdown',
            'total',
            'scopes',
            'company',
            'scopeChart',
            'emissionSourceChart'
        ))->setPaper('a4')->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'dejavu sans',
                ]);

        return $pdf->download('emissions-report.pdf');
    }

    private function generateChartUrl(array $config): string
    {
        $baseUrl = 'https://quickchart.io/chart';
        return $baseUrl . '?c=' . urlencode(json_encode($config));
    }

}
