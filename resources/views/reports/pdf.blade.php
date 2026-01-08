<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Emissions Report</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 30px;
        }

        h1 {
            font-size: 20px;
            margin-bottom: 5px;
        }

        h2 {
            font-size: 16px;
            margin-top: 30px;
            margin-bottom: 10px;
        }

        .text-muted {
            color: #666;
        }

        .header {
            border-bottom: 2px solid #6b46c1;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .summary-box {
            background: #f7f5ff;
            border: 1px solid #d6ccff;
            padding: 15px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 8px 10px;
            border: 1px solid #ddd;
        }

        th {
            background: #f1f1f1;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .scope-row {
            background: #fafafa;
            font-weight: bold;
        }

        .total-row {
            background: #e9e9ff;
            font-weight: bold;
        }

        footer {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #888;
        }
    </style>
</head>

<body>

    {{-- HEADER --}}
    <div class="header">
        <h1>Carbon Emissions Report</h1>
        <p class="text-muted">
            {{ $company->name }} |
            Fiscal Year: {{ $measurement->fiscal_year }}
        </p>
    </div>

    {{-- SUMMARY --}}
    <div class="summary-box">
        <strong>Total Emissions:</strong>
        <span style="font-size:16px;">
            {{ number_format($total, 2) }} tCO<sub>2</sub>e
        </span>
    </div>

    {{-- SCOPE SUMMARY --}}
    <h2>Scope Summary</h2>
    <div style="text-align:center; margin:20px 0;">
        <img src="{{ $scopeChart }}" width="600" alt="Scope Chart">
    </div>
    <table>
        <thead>
            <tr>
                <th>Scope</th>
                <th class="text-right">Emissions (tCO₂e)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($scopes as $scope => $value)
                <tr>
                    <td>{{ $scope }}</td>
                    <td class="text-right">{{ number_format($value, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- DETAILED BREAKDOWN --}}
    <h2>Results Breakdown</h2>
    <div style="text-align:center;">
        <img src="{{ $emissionSourceChart }}" width="600">
    </div>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th class="text-right">Emissions (tCO₂e)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($resultsBreakdown as $scope)
                <tr class="scope-row">
                    <td>{{ $scope['name'] }}</td>
                    <td class="text-right">{{ number_format($scope['value'], 2) }}</td>
                </tr>

                @foreach ($scope['children'] as $child)
                    <tr>
                        <td style="padding-left:20px;">{{ $child['name'] }}</td>
                        <td class="text-right">{{ number_format($child['value'], 2) }}</td>
                    </tr>
                @endforeach
            @endforeach

            <tr class="total-row">
                <td>Total</td>
                <td class="text-right">{{ number_format($total, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- FOOTER --}}
    <footer>
        Generated on {{ now()->format('d M Y') }} | © {{ date('Y') }} {{ $company->name }}
    </footer>

</body>
</html>
