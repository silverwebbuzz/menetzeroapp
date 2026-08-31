{{--
    MENetZero 2.0 — company portal shell (Phase 5.1).

    Overrides layouts/app.blade.php under the new theme. Honours the same
    section contract: title, content, sidebar.

    Rules applied from §16:
      1. Keeps the old stylesheets — 64 company page bodies are not migrated.
      2. Does NOT render page-title — like the consultant shell (and unlike
         admin), the company shell it replaces ignores it; company pages
         render their own headings.
      3. No `company-portal` class on <body> — avoids the specificity trap.
      4. Re-asserts --ink / --canvas, which app-shell.css also defines.

    Preserves every piece of shell state the old layout computes:
      - consultant acting-as banner, exit, and managed-client switcher
      - multi-company switcher for users with several companies
      - renewal nudge, flash alerts
      - guest branch (no auth: content renders bare)

    The sidebar reuses layouts.partials.nav-client unchanged: it is 21 KB of
    plan-gated navigation, and rewriting it is the six-tab IA change (5.3+),
    not shell work.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'MENetZero') }} - @yield('title', 'Carbon Emissions Tracking')</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌱</text></svg>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    {{-- FALLBACK CSS — risk R-3. Company page bodies still render existing
         markup (ent-*, btn, alert, card, Tailwind utilities). These stay
         until the Phase 5 page bodies are migrated. --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/app-shell.css') }}?v=20260824b">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/portal-design-system.css') }}?v=20260630">
    <link rel="stylesheet" href="{{ asset('css/portal-enterprise.css') }}?v=20260630">

    @foreach ($themeAssets['css'] ?? [] as $themeCss)
        <link rel="stylesheet" href="{{ $themeCss }}">
    @endforeach

    <style>
        /* app-shell.css defines --ink / --canvas on :root with different
           values than mnz-ui.css. This <style> loads last. */
        :root {
            --ink: #14161a; --ink-2: #5a6068; --ink-3: #8b9199; --ink-4: #a4a9ae;
            --line: #e5e6e3; --line-2: #f0f0ee; --line-3: #d6d7d3;
            --surface: #fff; --canvas: #fafaf9; --canvas-2: #f4f4f2;
        }

        .mnz-topbar__badge { display: inline-flex; align-items: center; gap: 7px;
            border: 1px solid var(--e-line); background: var(--e-tint);
            padding: 4px 9px; border-radius: 5px; flex-shrink: 0; }
        .mnz-topbar__badge span:first-child { width: 6px; height: 6px;
            border-radius: 2px; background: var(--e); }
        .mnz-topbar__badge span:last-child { font: 600 10.5px var(--mono);
            letter-spacing: .1em; color: var(--e); text-transform: uppercase;
            white-space: nowrap; }
        .mnz-topbar__rule { width: 1px; height: 22px; background: var(--line); flex-shrink: 0; }
        .mnz-topbar__spacer { margin-left: auto; }
        .mnz-topbar__ctx { font-size: 12.5px; font-weight: 500; min-width: 0;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .mnz-topbar__avatar { width: 28px; height: 28px; border-radius: 5px;
            background: var(--e-tint); color: var(--e); display: flex;
            align-items: center; justify-content: center;
            font: 600 12px var(--sans); flex-shrink: 0; }
        .mnz-signout { border: 1px solid var(--line); background: var(--surface);
            color: var(--ink-2); height: 30px; padding: 0 11px; border-radius: 5px;
            font: 500 12px var(--sans); cursor: pointer; white-space: nowrap; }
        .mnz-signout:hover { color: var(--bad); border-color: var(--bad-line); }

        /* Acting-as bar — a consultant working inside a client workspace.
           Amber, full width, always visible: it must never be missed. */
        .mnz-acting { background: var(--warn); color: #fff; padding: 0 24px;
            min-height: 38px; display: flex; align-items: center; gap: 12px;
            flex-wrap: wrap; flex-shrink: 0; }
        .mnz-acting__tag { font: 600 11px var(--mono); letter-spacing: .1em;
            text-transform: uppercase; white-space: nowrap; flex-shrink: 0; }
        .mnz-acting__text { font-size: 12.5px; min-width: 0; flex: 1; }
        .mnz-acting__btn { height: 26px; padding: 0 10px;
            border: 1px solid rgba(255,255,255,.35); background: rgba(255,255,255,.12);
            color: #fff; border-radius: 5px; font: 500 12px var(--sans);
            cursor: pointer; white-space: nowrap; flex-shrink: 0; }

        .mnz-side { overflow-y: auto; }
        /* Used by nav-client's six-tab section labels. The consultant and
           admin shells define this too; without it the labels render as
           plain body text. */
        .mnz-side__title { font: 500 10px var(--mono); letter-spacing: .1em;
            color: var(--ink-4); text-transform: uppercase; padding: 0 12px 6px; }
        .mnz-side__group + .mnz-side__group { margin-top: 4px; }
        .mnz-menu-btn { display: none; border: 1px solid var(--line);
            background: var(--surface); width: 32px; height: 30px; border-radius: 5px;
            cursor: pointer; align-items: center; justify-content: center; }

        .mnz-flash { display: flex; flex-direction: column; gap: 8px; margin-bottom: 18px; }

        .mnz-dropdown { position: relative; }
        .mnz-dropdown__menu { position: absolute; right: 0; top: calc(100% + 6px);
            min-width: 240px; background: var(--surface); border: 1px solid var(--line);
            border-radius: 6px; box-shadow: 0 8px 24px rgba(20,22,26,.10);
            padding: 5px; z-index: 50; }
        .mnz-dropdown__head { font: 500 10px var(--mono); letter-spacing: .1em;
            color: var(--ink-4); text-transform: uppercase; padding: 7px 9px 5px; }
        .mnz-dropdown__item { display: block; width: 100%; text-align: left;
            border: 0; background: none; padding: 7px 9px; border-radius: 5px;
            font: 400 12.5px var(--sans); color: var(--ink); cursor: pointer; }
        .mnz-dropdown__item:hover { background: var(--canvas-2); }
        .mnz-dropdown__item.is-active { background: var(--e-tint); color: var(--e); font-weight: 600; }

        @media (max-width: 900px) {
            .mnz-menu-btn { display: inline-flex; }
            .mnz-side { position: fixed; inset: 0 auto 0 0; z-index: 40;
                transform: translateX(-100%); transition: transform .18s ease; }
            .mnz-side.is-open { transform: none;
                box-shadow: 0 0 0 100vmax rgba(20,22,26,.32); }
            .mnz-main { padding: 20px 16px 32px; }
            .mnz-topbar__ctx { display: none; }
        }
        @media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
    </style>

    {{-- Chart.js loads in <head>, matching the shell this replaces.
         Company pages call `new Chart(...)` from body-level scripts, so a
         bottom-of-body load would be too late. --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

    @stack('head')
    @include('layouts.partials.google-analytics')
</head>
<body class="mnz-theme" data-pillar="e">
<div class="mnz-app" x-data="{ sidebarOpen: false }">

@auth('web')
    @php
        $user = auth('web')->user();
        $consultantWorkspace = app(\App\Services\ConsultantAgencyWorkspaceService::class);
        $isConsultantActing = $user && $consultantWorkspace->isActingAsManagedClient($user);
        $consultantActingEngagement = $isConsultantActing ? $consultantWorkspace->engagementForActing($user) : null;
        $consultantReadOnly = $isConsultantActing && $consultantWorkspace->isReadOnlyWorkspace();
        $consultantSwitchableClients = $isConsultantActing ? $consultantWorkspace->switchableEngagements($user) : collect();
        $activeCompany = $user ? $user->getActiveCompany() : null;
        $accessibleCompanies = $user ? $user->getAccessibleCompanies() : collect([]);
        $hasCompany = $activeCompany !== null;
        $userInitial = strtoupper(substr($user->name ?? '?', 0, 1));
    @endphp

    @if ($isConsultantActing)
        <div class="mnz-acting">
            <span class="mnz-acting__tag">{{ $consultantReadOnly ? 'Read-only' : 'Acting as' }}</span>
            <span class="mnz-acting__text">
                {{ $activeCompany?->name }}
                @if ($consultantActingEngagement)
                    · PRY {{ $consultantActingEngagement->primary_reporting_year }}
                @endif
                — every change is recorded against your agency
            </span>

            @if ($consultantSwitchableClients->count() > 1)
                <div class="mnz-dropdown" x-data="{ open: false }" @click.away="open = false">
                    <button type="button" class="mnz-acting__btn" @click="open = !open" :aria-expanded="open">
                        Switch client
                    </button>
                    <div class="mnz-dropdown__menu" x-show="open" x-transition style="display:none">
                        <div class="mnz-dropdown__head">Managed clients</div>
                        @foreach ($consultantSwitchableClients as $engagement)
                            <form action="{{ route('consultant.workspace.enter', $engagement) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="mnz-dropdown__item {{ $activeCompany && $activeCompany->id == $engagement->managed_company_id ? 'is-active' : '' }}">
                                    {{ $engagement->display_name ?: $engagement->managedCompany?->name }}
                                    · PRY {{ $engagement->primary_reporting_year }}
                                </button>
                            </form>
                        @endforeach
                        <a href="{{ route('consultant.workspace.switcher') }}" class="mnz-dropdown__item">All workspaces…</a>
                    </div>
                </div>
            @endif

            <form action="{{ route('consultant.workspace.exit') }}" method="POST">
                @csrf
                <button type="submit" class="mnz-acting__btn">Back to Agency Hub</button>
            </form>
        </div>
    @endif

    <header class="mnz-topbar">
        <div class="mnz-topbar__row">
            <button type="button" class="mnz-menu-btn" @click="sidebarOpen = !sidebarOpen"
                    :aria-expanded="sidebarOpen" aria-label="Toggle navigation">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:16px;height:16px">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <a href="{{ route('client.dashboard') }}">
                <img src="{{ asset('images/menetzero.svg') }}" alt="MENetZero" class="mnz-topbar__logo">
            </a>
            <span class="mnz-topbar__rule"></span>
            <span class="mnz-topbar__ctx">{{ $activeCompany?->name ?? config('app.name', 'MENetZero') }}</span>

            <span class="mnz-topbar__spacer"></span>

            {{-- Reporting-year context (Phase B): app-level, not per-link. --}}
            @include('layouts.partials.reporting-year-switcher')

            @if (Route::has('client.zero-ai'))
                <a href="{{ route('client.zero-ai') }}" class="mnz-btn mnz-btn--ghost">Zero AI</a>
            @endif

            @if (!$isConsultantActing && $accessibleCompanies->count() > 1)
                <div class="mnz-dropdown" x-data="{ open: false }" @click.away="open = false">
                    <button type="button" class="mnz-signout" @click="open = !open" :aria-expanded="open">
                        {{ $activeCompany?->name ?? 'Select company' }}
                    </button>
                    <div class="mnz-dropdown__menu" x-show="open" x-transition style="display:none">
                        <div class="mnz-dropdown__head">Your companies</div>
                        {{-- getAccessibleCompanies() returns a collection of
                             ARRAYS, not models — ['id','name','type','role_name',
                             'is_owner','company']. Object access here would be a
                             fatal error. --}}
                        @foreach ($accessibleCompanies as $company)
                            <form action="{{ route('account.switch') }}" method="POST">
                                @csrf
                                <input type="hidden" name="company_id" value="{{ $company['id'] }}">
                                <button type="submit"
                                        class="mnz-dropdown__item {{ $activeCompany && $activeCompany->id == $company['id'] ? 'is-active' : '' }}">
                                    {{ $company['name'] }}
                                    <span style="color:var(--ink-3)">· {{ $company['role_name'] }}</span>
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            @endif

            <span class="mnz-topbar__avatar">{{ $userInitial }}</span>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="mnz-signout">Sign out</button>
            </form>
        </div>
    </header>

    {{-- Six-pillar tab bar. Sits between the topbar and the body so it spans
         the full width, above BOTH the sidebar and the content -- the sidebar
         then shows only the active pillar's items. Rendered from the same
         config/navigation.php as the sidebar, via NavigationMap::tabs(). --}}
    @include('theme-new::layouts.partials.nav-tabs')

    <div class="mnz-body">
        <aside class="mnz-side" :class="{ 'is-open': sidebarOpen }"
               @keydown.escape.window="sidebarOpen = false">
            @include('layouts.partials.nav-client')
        </aside>

        <main class="mnz-main">
            @if (session('success') || session('error'))
                <div class="mnz-flash">
                    @if (session('success'))
                        <x-alert type="success" dismissible="true">{{ session('success') }}</x-alert>
                    @endif
                    @if (session('error'))
                        <x-alert type="error" dismissible="true">{{ session('error') }}</x-alert>
                    @endif
                </div>
            @endif

            @if (!empty($companyRenewalNudge['show']))
                <div class="mnz-panel" style="border-color:var(--warn-line);background:var(--warn-tint);margin-bottom:18px">
                    <div class="mnz-panel__body" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between">
                        <span style="font-size:12.5px;color:var(--warn)">
                            <strong>Renewal window</strong> — {{ $companyRenewalNudge['plan_name'] }}
                            expires {{ $companyRenewalNudge['expires_at']->format('d M Y') }}
                            ({{ $companyRenewalNudge['days_left'] }} days). Pricing is confirmed offline.
                        </span>
                        <a href="{{ $companyRenewalNudge['request_url'] }}" class="mnz-btn mnz-btn--primary">Request a package</a>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
@else
    {{-- Guest branch: the old shell renders content bare with a footer. --}}
    <main class="mnz-main">
        @yield('content')
    </main>
@endauth

</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@foreach ($themeAssets['js'] ?? [] as $themeJs)
    <script defer src="{{ $themeJs }}"></script>
@endforeach
@stack('scripts')
</body>
</html>
