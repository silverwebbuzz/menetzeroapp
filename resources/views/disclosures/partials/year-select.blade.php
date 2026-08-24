{{-- Reporting-year dropdown.
     Options come from $availableYears (ReportingYearsComposer) — years the
     company actually has data for. Falls back to a usable span so a company
     with no history still gets a working control.

     Usage: @include('disclosures.partials.year-select', ['action' => route(...)])
     Optional: 'label' (default "Reporting year"),
               'hidden' => ['category' => $activeCategory] to carry extra params
               through the GET submit. --}}
@php
    $ysFy = (int) ($fiscalYear ?? now()->year);

    $ysYears = collect($availableYears ?? [])
        ->push($ysFy)
        ->push((int) now()->year)
        ->map(fn ($y) => (int) $y)
        ->filter()
        ->unique()
        ->sortDesc()
        ->values();

    if ($ysYears->count() < 2) {
        $ysAnchor = max($ysFy, (int) now()->year);
        $ysYears = collect(range($ysAnchor - 3, $ysAnchor + 1))
            ->push($ysFy)
            ->map(fn ($y) => (int) $y)
            ->unique()
            ->sortDesc()
            ->values();
    }
@endphp

<form method="GET" action="{{ $action ?? url()->current() }}" class="flex items-center gap-2">
    @foreach(($hidden ?? []) as $hiddenName => $hiddenValue)
        <input type="hidden" name="{{ $hiddenName }}" value="{{ $hiddenValue }}">
    @endforeach
    <label for="fiscal_year" class="text-sm text-gray-600">{{ $label ?? 'Reporting year' }}</label>
    <select name="fiscal_year" id="fiscal_year"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm"
            onchange="this.form.submit()">
        @foreach($ysYears as $ysYear)
            <option value="{{ $ysYear }}" @selected($ysYear == $ysFy)>{{ $ysYear }}</option>
        @endforeach
    </select>
    <noscript><button type="submit" class="btn btn-secondary btn-sm">Go</button></noscript>
</form>
