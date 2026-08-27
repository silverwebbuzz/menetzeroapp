{{--
    Shared header for disclosure screens.

    Serves TWO kinds of page, and they need different chrome:

      'framework' (default) — a framework's own surface: overview, narrative
        section editor, report preview. The framework tab strip belongs here,
        because these pages really are parts of one framework.

      'register' — a data register that a PILLAR owns (climate risks,
        targets, sustainability risks). These pages get NO framework tab
        strip: the strip implied the framework owned the data, which is what
        made the same page appear under two different parents. They get the
        "Feeds:" lineage line instead, naming the reports that read them.

    Pass ['context' => 'register'] from a register page.
--}}
@php
    $fy = $fiscalYear ?? now()->year;
    $fw = $framework ?? 'ifrs_s2';
    $ctx = $context ?? 'framework';

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
        @if ($ctx !== 'register')
            <p class="text-sm text-gray-500">{{ $frameworkLabel }}</p>
        @endif
        <h2 class="text-xl font-semibold text-gray-900">{{ $company->name }}</h2>
    </div>
    {{-- The reporting year now lives in the topbar (Phase B), where it
         applies to the whole portal. This per-page copy stays as a
         convenience on framework screens, where switching year mid-report
         is a common action, and is dropped on register pages to avoid two
         controls for one value on the same screen. --}}
    @if ($ctx !== 'register')
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
    @endif
</div>

@if ($ctx === 'register')
    @include('layouts.partials.register-lineage')
@else
    @include('layouts.partials.nav-disclosures', ['fiscalYear' => $fy, 'framework' => $fw])
@endif
