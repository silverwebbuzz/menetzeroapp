<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>GRI Report — {{ $report['company']->name }}</title>
    <style>
        @page { margin: 28mm 18mm 22mm 18mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; line-height: 1.45; }
        .brand-bar { height: 4px; background: #059669; margin-bottom: 14px; }
        h2 { font-size: 13px; color: #065f46; border-bottom: 1px solid #d1fae5; padding-bottom: 4px; margin: 20px 0 8px 0; }
        .field-label { font-size: 9px; text-transform: uppercase; color: #9ca3af; font-weight: bold; margin-top: 6px; }
        .field-value { white-space: pre-wrap; margin-bottom: 4px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.data th, table.data td { border: 1px solid #e5e7eb; padding: 5px 7px; font-size: 9px; }
        table.data th { background: #ecfdf5; }
        .muted { color: #6b7280; font-size: 9px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="brand-bar"></div>
    <h1 style="font-size:20px;color:#065f46;margin:0;">GRI Sustainability Report</h1>
    <p class="muted">{{ $report['company']->name }} · FY {{ $report['fiscal_year'] }} · {{ $report['generated_at'] }}</p>

    <h2>GRI 3 — Material Topics</h2>
    @if($report['material_topics']->isNotEmpty())
        <table class="data"><thead><tr><th>Topic</th><th>GRI</th><th>Rationale</th></tr></thead><tbody>
            @foreach($report['material_topics'] as $t)
                <tr><td>{{ $t['label'] }}</td><td>{{ $t['gri'] ?? '—' }}</td><td>{{ $t['rationale'] ?: 'Material' }}</td></tr>
            @endforeach
        </tbody></table>
    @else
        <p class="muted">No material topics selected.</p>
    @endif

    @foreach(['material_topics_process' => 'GRI 3 Process', 'general' => 'GRI 2 General Disclosures', 'energy' => 'GRI 302 Energy', 'water' => 'GRI 303 Water', 'waste' => 'GRI 306 Waste', 'social_hr' => 'GRI 401–404 Employment', 'diversity' => 'GRI 405 Diversity', 'health_safety' => 'GRI 403 Health & Safety', 'supply_chain' => 'GRI 308/414 Supply Chain', 'governance_metrics' => 'Governance KPIs'] as $section => $title)
        @php $content = $report[$section] ?? []; $fields = $report['section_config'][$section]['fields'] ?? []; @endphp
        @if(!empty(array_filter($content ?? [])))
            <h2>{{ $title }}</h2>
            @foreach($fields as $key => $field)
                @if(isset($content[$key]) && $content[$key] !== '')
                    <div class="field-label">{{ $field['label'] }}</div>
                    <div class="field-value">{{ $content[$key] }}</div>
                @endif
            @endforeach
        @endif
    @endforeach

    <h2>GRI 305 — Emissions</h2>
    @if($report['gri_305']['has_data'])
        <table class="data">
            <tr><td>305-1 Scope 1</td><td>{{ number_format($report['gri_305']['scope1_tonnes'], 2) }} tCO₂e</td></tr>
            <tr><td>305-2 Scope 2 (location)</td><td>{{ number_format($report['gri_305']['scope2_location_tonnes'], 2) }} tCO₂e</td></tr>
            <tr><td>305-2 Scope 2 (market)</td><td>{{ number_format($report['gri_305']['scope2_market_tonnes'], 2) }} tCO₂e</td></tr>
            <tr><td>305-3 Scope 3</td><td>{{ number_format($report['gri_305']['scope3_tonnes'], 2) }} tCO₂e</td></tr>
            <tr><td><strong>Total</strong></td><td><strong>{{ number_format($report['gri_305']['total_tonnes'], 2) }} tCO₂e</strong></td></tr>
        </table>
        @if($report['gri_305']['reduction_targets']->isNotEmpty())
            <p class="muted">305-5 Reduction targets: {{ $report['gri_305']['reduction_targets']->count() }} active target(s) on file.</p>
        @endif
    @else
        <p class="muted">No GHG inventory — complete Quick Input to populate GRI 305.</p>
    @endif

    <div class="page-break"></div>
    <h2>GRI Content Index</h2>
    <table class="data">
        <thead><tr><th>Code</th><th>Disclosure</th><th>Status</th><th>UNGC</th><th>WEF</th><th>SDG</th></tr></thead>
        <tbody>
            @foreach($report['content_index'] as $row)
                <tr>
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['title'] }}</td>
                    <td>{{ $row['status'] }}</td>
                    <td>{{ $row['ungc'] ?? '—' }}</td>
                    <td>{{ $row['wef'] ?? '—' }}</td>
                    <td>{{ $row['sdg'] ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="muted" style="margin-top:20px;">Prepared using MenetZero. Review before external publication.</p>
</body>
</html>
