{{--
    MENetZero 2.0 — auth shell (Phase 1).

    A namespaced sibling of layouts/portal-auth.blade.php. It honours the
    exact same section contract, so every auth page renders through either
    shell unchanged:

        title, content, sidebar

    Split layout: form on the left, dark statistics panel on the right.
    Portal accent comes from $portalVariant (company / consultant / admin),
    matching the existing variable the old shell already receives.
--}}
@php
    $portalVariant = $portalVariant ?? 'company';
    $accent = match ($portalVariant) {
        'consultant' => '#1a6c9e',
        'admin' => '#14161a',
        default => '#0f7a4a',
    };
    $portalBadge = match ($portalVariant) {
        'consultant' => 'Consultant portal',
        'admin' => 'Platform administration',
        default => null,
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MENetZero')</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌱</text></svg>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    @foreach ($themeAssets['css'] ?? [] as $themeCss)
        <link rel="stylesheet" href="{{ $themeCss }}">
    @endforeach

    <style>
        :root { --mnz-auth-accent: {{ $accent }}; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: 'Inter Tight', system-ui, -apple-system, sans-serif;
            font-size: 13.5px; line-height: 1.45; color: #14161a;
            background: #fafaf9; -webkit-font-smoothing: antialiased;
            font-variant-numeric: tabular-nums;
        }
        *, *::before, *::after { box-sizing: border-box; }
        a { color: var(--mnz-auth-accent); text-decoration: none; }
        a:hover { opacity: .82; }

        .mnz-auth { min-height: 100vh; display: grid;
            grid-template-columns: 1fr 1fr; align-items: stretch; }
        .mnz-auth__form { display: flex; flex-direction: column;
            padding: 44px 56px; background: #fff; min-width: 0; }
        .mnz-auth__inner { margin: auto 0; padding: 40px 0; max-width: 400px; width: 100%; }
        .mnz-auth__logo { height: 28px; width: auto; align-self: flex-start; flex-shrink: 0; }

        .mnz-auth__badge { display: inline-flex; align-items: center; gap: 7px;
            border: 1px solid #d6d7d3; background: #f4f4f2; padding: 3px 9px;
            border-radius: 999px; margin-bottom: 18px; }
        .mnz-auth__badge span:first-child { width: 6px; height: 6px; border-radius: 2px;
            background: var(--mnz-auth-accent); }
        .mnz-auth__badge span:last-child { font: 500 10.5px 'IBM Plex Mono', monospace;
            letter-spacing: .08em; color: var(--mnz-auth-accent); text-transform: uppercase; }

        .mnz-auth h1, .auth-title { font-size: 27px; font-weight: 600;
            letter-spacing: -.03em; margin: 0; }
        .auth-eyebrow { font: 500 10.5px 'IBM Plex Mono', monospace; letter-spacing: .1em;
            color: #a4a9ae; text-transform: uppercase; margin: 0 0 6px; }
        .auth-lead { margin: 7px 0 0; color: #5a6068; font-size: 13.5px; text-wrap: pretty; }

        .mnz-auth form, .mnz-auth .space-y-4 { display: flex; flex-direction: column;
            gap: 14px; margin-top: 26px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; margin: 0; }
        .form-label { font-size: 12.5px; font-weight: 500; margin: 0; }
        .form-input, .mnz-auth input[type=text], .mnz-auth input[type=email],
        .mnz-auth input[type=password], .mnz-auth select {
            width: 100%; height: 38px; padding: 0 11px; border: 1px solid #e5e6e3;
            border-radius: 6px; background: #fff; font: 400 13px 'Inter Tight';
            color: #14161a; transition: border-color .12s, box-shadow .12s;
        }
        .form-input:focus, .mnz-auth input:focus, .mnz-auth select:focus {
            outline: none; border-color: var(--mnz-auth-accent);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--mnz-auth-accent) 13%, transparent);
        }
        .btn, .mnz-auth button[type=submit] { height: 40px; border: 0; border-radius: 6px;
            background: var(--mnz-auth-accent); color: #fff; font: 500 13.5px 'Inter Tight';
            cursor: pointer; display: inline-flex; align-items: center;
            justify-content: center; gap: 9px; }
        .btn-secondary { border: 1px solid #e5e6e3; background: #fff; color: #14161a; }
        .btn-full { width: 100%; }

        .auth-divider { display: flex; align-items: center; gap: 12px; margin: 18px 0 0;
            font: 500 10.5px 'IBM Plex Mono', monospace; letter-spacing: .08em;
            color: #a4a9ae; text-transform: uppercase; }
        .auth-divider::before, .auth-divider::after { content: ''; flex: 1; height: 1px;
            background: #f0f0ee; }

        .alert { border: 1px solid #e5e6e3; background: #fafaf9; padding: 11px 13px;
            font-size: 12.5px; border-radius: 6px; }
        .alert-danger { border-color: #e8c4bb; background: #fdf1ee; color: #b4402b; }
        .alert-success { border-color: #bfe0cf; background: #eef7f2; color: #0f7a4a; }

        .auth-footer, .auth-footer-sub { font-size: 12.5px; color: #5a6068;
            margin: 6px 0 0; text-align: center; text-wrap: pretty; }

        .mnz-auth__foot { display: flex; align-items: center; justify-content: space-between;
            gap: 16px; flex-wrap: wrap; padding-top: 20px; border-top: 1px solid #f0f0ee;
            font-size: 12px; color: #a4a9ae; }
        .mnz-auth__foot a { color: #8b9199; margin-left: 14px; }

        .mnz-auth__panel { background: #14161a; color: #fff; padding: 44px 56px;
            display: flex; flex-direction: column; min-width: 0;
            position: relative; overflow: hidden; }
        .mnz-auth__grid { position: absolute; inset: 0;
            background-image: linear-gradient(#ffffff0d 1px, transparent 1px),
                              linear-gradient(90deg, #ffffff0d 1px, transparent 1px);
            background-size: 56px 56px; }
        .mnz-auth__kicker { position: relative; display: flex; align-items: center; gap: 9px;
            font: 500 10.5px 'IBM Plex Mono', monospace; letter-spacing: .12em;
            color: #8b9199; text-transform: uppercase; }
        .mnz-auth__kicker::before { content: ''; width: 7px; height: 7px; border-radius: 2px;
            background: var(--mnz-auth-accent); }
        .mnz-auth__panel-body { position: relative; margin: auto 0; padding: 32px 0; }
        .mnz-auth__panel-body ul { list-style: none; padding: 0;
            margin: 14px 0 0; display: flex; flex-direction: column; gap: 9px; }
        .mnz-auth__panel-body li { display: flex; gap: 10px; font-size: 13.5px;
            color: #c9cbc7; text-wrap: pretty; }
        .mnz-auth__panel-body li::before { content: '—'; color: var(--mnz-auth-accent);
            flex-shrink: 0; }
        .mnz-auth__panel-body p { font: 500 10.5px 'IBM Plex Mono', monospace;
            letter-spacing: .1em; color: #8b9199; text-transform: uppercase;
            margin: 22px 0 0; }
        .mnz-auth__panel-body p:first-child { margin-top: 0; }
        .mnz-auth__panel-body span { display: inline-flex; align-items: center; gap: 7px;
            font-size: 15px; color: #fff; letter-spacing: -.015em; }

        @media (max-width: 880px) {
            .mnz-auth { grid-template-columns: 1fr; }
            .mnz-auth__form { padding: 32px 24px; }
            .mnz-auth__panel { display: none; }
        }
        @media (prefers-reduced-motion: reduce) { * { animation: none !important; } }
    </style>

    @stack('head')
    @include('layouts.partials.google-analytics')
</head>
<body class="mnz-theme portal-auth portal-auth--{{ $portalVariant }}">
<div class="mnz-auth">

    <div class="mnz-auth__form">
        <a href="{{ $portalVariant === 'consultant' ? route('consultant.landing') : url('/') }}">
            <img src="{{ asset('images/menetzero.svg') }}" alt="MENetZero" class="mnz-auth__logo">
        </a>

        <div class="mnz-auth__inner">
            @if ($portalBadge)
                <div class="mnz-auth__badge"><span></span><span>{{ $portalBadge }}</span></div>
            @endif

            @yield('content')
        </div>

        <div class="mnz-auth__foot">
            <span>&copy; {{ date('Y') }} MENetZero — a brand of Silver Webbuzz PVT Ltd</span>
            <span>
                <a href="{{ route('privacy') }}">Privacy</a>
                <a href="{{ route('terms') }}">Terms</a>
                <a href="{{ route('contact') }}">Support</a>
            </span>
        </div>
    </div>

    <div class="mnz-auth__panel">
        <div class="mnz-auth__grid"></div>
        <div class="mnz-auth__kicker">{{ $portalBadge ?? 'Carbon accounting' }}</div>
        <div class="mnz-auth__panel-body">
            @hasSection('sidebar')
                @yield('sidebar')
            @else
                @yield('sidebar-content')
            @endif
        </div>
    </div>

</div>

@foreach ($themeAssets['js'] ?? [] as $themeJs)
    <script defer src="{{ $themeJs }}"></script>
@endforeach
@stack('scripts')
</body>
</html>
