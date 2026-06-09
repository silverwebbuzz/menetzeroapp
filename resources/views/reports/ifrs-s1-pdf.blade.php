<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>IFRS S1 Report — {{ $report['company']->name }}</title>
    <style>
        @page { margin: 28mm 18mm 22mm 18mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; line-height: 1.45; }
        .brand-bar { height: 4px; background: #059669; margin-bottom: 14px; }
        h2 { font-size: 13px; color: #065f46; border-bottom: 1px solid #d1fae5; padding-bottom: 4px; margin: 22px 0 10px 0; }
        .field-label { font-size: 9px; text-transform: uppercase; color: #9ca3af; font-weight: bold; margin-top: 8px; }
        .field-value { font-size: 10px; white-space: pre-wrap; margin-bottom: 6px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data th, table.data td { border: 1px solid #e5e7eb; padding: 6px 8px; font-size: 9px; }
        table.data th { background: #ecfdf5; color: #065f46; }
        .muted { color: #6b7280; font-size: 9px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="brand-bar"></div>
    <h1 style="font-size:20px;color:#065f46;margin:0;">IFRS S1 General Sustainability-related Disclosures</h1>
    <p class="muted">{{ $report['company']->name }} · FY {{ $report['fiscal_year'] }} · {{ $report['generated_at'] }} · {{ $report['completeness']['percent'] }}% complete</p>

    <h2>1. Material Sustainability Topics</h2>
    @if($report['material_topics']->isNotEmpty())
        <table class="data">
            <thead><tr><th>Topic</th><th>GRI ref</th><th>Rationale</th></tr></thead>
            <tbody>
                @foreach($report['material_topics'] as $topic)
                    <tr>
                        <td>{{ $topic['label'] }}</td>
                        <td>{{ $topic['gri'] ?? '—' }}</td>
                        <td>{{ $topic['rationale'] ?: 'Material for this period' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">No material topics marked.</p>
    @endif

    <h2>2. Sustainability Governance (IFRS S1 §27–29)</h2>
    @foreach($report['section_config']['governance']['fields'] ?? [] as $key => $field)
        @if(!empty($report['governance'][$key]))
            <div class="field-label">{{ $field['label'] }}</div>
            <div class="field-value">{{ $report['governance'][$key] }}</div>
        @endif
    @endforeach

    <h2>3. Sustainability Strategy (IFRS S1 §30–33)</h2>
    @foreach($report['section_config']['strategy']['fields'] ?? [] as $key => $field)
        @if(!empty($report['strategy'][$key]))
            <div class="field-label">{{ $field['label'] }}</div>
            <div class="field-value">{{ $report['strategy'][$key] }}</div>
        @endif
    @endforeach

    <h2>4. Sustainability Risk Management (IFRS S1 §34–36)</h2>
    @foreach($report['section_config']['risk_management']['fields'] ?? [] as $key => $field)
        @if(!empty($report['risk_management'][$key]))
            <div class="field-label">{{ $field['label'] }}</div>
            <div class="field-value">{{ $report['risk_management'][$key] }}</div>
        @endif
    @endforeach

    <h2>5. Sustainability Risk Register</h2>
    @if($report['sustainability_risks']->isNotEmpty())
        <table class="data">
            <thead><tr><th>Risk</th><th>Topic</th><th>Horizon</th><th>Impact</th></tr></thead>
            <tbody>
                @foreach($report['sustainability_risks'] as $risk)
                    <tr>
                        <td><strong>{{ $risk->name }}</strong><br><span class="muted">{{ $risk->description }}</span></td>
                        <td>{{ $risk->topicLabel() }}</td>
                        <td>{{ \App\Models\SustainabilityRisk::HORIZONS[$risk->time_horizon] ?? $risk->time_horizon }}</td>
                        <td>{{ $risk->financial_impact ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">No sustainability risks registered.</p>
    @endif

    @if($report['include_s2'] && $report['s2_report'])
        <div class="page-break"></div>
        <h2>Appendix A — IFRS S2 Climate-related Disclosures (incorporated)</h2>
        <p class="muted">The following climate disclosures are drawn from the entity's IFRS S2 workspace for FY {{ $report['fiscal_year'] }}.</p>

        @php $s2 = $report['s2_report']; @endphp
        <h2 style="font-size:11px;">Climate Governance (summary)</h2>
        @foreach($s2['section_config']['governance']['fields'] ?? [] as $key => $field)
            @if(!empty($s2['governance'][$key]))
                <div class="field-label">{{ $field['label'] }}</div>
                <div class="field-value">{{ $s2['governance'][$key] }}</div>
            @endif
        @endforeach

        <h2 style="font-size:11px;">GHG Emissions (IFRS S2 §29)</h2>
        @if($s2['ghg']['has_data'])
            <table class="data">
                <thead><tr><th>Scope</th><th>tCO₂e</th></tr></thead>
                <tbody>
                    @foreach($s2['ghg']['scope_tonnes'] as $scope => $tonnes)
                        <tr><td>{{ $scope }}</td><td>{{ number_format($tonnes, 2) }}</td></tr>
                    @endforeach
                    <tr><td><strong>Total</strong></td><td><strong>{{ number_format($s2['ghg']['total_tonnes'], 2) }}</strong></td></tr>
                </tbody>
            </table>
        @else
            <p class="muted">No GHG data for this year.</p>
        @endif

        @if($s2['climate_risks']->isNotEmpty())
            <h2 style="font-size:11px;">Climate Risk Register ({{ $s2['climate_risks']->count() }} risks)</h2>
            <table class="data">
                <thead><tr><th>Risk</th><th>Type</th></tr></thead>
                <tbody>
                    @foreach($s2['climate_risks'] as $risk)
                        <tr><td>{{ $risk->name }}</td><td>{{ ucfirst($risk->risk_type) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    <p class="muted" style="margin-top:24px;">Draft sustainability disclosure prepared using MenetZero. Review with qualified advisors before external publication.</p>
</body>
</html>
