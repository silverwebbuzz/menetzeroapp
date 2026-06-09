@php $q = ['fiscal_year' => $fiscalYear]; @endphp
<nav class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-3 text-sm">
    <a href="{{ route('disclosures.hub', $q) }}" class="px-3 py-1.5 rounded-lg text-gray-600 hover:bg-gray-50">← All disclosures</a>
    <a href="{{ route('disclosures.s2.overview', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s2.overview') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Overview</a>
    <a href="{{ route('disclosures.s2.sections.edit', array_merge($q, ['section' => 'governance'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s2.sections.*') && request()->route('section') === 'governance' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Governance</a>
    <a href="{{ route('disclosures.s2.sections.edit', array_merge($q, ['section' => 'strategy'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s2.sections.*') && request()->route('section') === 'strategy' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Strategy</a>
    <a href="{{ route('disclosures.s2.sections.edit', array_merge($q, ['section' => 'risk_management'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s2.sections.*') && request()->route('section') === 'risk_management' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Risk Management</a>
    <a href="{{ route('disclosures.s2.climate-risks.index', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s2.climate-risks.*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Climate Risks</a>
    <a href="{{ route('disclosures.s2.climate-opportunities.index', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s2.climate-opportunities.*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Opportunities</a>
    <a href="{{ route('disclosures.s2.targets.index', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s2.targets.*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Targets &amp; Roadmap</a>
    <a href="{{ route('disclosures.s2.report.preview', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s2.report.*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Generate Report</a>
</nav>
