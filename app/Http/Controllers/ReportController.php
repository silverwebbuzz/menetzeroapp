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
        $moccaeOnly = $request->boolean('moccae_only');
        $report = $this->reportService->finalizeReport($report, $moccaeOnly);

        $chartPayload = $this->buildChartPayload($report, $moccaeOnly);

        return view('reports.index', array_merge(
            compact('locations', 'fiscalYears', 'measurement', 'company'),
            [
                'report' => $report,
                'moccaeOnly' => $moccaeOnly,
                'chartPayload' => $chartPayload,
                'selectedFiscalYear' => $request->fiscal_year,
                'selectedLocationId' => $request->location_id,
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
        $moccaeOnly = $request->boolean('moccae_only');
        $report = $this->reportService->finalizeReport(
            $this->reportService->build($measurement),
            $moccaeOnly
        );

        return Excel::download(
            new ResultsBreakdownExport(
                $report['results_breakdown'],
                $report['display_total_tonnes'],
                $report['scope_3_categories'] ?? collect()
            ),
            $this->reportFilename($measurement, 'xlsx', $moccaeOnly)
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
        $moccaeOnly = $request->boolean('moccae_only');
        $report = $this->reportService->finalizeReport(
            $this->reportService->build($measurement),
            $moccaeOnly
        );

        $scopeChart = $this->buildScopeChart($report);
        $sourceChart = $this->buildSourceChart($report);

        $pdf = Pdf::loadView('reports.pdf', [
            'company' => $company,
            'report' => $report,
            'scopeChart' => $scopeChart,
            'sourceChart' => $sourceChart,
            'companyLogo' => $company->logoDataUri(),
            'platformLogo' => $this->platformLogoDataUri(),
        ])->setPaper('a4', 'portrait')->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'dejavu sans',
        ]);

        return $pdf->download($this->reportFilename($measurement, 'pdf', $moccaeOnly));
    }

    protected function findMeasurement(Request $request, int $companyId): Measurement
    {
        return Measurement::with('location')
            ->where('fiscal_year', $request->fiscal_year)
            ->where('location_id', $request->location_id)
            ->whereHas('location', fn ($q) => $q->where('company_id', $companyId))
            ->firstOrFail();
    }

    protected function reportFilename(Measurement $measurement, string $ext, bool $moccaeOnly = false): string
    {
        $location = preg_replace('/[^a-z0-9]+/i', '-', strtolower($measurement->location->name ?? 'location'));
        $prefix = $moccaeOnly ? 'ghg-inventory-moccae' : 'ghg-inventory';

        return "{$prefix}-{$measurement->fiscal_year}-{$location}.{$ext}";
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

    protected function buildChartPayload(array $report, bool $moccaeOnly): array
    {
        $sources = $report['emission_source_data'];

        return [
            'scopeLabels' => $moccaeOnly
                ? ['Scope 1', 'Scope 2']
                : ['Scope 1', 'Scope 2', 'Scope 3'],
            'scopeColors' => $moccaeOnly
                ? ['#059669', '#0284c7']
                : ['#059669', '#0284c7', '#9333ea'],
            'scopePercentages' => array_values($report['scope_percentages']),
            'scopeRawValues' => array_values($report['scope_raw_tonnes']),
            'sourceLabels' => $sources->pluck('label')->values()->all(),
            'sourcePercents' => $sources->pluck('percent')->values()->all(),
            'sourceRawTonnes' => $sources
                ->map(fn ($row) => number_format($row['tonnes'], 2))
                ->values()
                ->all(),
            'sourceColors' => ['#059669', '#0ea5a3', '#0284c7', '#6366f1', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6'],
        ];
    }

    protected function platformLogoDataUri(): ?string
    {
        $candidates = [
            public_path('images/menetzero.png'),
            public_path('images/menetzero.jpg'),
        ];

        foreach ($candidates as $path) {
            if (!is_file($path)) {
                continue;
            }

            $mime = mime_content_type($path) ?: 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
        }

        return null;
    }
}
