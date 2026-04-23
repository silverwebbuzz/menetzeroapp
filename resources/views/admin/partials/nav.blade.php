@php
    $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
    $isActive = fn($prefix) => str_starts_with($routeName ?? '', $prefix);
@endphp

<div class="nav-section">
    <div class="nav-section-title">Overview</div>
    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ $isActive('admin.dashboard') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6"/>
        </svg>
        Dashboard
    </a>
</div>

<div class="nav-section">
    <div class="nav-section-title">Management</div>
    <a href="{{ route('admin.companies.index') }}" class="nav-link {{ $isActive('admin.companies') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
        Companies
    </a>
    <a href="{{ route('admin.users.index') }}" class="nav-link {{ $isActive('admin.users') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 3a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Users
    </a>
    <a href="{{ route('admin.subscription-plans') }}" class="nav-link {{ $isActive('admin.subscription-plans') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8c-1.657 0-3 .843-3 1.882v4.236C9 15.157 10.343 16 12 16s3-.843 3-1.882V9.882C15 8.843 13.657 8 12 8z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M5 10a7 7 0 1114 0v4a7 7 0 11-14 0v-4z"/>
        </svg>
        Subscription Plans
    </a>
    <a href="{{ route('admin.role-templates') }}" class="nav-link {{ $isActive('admin.role-templates') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
        Role Templates
    </a>
</div>

<div class="nav-section">
    <div class="nav-section-title">Emission Management</div>
    <a href="{{ route('admin.emissions.index') }}" class="nav-link {{ $isActive('admin.emissions') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        Emission Data
    </a>
</div>

<div class="nav-section">
    <div class="nav-section-title">Analytics</div>
    <a href="{{ route('admin.statistics') }}" class="nav-link {{ $isActive('admin.statistics') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M11 3v18M6 8v13M16 13v8M21 6v15"/>
        </svg>
        Statistics
    </a>
</div>

<div class="nav-section">
    <div class="nav-section-title">Client Portal</div>
    <a href="{{ route('client.dashboard') }}" class="nav-link">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Back to Client Portal
    </a>
</div>
