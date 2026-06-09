<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>IFRS S2 Climate Report — {{ $report['company']->name }}</title>
    <style>
        @page { margin: 28mm 18mm 22mm 18mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; line-height: 1.45; }
        .brand-bar { height: 4px; background: #059669; margin-bottom: 14px; }
        .report-header { border-bottom: 2px solid #059669; padding-bottom: 12px; margin-bottom: 18px; }
        .report-title { font-size: 20px; font-weight: bold; color: #065f46; margin: 0 0 4px 0; }
        .report-subtitle { font-size: 11px; color: #6b7280; margin: 0; }
        h2 { font-size: 13px; color: #065f46; border-bottom: 1px solid #d1fae5; padding-bottom: 4px; margin: 22px 0 10px 0; }
        h3 { font-size: 11px; color: #374151; margin: 14px 0 6px 0; }
        .field-label { font-size: 9px; text-transform: uppercase; color: #9ca3af; font-weight: bold; margin-top: 8px; }
        .field-value { font-size: 10px; color: #111827; margin-bottom: 6px; white-space: pre-wrap; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data th, table.data td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; font-size: 9px; }
        table.data th { background: #ecfdf5; color: #065f46; }
        .muted { color: #6b7280; font-size: 9px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="brand-bar"></div>
    <div class="report-header">
        <table width="100%"><tr>
            <td>
                <p class="report-title">IFRS S2 Climate-related Disclosures</p>
                <p class="report-subtitle">{{ $report['company']->name }} · Fiscal year {{ $report['fiscal_year'] }}</p>
                <p class="report-subtitle">Generated {{ $report['generated_at'] }} · Completeness {{ $report['completeness']['percent'] }}%</p>
            </td>
            <td align="right" width="120">
                @if($companyLogo ?? null)
                    <img src="{{ $companyLogo }}" height="48" alt="">
                @endif
            </td>
        </tr></table>
    </div>

    <h2>1. Climate Governance (IFRS S2 §5–7)</h2>
    @php $gov = $report['governance']; $govFields = $report['section_config']['governance']['fields'] ?? []; @endphp
    @foreach($govFields as $key => $field)
        @if(!empty($gov[$key]))
            <div class="field-label">{{ $field['label'] }}</div>
            <div class="field-value">{{ $gov[$key] }}</div>
        @endif
    @endforeach
    @if(empty(array_filter($gov ?? [])))
        <p class="muted">Not yet completed.</p>
    @endif

    <h2>2. Climate Strategy (IFRS S2 §8–13)</h2>
    @php $strat = $report['strategy']; $stratFields = $report['section_config']['strategy']['fields'] ?? []; @endphp
    @foreach($stratFields as $key => $field)
        @if(!empty($strat[$key]))
            <div class="field-label">{{ $field['label'] }}</div>
            <div class="field-value">{{ $strat[$key] }}</div>
        @endif
    @endforeach

    <h2>3. Climate Risk Management (IFRS S2 §14–17)</h2>
    @php $rm = $report['risk_management']; $rmFields = $report['section_config']['risk_management']['fields'] ?? []; @endphp
    @foreach($rmFields as $key => $field)
        @if(!empty($rm[$key]))
            <div class="field-label">{{ $field['label'] }}</div>
            <div class="field-value">{{ $rm[$key] }}</div>
        @endif
    @endforeach

    <div class="page-break"></div>
    <h2>4. Climate Risk Register</h2>
    @if($report['climate_risks']->isNotEmpty())
        <table class="data">
            <thead>
                <tr>
                    <th>Risk</th><th>Type</th><th>Horizon</th><th>Likelihood</th><th>Financial impact</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['climate_risks'] as $risk)
                    <tr>
                        <td><strong>{{ $risk->name }}</strong><br><span class="muted">{{ $risk->description }}</span></td>
                        <td>{{ ucfirst($risk->risk_type) }}</td>
                        <td>{{ \App\Models\ClimateRisk::HORIZONS[$risk->time_horizon] ?? $risk->time_horizon }}</td>
                        <td>{{ ucfirst($risk->likelihood ?? '—') }}</td>
                        <td>{{ $risk->financial_impact ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">No climate risks registered.</p>
    @endif

    <h2>5. Climate Opportunities</h2>
    @if($report['climate_opportunities']->isNotEmpty())
        <table class="data">
            <thead><tr><th>Opportunity</th><th>Category</th><th>Potential impact</th></tr></thead>
            <tbody>
                @foreach($report['climate_opportunities'] as $opp)
                    <tr>
                        <td><strong>{{ $opp->name }}</strong><br><span class="muted">{{ $opp->description }}</span></td>
                        <td>{{ $opp->category ?? '—' }}</td>
                        <td>{{ $opp->potential_impact ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">No opportunities registered.</p>
    @endif

    <h2>6. GHG Emissions (IFRS S2 §29)</h2>
    @if($report['ghg']['has_data'])
        <table class="data">
            <thead><tr><th>Scope</th><th>Emissions (tCO₂e)</th></tr></thead>
            <tbody>
                @foreach($report['ghg']['scope_tonnes'] as $scope => $tonnes)
                    <tr><td>{{ $scope }}</td><td>{{ number_format($tonnes, 2) }}</td></tr>
                @endforeach
                <tr><td><strong>Total</strong></td><td><strong>{{ number_format($report['ghg']['total_tonnes'], 2) }}</strong></td></tr>
            </tbody>
        </table>
        <p class="muted">Scope 2 location-based: {{ number_format($report['ghg']['scope2_location_tonnes'], 2) }} tCO₂e · Market-based: {{ number_format($report['ghg']['scope2_market_tonnes'], 2) }} tCO₂e · Locations: {{ $report['ghg']['location_count'] }}</p>
        @if($report['ghg']['scope_3_categories']->isNotEmpty())
            <h3>Scope 3 by category</h3>
            <table class="data">
                <thead><tr><th>Category</th><th>tCO₂e</th><th>Data quality</th></tr></thead>
                <tbody>
                    @foreach($report['ghg']['scope_3_categories'] as $row)
                        <tr>
                            <td>{{ $row['category'] }}</td>
                            <td>{{ number_format($row['tonnes'], 2) }}</td>
                            <td>{{ $row['data_quality'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @else
        <p class="muted">No GHG inventory data for this fiscal year.</p>
    @endif

    <h2>7. Reduction Targets &amp; Transition Plan (IFRS S2 §33–36)</h2>
    @if($report['reduction_targets']->isNotEmpty())
        @foreach($report['reduction_targets'] as $target)
            <h3>{{ $target->name }}</h3>
            <p class="field-value">
                {{ ucfirst($target->target_type) }} target · {{ \App\Models\ReductionTarget::SCOPE_COVERAGE[$target->scope_coverage] ?? $target->scope_coverage }}
                · Base {{ $target->base_year ?? '—' }} → Target {{ $target->target_year }}
                @if($target->reduction_percent) · {{ $target->reduction_percent }}% reduction @endif
                @if($target->sbti_aligned) · SBTi-aligned @endif
            </p>
            @if($target->transitionActions->isNotEmpty())
                <table class="data">
                    <thead><tr><th>Action</th><th>Year</th><th>Expected reduction</th></tr></thead>
                    <tbody>
                        @foreach($target->transitionActions as $action)
                            <tr>
                                <td>{{ $action->title }}<br><span class="muted">{{ $action->description }}</span></td>
                                <td>{{ $action->planned_year ?? '—' }}</td>
                                <td>{{ $action->expected_reduction_tco2e ? number_format($action->expected_reduction_tco2e, 2) . ' tCO₂e' : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach
    @else
        <p class="muted">No reduction targets defined.</p>
    @endif

    <h2>Methodology</h2>
    @if(!empty($report['methodology']))
        @foreach($report['methodology'] as $label => $line)
            <p class="muted"><strong>{{ ucfirst(str_replace('_', ' ', $label)) }}:</strong> {{ $line }}</p>
        @endforeach
    @endif
    @if($report['reporting_settings'])
        <p class="muted">Organisational boundary: {{ $report['reporting_settings']->organisational_boundary }} · GWP: IPCC {{ $report['reporting_settings']->gwp_version }}</p>
    @endif

    <p class="muted" style="margin-top:24px;">Draft climate disclosure report prepared using MenetZero. Review with qualified advisors before external publication.</p>
</body>
</html>
