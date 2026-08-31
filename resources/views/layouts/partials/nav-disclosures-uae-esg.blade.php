{{-- UAE ESG framework tabs.

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
    @foreach($sectionConfig ?? config('esg_report.sections', []) as $key => $section)
        <a href="{{ route('disclosures.uae-esg.sections.edit', array_merge($q, ['section' => $key])) }}"
           class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.uae-esg.sections.*') && request()->route('section') === $key ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">{{ $section['title'] }}</a>
    @endforeach
    <a href="{{ route('disclosures.uae-esg.report.preview', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.uae-esg.report.*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Report</a>
</nav>
