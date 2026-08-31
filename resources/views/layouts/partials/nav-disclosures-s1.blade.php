{{-- IFRS S1 framework tabs.

     The Sustainability Risks tab was REMOVED: that register is owned by the
     Governance pillar and reached from the sidebar. Material Topics stays —
     it is the framework's own materiality determination, edited here.

     TABS REMOVED (section 61): "← All disclosures" and this framework's own
     "Overview". Both duplicated the Reports sidebar, which lists the
     disclosure hub and all four framework overviews. Neither page lost its
     only route -- the sidebar and disclosures/hub still link both.

     LABELS: tab text now matches the section 'title' in config/disclosure.php
     rather than a generic word. "Governance", "Strategy", "Risk Management",
     "Material Topics" and "Generate Report" each appeared in two or more
     strips pointing at different pages, so the label alone could not tell a
     user which one they were on. --}}
@php $q = ['fiscal_year' => $fiscalYear]; @endphp
<nav class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-3 text-sm">
    <a href="{{ route('disclosures.s1.material-topics', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s1.material-topics*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">S1 material topics</a>
    <a href="{{ route('disclosures.s1.sections.edit', array_merge($q, ['section' => 'governance'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s1.sections.*') && request()->route('section') === 'governance' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Sustainability oversight</a>
    <a href="{{ route('disclosures.s1.sections.edit', array_merge($q, ['section' => 'strategy'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s1.sections.*') && request()->route('section') === 'strategy' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Sustainability strategy</a>
    <a href="{{ route('disclosures.s1.sections.edit', array_merge($q, ['section' => 'risk_management'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s1.sections.*') && request()->route('section') === 'risk_management' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Sustainability risk process</a>
    <a href="{{ route('disclosures.s1.report.preview', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s1.report.*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">S1 report</a>
</nav>
