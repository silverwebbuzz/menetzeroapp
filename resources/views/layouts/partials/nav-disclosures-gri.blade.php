@php $q = ['fiscal_year' => $fiscalYear]; @endphp
<nav class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-3 text-sm">
    <a href="{{ route('disclosures.hub', $q) }}" class="px-3 py-1.5 rounded-lg text-gray-600 hover:bg-gray-50">← All disclosures</a>
    <a href="{{ route('disclosures.gri.overview', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.gri.overview') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Overview</a>
    <a href="{{ route('disclosures.gri.material-topics', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.gri.material-topics*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Material Topics</a>
    <a href="{{ route('disclosures.gri.sections.edit', array_merge($q, ['section' => 'material_topics_process'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.gri.sections.*') && request()->route('section') === 'material_topics_process' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">GRI 3 Process</a>
    <a href="{{ route('disclosures.gri.sections.edit', array_merge($q, ['section' => 'general'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.gri.sections.*') && request()->route('section') === 'general' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">GRI 2 General</a>
    <a href="{{ route('disclosures.gri.sections.edit', array_merge($q, ['section' => 'energy'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.gri.sections.*') && request()->route('section') === 'energy' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Energy</a>
    <a href="{{ route('disclosures.gri.sections.edit', array_merge($q, ['section' => 'water'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.gri.sections.*') && request()->route('section') === 'water' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Water</a>
    <a href="{{ route('disclosures.gri.sections.edit', array_merge($q, ['section' => 'waste'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.gri.sections.*') && request()->route('section') === 'waste' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Waste</a>
    <a href="{{ route('disclosures.gri.sections.edit', array_merge($q, ['section' => 'social_hr'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.gri.sections.*') && request()->route('section') === 'social_hr' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Employment</a>
    <a href="{{ route('disclosures.gri.sections.edit', array_merge($q, ['section' => 'diversity'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.gri.sections.*') && request()->route('section') === 'diversity' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Diversity</a>
    <a href="{{ route('disclosures.gri.sections.edit', array_merge($q, ['section' => 'health_safety'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.gri.sections.*') && request()->route('section') === 'health_safety' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Health &amp; Safety</a>
    <a href="{{ route('disclosures.gri.sections.edit', array_merge($q, ['section' => 'supply_chain'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.gri.sections.*') && request()->route('section') === 'supply_chain' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Supply Chain</a>
    <a href="{{ route('disclosures.gri.sections.edit', array_merge($q, ['section' => 'governance_metrics'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.gri.sections.*') && request()->route('section') === 'governance_metrics' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Governance</a>
    <a href="{{ route('disclosures.gri.report.preview', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.gri.report.*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">GRI Report</a>
</nav>
