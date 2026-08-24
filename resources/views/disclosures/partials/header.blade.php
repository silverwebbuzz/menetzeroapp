@php
    $fy = $fiscalYear ?? now()->year;
    $fw = $framework ?? 'ifrs_s2';

    // $availableYears is shared by ReportingYearsComposer (years the company
    // actually holds data for). Falls back to a usable span below when a page
    // renders outside that composer's scope.
    $years = collect($availableYears ?? [])
        ->push($fy)
        ->push((int) now()->year)
        ->map(fn ($y) => (int) $y)
        ->filter()
        ->unique()
        ->sortDesc()
        ->values();

    // A brand-new company has no history yet — offer a usable span rather than
    // a dropdown with a single option.
    if ($years->count() < 2) {
        $anchor = max($fy, (int) now()->year);
        $years = collect(range($anchor - 3, $anchor + 1))
            ->push($fy)
            ->map(fn ($y) => (int) $y)
            ->unique()
            ->sortDesc()
            ->values();
    }
    $frameworkLabel = match ($fw) {
        'ifrs_s1' => 'IFRS S1 Sustainability Disclosures',
        'gri' => 'GRI Sustainability Reporting',
        'esg_report' => 'UAE ESG Report',
        default => 'IFRS S2 Climate Disclosures',
    };
@endphp

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <p class="text-sm text-gray-500">{{ $frameworkLabel }}</p>
        <h2 class="text-xl font-semibold text-gray-900">{{ $company->name }}</h2>
    </div>
    <form method="GET" action="{{ url()->current() }}" class="flex items-center gap-2">
        <label for="fiscal_year" class="text-sm text-gray-600">Reporting year</label>
        <select name="fiscal_year" id="fiscal_year"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm"
                onchange="this.form.submit()">
            @foreach($years as $year)
                <option value="{{ $year }}" @selected($year == $fy)>{{ $year }}</option>
            @endforeach
        </select>
        <noscript><button type="submit" class="btn btn-secondary btn-sm">Go</button></noscript>
    </form>
</div>

@include('layouts.partials.nav-disclosures', ['fiscalYear' => $fy, 'framework' => $fw])
