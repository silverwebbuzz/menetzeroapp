@extends('layouts.app')

@section('title', 'Disclosures - MenetZero')
@section('page-title', 'Disclosures')

@section('content')
<div class="w-full">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <p class="text-sm text-gray-500">ISSB disclosure workspace</p>
            <h2 class="text-xl font-semibold text-gray-900">{{ $company->name }}</h2>
        </div>
        <form method="GET" action="{{ route('disclosures.hub') }}" class="flex items-center gap-2">
            <label class="text-sm text-gray-600">Reporting year</label>
            <input type="number" name="fiscal_year" value="{{ $fiscalYear }}" min="2000" max="2100"
                   class="w-28 border border-gray-300 rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="{{ route('disclosures.s2.overview', ['fiscal_year' => $fiscalYear]) }}" class="card hover:border-brand-300 transition block">
            <div class="card-header">
                <div>
                    <h3 class="card-title">IFRS S2 — Climate</h3>
                    <p class="card-subtitle">Governance, strategy, climate risks, targets &amp; GHG metrics.</p>
                </div>
                <div class="text-2xl font-bold text-brand-600">{{ $s2Completeness['percent'] }}%</div>
            </div>
            <div class="card-body">
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="bg-brand-500 h-2 rounded-full" style="width: {{ $s2Completeness['percent'] }}%"></div>
                </div>
            </div>
        </a>

        <a href="{{ route('disclosures.s1.overview', ['fiscal_year' => $fiscalYear]) }}" class="card hover:border-brand-300 transition block">
            <div class="card-header">
                <div>
                    <h3 class="card-title">IFRS S1 — Sustainability</h3>
                    <p class="card-subtitle">Material topics, broader sustainability risks &amp; governance.</p>
                </div>
                <div class="text-2xl font-bold text-brand-600">{{ $s1Completeness['percent'] }}%</div>
            </div>
            <div class="card-body">
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="bg-brand-500 h-2 rounded-full" style="width: {{ $s1Completeness['percent'] }}%"></div>
                </div>
            </div>
        </a>

        <a href="{{ route('disclosures.gri.overview', ['fiscal_year' => $fiscalYear]) }}" class="card hover:border-brand-300 transition block">
            <div class="card-header">
                <div>
                    <h3 class="card-title">GRI — Sustainability</h3>
                    <p class="card-subtitle">Material topics, E/S/G metrics, content index &amp; report.</p>
                </div>
                <div class="text-2xl font-bold text-brand-600">{{ $griCompleteness['percent'] }}%</div>
            </div>
            <div class="card-body">
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="bg-brand-500 h-2 rounded-full" style="width: {{ $griCompleteness['percent'] }}%"></div>
                </div>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        <a href="{{ route('disclosures.esg-dashboard', ['fiscal_year' => $fiscalYear]) }}" class="card hover:border-brand-300 transition block">
            <div class="card-body">
                <h3 class="font-semibold text-gray-900">ESG Dashboard</h3>
                <p class="text-sm text-gray-500 mt-1">Environmental, social, and governance scorecards across all frameworks.</p>
            </div>
        </a>
        <div class="card">
            <div class="card-body text-sm text-gray-600">
                <p>IFRS S1 report can include the S2 climate appendix. GRI 305 emissions auto-map from your GHG inventory.</p>
            </div>
        </div>
    </div>
</div>
@endsection
