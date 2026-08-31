{{--
    MENetZero 2.0 - company navigation (new theme): pillar tabs + sidebar.

    Overrides layouts/partials/nav-client.blade.php under the new theme.

    STRUCTURE COMES FROM config/navigation.php - not from this file. The old
    theme renders the SAME config with its own markup, so the two cannot
    drift: a page added to the config appears in both, with the same
    grouping, gate and active-state rules.

    FILTERED SIDEBAR. NavigationMap::tabs() returns the items of the ACTIVE
    tab only; every other tab's items are not rendered here at all. The tab
    BAR itself lives in the shell (theme-new::layouts.partials.nav-tabs) so
    it can span the full width above both this sidebar and the content.

    OVERVIEW HAS ONE ITEM TODAY. Its sidebar therefore shows a single link.
    That is expected and was chosen deliberately over hiding the rail: the
    section fills out as Overview pages are added, and the layout does not
    change shape when they arrive.

    This file is presentation only (mnz-* classes). Anything that could be
    wrong - route resolution, active state, fiscal-year propagation,
    permission gating - lives in App\Support\NavigationMap and
    App\Support\NavigationGates.

    CONSULTANTS SEE THIS TOO: both themes' layouts/app.blade.php include
    nav-client for company workspaces, so a consultant "acting as" a client
    gets exactly this nav. Nothing consultant-specific belongs here.

    GATING: permission-based, unchanged, computed once in NavigationGates.
    A link the old nav hides MUST stay hidden here, or a lower tier sees a
    paid feature (risk R-1). PlanGateComposer is bound to both
    'layouts.partials.nav-client' and its theme-new:: form.
--}}
@php
    // Gates come from App\Support\NavigationGates - a PHP class, not an
    // @include. Blade renders an included partial in a CHILD scope, so any
    // variable it defines is discarded when it returns; the including view
    // never sees it.
    $navGates = \App\Support\NavigationGates::forUser();
    $navTabs = \App\Support\NavigationMap::tabs($navGates);
@endphp
<div class="mnz-side__group" @if ($navTabs['pillar']) data-pillar="{{ $navTabs['pillar'] }}" @endif>
    @if ($navTabs['eyebrow'])
        <div class="mnz-side__eyebrow">{{ $navTabs['eyebrow'] }}</div>
    @endif
    @if ($navTabs['title'])
        <div class="mnz-side__heading">{{ $navTabs['title'] }}</div>
    @endif

    @foreach ($navTabs['items'] as $item)
        <a href="{{ $item['url'] }}"
           class="mnz-nav {{ $item['active'] ? 'is-active' : '' }}"
           @if ($item['active']) aria-current="page" @endif>
            <span class="mnz-nav__dot"></span>
            <span class="mnz-nav__label">{{ $item['label'] }}</span>
        </a>
    @endforeach
</div>
