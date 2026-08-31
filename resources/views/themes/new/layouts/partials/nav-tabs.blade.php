{{--
    MENetZero 2.0 - six-pillar tab bar (new theme).

    Renders one tab per config/navigation.php group, plus Settings promoted
    from the footer block. The SIDEBAR (nav-client) then shows only the
    active tab's items.

    SELF-CONTAINED ON PURPOSE. This partial recomputes gates and tabs rather
    than receiving them from the sidebar: Blade renders an included partial
    in a CHILD scope, so a variable defined in one include is discarded
    before the next one runs. NavigationGates::forUser() is memoised per
    request, so the second call is not a second set of queries.

    NO JAVASCRIPT. A tab is a link to its first item; that item's route then
    makes the tab active on the next request. Nothing to keep in sync
    client-side, and the bar works with JS disabled.

    A tab whose items are all gated away is dropped by NavigationMap::tabs(),
    so a user never sees a tab that opens onto an empty sidebar.

    Data: none - computed here.
--}}
@php
    $navTabs = \App\Support\NavigationMap::tabs(\App\Support\NavigationGates::forUser());
@endphp

@if (! empty($navTabs['tabs']))
    <nav class="mnz-tabs" aria-label="Sections">
        @foreach ($navTabs['tabs'] as $tab)
            @if ($tab['url'])
                <a href="{{ $tab['url'] }}"
                   class="mnz-tab {{ $tab['active'] ? 'is-active' : '' }}"
                   @if ($tab['active']) aria-current="page" @endif>
                    <span class="mnz-tab__dot" @if ($tab['pillar']) data-pillar="{{ $tab['pillar'] }}" @endif></span>
                    {{ $tab['label'] }}
                </a>
            @endif
        @endforeach
    </nav>
@endif
