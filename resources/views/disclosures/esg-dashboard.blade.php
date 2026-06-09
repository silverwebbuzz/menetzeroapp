@extends('layouts.app')

@section('title', 'ESG Dashboard - MenetZero')
@section('page-title', 'ESG Dashboard')

@section('content')
<div class="max-w-5xl mx-auto">
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
                    <div><div class="text-xs text-gray-500">Scope 1</div><div class="font-semibold">{{ number_format($dashboard['ghg_summary']['scope1'], 1) }} t</div></div>
                    <div><div class="text-xs text-gray-500">Scope 2</div><div class="font-semibold">{{ number_format($dashboard['ghg_summary']['scope2'], 1) }} t</div></div>
                    <div><div class="text-xs text-gray-500">Scope 3</div><div class="font-semibold">{{ number_format($dashboard['ghg_summary']['scope3'], 1) }} t</div></div>
                    <div><div class="text-xs text-gray-500">Total</div><div class="font-semibold">{{ number_format($dashboard['ghg_summary']['total_tonnes'], 1) }} t</div></div>
                </div>
            @else
                <p class="text-sm text-gray-500">No GHG data for {{ $fiscalYear }}.</p>
            @endif
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
</div>
@endsection
