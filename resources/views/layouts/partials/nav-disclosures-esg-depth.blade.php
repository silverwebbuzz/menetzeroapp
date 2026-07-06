@php $q = ['fiscal_year' => $fiscalYear ?? now()->year]; @endphp
<nav class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-3 text-sm">
    <a href="{{ route('disclosures.hub', $q) }}" class="px-3 py-1.5 rounded-lg text-gray-600 hover:bg-gray-50">← Disclosures</a>
    <a href="{{ route('disclosures.esg-depth.overview', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.esg-depth.*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Overview</a>
    <a href="{{ route('disclosures.stakeholders.index', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.stakeholders.*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Stakeholders</a>
    <a href="{{ route('disclosures.materiality-matrix.index', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.materiality-matrix.*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Materiality</a>
    <a href="{{ route('disclosures.supply-chain.index', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.supply-chain.*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Supply Chain</a>
    <a href="{{ route('disclosures.esg-targets.index', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.esg-targets.*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">ESG Targets</a>
    <a href="{{ route('disclosures.sasb.index', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.sasb.*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">SASB Index</a>
    <a href="{{ route('disclosures.gri.sections.edit', array_merge($q, ['section' => 'health_safety'])) }}"
       class="px-3 py-1.5 rounded-lg text-gray-600 hover:bg-gray-50">GRI 403 Safety</a>
    <a href="{{ route('disclosures.gri.sections.edit', array_merge($q, ['section' => 'governance_metrics'])) }}"
       class="px-3 py-1.5 rounded-lg text-gray-600 hover:bg-gray-50">Governance KPIs</a>
</nav>
