<?php

namespace App\Http\Controllers;

use App\Models\Measurement;
use App\Exports\ResultsBreakdownExport;
use App\Services\GhgReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct(
        protected GhgReportService $reportService
    ) {}

    public function index()
    {
        $user = Auth::user();
        $company = $user->getActiveCompany();

        if (!$company) {
            abort(403, 'No active company found.');
        }

        $locations = $company->locations()->with('measurements')->get();
        $fiscalYears = $locations->pluck('measurements')->flatten()
            ->pluck('fiscal_year')
            ->unique()
            ->sortDesc()
            ->values();

        return view('reports.index', compact('locations', 'fiscalYears'));
    }

    public function show(Request $request)
    {
        $user = Auth::user();
        $company = $user->getActiveCompany();
        if (!$company) {
            abort(403, 'No active company found.');
        }

        $locations = $company->locations()->with('measurements')->get();
        $fiscalYears = $locations->pluck('measurements')->flatten()
            ->pluck('fiscal_year')
            ->unique()
            ->sortDesc()
            ->values();

        $measurement = Measurement::with('location')
            ->where('fiscal_year', $request->fiscal_year)
            ->where('location_id', $request->location_id)
            ->whereHas('location', fn ($q) => $q->where('company_id', $company->id))
            ->first();

        if (!$measurement) {
            return view('reports.index', compact('locations', 'fiscalYears'))
                ->with('error', 'No emission data found for this location and year. Enter data in Input Data first.');
        }

        $report = $this->reportService->build($measurement);

        return view('reports.index', array_merge(
            compact('locations', 'fiscalYears', 'measurement', 'company'),
            [
                'report' => $report,
                'selectedFiscalYear' => $request->fiscal_year,
                'selectedLocationId' => $request->location_id,
                // Legacy keys for chart script
                'scopePercentages' => $report['scope_percentages'],
                'scopeRawValues' => $report['scope_raw_tonnes'],
                'emissionSourceData' => $report['emission_source_data'],
                'resultsBreakdown' => $report['results_breakdown'],
            ]
        ));
    }

    public function exportExcel(Request $request)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            abort(403);
        }

        $measurement = $this->findMeasurement($request, $company->id);
        $report = $this->reportService->build($measurement);

        return Excel::download(
            new ResultsBreakdownExport($report['results_breakdown']),
            $this->reportFilename($measurement, 'xlsx')
        );
    }

    public function exportPDF(Request $request)
    {
        $user = Auth::user();
        $company = $user->getActiveCompany();
        if (!$company) {
            abort(403);
        }

        $measurement = $this->findMeasurement($request, $company->id);
        $report = $this->reportService->build($measurement);

        $scopeChart = $this->buildScopeChart($report);
        $sourceChart = $this->buildSourceChart($report);

        $pdf = Pdf::loadView('reports.pdf', [
            'company' => $company,
            'report' => $report,
            'scopeChart' => $scopeChart,
            'sourceChart' => $sourceChart,
        ])->setPaper('a4', 'portrait')->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'dejavu sans',
        ]);

        return $pdf->download($this->reportFilename($measurement, 'pdf'));
    }

    protected function findMeasurement(Request $request, int $companyId): Measurement
    {
        return Measurement::with('location')
            ->where('fiscal_year', $request->fiscal_year)
            ->where('location_id', $request->location_id)
            ->whereHas('location', fn ($q) => $q->where('company_id', $companyId))
            ->firstOrFail();
    }

    protected function reportFilename(Measurement $measurement, string $ext): string
    {
        $location = preg_replace('/[^a-z0-9]+/i', '-', strtolower($measurement->location->name ?? 'location'));
        return "ghg-inventory-{$measurement->fiscal_year}-{$location}.{$ext}";
    }

    protected function buildScopeChart(array $report): ?string
    {
        $labels = [];
        $values = [];
        $colors = ['#059669', '#0284c7', '#9333ea'];

        foreach ($report['scope_tonnes'] as $scope => $tonnes) {
            if ($tonnes <= 0 && $scope === 'Scope 3' && !$report['has_scope_3']) {
                continue;
            }
            if ($tonnes > 0 || $scope !== 'Scope 3') {
                $labels[] = $scope;
                $values[] = $tonnes;
            }
        }

        if (array_sum($values) <= 0) {
            return null;
        }

        return $this->generateChartUrl([
            'type' => 'doughnut',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $values,
                    'backgroundColor' => array_slice($colors, 0, count($values)),
                ]],
            ],
            'options' => [
                'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['font' => ['size' => 11]]]],
            ],
        ]);
    }

    protected function buildSourceChart(array $report): ?string
    {
        $sources = $report['emission_source_data']->filter(fn ($s) => $s['tonnes'] > 0);
        if ($sources->isEmpty()) {
            return null;
        }

        return $this->generateChartUrl([
            'type' => 'bar',
            'data' => [
                'labels' => $sources->pluck('label')->take(8)->values()->all(),
                'datasets' => [[
                    'label' => 'tCO2e',
                    'data' => $sources->pluck('tonnes')->take(8)->values()->all(),
                    'backgroundColor' => '#059669',
                ]],
            ],
            'options' => [
                'plugins' => ['legend' => ['display' => false]],
                'scales' => ['y' => ['beginAtZero' => true]],
            ],
        ]);
    }

    private function generateChartUrl(array $config): string
    {
        return 'https://quickchart.io/chart?c=' . urlencode(json_encode($config)) . '&w=500&h=280';
    }
}
