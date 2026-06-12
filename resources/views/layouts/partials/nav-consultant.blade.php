@php
    $iconMap = [
        'grid'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6v6H4V6zm0 10h6v2H4v-2zm10-10h6v2h-6V6zm0 6h6v6h-6v-6z"/>',
        'users'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 3a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'switch' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>',
        'card'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>',
        'refresh'=> '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>',
        'user'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
        'doc'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        'inbox'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>',
        'cart'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>',
        'help'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    ];

    $svg = function ($name) use ($iconMap) {
        $path = $iconMap[$name] ?? $iconMap['grid'];
        return '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">' . $path . '</svg>';
    };
@endphp

<div class="nav-section">
    <a href="{{ route('consultant.dashboard') }}" class="nav-link {{ request()->routeIs('consultant.dashboard') ? 'active' : '' }}">
        {!! $svg('grid') !!}
        Dashboard
    </a>
</div>

<div class="nav-section">
    <div class="nav-section-title">Client workspaces</div>
    <a href="{{ route('consultant.clients.index') }}" class="nav-link {{ request()->routeIs('consultant.clients.*') ? 'active' : '' }}">
        {!! $svg('users') !!}
        Managed clients
    </a>
    <a href="{{ route('consultant.workspace.switcher') }}" class="nav-link {{ request()->routeIs('consultant.workspace.*') ? 'active' : '' }}">
        {!! $svg('switch') !!}
        Workspaces
    </a>
    <a href="{{ route('consultant.packs.index') }}" class="nav-link {{ request()->routeIs('consultant.packs.*') ? 'active' : '' }}">
        {!! $svg('card') !!}
        Agency packs
    </a>
    @if(!empty($showRenewalNav))
        <a href="{{ route('consultant.renewal.index') }}" class="nav-link {{ request()->routeIs('consultant.renewal.*') ? 'active' : '' }}">
            {!! $svg('refresh') !!}
            Renewal
        </a>
    @endif
</div>

<div class="nav-section">
    <div class="nav-section-title">Directory</div>
    <a href="{{ route('consultant.profile.edit') }}" class="nav-link {{ request()->routeIs('consultant.profile.*') ? 'active' : '' }}">
        {!! $svg('user') !!}
        Profile
    </a>
    <a href="{{ route('consultant.documents.index') }}" class="nav-link {{ request()->routeIs('consultant.documents.*') ? 'active' : '' }}">
        {!! $svg('doc') !!}
        Documents
    </a>
    <a href="{{ route('consultant.intro-requests.index') }}" class="nav-link {{ request()->routeIs('consultant.intro-requests.*') ? 'active' : '' }}">
        {!! $svg('inbox') !!}
        Leads
    </a>
    <a href="{{ route('consultant.orders.index') }}" class="nav-link {{ request()->routeIs('consultant.orders.*') ? 'active' : '' }}">
        {!! $svg('cart') !!}
        Orders
    </a>
</div>

<div class="nav-section">
    <div class="nav-section-title">Agency</div>
    <a href="{{ route('consultant.team.index') }}" class="nav-link {{ request()->routeIs('consultant.team.*') ? 'active' : '' }}">
        {!! $svg('users') !!}
        Team &amp; Access
    </a>
</div>

<div class="nav-section nav-section--help">
    <div class="nav-section-title">Help</div>
    <a href="{{ route('consultant.help') }}" class="nav-link {{ request()->routeIs('consultant.help') ? 'active' : '' }}">
        {!! $svg('help') !!}
        Help &amp; guide
    </a>
    <a href="{{ route('consultant.company-guide') }}" class="nav-link {{ request()->routeIs('consultant.company-guide') ? 'active' : '' }}">
        {!! $svg('doc') !!}
        Company portal guide
    </a>
</div>
