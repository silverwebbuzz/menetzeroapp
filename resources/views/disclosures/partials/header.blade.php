@php
    $fy = $fiscalYear ?? now()->year;
    $fw = $framework ?? 'ifrs_s2';
    $frameworkLabel = match ($fw) {
        'ifrs_s1' => 'IFRS S1 Sustainability Disclosures',
        'gri' => 'GRI Sustainability Reporting',
        default => 'IFRS S2 Climate Disclosures',
    };
@endphp

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <p class="text-sm text-gray-500">{{ $frameworkLabel }}</p>
        <h2 class="text-xl font-semibold text-gray-900">{{ $company->name }}</h2>
    </div>
    <form method="GET" action="{{ url()->current() }}" class="flex items-center gap-2">
        <label class="text-sm text-gray-600">Reporting year</label>
        <input type="number" name="fiscal_year" value="{{ $fy }}" min="2000" max="2100"
               class="w-28 border border-gray-300 rounded-lg px-3 py-2 text-sm"
               onchange="this.form.submit()">
    </form>
</div>

@include('layouts.partials.nav-disclosures', ['fiscalYear' => $fy, 'framework' => $fw])
