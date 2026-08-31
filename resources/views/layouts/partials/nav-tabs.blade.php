{{--
    Six-pillar tab switcher (old theme).

    One entry per config/navigation.php group, plus Settings promoted from the
    footer block. The section BELOW it (nav-client) then shows only the active
    tab's items.

    RENDERED INSIDE THE SIDEBAR, not as a full-width bar like the new theme's.
    The old shell's .sidebar is position:fixed at top:0 and .main-content is
    offset by its width, so there is no full-width band above both -- putting
    one there would mean restructuring the whole shell. Same information,
    same source, laid out to suit the shell it lives in.

    SELF-CONTAINED ON PURPOSE. Recomputes gates rather than receiving them:
    Blade renders an included partial in a CHILD scope, so a variable defined
    in one include is discarded before the next runs. NavigationGates::forUser()
    is memoised per request, so this is not a second set of queries.

    NO JAVASCRIPT. A tab is a link to its first item; that item's route makes
    the tab active on the next request.

    Structure, gating and active state come from App\Support\NavigationMap --
    the same source the new theme's tab bar uses, so the two cannot drift.
--}}
@php
    $tabNav = \App\Support\NavigationMap::tabs(\App\Support\NavigationGates::forUser());
@endphp

@if (! empty($tabNav['tabs']))
    <nav class="pillar-tabs" aria-label="Sections">
        @foreach ($tabNav['tabs'] as $tab)
            @if ($tab['url'])
                <a href="{{ $tab['url'] }}"
                   class="pillar-tab {{ $tab['active'] ? 'active' : '' }}"
                   @if ($tab['active']) aria-current="page" @endif>
                    <span class="pillar-tab__dot" @if ($tab['pillar']) data-pillar="{{ $tab['pillar'] }}" @endif></span>
                    {{ $tab['label'] }}
                </a>
            @endif
        @endforeach
    </nav>
@endif
