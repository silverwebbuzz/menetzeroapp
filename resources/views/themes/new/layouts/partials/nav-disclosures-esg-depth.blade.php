{{--
    MENetZero 2.0 — ESG depth sub-nav (Phase 5.4/5.5).

    Same links, same order, same active logic as
    layouts/partials/nav-disclosures-esg-depth.blade.php. Every link carries
    the fiscal_year query param, which the disclosure controllers read via
    resolveContext() — dropping it would silently reset the user's year.
--}}
@php $q = ['fiscal_year' => $fiscalYear ?? now()->year]; @endphp

<nav class="mnz-tabs" aria-label="ESG depth">
    <a href="{{ route('disclosures.hub', $q) }}" class="mnz-tab">← Disclosures</a>

    <a href="{{ route('disclosures.esg-depth.overview', $q) }}"
       class="mnz-tab {{ request()->routeIs('disclosures.esg-depth.*') ? 'is-active' : '' }}">
        <span class="mnz-tab__dot"></span>Overview
    </a>
    <a href="{{ route('disclosures.stakeholders.index', $q) }}"
       class="mnz-tab {{ request()->routeIs('disclosures.stakeholders.*') ? 'is-active' : '' }}">
        <span class="mnz-tab__dot"></span>Stakeholders
    </a>
    <a href="{{ route('disclosures.materiality-matrix.index', $q) }}"
       class="mnz-tab {{ request()->routeIs('disclosures.materiality-matrix.*') ? 'is-active' : '' }}">
        <span class="mnz-tab__dot"></span>Materiality
    </a>
    <a href="{{ route('disclosures.supply-chain.index', $q) }}"
       class="mnz-tab {{ request()->routeIs('disclosures.supply-chain.*') ? 'is-active' : '' }}">
        <span class="mnz-tab__dot"></span>Supply chain
    </a>
    <a href="{{ route('disclosures.esg-targets.index', $q) }}"
       class="mnz-tab {{ request()->routeIs('disclosures.esg-targets.*') ? 'is-active' : '' }}">
        <span class="mnz-tab__dot"></span>ESG targets
    </a>
    <a href="{{ route('disclosures.sasb.index', $q) }}"
       class="mnz-tab {{ request()->routeIs('disclosures.sasb.*') ? 'is-active' : '' }}">
        <span class="mnz-tab__dot"></span>SASB index
    </a>
    <a href="{{ route('disclosures.gri.sections.edit', array_merge($q, ['section' => 'health_safety'])) }}"
       class="mnz-tab">GRI 403 safety</a>
    <a href="{{ route('disclosures.gri.sections.edit', array_merge($q, ['section' => 'governance_metrics'])) }}"
       class="mnz-tab">Governance KPIs</a>
</nav>
