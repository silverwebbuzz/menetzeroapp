@php $q = ['fiscal_year' => $fiscalYear]; @endphp
<nav class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-3 text-sm">
    <a href="{{ route('disclosures.hub', $q) }}" class="px-3 py-1.5 rounded-lg text-gray-600 hover:bg-gray-50">← All disclosures</a>
    <a href="{{ route('disclosures.uae-esg.overview', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.uae-esg.overview') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Overview</a>
    @foreach($sectionConfig ?? config('esg_report.sections', []) as $key => $section)
        <a href="{{ route('disclosures.uae-esg.sections.edit', array_merge($q, ['section' => $key])) }}"
           class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.uae-esg.sections.*') && request()->route('section') === $key ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">{{ $section['title'] }}</a>
    @endforeach
    <a href="{{ route('disclosures.uae-esg.report.preview', $q) }}"
       class="px-3 py-1.5 rounded-lg {{ request()->routeIs('disclosures.uae-esg.report.*') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">Report</a>
</nav>
