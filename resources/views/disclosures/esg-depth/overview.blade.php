@extends('layouts.app')

@section('title', 'ESG Depth - MenetZero')
@section('page-title', 'ESG Depth & Extended Disclosures')

@section('content')
<div class="w-full">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-2">
        <div>
            <p class="text-sm text-gray-500">Phase C — stakeholders, materiality, supply chain, targets</p>
            <h2 class="text-xl font-semibold text-gray-900">{{ $company->name }}</h2>
        </div>
        <form method="GET" action="{{ route('disclosures.esg-depth.overview') }}" class="flex items-center gap-2">
            <label class="text-sm text-gray-600">Year</label>
            <input type="number" name="fiscal_year" value="{{ $fiscalYear }}" min="2000" max="2100"
                   class="w-28 border border-gray-300 rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
        </form>
    </div>

    @include('layouts.partials.nav-disclosures-esg-depth', ['fiscalYear' => $fiscalYear])

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <a href="{{ route('disclosures.stakeholders.index', ['fiscal_year' => $fiscalYear]) }}" class="card hover:border-brand-300 block">
            <div class="card-body">
                <div class="text-2xl font-bold text-brand-600">{{ $stakeholderCount }}</div>
                <div class="text-sm text-gray-600">Stakeholder groups</div>
            </div>
        </a>
        <a href="{{ route('disclosures.materiality-matrix.index', ['fiscal_year' => $fiscalYear]) }}" class="card hover:border-brand-300 block">
            <div class="card-body">
                <div class="text-2xl font-bold text-brand-600">{{ $matrixComplete }}/{{ count(config('disclosure.ifrs_s1.material_topics', [])) }}</div>
                <div class="text-sm text-gray-600">Materiality matrix scored</div>
            </div>
        </a>
        <a href="{{ route('disclosures.supply-chain.index', ['fiscal_year' => $fiscalYear]) }}" class="card hover:border-brand-300 block">
            <div class="card-body">
                <div class="text-2xl font-bold text-brand-600">{{ $supplierCount }}</div>
                <div class="text-sm text-gray-600">Suppliers tracked</div>
            </div>
        </a>
        <a href="{{ route('disclosures.esg-targets.index', ['fiscal_year' => $fiscalYear]) }}" class="card hover:border-brand-300 block">
            <div class="card-body">
                <div class="text-2xl font-bold text-brand-600">{{ $esgTargetCount }}</div>
                <div class="text-sm text-gray-600">Non-climate ESG targets</div>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach([
            ['Stakeholder register', 'GRI 2-29 — map engagement by stakeholder group.', route('disclosures.stakeholders.index', ['fiscal_year' => $fiscalYear])],
            ['Materiality matrix', 'Double materiality — impact × financial significance.', route('disclosures.materiality-matrix.index', ['fiscal_year' => $fiscalYear])],
            ['Supply chain', 'Scope 3 Cat 1 suppliers, spend, and screening status.', route('disclosures.supply-chain.index', ['fiscal_year' => $fiscalYear])],
            ['ESG targets', 'Water, waste, diversity, and other non-GHG targets.', route('disclosures.esg-targets.index', ['fiscal_year' => $fiscalYear])],
            ['GRI 403 Health & Safety', 'LTIFR, fatalities, OHS management.', route('disclosures.gri.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'health_safety'])],
            ['Governance KPIs', 'Ethics incidents, compliance, board diversity.', route('disclosures.gri.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'governance_metrics'])],
            ['SASB Index', 'Sector-specific metrics (TR-MT ports, TR-RO logistics, IF-RE).', route('disclosures.sasb.index', ['fiscal_year' => $fiscalYear])],
            ['Community Investment', 'Optional B4SI-style community disclosure.', route('disclosures.uae-esg.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'community_impact'])],
        ] as [$title, $desc, $url])
            <a href="{{ $url }}" class="card hover:border-brand-300 block">
                <div class="card-body">
                    <h3 class="font-semibold text-gray-900">{{ $title }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ $desc }}</p>
                </div>
            </a>
        @endforeach
    </div>
</div>
@endsection
