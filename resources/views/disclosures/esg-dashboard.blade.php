@extends('layouts.app')

@section('title', 'ESG Dashboard - MenetZero')
@section('page-title', 'ESG Dashboard')

@section('content')
<div class="w-full">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <p class="text-sm text-gray-500">E + S + G scorecards</p>
            <h2 class="text-xl font-semibold text-gray-900">{{ $company->name }}</h2>
        </div>
        <form method="GET" action="{{ route('disclosures.esg-dashboard') }}" class="flex items-center gap-2">
            <label class="text-sm text-gray-600">Year</label>
            <input type="number" name="fiscal_year" value="{{ $fiscalYear }}" min="2000" max="2100"
                   class="w-28 border border-gray-300 rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
        </form>
    </div>

    <div class="card mb-6">
        <div class="card-body text-center">
            <div class="text-sm text-gray-500">Overall ESG readiness</div>
            <div class="text-4xl font-bold text-brand-600 mt-1">{{ $dashboard['overall'] }}%</div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        @foreach(['environmental', 'social', 'governance'] as $pillar)
            @php $p = $dashboard[$pillar]; @endphp
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $p['label'] }}</h3>
                    <span class="text-xl font-bold text-brand-600">{{ $p['percent'] }}%</span>
                </div>
                <div class="card-body space-y-2 text-sm">
                    @foreach($p['checks'] as $check)
                        <div class="flex items-center gap-2">
                            <span class="{{ $check['done'] ? 'text-green-600' : 'text-gray-300' }}">{{ $check['done'] ? '✓' : '○' }}</span>
                            <span>{{ $check['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-6">
        <div class="card-header"><h3 class="card-title">GHG summary (GRI 305 / IFRS S2)</h3></div>
        <div class="card-body">
            @if($dashboard['ghg_summary']['has_data'])
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                    <div><div class="text-xs text-gray-500">Scope 1</div><div class="font-semibold">{{ number_format($dashboard['ghg_summary']['scope1'], 1) }} tCO₂e</div></div>
                    <div><div class="text-xs text-gray-500">Scope 2</div><div class="font-semibold">{{ number_format($dashboard['ghg_summary']['scope2'], 1) }} tCO₂e</div></div>
                    <div><div class="text-xs text-gray-500">Scope 3</div><div class="font-semibold">{{ number_format($dashboard['ghg_summary']['scope3'], 1) }} tCO₂e</div></div>
                    <div><div class="text-xs text-gray-500">Total</div><div class="font-semibold">{{ number_format($dashboard['ghg_summary']['total_tonnes'], 1) }} tCO₂e</div></div>
                </div>
            @else
                <p class="text-sm text-gray-500">No GHG data for {{ $fiscalYear }}.</p>
            @endif
        </div>
    </div>

    {{-- Climate targets vs actuals. Targets are captured under Disclosures →
         Targets; this is where they meet the inventory. --}}
    <div class="card mb-6">
        <div class="card-header flex items-center justify-between">
            <h3 class="card-title">Climate targets vs actual</h3>
            <a href="{{ route('disclosures.s2.targets.index', ['fiscal_year' => $fiscalYear]) }}" class="text-sm text-brand-600 hover:underline">Manage targets</a>
        </div>
        <div class="card-body">
            @forelse($dashboard['targets'] as $t)
                @php
                    $badge = match($t['status']['key']) {
                        'achieved', 'on_track' => 'bg-green-100 text-green-800',
                        'off_track', 'missed' => 'bg-red-100 text-red-800',
                        'no_data', 'incomplete' => 'bg-gray-100 text-gray-600',
                        default => 'bg-blue-100 text-blue-800',
                    };
                    $barColor = match($t['status']['key']) {
                        'achieved', 'on_track' => 'bg-green-500',
                        'off_track', 'missed' => 'bg-red-500',
                        default => 'bg-brand-500',
                    };
                @endphp
                <div class="py-4 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                        <div>
                            <div class="font-semibold text-gray-900">{{ $t['name'] }}</div>
                            <div class="text-xs text-gray-500">
                                Target {{ $t['target_year'] }} · {{ $t['scope_label'] }}
                                @if($t['sbti_aligned']) · <span class="text-brand-600">SBTi aligned</span> @endif
                            </div>
                        </div>
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $badge }}">{{ $t['status']['label'] }}</span>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-3">
                        <div>
                            <div class="text-xs text-gray-500">Baseline{{ $t['base_year'] ? ' ' . $t['base_year'] : '' }}</div>
                            <div class="font-semibold">
                                {{ $t['baseline_tco2e'] !== null ? number_format($t['baseline_tco2e'], 2) . ' tCO₂e' : '—' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Current {{ $t['current_year'] }}</div>
                            <div class="font-semibold">
                                {{ $t['current_tco2e'] !== null ? number_format($t['current_tco2e'], 2) . ' tCO₂e' : '—' }}
                            </div>
                            @if($t['change_vs_baseline_percent'] !== null)
                                <div class="text-xs {{ $t['change_vs_baseline_percent'] <= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $t['change_vs_baseline_percent'] <= 0 ? '▼' : '▲' }}
                                    {{ number_format(abs($t['change_vs_baseline_percent']), 1) }}% vs baseline
                                </div>
                            @endif
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Target {{ $t['target_year'] }}</div>
                            <div class="font-semibold text-brand-600">
                                {{ $t['target_tco2e'] !== null ? number_format($t['target_tco2e'], 2) . ' tCO₂e' : '—' }}
                            </div>
                            @if($t['target_is_derived'])
                                <div class="text-xs text-gray-400">derived from {{ number_format($t['reduction_percent'], 1) }}%</div>
                            @endif
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Still to reduce</div>
                            <div class="font-semibold">
                                {{ $t['remaining_tco2e'] !== null ? number_format($t['remaining_tco2e'], 2) . ' tCO₂e' : '—' }}
                            </div>
                        </div>
                    </div>

                    @if($t['achieved_percent'] !== null)
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="{{ $barColor }} h-2 rounded-full" style="width: {{ $t['achieved_percent'] }}%"></div>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">{{ $t['achieved_percent'] }}% of required reduction achieved</div>
                    @elseif($t['status']['key'] === 'no_data')
                        <p class="text-xs text-gray-500">No GHG inventory data for {{ $fiscalYear }} — enter emissions to track progress.</p>
                    @else
                        <p class="text-xs text-gray-500">Add a baseline and target value to track progress.</p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500">
                    No reduction targets set.
                    <a href="{{ route('disclosures.s2.targets.index', ['fiscal_year' => $fiscalYear]) }}" class="text-brand-600 hover:underline">Add a target</a>
                    to compare your inventory against where you plan to be.
                </p>
            @endforelse
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach(['ifrs_s2' => 'IFRS S2', 'ifrs_s1' => 'IFRS S1', 'gri' => 'GRI'] as $key => $label)
            <div class="card">
                <div class="card-body">
                    <div class="text-sm text-gray-500">{{ $label }}</div>
                    <div class="text-2xl font-bold text-brand-600">{{ $dashboard['frameworks'][$key]['percent'] }}%</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">ESG Scorecard preview</h3>
            <p class="text-sm text-gray-500">3-year KPI tables — {{ implode(', ', $dashboard['scorecard']['years']) }}</p>
        </div>
        <a href="{{ route('disclosures.esg-scorecard.index', ['fiscal_year' => $fiscalYear]) }}" class="btn btn-secondary">Open full scorecard</a>
    </div>

    @foreach($dashboard['scorecard']['categories'] as $catKey => $category)
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">{{ $category['title'] }}</h3>
            </div>
            <div class="card-body overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-2 pr-4">Metric</th>
                            @foreach($dashboard['scorecard']['years'] as $year)
                                <th class="py-2 px-2 text-right">{{ $year }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($category['rows'] as $row)
                            <tr class="border-b border-gray-50">
                                <td class="py-2 pr-4">
                                    <div>{{ $row['label'] }}</div>
                                    @if(!empty($row['unit']))
                                        <div class="text-xs text-gray-400">{{ $row['unit'] }}</div>
                                    @endif
                                </td>
                                @foreach($dashboard['scorecard']['years'] as $year)
                                    <td class="py-2 px-2 text-right">
                                        @php $val = $row['values'][$year] ?? null; @endphp
                                        {{ $val !== null ? number_format($val, $row['decimals']) : '—' }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
@endsection
