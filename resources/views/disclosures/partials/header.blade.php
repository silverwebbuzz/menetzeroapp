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

    // The $availableYears year-list build that used to live here went with
    // the dropdown below. The topbar switcher builds its own from the same
    // ReportingYearsComposer data.
    $frameworkLabel = match ($fw) {
        'ifrs_s1' => 'IFRS S1 Sustainability Disclosures',
        'gri' => 'GRI Sustainability Reporting',
        'esg_report' => 'UAE ESG Report',
        default => 'IFRS S2 Climate Disclosures',
    };
@endphp

{{-- The per-page reporting-year dropdown was REMOVED from this header.

     The topbar switcher (layouts.partials.reporting-year-switcher) and this
     control both wrote the same session key, 'disclosure_fiscal_year' -- the
     topbar via ReportingYearController, this one via
     DisclosureBaseController::resolveContext(). Two controls for one value on
     the same screen is a duplication the register pages had already dropped;
     framework pages kept it only as a convenience.

     Nothing is lost: ?fiscal_year= on an incoming link is still honoured by
     resolveContext() and by CheckDisclosureAccess, so existing links and
     bookmarks continue to work and still update the topbar.

     The flex row that positioned the title against the dropdown went with it;
     with one child left there is nothing to space apart. --}}
<div class="mb-6">
    @if ($ctx !== 'register')
        <p class="text-sm text-gray-500">{{ $frameworkLabel }}</p>
    @endif
    <h2 class="text-xl font-semibold text-gray-900">{{ $company->name }}</h2>
</div>

@if ($ctx === 'register')
    @include('layouts.partials.register-lineage')
@else
    @include('layouts.partials.nav-disclosures', ['fiscalYear' => $fy, 'framework' => $fw])
@endif
