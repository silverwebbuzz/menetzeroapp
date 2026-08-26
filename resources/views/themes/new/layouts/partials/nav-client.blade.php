{{--
    MENetZero 2.0 — company six-tab navigation (Phase 5.3).

    Overrides layouts/partials/nav-client.blade.php under the new theme.

    Top-level tabs: Overview / Environmental / Social / Governance /
    Reports / Settings, each with a contextual sub-nav.

    GATING: permission-based, reproduced verbatim from the old nav. A link
    the old nav hides MUST stay hidden here, or a lower tier sees a paid
    feature (risk R-1). PlanGateComposer is bound to both
    'layouts.partials.nav-client' and its theme-new:: form, and shares $gate
    (a PlanGate) plus $companyRenewalNudge.

    The old nav's routes all remain registered, so nothing here can 404.

    DELIBERATE OMISSION: the old nav expands every emission source as its own
    link via route('quick-input.show', ['scope' => …, 'slug' => …]) — dozens of
    entries. The six-tab IA replaces that with a single Measure link into
    quick-input.index, where the same sources are chosen on-page. No
    destination is lost; the routes stay registered and reachable.
--}}
@php
    // Permission gating is reproduced VERBATIM from
    // layouts/partials/nav-client.blade.php. It is permission-based, not
    // plan-based: PlanGateComposer shares $gate (a PlanGate) and
    // companyRenewalNudge. There is no planGate variable and no allows() method.
    // A link the old nav hides MUST stay hidden here (risk R-1).
    $user = auth('web')->user();

    $activeCompany = ($user && method_exists($user, 'getActiveCompany'))
        ? $user->getActiveCompany()
        : null;
    $hasCompany = $activeCompany !== null;
    $companyId = $activeCompany ? $activeCompany->id : null;

    $isAdmin = $user && ($user->isAdmin() || ($companyId && $user->isCompanyAdmin($companyId)));

    $canViewLocations = $isAdmin || ($hasCompany && (
        $user->hasPermission('locations.*', $companyId) ||
        $user->hasPermission('manage_locations', $companyId) ||
        $user->hasModulePermission('locations', 'view', $companyId)
    ));

    $canViewQuickInput = $isAdmin || ($hasCompany && (
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

    $canViewDisclosures = $isAdmin || ($hasCompany && (
        $user->hasPermission('disclosures.view', $companyId) ||
        $user->hasPermission('disclosures.*', $companyId) ||
        $user->hasModulePermission('disclosures', 'view', $companyId) ||
        $canViewReports
    ));

    $canViewStaff = $isAdmin || ($hasCompany && $user->hasModulePermission('staff_management', 'view', $companyId));
    $canViewRoles = $isAdmin || ($hasCompany && $user->hasModulePermission('roles_permissions', 'view', $companyId));

    $routeName = \Illuminate\Support\Facades\Route::currentRouteName() ?? '';
    $on = fn (string ...$prefixes) => collect($prefixes)
        ->contains(fn ($p) => str_starts_with($routeName, $p));
@endphp

<div class="mnz-side__group">
    <a href="{{ route('client.dashboard') }}"
       class="mnz-nav {{ $routeName === 'client.dashboard' ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Overview</span>
    </a>
</div>

{{-- Environmental --}}
<div class="mnz-side__group" data-pillar="e">
    <div class="mnz-side__title">Environmental</div>

    <a href="{{ route('env.overview') }}"
       class="mnz-nav {{ $routeName === 'env.overview' ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Summary</span>
    </a>
    @if ($canViewQuickInput)
        <a href="{{ route('quick-input.index') }}"
           class="mnz-nav {{ $on('quick-input.', 'env.measure') ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Measure</span>
        </a>
        <a href="{{ route('quick-input.bulk-import.index') }}"
           class="mnz-nav {{ $routeName === 'quick-input.bulk-import.index' ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Bulk import</span>
        </a>
    @endif
    @if ($canViewLocations)
        <a href="{{ route('locations.index') }}"
           class="mnz-nav {{ $on('locations.', 'emission-boundaries.', 'env.locations', 'env.boundaries') ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Locations &amp; boundaries</span>
        </a>
    @endif

    @if ($canViewDisclosures)
        <a href="{{ route('disclosures.s2.climate-risks.index') }}"
           class="mnz-nav {{ $on('disclosures.s2.climate-risks', 'env.climate-risks') ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Climate risks</span>
        </a>
        <a href="{{ route('disclosures.s2.climate-opportunities.index') }}"
           class="mnz-nav {{ $on('disclosures.s2.climate-opportunities', 'env.opportunities') ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Opportunities</span>
        </a>
        <a href="{{ route('disclosures.s2.targets.index') }}"
           class="mnz-nav {{ $on('disclosures.s2.targets', 'env.targets') ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Targets</span>
        </a>
    @endif
</div>

{{-- Social --}}
@if ($canViewDisclosures)
    <div class="mnz-side__group" data-pillar="s">
        <div class="mnz-side__title">Social</div>

        <a href="{{ route('social.overview') }}"
           class="mnz-nav {{ $routeName === 'social.overview' ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Summary</span>
        </a>
        <a href="{{ route('disclosures.stakeholders.index') }}"
           class="mnz-nav {{ $on('disclosures.stakeholders', 'social.stakeholders') ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Stakeholders</span>
        </a>
        <a href="{{ route('disclosures.supply-chain.index') }}"
           class="mnz-nav {{ $on('disclosures.supply-chain', 'social.supply-chain') ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Supply chain</span>
        </a>
        <a href="{{ route('disclosures.esg-scorecard.index') }}"
           class="mnz-nav {{ $on('disclosures.esg-scorecard', 'social.scorecard') ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">ESG scorecard</span>
        </a>
        <a href="{{ route('disclosures.esg-dashboard') }}"
           class="mnz-nav {{ $routeName === 'disclosures.esg-dashboard' ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">ESG dashboard</span>
        </a>
        <a href="{{ route('disclosures.esg-depth.overview') }}"
           class="mnz-nav {{ $on('disclosures.esg-depth') ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">ESG depth</span>
        </a>
    </div>

    {{-- Governance --}}
    <div class="mnz-side__group" data-pillar="g">
        <div class="mnz-side__title">Governance</div>

        <a href="{{ route('gov.overview') }}"
           class="mnz-nav {{ $routeName === 'gov.overview' ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Summary</span>
        </a>
        <a href="{{ route('disclosures.materiality-matrix.index') }}"
           class="mnz-nav {{ $on('disclosures.materiality-matrix', 'gov.materiality') ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Materiality</span>
        </a>
        <a href="{{ route('disclosures.s1.sustainability-risks.index') }}"
           class="mnz-nav {{ $on('disclosures.s1.sustainability-risks', 'gov.risks') ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Risk register</span>
        </a>
        <a href="{{ route('disclosures.sasb.index') }}"
           class="mnz-nav {{ $on('disclosures.sasb', 'gov.sasb') ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">SASB</span>
        </a>
        <a href="{{ route('gov.policies') }}"
           class="mnz-nav {{ $routeName === 'gov.policies' ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Policies</span>
        </a>
    </div>
@endif

{{-- Reports --}}
<div class="mnz-side__group">
    <div class="mnz-side__title">Reports</div>

    @if ($canViewReports)
        <a href="{{ route('reports.index') }}"
           class="mnz-nav {{ $routeName === 'reports.index' ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">GHG inventory</span>
        </a>
    @endif

    @if ($canViewDisclosures)
        <a href="{{ route('disclosures.hub') }}"
           class="mnz-nav {{ $on('disclosures.hub', 'reports.hub') ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Disclosure hub</span>
        </a>
        <a href="{{ route('disclosures.uae-esg.overview') }}"
           class="mnz-nav {{ $on('disclosures.uae-esg', 'reports.uae-esg') ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">UAE ESG report</span>
        </a>
        <a href="{{ route('disclosures.gri.overview') }}"
           class="mnz-nav {{ $on('disclosures.gri', 'reports.gri') ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">GRI</span>
        </a>
        <a href="{{ route('disclosures.s2.overview') }}"
           class="mnz-nav {{ $on('disclosures.s2.overview', 'reports.s2') ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">IFRS S2</span>
        </a>
        <a href="{{ route('disclosures.s1.overview') }}"
           class="mnz-nav {{ $on('disclosures.s1.overview', 'reports.s1') ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">IFRS S1</span>
        </a>
    @endif
</div>

{{-- Settings --}}
<div class="mnz-side__foot">
    <div class="mnz-side__title">Settings</div>

    <a href="{{ route('settings.reporting') }}"
       class="mnz-nav {{ $on('settings.') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Reporting</span>
    </a>
    @if ($canViewStaff || $canViewRoles)
        <a href="{{ route('roles.index') }}"
           class="mnz-nav {{ $on('roles.', 'staff.') ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Team &amp; access</span>
        </a>
    @endif
    <a href="{{ route('client.profile') }}"
       class="mnz-nav {{ $on('client.profile', 'profile.') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Profile</span>
    </a>
    <a href="{{ route('subscriptions.billing') }}"
       class="mnz-nav {{ $on('subscriptions.') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Billing</span>
    </a>
    <a href="{{ route('client.consultants.index') }}"
       class="mnz-nav {{ $on('client.consultants.') ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Find a consultant</span>
    </a>
    <a href="{{ route('client.help') }}"
       class="mnz-nav {{ $routeName === 'client.help' ? 'is-active' : '' }}">
        <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Help &amp; guide</span>
    </a>

    {{-- Escape hatch back to the admin portal, as in the old nav. --}}
    @if ($user && method_exists($user, 'isAdmin') && $user->isAdmin())
        <a href="{{ route('admin.dashboard') }}"
           class="mnz-nav {{ $on('admin.') ? 'is-active' : '' }}">
            <span class="mnz-nav__dot"></span><span class="mnz-nav__label">Admin portal</span>
            <span class="mnz-nav__meta">↗</span>
        </a>
    @endif
</div>
