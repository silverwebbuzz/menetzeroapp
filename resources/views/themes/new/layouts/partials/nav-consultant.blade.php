{{--
    MENetZero 2.0 — consultant sidebar nav (Phase 3).

    Same routes, same grouping, same conditional ($showRenewalNav, supplied
    by ConsultantAgencyComposer) as layouts/partials/nav-consultant.blade.php.
    Only the markup and styling change.
--}}
<div class="mnz-side__group">
    <a href="{{ route('consultant.dashboard') }}"
       class="mnz-nav {{ request()->routeIs('consultant.dashboard') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span>
        <span class="mnz-nav__label">Dashboard</span>
    </a>
</div>

<div class="mnz-side__group">
    <div class="mnz-side__title">Client workspaces</div>

    <a href="{{ route('consultant.clients.index') }}"
       class="mnz-nav {{ request()->routeIs('consultant.clients.*') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span>
        <span class="mnz-nav__label">Managed clients</span>
    </a>

    <a href="{{ route('consultant.workspace.switcher') }}"
       class="mnz-nav {{ request()->routeIs('consultant.workspace.*') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span>
        <span class="mnz-nav__label">Workspaces</span>
    </a>

    <a href="{{ route('consultant.packs.index') }}"
       class="mnz-nav {{ request()->routeIs('consultant.packs.*') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span>
        <span class="mnz-nav__label">Request clients</span>
    </a>

    @if (!empty($showRenewalNav))
        <a href="{{ route('consultant.renewal.index') }}"
           class="mnz-nav {{ request()->routeIs('consultant.renewal.*') ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span>
            <span class="mnz-nav__label">Renewal</span>
        </a>
    @endif
</div>

<div class="mnz-side__group">
    <div class="mnz-side__title">Directory</div>

    <a href="{{ route('consultant.profile.edit') }}"
       class="mnz-nav {{ request()->routeIs('consultant.profile.*') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span>
        <span class="mnz-nav__label">Profile</span>
    </a>

    <a href="{{ route('consultant.documents.index') }}"
       class="mnz-nav {{ request()->routeIs('consultant.documents.*') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span>
        <span class="mnz-nav__label">Documents</span>
    </a>

    <a href="{{ route('consultant.intro-requests.index') }}"
       class="mnz-nav {{ request()->routeIs('consultant.intro-requests.*') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span>
        <span class="mnz-nav__label">Leads</span>
    </a>

    <a href="{{ route('consultant.orders.index') }}"
       class="mnz-nav {{ request()->routeIs('consultant.orders.*') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span>
        <span class="mnz-nav__label">Orders</span>
    </a>
</div>

<div class="mnz-side__group">
    <div class="mnz-side__title">Agency</div>

    <a href="{{ route('consultant.team.index') }}"
       class="mnz-nav {{ request()->routeIs('consultant.team.*') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span>
        <span class="mnz-nav__label">Team &amp; access</span>
    </a>
</div>

<div class="mnz-side__foot">
    <a href="{{ route('consultant.help') }}"
       class="mnz-nav {{ request()->routeIs('consultant.help') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span>
        <span class="mnz-nav__label">Help &amp; guide</span>
    </a>

    <a href="{{ route('consultant.company-guide') }}"
       class="mnz-nav {{ request()->routeIs('consultant.company-guide') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span>
        <span class="mnz-nav__label">Company portal guide</span>
    </a>
</div>
