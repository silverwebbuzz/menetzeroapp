{{--
    MENetZero 2.0 — admin portal shell (Phase 4).

    Overrides admin/layouts/app.blade.php under the new theme. Honours the
    same section contract: title, page-title, content.

    Rules applied from the Phase 3 hotfix (documentation/redesign.md §16):
      1. Keeps the old stylesheets — admin page bodies are not migrated yet.
      2. DOES render page-title — unlike the consultant shell, the admin
         shell it replaces renders it in the header, and admin pages do NOT
         render their own <h1>. Dropping it would lose every heading.
      3. No `bg-slate-50` on <body> — Tailwind utility, no specificity trap,
         but the shell paints its own background.
      4. Re-asserts --ink / --canvas, which app-shell.css also defines.

    Also preserves the flash-message block the old shell renders above
    @yield('content').
--}}
@php
    $admin = auth('admin')->user();
    $adminInitial = strtoupper(substr($admin->name ?? 'A', 0, 1));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin | MENetZero')</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌱</text></svg>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    {{-- FALLBACK CSS — risk R-3. Admin page bodies still render their
         existing markup (Tailwind utilities, .btn, .alert, table styles),
         so these must stay until Phase 4 page bodies are migrated.
         mnz-ui.css is `mnz-` prefixed and loads after, so the shell wins. --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/portal-design-system.css') }}?v=20260630">

    @foreach ($themeAssets['css'] ?? [] as $themeCss)
        <link rel="stylesheet" href="{{ $themeCss }}">
    @endforeach

    <style>
        /* app-shell.css defines --ink / --canvas on :root with different
           values than mnz-ui.css. This <style> loads last, so re-asserting
           them here keeps the shell on the 2.0 palette while old page
           bodies keep their class-scoped rules. */
        :root {
            --ink: #14161a; --ink-2: #5a6068; --ink-3: #8b9199; --ink-4: #a4a9ae;
            --line: #e5e6e3; --line-2: #f0f0ee; --line-3: #d6d7d3;
            --surface: #fff; --canvas: #fafaf9; --canvas-2: #f4f4f2;
        }

        /* Admin reads neutral-ink, distinct from company green and
           consultant blue. data-pillar drives --accent in mnz-ui.css. */
        .mnz-topbar__badge { display: inline-flex; align-items: center; gap: 7px;
            border: 1px solid var(--line); background: var(--canvas-2);
            padding: 4px 9px; border-radius: 5px; flex-shrink: 0; }
        .mnz-topbar__badge span:first-child { width: 6px; height: 6px;
            border-radius: 2px; background: var(--ink); }
        .mnz-topbar__badge span:last-child { font: 600 10.5px var(--mono);
            letter-spacing: .1em; color: var(--ink); text-transform: uppercase;
            white-space: nowrap; }
        .mnz-topbar__rule { width: 1px; height: 22px; background: var(--line); flex-shrink: 0; }
        .mnz-topbar__spacer { margin-left: auto; }
        .mnz-topbar__user { display: flex; align-items: center; gap: 9px; min-width: 0; }
        .mnz-topbar__avatar { width: 28px; height: 28px; border-radius: 5px;
            background: var(--canvas-2); color: var(--ink); display: flex;
            align-items: center; justify-content: center;
            font: 600 12px var(--sans); flex-shrink: 0; }
        .mnz-topbar__name { font-size: 12.5px; font-weight: 500; overflow: hidden;
            text-overflow: ellipsis; white-space: nowrap; }
        .mnz-signout { border: 1px solid var(--line); background: var(--surface);
            color: var(--ink-2); height: 30px; padding: 0 11px; border-radius: 5px;
            font: 500 12px var(--sans); cursor: pointer; white-space: nowrap; }
        .mnz-signout:hover { color: var(--bad); border-color: var(--bad-line); }

        .mnz-side { overflow-y: auto; }
        .mnz-side__title { font: 500 10px var(--mono); letter-spacing: .1em;
            color: var(--ink-4); text-transform: uppercase; padding: 0 12px 6px; }
        .mnz-side__group + .mnz-side__group { margin-top: 4px; }

        .mnz-menu-btn { display: none; border: 1px solid var(--line);
            background: var(--surface); width: 32px; height: 30px; border-radius: 5px;
            cursor: pointer; align-items: center; justify-content: center; }

        /* Flash messages, matching the old shell's .flash-stack position. */
        .mnz-flash { display: flex; flex-direction: column; gap: 8px; margin-bottom: 18px; }
        .mnz-flash__item { border: 1px solid var(--line); background: var(--surface);
            padding: 11px 13px; border-radius: 6px; font-size: 12.5px; }
        .mnz-flash__item--ok { border-color: var(--ok-line); background: var(--ok-tint); color: var(--ok); }
        .mnz-flash__item--bad { border-color: var(--bad-line); background: var(--bad-tint); color: var(--bad); }

        @media (max-width: 900px) {
            .mnz-menu-btn { display: inline-flex; }
            .mnz-side { position: fixed; inset: 0 auto 0 0; z-index: 40;
                transform: translateX(-100%); transition: transform .18s ease; }
            .mnz-side.is-open { transform: none;
                box-shadow: 0 0 0 100vmax rgba(20,22,26,.32); }
            .mnz-main { padding: 20px 16px 32px; }
        }
        @media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
    </style>

    @stack('styles')
    @stack('head')
</head>
<body class="mnz-theme" data-pillar="neutral">
<div class="mnz-app" x-data="{ sidebarOpen: false }">

    <header class="mnz-topbar">
        <div class="mnz-topbar__row">
            <button type="button" class="mnz-menu-btn" @click="sidebarOpen = !sidebarOpen"
                    :aria-expanded="sidebarOpen" aria-label="Toggle navigation">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:16px;height:16px">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <a href="{{ route('admin.dashboard') }}">
                <img src="{{ asset('images/menetzero.svg') }}" alt="MENetZero" class="mnz-topbar__logo">
            </a>
            <span class="mnz-topbar__rule"></span>
            <span class="mnz-topbar__badge"><span></span><span>Super Admin</span></span>

            <span class="mnz-topbar__spacer"></span>

            <div class="mnz-topbar__user">
                <span class="mnz-topbar__avatar">{{ $adminInitial }}</span>
                <span class="mnz-topbar__name">{{ $admin?->name ?? 'Admin' }}</span>
            </div>

            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="mnz-signout">Logout</button>
            </form>
        </div>
    </header>

    <div class="mnz-body">
        <aside class="mnz-side" :class="{ 'is-open': sidebarOpen }"
               @keydown.escape.window="sidebarOpen = false">
            @include('theme-new::admin.partials.nav')
        </aside>

        <main class="mnz-main">
            {{-- Admin pages do NOT render their own <h1>; the shell owns the
                 page heading. Dropping this would lose every heading. --}}
            <div class="mnz-pagehead">
                <h1>@yield('page-title', 'Admin Dashboard')</h1>
                <div class="mnz-pagehead__actions">@yield('page-actions')</div>
            </div>

            @if (session('success') || session('error'))
                <div class="mnz-flash">
                    @if (session('success'))
                        <div class="mnz-flash__item mnz-flash__item--ok">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="mnz-flash__item mnz-flash__item--bad">{{ session('error') }}</div>
                    @endif
                </div>
            @endif

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
