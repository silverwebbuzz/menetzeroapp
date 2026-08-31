{{-- IFRS S2 framework tabs.

     The Climate Risks / Opportunities / Targets tabs were REMOVED: those are
     registers owned by the Environmental pillar, reached from the sidebar.
     Keeping them here gave each of those pages two parents (pillar and
     framework), which is what made the IA confusing. What remains is what
     genuinely belongs to the framework: its narrative sections and its
     report. The registers still feed this report — each register page names
     it in its "Feeds:" line.

     TABS REMOVED (section 61): "All disclosures" and this framework's own
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
    <a href="{{ route('disclosures.s2.sections.edit', array_merge($q, ['section' => 'governance'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s2.sections.*') && request()->route('section') === 'governance' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Climate oversight</a>
    <a href="{{ route('disclosures.s2.sections.edit', array_merge($q, ['section' => 'strategy'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s2.sections.*') && request()->route('section') === 'strategy' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Climate strategy</a>
    <a href="{{ route('disclosures.s2.sections.edit', array_merge($q, ['section' => 'risk_management'])) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s2.sections.*') && request()->route('section') === 'risk_management' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Climate risk process</a>
    <a href="{{ route('disclosures.s2.report.preview', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.s2.report.*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">S2 report</a>
</nav>
