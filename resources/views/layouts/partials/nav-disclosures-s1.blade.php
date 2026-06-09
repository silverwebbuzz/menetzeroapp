@php $q = ['fiscal_year' => $fiscalYear]; @endphp
<nav class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-3 text-sm">
    <a href="{{ route('disclosures.hub', $q) }}" class="px-3 py-1.5 rounded-lg text-gray-600 hover:bg-gray-50">← All disclosures</a>
    <a href="{{ route('disclosures.s1.overview', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s1.overview') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Overview</a>
    <a href="{{ route('disclosures.s1.material-topics', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s1.material-topics*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Material Topics</a>
    <a href="{{ route('disclosures.s1.sections.edit', array_merge($q, ['section' => 'governance'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s1.sections.*') && request()->route('section') === 'governance' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Governance</a>
    <a href="{{ route('disclosures.s1.sections.edit', array_merge($q, ['section' => 'strategy'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s1.sections.*') && request()->route('section') === 'strategy' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Strategy</a>
    <a href="{{ route('disclosures.s1.sections.edit', array_merge($q, ['section' => 'risk_management'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s1.sections.*') && request()->route('section') === 'risk_management' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Risk Management</a>
    <a href="{{ route('disclosures.s1.sustainability-risks.index', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s1.sustainability-risks.*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Sustainability Risks</a>
    <a href="{{ route('disclosures.s1.report.preview', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s1.report.*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Generate Report</a>
</nav>
