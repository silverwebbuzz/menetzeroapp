@php
    $fy = $fiscalYear ?? request('fiscal_year', session('disclosure_fiscal_year', now()->year));
    $q = ['fiscal_year' => $fy];
@endphp

<nav class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-3 text-sm">
    <a href="{{ route('disclosures.overview', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.overview') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
        Overview
    </a>
    <a href="{{ route('disclosures.sections.edit', array_merge($q, ['section' => 'governance'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.sections.*') && request()->route('section') === 'governance' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
        Governance
    </a>
    <a href="{{ route('disclosures.sections.edit', array_merge($q, ['section' => 'strategy'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.sections.*') && request()->route('section') === 'strategy' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
        Strategy
    </a>
    <a href="{{ route('disclosures.sections.edit', array_merge($q, ['section' => 'risk_management'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.sections.*') && request()->route('section') === 'risk_management' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
        Risk Management
    </a>
    <a href="{{ route('disclosures.climate-risks.index', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.climate-risks.*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
        Climate Risks
    </a>
    <a href="{{ route('disclosures.climate-opportunities.index', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.climate-opportunities.*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
        Opportunities
    </a>
    <a href="{{ route('disclosures.targets.index', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.targets.*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
        Targets &amp; Roadmap
    </a>
    <a href="{{ route('disclosures.report.preview', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.report.*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
        Generate Report
    </a>
</nav>
