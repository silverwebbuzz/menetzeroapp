{{--
    MENetZero — company sidebar navigation (old / current theme).

    STRUCTURE COMES FROM config/navigation.php — not from this file. The new
    theme's nav-client.blade.php renders the SAME config with mnz-* markup,
    so the two themes cannot drift: a page added to the config appears in
    both, with the same grouping, gate and active-state rules.

    This file keeps the old theme's presentation intact — the SVG icon
    library, the .nav-section/.nav-link classes, and the Alpine-driven
    Scope 1/2/3 emission-source tree under Measure. Only the top-level
    structure moved to config.

    Permission gating now lives in layouts/partials/nav-gates.blade.php,
    included below. It is the SAME logic that was inline here before, and
    the new theme includes the identical file — which is what closes the
    R-1 drift risk (a link hidden here could previously still appear in the
    new theme).

    CONSULTANTS SEE THIS TOO: layouts/app.blade.php includes nav-client for
    company workspaces, so a consultant "acting as" a client gets exactly
    this sidebar.
--}}
@include('layouts.partials.nav-gates')

@php
    $nav = \App\Support\NavigationMap::build($navGates);
    $scope3Locked = isset($gate) ? $gate->isScope3Locked() : true;

    // Route helper for scope icons — each source gets a distinct icon where possible
    $sourceIcon = function ($slug) {
        return match ($slug) {
            'natural-gas'       => 'flame',
            'fuel'              => 'droplet',
            'vehicle'           => 'truck',
            'refrigerants'      => 'snowflake',
            'process'           => 'cog',
            'electricity'       => 'bolt',
            'heat-steam-cooling'=> 'wave',
            'flights'           => 'plane',
            'public-transport'  => 'bus',
            'home-workers'      => 'home',
            // Scope 3 — 15 GHG Protocol categories
            'purchased-goods'       => 'card',
            'capital-goods'         => 'cog',
            'fuel-energy-related'   => 'bolt',
            'upstream-transport'    => 'truck',
            'waste-operations'      => 'droplet',
            'business-travel'       => 'plane',
            'employee-commuting'    => 'bus',
            'upstream-leased'       => 'home',
            'downstream-transport'  => 'truck',
            'processing-sold'       => 'cog',
            'use-sold'              => 'bolt',
            'end-of-life'           => 'droplet',
            'downstream-leased'     => 'home',
            'franchises'            => 'shield',
            'investments'           => 'chart',
            default             => 'dot',
        };
    };

    // Reusable icon library — keeps SVG mess out of the markup
    $iconMap = [
        'grid'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6v6H4V6zm0 10h6v2H4v-2zm10-10h6v2h-6V6zm0 6h6v6h-6v-6z"/>',
        'pin'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'chart'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l4-4 4 4 5-7"/>',
        'list'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'doc'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        'user'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
        'users'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 3a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'card'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>',
        'shield'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l8 4v5c0 5-3.5 8-8 10-4.5-2-8-5-8-10V7l8-4z"/>',
        'cog'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'chevron'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>',
        'help'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'flame'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2C8 6 6 9 6 13a6 6 0 0012 0c0-2-1-4-3-5 0 2-1 3-2 3s-2-2-1-4c.5-1 .5-3 0-5z"/>',
        'droplet'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l5 7a5 5 0 11-10 0l5-7z"/>',
        'truck'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8h11v8H3zM14 11h4l3 3v2h-7zM7 19a2 2 0 104 0 2 2 0 00-4 0zM17 19a2 2 0 104 0 2 2 0 00-4 0z"/>',
        'snowflake' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v20M4.93 4.93l14.14 14.14M2 12h20M4.93 19.07L19.07 4.93"/>',
        'bolt'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
        'wave'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12c2 0 2-3 4-3s2 3 4 3 2-3 4-3 2 3 4 3M3 18c2 0 2-3 4-3s2 3 4 3 2-3 4-3 2 3 4 3"/>',
        'plane'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16l18-6-7 10v-4l-6-2 2 6-4-2z"/>',
        'bus'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16V6a2 2 0 012-2h12a2 2 0 012 2v10m-16 0a2 2 0 002 2h12a2 2 0 002-2m-16 0V9h16v7m-11 3v2m6-2v2M8 12h.01M16 12h.01"/>',
        'home'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10"/>',
        'upload'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0-12l-4 4m4-4l4 4"/>',
        'dot'       => '<circle cx="12" cy="12" r="3" fill="currentColor" stroke="none"/>',
    ];

    $svg = function ($name) use ($iconMap) {
        $path = $iconMap[$name] ?? $iconMap['dot'];
        return '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">' . $path . '</svg>';
    };
@endphp

@foreach ($nav['groups'] as $group)
    <div class="nav-section">
        @if ($group['title'])
            <div class="nav-section-title">{{ $group['title'] }}</div>
        @endif

        @foreach ($group['items'] as $item)
            <a href="{{ $item['url'] }}"
               class="nav-link {{ $item['active'] ? 'active' : '' }}"
               @if ($item['active']) aria-current="page" @endif>
                {!! $svg($item['icon'] ?? 'dot') !!}
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>

    {{-- The Scope 1/2/3 emission-source tree hangs off Environmental, directly
         under Measure. Preserved verbatim from the pre-config nav: it is a
         genuinely useful shortcut into quick-input.show and no destination
         should be lost. --}}
    @if ($group['key'] === 'environmental')
    @if($canViewQuickInput)
        @php
            $quickInputSources = \App\Models\EmissionSourceMaster::where('is_quick_input', true)
                ->orderBy('scope')
                ->orderBy('quick_input_order')
                ->get()
                ->groupBy('scope');

            $scope1Sources = $quickInputSources->get('Scope 1', collect());
            $scope2Sources = $quickInputSources->get('Scope 2', collect());
            $scope3Sources = $quickInputSources->get('Scope 3', collect());

            $currentSlug = request()->routeIs('quick-input.show') ? request()->route('slug') : null;
            $currentScope = request()->routeIs('quick-input.show') ? request()->route('scope') : null;
        @endphp

        <div class="nav-section">
            <div class="nav-section-title">Emission sources</div>



            <div x-data="{
                scope1Open: {{ $currentScope == 1 ? 'true' : 'false' }},
                scope2Open: {{ $currentScope == 2 ? 'true' : 'false' }},
                scope3Open: {{ $currentScope == 3 ? 'true' : 'false' }}
            }">
                {{-- Scope 1 --}}
                <button type="button" @click="scope1Open = !scope1Open"
                        class="nav-link w-full text-left justify-between">
                    <span class="flex items-center gap-[0.625rem]">
                        {!! $svg('flame') !!}
                        Scope 1
                    </span>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         class="w-3.5 h-3.5 transition-transform"
                         :class="{ 'rotate-180': scope1Open }"
                         style="width:0.875rem;height:0.875rem;margin:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="scope1Open" x-transition.origin.top class="ml-5 space-y-0.5 border-l border-slate-200 pl-2 mb-2">
                    @foreach($scope1Sources as $source)
                        <a href="{{ route('quick-input.show', ['scope' => 1, 'slug' => $source->quick_input_slug]) }}"
                           class="nav-link text-[0.8125rem] {{ $currentScope == 1 && $currentSlug == $source->quick_input_slug ? 'active' : '' }}"
                           style="padding-top:0.375rem;padding-bottom:0.375rem;">
                            {!! $svg($sourceIcon($source->quick_input_slug)) !!}
                            {{ $source->name }}
                        </a>
                    @endforeach
                </div>

                {{-- Scope 2 --}}
                <button type="button" @click="scope2Open = !scope2Open"
                        class="nav-link w-full text-left justify-between">
                    <span class="flex items-center gap-[0.625rem]">
                        {!! $svg('bolt') !!}
                        Scope 2
                    </span>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         class="transition-transform" :class="{ 'rotate-180': scope2Open }"
                         style="width:0.875rem;height:0.875rem;margin:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="scope2Open" x-transition.origin.top class="ml-5 space-y-0.5 border-l border-slate-200 pl-2 mb-2">
                    @foreach($scope2Sources as $source)
                        <a href="{{ route('quick-input.show', ['scope' => 2, 'slug' => $source->quick_input_slug]) }}"
                           class="nav-link text-[0.8125rem] {{ $currentScope == 2 && $currentSlug == $source->quick_input_slug ? 'active' : '' }}"
                           style="padding-top:0.375rem;padding-bottom:0.375rem;">
                            {!! $svg($sourceIcon($source->quick_input_slug)) !!}
                            {{ $source->name }}
                        </a>
                    @endforeach
                </div>

                {{-- Scope 3 --}}
                <button type="button" @click="scope3Open = !scope3Open"
                        class="nav-link w-full text-left justify-between">
                    <span class="flex items-center gap-[0.625rem]">
                        {!! $svg('plane') !!}
                        Scope 3
                    </span>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         class="transition-transform" :class="{ 'rotate-180': scope3Open }"
                         style="width:0.875rem;height:0.875rem;margin:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="scope3Open" x-transition.origin.top class="ml-5 space-y-0.5 border-l border-slate-200 pl-2 mb-2">
                    @if($scope3Locked)
                        <a href="{{ isset($gate) ? $gate->upgradeRoute() : route('subscriptions.billing') }}"
                           class="nav-link text-[0.8125rem] opacity-80"
                           style="padding-top:0.375rem;padding-bottom:0.375rem;"
                           title="{{ isset($gate) && $gate->isAgencyWorkspace() ? $gate->agencyLockedMessage('Scope 3') : 'Request a package to expand Scope 3' }}">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:1rem;height:1rem;margin:0;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            {{ isset($gate) && $gate->isAgencyWorkspace() ? 'Request slots' : 'Request a package' }}
                        </a>
                    @else
                        @foreach($scope3Sources as $source)
                            <a href="{{ route('quick-input.show', ['scope' => 3, 'slug' => $source->quick_input_slug]) }}"
                               class="nav-link text-[0.8125rem] {{ $currentScope == 3 && $currentSlug == $source->quick_input_slug ? 'active' : '' }}"
                               style="padding-top:0.375rem;padding-bottom:0.375rem;">
                                {!! $svg($sourceIcon($source->quick_input_slug)) !!}
                                {{ $source->name }}
                            </a>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    @endif
    @endif
@endforeach

<div class="nav-section" style="border-top: 1px solid var(--line); padding-top: 0.75rem;">
    @if ($nav['footer']['title'])
        <div class="nav-section-title">{{ $nav['footer']['title'] }}</div>
    @endif

    @foreach ($nav['footer']['items'] as $item)
        <a href="{{ $item['url'] }}"
           class="nav-link {{ $item['active'] ? 'active' : '' }}"
           @if ($item['active']) aria-current="page" @endif>
            {!! $svg($item['icon'] ?? 'dot') !!}
            {{ $item['label'] }}
        </a>
    @endforeach
</div>

{{-- Escape hatch back to the admin portal. Not in config/navigation.php:
     it is an admin-only cross-portal link, not part of the company IA. --}}
@if($user && method_exists($user, 'isAdmin') && $user->isAdmin())
    <div class="nav-section">
        <div class="nav-section-title">System</div>
        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}">
            {!! $svg('cog') !!}
            Admin
        </a>
    </div>
@endif
