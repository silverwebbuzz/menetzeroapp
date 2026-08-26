{{--
    MENetZero 2.0 — admin sidebar nav (Phase 4).

    Same routes, same 7 sections, same active-state logic as
    admin/partials/nav.blade.php. Only markup and styling change.

    Two links leave the admin portal and are preserved as-is:
      - Pricing page (public, opens in a new tab)
      - Client Portal (company dashboard)
--}}
@php
    $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
    $isActive = fn ($prefix) => str_starts_with($routeName ?? '', $prefix);
@endphp

<div class="mnz-side__group">
    <div class="mnz-side__title">Overview</div>
    <a href="{{ route('admin.dashboard') }}"
       class="mnz-nav {{ $isActive('admin.dashboard') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Dashboard</span>
    </a>
</div>

<div class="mnz-side__group">
    <div class="mnz-side__title">Management</div>
    <a href="{{ route('admin.companies.index') }}"
       class="mnz-nav {{ $isActive('admin.companies') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Companies</span>
    </a>
    <a href="{{ route('admin.users.index') }}"
       class="mnz-nav {{ $isActive('admin.users') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Users</span>
    </a>
    <a href="{{ route('admin.coupons.index') }}"
       class="mnz-nav {{ $isActive('admin.coupons') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Coupons</span>
    </a>
</div>

<div class="mnz-side__group">
    <div class="mnz-side__title">Commercial</div>
    <a href="{{ route('admin.subscription-plans') }}"
       class="mnz-nav {{ $isActive('admin.subscription-plans') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Subscription plans</span>
    </a>
    <a href="{{ route('admin.price-book.index') }}"
       class="mnz-nav {{ $isActive('admin.price-book') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Price book</span>
    </a>
    <a href="{{ route('admin.pricing.index') }}"
       class="mnz-nav {{ $isActive('admin.pricing') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Pricing content</span>
    </a>
    <a href="{{ route('pricing') }}" target="_blank" class="mnz-nav">
        <span class="mnz-nav__dot"></span>
        <span class="mnz-nav__label">View pricing page</span>
        <span class="mnz-nav__meta">↗</span>
    </a>
    <a href="{{ route('admin.consultants.index') }}"
       class="mnz-nav {{ $isActive('admin.consultants') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Consultants</span>
    </a>
    <a href="{{ route('admin.payment-gateways.index') }}"
       class="mnz-nav {{ $isActive('admin.payment-gateways') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Payment gateways</span>
    </a>
    <a href="{{ route('admin.package-requests.index') }}"
       class="mnz-nav {{ $isActive('admin.package-requests') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Package requests</span>
    </a>
    <a href="{{ route('admin.entity-requests.index') }}"
       class="mnz-nav {{ $isActive('admin.entity-requests') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Entity requests</span>
    </a>
    <a href="{{ route('admin.package-assignments.index') }}"
       class="mnz-nav {{ $isActive('admin.package-assignments') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Package assignments</span>
    </a>
</div>

<div class="mnz-side__group">
    <div class="mnz-side__title">Configuration</div>
    <a href="{{ route('admin.email-templates.index') }}"
       class="mnz-nav {{ $isActive('admin.email-templates') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Email templates</span>
    </a>
    <a href="{{ route('admin.email-test.index') }}"
       class="mnz-nav {{ $isActive('admin.email-test') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Email test</span>
    </a>
    <a href="{{ route('admin.site-content.index') }}"
       class="mnz-nav {{ $isActive('admin.site-content') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Site content</span>
    </a>
    <a href="{{ route('admin.role-templates') }}"
       class="mnz-nav {{ $isActive('admin.role-templates') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Role templates</span>
    </a>
</div>

<div class="mnz-side__group">
    <div class="mnz-side__title">Emission management</div>
    <a href="{{ route('admin.emissions.index') }}"
       class="mnz-nav {{ $isActive('admin.emissions') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Emission data</span>
    </a>
</div>

<div class="mnz-side__group">
    <div class="mnz-side__title">Analytics</div>
    <a href="{{ route('admin.statistics') }}"
       class="mnz-nav {{ $isActive('admin.statistics') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Statistics</span>
    </a>
</div>

<div class="mnz-side__foot">
    <div class="mnz-side__title">Client portal</div>
    <a href="{{ route('client.dashboard') }}" class="mnz-nav">
        <span class="mnz-nav__dot"></span>
        <span class="mnz-nav__label">Company dashboard</span>
        <span class="mnz-nav__meta">↗</span>
    </a>
</div>
