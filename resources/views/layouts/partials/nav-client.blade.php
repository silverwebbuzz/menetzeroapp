{{-- Client Navigation Menu --}}
@php
    // Always use the web guard here so admin users (App\Models\Admin) don't hit client-specific methods
    $user = auth('web')->user();

    // Check for active company (works for both owners and staff members), only if method exists
    $activeCompany = ($user && method_exists($user, 'getActiveCompany'))
        ? $user->getActiveCompany()
        : null;
    $hasCompany = $activeCompany !== null;
    $companyId = $activeCompany ? $activeCompany->id : null;

    // Check permissions for each module — super admin and company admin have all
    $isAdmin = $user && ($user->isAdmin() || ($companyId && $user->isCompanyAdmin($companyId)));

    $canViewLocations = $isAdmin || ($hasCompany && (
        $user->hasPermission('locations.*', $companyId) ||
        $user->hasPermission('manage_locations', $companyId) ||
        $user->hasModulePermission('locations', 'view', $companyId)
    ));

    $canViewMeasurements = $isAdmin || ($hasCompany && (
        $user->hasPermission('measurements.view', $companyId) ||
        $user->hasPermission('measurements.*', $companyId) ||
        $user->hasPermission('manage_measurements', $companyId) ||
        $user->hasModulePermission('measurements', 'view', $companyId)
    ));

    $canViewReports = $isAdmin || ($hasCompany && (
        $user->hasPermission('reports.view', $companyId) ||
        $user->hasPermission('reports.*', $companyId) ||
        $user->hasModulePermission('reports', 'view', $companyId)
    ));

    $canViewStaff = $isAdmin || ($hasCompany && $user->hasModulePermission('staff_management', 'view', $companyId));
    $canViewRoles = $isAdmin || ($hasCompany && $user->hasModulePermission('roles_permissions', 'view', $companyId));

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
        'flame'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2C8 6 6 9 6 13a6 6 0 0012 0c0-2-1-4-3-5 0 2-1 3-2 3s-2-2-1-4c.5-1 .5-3 0-5z"/>',
        'droplet'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l5 7a5 5 0 11-10 0l5-7z"/>',
        'truck'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8h11v8H3zM14 11h4l3 3v2h-7zM7 19a2 2 0 104 0 2 2 0 00-4 0zM17 19a2 2 0 104 0 2 2 0 00-4 0z"/>',
        'snowflake' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v20M4.93 4.93l14.14 14.14M2 12h20M4.93 19.07L19.07 4.93"/>',
        'bolt'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
        'wave'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12c2 0 2-3 4-3s2 3 4 3 2-3 4-3 2 3 4 3M3 18c2 0 2-3 4-3s2 3 4 3 2-3 4-3 2 3 4 3"/>',
        'plane'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16l18-6-7 10v-4l-6-2 2 6-4-2z"/>',
        'bus'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16V6a2 2 0 012-2h12a2 2 0 012 2v10m-16 0a2 2 0 002 2h12a2 2 0 002-2m-16 0V9h16v7m-11 3v2m6-2v2M8 12h.01M16 12h.01"/>',
        'home'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10"/>',
        'dot'       => '<circle cx="12" cy="12" r="3" fill="currentColor" stroke="none"/>',
    ];

    $svg = function ($name) use ($iconMap) {
        $path = $iconMap[$name] ?? $iconMap['dot'];
        return '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">' . $path . '</svg>';
    };
@endphp

<div class="nav-section">
    <a href="{{ route('client.dashboard') }}" class="nav-link {{ request()->routeIs('client.dashboard') ? 'active' : '' }}">
        {!! $svg('grid') !!}
        Dashboard
    </a>
</div>

@if($hasCompany)
    <div class="nav-section">
        <div class="nav-section-title">Emissions</div>
        @if($canViewLocations)
            <a href="{{ route('locations.index') }}" class="nav-link {{ request()->routeIs('locations.*') ? 'active' : '' }}">
                {!! $svg('pin') !!}
                Locations
            </a>
        @endif
        @if($canViewMeasurements)
            <a href="{{ route('measurements.index') }}" class="nav-link {{ request()->routeIs('measurements.*') ? 'active' : '' }}">
                {!! $svg('chart') !!}
                Measurements
            </a>
        @endif
    </div>

    @if($canViewMeasurements)
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
            <div class="nav-section-title">Quick Input</div>

            <a href="{{ route('quick-input.index') }}" class="nav-link {{ request()->routeIs('quick-input.index') ? 'active' : '' }}">
                {!! $svg('list') !!}
                View Entries
            </a>

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
                    <a href="{{ route('quick-input.show', ['scope' => 3, 'slug' => 'flights']) }}"
                       class="nav-link text-[0.8125rem] {{ $currentScope == 3 && $currentSlug == 'flights' ? 'active' : '' }}"
                       style="padding-top:0.375rem;padding-bottom:0.375rem;">
                        {!! $svg('plane') !!}
                        Flights
                    </a>
                    <a href="{{ route('quick-input.show', ['scope' => 3, 'slug' => 'public-transport']) }}"
                       class="nav-link text-[0.8125rem] {{ $currentScope == 3 && $currentSlug == 'public-transport' ? 'active' : '' }}"
                       style="padding-top:0.375rem;padding-bottom:0.375rem;">
                        {!! $svg('bus') !!}
                        Public Transport
                    </a>
                    <a href="{{ route('quick-input.show', ['scope' => 3, 'slug' => 'home-workers']) }}"
                       class="nav-link text-[0.8125rem] {{ $currentScope == 3 && $currentSlug == 'home-workers' ? 'active' : '' }}"
                       style="padding-top:0.375rem;padding-bottom:0.375rem;">
                        {!! $svg('home') !!}
                        Home Workers
                    </a>
                </div>
            </div>
        </div>
    @endif

    @if($canViewReports)
        <div class="nav-section">
            <div class="nav-section-title">Reports &amp; Profile</div>
            <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                {!! $svg('doc') !!}
                Reports
            </a>
            <a href="{{ route('client.profile') }}" class="nav-link {{ request()->routeIs('client.profile') || request()->routeIs('profile.*') ? 'active' : '' }}">
                {!! $svg('user') !!}
                My Profile
            </a>
        </div>
    @endif
@endif

@if(!$hasCompany)
    <div class="nav-section">
        <a href="{{ route('client.profile') }}" class="nav-link {{ request()->routeIs('client.profile') || request()->routeIs('profile.*') ? 'active' : '' }}">
            {!! $svg('user') !!}
            My Profile
        </a>
    </div>
@endif

@if($user && method_exists($user, 'isAdmin') && $user->isAdmin())
    <div class="nav-section">
        <div class="nav-section-title">System</div>
        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}">
            {!! $svg('cog') !!}
            Admin
        </a>
    </div>
@endif

@if($hasCompany && ($canViewStaff || $canViewRoles || $isAdmin))
    <div class="nav-section" style="margin-top: auto; border-top: 1px solid var(--line); padding-top: 0.75rem;">
        <div class="nav-section-title">Settings</div>
        @if($canViewStaff || $canViewRoles)
            <a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') || request()->routeIs('staff.*') ? 'active' : '' }}">
                {!! $svg('users') !!}
                Staff &amp; Roles
            </a>
        @endif
        @if($isAdmin)
            <a href="{{ route('subscriptions.billing') }}" class="nav-link {{ request()->routeIs('subscriptions.*') ? 'active' : '' }}">
                {!! $svg('card') !!}
                Billing
            </a>
        @endif
    </div>
@endif
