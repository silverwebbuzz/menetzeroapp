{{--
    MENetZero 2.0 — company sidebar navigation (new theme).

    Overrides layouts/partials/nav-client.blade.php under the new theme.

    STRUCTURE COMES FROM config/navigation.php — not from this file. The old
    theme's nav-client.blade.php renders the SAME config with its own markup,
    so the two themes cannot drift: a page added to the config appears in
    both, with the same grouping, gate and active-state rules.

    This file is presentation only (mnz-* classes). Anything that could be
    wrong — route resolution, active state, fiscal-year propagation,
    permission gating — lives in App\Support\NavigationMap and
    App\Support\NavigationGates.

    CONSULTANTS SEE THIS TOO: both themes' layouts/app.blade.php include
    nav-client for company workspaces, so a consultant "acting as" a client
    gets exactly this sidebar. Nothing consultant-specific belongs here.

    GATING: permission-based, unchanged, computed once in NavigationGates.
    A link the old nav hides MUST stay hidden here, or a lower tier sees a
    paid feature (risk R-1). PlanGateComposer is bound to both
    'layouts.partials.nav-client' and its theme-new:: form, and shares $gate
    (a PlanGate) plus $companyRenewalNudge.

    DELIBERATE OMISSION: the pre-2.0 nav expanded every emission source as
    its own link via route('quick-input.show', [...]) — dozens of entries.
    The pillar IA replaces that with a single Measure link into
    quick-input.index, where the same sources are chosen on-page. No
    destination is lost; the routes stay registered and reachable.
--}}
@php
    // Gates come from App\Support\NavigationGates — a PHP class, not an
    // @include. Blade renders an included partial in a CHILD scope, so any
    // variable it defines is discarded when it returns; the including view
    // never sees it.
    $navGates = \App\Support\NavigationGates::forUser();
    $nav = \App\Support\NavigationMap::build($navGates);
@endphp

@foreach ($nav['groups'] as $group)
    <div class="mnz-side__group" @if ($group['pillar']) data-pillar="{{ $group['pillar'] }}" @endif>
        @if ($group['title'])
            <div class="mnz-side__title">{{ $group['title'] }}</div>
        @endif

        @foreach ($group['items'] as $item)
            <a href="{{ $item['url'] }}"
               class="mnz-nav {{ $item['active'] ? 'is-active' : '' }}"
               @if ($item['active']) aria-current="page" @endif>
                <span class="mnz-nav__dot"></span>
                <span class="mnz-nav__label">{{ $item['label'] }}</span>
                {{-- Which framework reports read this register. Answers "where
                     does what I type here end up?" at the point of CHOOSING,
                     not only once the page has loaded. Empty for every item
                     that is not a register, so nothing else shifts. --}}
                @if (! empty($item['feeds']))
                    <span class="mnz-nav__meta">{{ implode(' · ', $item['feeds']) }}</span>
                @endif
            </a>
        @endforeach
    </div>
@endforeach

<div class="mnz-side__foot">
    @if ($nav['footer']['title'])
        <div class="mnz-side__title">{{ $nav['footer']['title'] }}</div>
    @endif

    @foreach ($nav['footer']['items'] as $item)
        <a href="{{ $item['url'] }}"
           class="mnz-nav {{ $item['active'] ? 'is-active' : '' }}"
           @if ($item['active']) aria-current="page" @endif>
            <span class="mnz-nav__dot"></span>
            <span class="mnz-nav__label">{{ $item['label'] }}</span>
        </a>
    @endforeach
</div>
