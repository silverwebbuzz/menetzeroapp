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
{{-- Sidebar head: kicker + section name, per the design canvas. Sits in its
     own block above the links, NOT inside the link group. --}}
<div class="mnz-side__head">
    @if ($navTabs['eyebrow'])
        <div class="mnz-side__eyebrow">{{ $navTabs['eyebrow'] }}</div>
    @endif
    @if ($navTabs['title'])
        <div class="mnz-side__heading">{{ $navTabs['title'] }}</div>
    @endif
</div>

<div class="mnz-side__group" @if ($navTabs['pillar']) data-pillar="{{ $navTabs['pillar'] }}" @endif>
    @foreach ($navTabs['items'] as $item)
        <a href="{{ $item['url'] }}"
           class="mnz-nav {{ $item['active'] ? 'is-active' : '' }}"
           @if ($item['active']) aria-current="page" @endif>
            <span class="mnz-nav__dot"></span>
            <span class="mnz-nav__label">{{ $item['label'] }}</span>
        </a>
    @endforeach
</div>

{{-- Scope 1/2/3 emission-source tree, under Environmental.

     PARITY WITH THE OLD THEME. This shortcut into quick-input.show existed
     only in the old nav; the new theme was missing every source link. Ported
     with the same data query, the same Alpine collapse behaviour and -- most
     importantly -- the same Scope 3 plan gate.

     GATING IS NOT COSMETIC. When $scope3Locked the source links are replaced
     by an upgrade link, exactly as in the old theme. Rendering the sources
     here would expose a paid feature to a lower tier (risk R-1). --}}
@if ($navTabs['active'] === 'environmental' && ($navGates['quick_input'] ?? false))
    @php
        $quickInputSources = \App\Models\EmissionSourceMaster::where('is_quick_input', true)
            ->orderBy('scope')
            ->orderBy('quick_input_order')
            ->get()
            ->groupBy('scope');

        $scopeSources = [
            1 => $quickInputSources->get('Scope 1', collect()),
            2 => $quickInputSources->get('Scope 2', collect()),
            3 => $quickInputSources->get('Scope 3', collect()),
        ];

        $scope3Locked = isset($gate) ? $gate->isScope3Locked() : true;
        $currentSlug = request()->routeIs('quick-input.show') ? request()->route('slug') : null;
        $currentScope = request()->routeIs('quick-input.show') ? (int) request()->route('scope') : null;
    @endphp

    <div class="mnz-side__group">
        <div class="mnz-side__eyebrow" style="padding:0 12px 6px">Emission sources</div>

        <div x-data="{ open: {{ $currentScope ?: 0 }} }">
            @foreach ([1 => 'Scope 1', 2 => 'Scope 2', 3 => 'Scope 3'] as $scopeNum => $scopeLabel)
                <button type="button"
                        @click="open = open === {{ $scopeNum }} ? 0 : {{ $scopeNum }}"
                        class="mnz-nav mnz-nav--toggle"
                        :aria-expanded="open === {{ $scopeNum }}">
                    <span class="mnz-nav__dot"></span>
                    <span class="mnz-nav__label">{{ $scopeLabel }}</span>
                    <span class="mnz-nav__chev" :class="{ 'is-open': open === {{ $scopeNum }} }">&rsaquo;</span>
                </button>

                <div x-show="open === {{ $scopeNum }}" x-transition.origin.top x-cloak class="mnz-subnav">
                    @if ($scopeNum === 3 && $scope3Locked)
                        <a href="{{ isset($gate) ? $gate->upgradeRoute() : route('subscriptions.billing') }}"
                           class="mnz-subnav__link is-locked"
                           title="{{ isset($gate) && $gate->isAgencyWorkspace() ? $gate->agencyLockedMessage('Scope 3') : 'Request a package to expand Scope 3' }}">
                            {{ isset($gate) && $gate->isAgencyWorkspace() ? 'Request slots' : 'Request a package' }}
                        </a>
                    @else
                        @foreach ($scopeSources[$scopeNum] as $source)
                            <a href="{{ route('quick-input.show', ['scope' => $scopeNum, 'slug' => $source->quick_input_slug]) }}"
                               class="mnz-subnav__link {{ $currentScope === $scopeNum && $currentSlug === $source->quick_input_slug ? 'is-active' : '' }}">
                                {{ $source->name }}
                            </a>
                        @endforeach
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
