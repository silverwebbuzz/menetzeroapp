{{--
    Topbar reporting-year switcher (Phase B).

    The reporting year is app-level CONTEXT. Before this it travelled only as
    ?fiscal_year= on individual links, so any link that forgot to carry it
    silently returned the user to the current calendar year — they could edit
    2026 data believing they were in 2025. This posts the year to the session
    once, and every page reads it from there.

    Included by BOTH themes' layouts/app.blade.php. Styling is inherited from
    whichever theme renders it: the classes below exist in both stylesheets,
    and the control degrades to a plain <select> if neither matches.

    $availableYears comes from ReportingYearsComposer (bound to layouts.app),
    and is the set of years the company actually holds data for.
--}}
@php
    // Both layouts also serve users with no active company (onboarding,
    // profile, company selection). A reporting year means nothing there, so
    // the control hides rather than offering a scope that does not exist.
    $ryCompany = auth('web')->user()?->getActiveCompany();

    $ryCurrent = (int) \App\Support\NavigationMap::fiscalYear();

    $ryYears = collect($availableYears ?? [])
        ->push($ryCurrent)
        ->push((int) now()->year)
        ->map(fn ($y) => (int) $y)
        ->filter()
        ->unique()
        ->sortDesc()
        ->values();

    // A company with no history still needs a usable range to pick from.
    if ($ryYears->count() < 2) {
        $ryAnchor = max($ryCurrent, (int) now()->year);
        $ryYears = collect(range($ryAnchor - 3, $ryAnchor + 1))
            ->push($ryCurrent)
            ->map(fn ($y) => (int) $y)
            ->unique()
            ->sortDesc()
            ->values();
    }
@endphp

@if ($ryCompany)
<form method="POST" action="{{ route('reporting-year.update') }}" class="mnz-yearpick">
    @csrf
    <label for="mnz-reporting-year" class="mnz-yearpick__label">Year</label>
    <select name="fiscal_year" id="mnz-reporting-year"
            class="mnz-yearpick__select"
            onchange="this.form.submit()"
            aria-label="Reporting year">
        @foreach ($ryYears as $ryYear)
            <option value="{{ $ryYear }}" @selected($ryYear === $ryCurrent)>{{ $ryYear }}</option>
        @endforeach
    </select>
    {{-- Keyboard/no-JS path: the onchange submit is a convenience, not the
         only way to apply the change. --}}
    <noscript><button type="submit" class="mnz-yearpick__go">Go</button></noscript>
</form>
@endif
