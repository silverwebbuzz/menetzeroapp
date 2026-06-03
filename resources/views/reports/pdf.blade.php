<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>GHG Inventory Report — {{ $company->name }}</title>
    <style>
        @page { margin: 28mm 18mm 22mm 18mm; }

        * { box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1f2937;
            line-height: 1.45;
        }

        .brand-bar {
            height: 4px;
            background: #059669;
            margin-bottom: 14px;
        }

        .report-header {
            border-bottom: 2px solid #059669;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .report-title {
            font-size: 20px;
            font-weight: bold;
            color: #065f46;
            margin: 0 0 4px 0;
        }

        .report-subtitle {
            font-size: 11px;
            color: #6b7280;
            margin: 0;
        }

        .meta-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        .meta-grid td {
            padding: 4px 8px 4px 0;
            vertical-align: top;
            width: 50%;
        }

        .meta-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #9ca3af;
            font-weight: bold;
        }

        .meta-value {
            font-size: 11px;
            color: #111827;
            font-weight: bold;
        }

        h2 {
            font-size: 13px;
            color: #065f46;
            border-bottom: 1px solid #d1fae5;
            padding-bottom: 4px;
            margin: 22px 0 10px 0;
        }

        h3 {
            font-size: 11px;
            color: #374151;
            margin: 14px 0 6px 0;
        }

        .kpi-row {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin: 0 -8px 16px -8px;
        }

        .kpi-cell {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 4px;
            padding: 10px 12px;
            text-align: center;
            width: 25%;
        }

        .kpi-cell.highlight {
            background: #ecfdf5;
            border-color: #059669;
        }

        .kpi-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            font-weight: bold;
        }

        .kpi-value {
            font-size: 18px;
            font-weight: bold;
            color: #059669;
            margin-top: 2px;
        }

        .kpi-unit {
            font-size: 9px;
            color: #6b7280;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        table.data-table th,
        table.data-table td {
            padding: 6px 8px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }

        table.data-table th {
            background: #f3f4f6;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #374151;
            font-weight: bold;
        }

        table.data-table td.num {
            text-align: right;
            white-space: nowrap;
        }

        tr.scope-row td {
            background: #f9fafb;
            font-weight: bold;
        }

        tr.total-row td {
            background: #ecfdf5;
            font-weight: bold;
            border-top: 2px solid #059669;
        }

        tr.subtotal-row td {
            background: #f0fdf4;
            font-weight: bold;
        }

        .chart-wrap {
            text-align: center;
            margin: 10px 0 14px 0;
        }

        .chart-wrap img {
            max-width: 100%;
            height: auto;
        }

        .two-col {
            width: 100%;
            border-collapse: collapse;
        }

        .two-col td {
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }

        .notice-box {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-left: 4px solid #f59e0b;
            padding: 10px 12px;
            margin-top: 14px;
            font-size: 9px;
            color: #78350f;
        }

        .methodology-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 10px 12px;
            margin-top: 8px;
            font-size: 9px;
        }

        .methodology-box ul {
            margin: 4px 0 0 0;
            padding-left: 16px;
        }

        .methodology-box li {
            margin-bottom: 3px;
        }

        .text-muted { color: #6b7280; }
        .text-small { font-size: 8px; }
        .page-break { page-break-before: always; }

        footer {
            position: fixed;
            bottom: -14mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
        }

        .cover-logos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .cover-logos td {
            vertical-align: middle;
            padding: 0;
        }

        .cover-logos .logo-left { text-align: left; width: 50%; }
        .cover-logos .logo-right { text-align: right; width: 50%; }

        .cover-logos img {
            max-height: 52px;
            max-width: 180px;
        }

        .platform-wordmark {
            font-size: 11px;
            font-weight: bold;
            color: #059669;
            letter-spacing: 0.3px;
        }

        .mode-badge {
            display: inline-block;
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #bbf7d0;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 3px 8px;
            border-radius: 3px;
            margin-top: 6px;
        }
    </style>
</head>
<body>

@php
    $location = $report['location'];
    $methodology = $report['methodology'];
    $moccaeOnly = $report['moccae_only'] ?? false;
    $displayTotal = $report['display_total_tonnes'];
    $totalTonnes = $report['total_tonnes'];
    $scopeTonnes = $report['scope_tonnes'];
    $scope1 = $scopeTonnes['Scope 1'] ?? 0;
    $scope2 = $scopeTonnes['Scope 2'] ?? 0;
@endphp

<div class="brand-bar"></div>

{{-- Cover / Header --}}
<div class="report-header">
    <table class="cover-logos">
        <tr>
            <td class="logo-left">
                @if(!empty($companyLogo))
                    <img src="{{ $companyLogo }}" alt="{{ $company->name }}">
                @else
                    <div class="meta-value" style="font-size: 14px;">{{ $company->name }}</div>
                @endif
            </td>
            <td class="logo-right">
                @if(!empty($platformLogo))
                    <img src="{{ $platformLogo }}" alt="MENetZero">
                @else
                    <div class="platform-wordmark">MENetZero</div>
                @endif
            </td>
        </tr>
    </table>

    <p class="report-title">Greenhouse Gas (GHG) Inventory Report</p>
    <p class="report-subtitle">
        @if($moccaeOnly)
            MOCCAE Scope 1 &amp; 2 inventory — UAE mandatory reporting format
        @else
            Full GHG inventory summary — all scopes included
        @endif
    </p>
    <span class="mode-badge">{{ $report['export_mode_label'] ?? ($moccaeOnly ? 'MOCCAE Scope 1 & 2' : 'Full inventory') }}</span>

    <table class="meta-grid">
        <tr>
            <td>
                <div class="meta-label">Organisation</div>
                <div class="meta-value">{{ $company->name }}</div>
            </td>
            <td>
                <div class="meta-label">Reporting location</div>
                <div class="meta-value">{{ $location->name ?? '—' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="meta-label">Reporting period</div>
                <div class="meta-value">{{ $report['reporting_period'] }}</div>
            </td>
            <td>
                <div class="meta-label">Report generated</div>
                <div class="meta-value">{{ now()->format('d M Y') }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="meta-label">Inventory status</div>
                <div class="meta-value">{{ ucfirst($report['measurement']->status ?? 'draft') }}</div>
            </td>
            <td>
                <div class="meta-label">Data entries</div>
                <div class="meta-value">{{ $report['entry_count'] }} activity records</div>
            </td>
        </tr>
    </table>
</div>

{{-- Executive Summary --}}
<h2>1. Executive Summary</h2>

<table class="kpi-row">
    <tr>
        <td class="kpi-cell">
            <div class="kpi-label">Scope 1</div>
            <div class="kpi-value">{{ number_format($scope1, 2) }}</div>
            <div class="kpi-unit">tCO₂e (direct)</div>
        </td>
        <td class="kpi-cell">
            <div class="kpi-label">Scope 2</div>
            <div class="kpi-value">{{ number_format($scope2, 2) }}</div>
            <div class="kpi-unit">tCO₂e (energy)</div>
        </td>
        <td class="kpi-cell highlight">
            <div class="kpi-label">Scope 1 + 2</div>
            <div class="kpi-value">{{ number_format($report['scope_12_tonnes'], 2) }}</div>
            <div class="kpi-unit">tCO₂e (MOCCAE reporting total)</div>
        </td>
        @if(!$moccaeOnly)
        <td class="kpi-cell">
            <div class="kpi-label">Total (all scopes)</div>
            <div class="kpi-value">{{ number_format($totalTonnes, 2) }}</div>
            <div class="kpi-unit">tCO₂e</div>
        </td>
        @endif
    </tr>
</table>

<p class="text-muted text-small">
    All emissions are expressed in metric tonnes of carbon dioxide equivalent (tCO₂e), converted from kg CO₂e stored in the inventory system (÷ 1,000).
</p>

{{-- Scope Summary --}}
<h2>2. Emissions by Scope</h2>

@if($scopeChart)
    <div class="chart-wrap">
        <img src="{{ $scopeChart }}" width="420" alt="Emissions by scope">
    </div>
@endif

<table class="data-table">
    <thead>
        <tr>
            <th>Scope</th>
            <th>Description</th>
            <th class="num">Emissions (tCO₂e)</th>
            <th class="num">Share (%)</th>
        </tr>
    </thead>
    <tbody>
        @php $pct = $report['scope_percentages']; @endphp
        <tr>
            <td>Scope 1</td>
            <td>Direct emissions (fuel combustion, refrigerants, owned vehicles)</td>
            <td class="num">{{ number_format($scope1, 2) }}</td>
            <td class="num">{{ $pct[0] ?? 0 }}%</td>
        </tr>
        <tr>
            <td>Scope 2</td>
            <td>Indirect emissions from purchased electricity (location-based)</td>
            <td class="num">{{ number_format($scope2, 2) }}</td>
            <td class="num">{{ $pct[1] ?? 0 }}%</td>
        </tr>
        @if(!$moccaeOnly && ($report['has_scope_3'] || ($scopeTonnes['Scope 3'] ?? 0) > 0))
        <tr>
            <td>Scope 3</td>
            <td>Other indirect emissions (value chain)</td>
            <td class="num">{{ number_format($scopeTonnes['Scope 3'] ?? 0, 2) }}</td>
            <td class="num">{{ $pct[2] ?? 0 }}%</td>
        </tr>
        @endif
        <tr class="{{ $moccaeOnly ? 'total-row' : 'subtotal-row' }}">
            <td colspan="2">Scope 1 + Scope 2 (UAE mandatory reporting)</td>
            <td class="num">{{ number_format($report['scope_12_tonnes'], 2) }}</td>
            <td class="num">{{ $moccaeOnly ? '100' : '—' }}%</td>
        </tr>
        @if(!$moccaeOnly)
        <tr class="total-row">
            <td colspan="2">Grand total (all scopes)</td>
            <td class="num">{{ number_format($totalTonnes, 2) }}</td>
            <td class="num">100%</td>
        </tr>
        @endif
    </tbody>
</table>

{{-- Breakdown by source --}}
<h2>3. Emissions by Source Category</h2>

@if($sourceChart)
    <div class="chart-wrap">
        <img src="{{ $sourceChart }}" width="480" alt="Emissions by source">
    </div>
@endif

<table class="data-table">
    <thead>
        <tr>
            <th>Scope</th>
            <th>Emission source</th>
            <th class="num">Emissions (tCO₂e)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($report['results_breakdown'] as $scope)
            @if($scope['tonnes'] > 0 || $scope['name'] !== 'Scope 3')
            <tr class="scope-row">
                <td>{{ $scope['name'] }}</td>
                <td>—</td>
                <td class="num">{{ number_format($scope['tonnes'], 2) }}</td>
            </tr>
            @foreach($scope['children'] as $child)
            <tr>
                <td></td>
                <td style="padding-left: 16px;">{{ $child['name'] }}</td>
                <td class="num">{{ number_format($child['tonnes'], 2) }}</td>
            </tr>
            @endforeach
            @endif
        @endforeach
        <tr class="total-row">
            <td colspan="2">Total{{ $moccaeOnly ? ' (Scope 1 + 2)' : '' }}</td>
            <td class="num">{{ number_format($displayTotal, 2) }}</td>
        </tr>
    </tbody>
</table>

{{-- Activity register --}}
<div class="page-break"></div>
<h2>4. Activity Data Register</h2>
<p class="text-muted text-small" style="margin-bottom: 8px;">
    Detailed record of each activity entry used to calculate the inventory. Quantities, emission factors, and methodology are shown per line item.
</p>

@if($report['activity_register']->isEmpty())
    <p class="text-muted">No activity data recorded for this reporting period.</p>
@else
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Scope</th>
                <th>Source / Activity</th>
                <th class="num">Quantity</th>
                <th>Unit</th>
                <th class="num">Factor</th>
                <th>Methodology</th>
                <th class="num">tCO₂e</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report['activity_register'] as $row)
            <tr>
                <td>{{ $row['entry_date'] }}</td>
                <td>{{ $row['scope'] }}</td>
                <td>
                    <strong>{{ $row['source'] }}</strong>
                    @if($row['activity'] !== $row['source'])
                        <br><span class="text-muted">{{ $row['activity'] }}</span>
                    @endif
                </td>
                <td class="num">{{ $row['quantity'] }}</td>
                <td>{{ $row['unit'] }}</td>
                <td class="num">{{ $row['factor_value'] }}<br><span class="text-muted text-small">{{ $row['factor_unit'] }}</span></td>
                <td class="text-small">{{ $row['methodology'] }}<br><span class="text-muted">{{ $row['reference'] }}</span></td>
                <td class="num">{{ number_format($row['tonnes'], 4) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- Methodology --}}
<h2>5. Methodology &amp; Reporting Notes</h2>

<div class="methodology-box">
    <strong>Calculation framework</strong>
    <ul>
        <li><strong>Standard:</strong> {{ $methodology['framework'] }}</li>
        <li><strong>Emission factors:</strong> {{ $methodology['factors'] }}</li>
        <li><strong>Scopes included:</strong> {{ $methodology['scopes'] }}</li>
        <li><strong>GWP values:</strong> {{ $methodology['gwp'] }}</li>
    </ul>
</div>

<div class="notice-box">
    <strong>UAE legal submission:</strong> {{ $methodology['disclaimer'] }}
    For official MOCCAE reporting, register at <strong>mrv.ae</strong> and submit your inventory through the Integrated Emissions Quantification Tool (IEQT).
</div>

<footer>
    GHG Inventory Report — {{ $company->name }} — {{ $report['reporting_period'] }}
    &nbsp;|&nbsp; Prepared with MENetZero &nbsp;|&nbsp; {{ now()->format('d M Y H:i') }} UTC
</footer>

</body>
</html>
