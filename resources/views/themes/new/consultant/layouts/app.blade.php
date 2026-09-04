{{--
    MENetZero 2.0 — consultant portal shell (Phase 3).

    Overrides consultant/layouts/app.blade.php under the new theme. Honours
    the same section contract as the shell it replaces, so every consultant
    page renders through either without edits: title, content, page-title.

    Structure follows mnz-ui.css:
        .mnz-app > .mnz-topbar + .mnz-body > (.mnz-side + .mnz-main)

    Note .mnz-body is the CONTENT ROW, not the <body> element. Applying it
    to <body> breaks the layout — that mistake cost a hotfix in Phase 1.
--}}
@php
    $consultant = auth('consultant')->user();
    $consultantInitial = strtoupper(substr($consultant->name ?? '?', 0, 1));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'MENetZero') }} — @yield('title', 'Consultant Portal')</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌱</text></svg>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    {{-- FALLBACK CSS — risk R-3 in documentation/redesign.md.
         Page bodies are migrated one at a time. Until a page has a themed
         view, it renders its EXISTING markup inside this shell, and that
         markup depends on the old stylesheets (ent-kpi-card, ent-grid-6,
         btn, cd-notice) plus Tailwind utilities. Dropping them leaves an
         unstyled wall of text.

         These stay until Phase 3 page bodies are migrated. mnz-ui.css is
         `mnz-` prefixed throughout, so it never collides with them, and it
         loads AFTER so the shell always wins. --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        /* Brand palette for the UNMIGRATED page bodies this shell hosts.
           Those bodies are Tailwind-utility markup written against the old
           consultant shell, which extends the palette below. Loading the CDN
           without this config left `text-brand`/`bg-brand` (36 uses) matching
           no rule at all -- a primary button rendered white-on-white -- and
           the teal/indigo aliases (74 uses) falling back to literal Tailwind
           teal instead of MENetZero blue.

           Keep in step with consultant/layouts/app.blade.php until the
           Phase 3 page bodies are themed; delete it when none remain.
           fontFamily is deliberately NOT overridden here -- the 2.0 shell
           sets its own Inter Tight / IBM Plex Mono typography below. */
        const BRAND = {
            50:  '#eef4fe',
            100: '#dce9fd',
            200: '#b9d3fb',
            300: '#8cb8f8',
            400: '#5a97f2',
            500: '#1563eb',
            600: '#1254c9',
            700: '#0f459f',
            800: '#0c3678',
            900: '#021d71',
        };
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: BRAND[500], dark: BRAND[900], soft: BRAND[50], ...BRAND },
                        indigo: BRAND,
                        purple: BRAND,
                        violet: BRAND,
                        teal: BRAND,
                        emerald: BRAND,
                        blue: BRAND,
                    },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="{{ asset('css/app-shell.css') }}?v=20260904b">
    <link rel="stylesheet" href="{{ asset('css/consultant-shell.css') }}?v=20260630">
    <link rel="stylesheet" href="{{ asset('css/portal-design-system.css') }}?v=20260630">
    <link rel="stylesheet" href="{{ asset('css/portal-enterprise.css') }}?v=20260630">

    @foreach ($themeAssets['css'] ?? [] as $themeCss)
        <link rel="stylesheet" href="{{ $themeCss }}">
    @endforeach

    <style>
        /* The old stylesheets define --ink / --canvas on :root with their
           own values, and mnz-ui.css reuses those names. Re-assert the
           MENetZero 2.0 values here (this <style> loads last) so the shell
           renders with its own palette while old page bodies keep their
           class-scoped rules. */
        :root {
            --ink: #14161a; --ink-2: #5a6068; --ink-3: #8b9199; --ink-4: #a4a9ae;
            --line: #e5e6e3; --line-2: #f0f0ee; --line-3: #d6d7d3;
            --surface: #fff; --canvas: #fafaf9; --canvas-2: #f4f4f2;
        }

        /* Consultant portal reads as Social-blue; the company portal is
           Environmental-green. data-pillar drives --accent in mnz-ui.css. */
        .mnz-topbar__badge { display: inline-flex; align-items: center; gap: 7px;
            border: 1px solid var(--s-line); background: var(--s-tint);
            padding: 4px 9px; border-radius: 5px; flex-shrink: 0; }
        .mnz-topbar__badge span:first-child { width: 6px; height: 6px;
            border-radius: 2px; background: var(--s); }
        .mnz-topbar__badge span:last-child { font: 600 10.5px var(--mono);
            letter-spacing: .1em; color: var(--s); text-transform: uppercase;
            white-space: nowrap; }
        .mnz-topbar__rule { width: 1px; height: 22px; background: var(--line); flex-shrink: 0; }
        .mnz-topbar__spacer { margin-left: auto; }
        .mnz-topbar__user { display: flex; align-items: center; gap: 9px; min-width: 0; }
        .mnz-topbar__avatar { width: 28px; height: 28px; border-radius: 5px;
            background: var(--s-tint); color: var(--s); display: flex;
            align-items: center; justify-content: center;
            font: 600 12px var(--sans); flex-shrink: 0; }
        .mnz-topbar__name { font-size: 12.5px; font-weight: 500; overflow: hidden;
            text-overflow: ellipsis; white-space: nowrap; }
        .mnz-topbar__org { font: 500 10.5px var(--mono); color: var(--ink-3);
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .mnz-signout { border: 1px solid var(--line); background: var(--surface);
            color: var(--ink-2); height: 30px; padding: 0 11px; border-radius: 5px;
            font: 500 12px var(--sans); cursor: pointer; white-space: nowrap; }
        .mnz-signout:hover { color: var(--bad); border-color: var(--bad-line); }

        .mnz-side__title { font: 500 10px var(--mono); letter-spacing: .1em;
            color: var(--ink-4); text-transform: uppercase; padding: 0 12px 6px; }
        .mnz-side__group + .mnz-side__group { margin-top: 4px; }

        .mnz-menu-btn { display: none; border: 1px solid var(--line);
            background: var(--surface); width: 32px; height: 30px; border-radius: 5px;
            cursor: pointer; align-items: center; justify-content: center; }

        @media (max-width: 900px) {
            .mnz-menu-btn { display: inline-flex; }
            .mnz-side { position: fixed; inset: 0 auto 0 0; z-index: 40;
                transform: translateX(-100%); transition: transform .18s ease;
                box-shadow: 0 0 0 100vmax rgba(20,22,26,0); }
            .mnz-side.is-open { transform: none;
                box-shadow: 0 0 0 100vmax rgba(20,22,26,.32); }
            .mnz-main { padding: 20px 16px 32px; }
            .mnz-topbar__org { display: none; }
        }
        @media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
    </style>

    @stack('styles')
    @stack('head')
    @include('layouts.partials.google-analytics')
</head>
{{-- No `consultant-portal` class: consultant-shell.css defines
     `body.consultant-portal` whose specificity beats mnz-ui.css's bare
     `body`, overriding background and colour regardless of load order.
     Old page bodies do not need that class — their own `ent-*` and `.btn`
     rules are class-scoped and still apply. --}}
<body class="mnz-theme" data-pillar="s">
<div class="mnz-app" x-data="{ sidebarOpen: false }">

    <header class="mnz-topbar">
        <div class="mnz-topbar__row">
            <button type="button" class="mnz-menu-btn" @click="sidebarOpen = !sidebarOpen"
                    :aria-expanded="sidebarOpen" aria-label="Toggle navigation">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:16px;height:16px">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <a href="{{ route('consultant.dashboard') }}">
                <img src="{{ asset('images/menetzero.svg') }}" alt="MENetZero" class="mnz-topbar__logo">
            </a>
            <span class="mnz-topbar__rule"></span>
            <span class="mnz-topbar__badge"><span></span><span>Consultant</span></span>

            <span class="mnz-topbar__spacer"></span>

            @if (Route::has('consultant.zero-ai'))
                <a href="{{ route('consultant.zero-ai') }}" class="mnz-btn mnz-btn--ghost">Zero AI</a>
            @endif

            <div class="mnz-topbar__user">
                <span class="mnz-topbar__avatar">{{ $consultantInitial }}</span>
                <span style="min-width:0">
                    <span class="mnz-topbar__name">{{ $consultant->name ?? '' }}</span>
                    <span class="mnz-topbar__org">{{ $consultant->company_name ?? '' }}</span>
                </span>
            </div>

            <form action="{{ route('consultant.logout') }}" method="POST">
                @csrf
                <button type="submit" class="mnz-signout">Sign out</button>
            </form>
        </div>
    </header>

    <div class="mnz-body">
        <aside class="mnz-side" :class="{ 'is-open': sidebarOpen }"
               @keydown.escape.window="sidebarOpen = false">
            @include('theme-new::layouts.partials.nav-consultant')
        </aside>

        <main class="mnz-main">
            {{-- NOTE: page-title is deliberately NOT rendered here.
                 The consultant shell this replaces ignores it too — consultant
                 pages render their own <h1 class="ent-page-title"> inside
                 their content section. Rendering it here duplicated every
                 heading. Themed page bodies (3.2-3.5) will own their headers
                 the same way. --}}
            @yield('content')
        </main>
    </div>

</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@foreach ($themeAssets['js'] ?? [] as $themeJs)
    <script defer src="{{ $themeJs }}"></script>
@endforeach
@stack('scripts')
</body>
</html>
