<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin | MENetZero')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        const BRAND = {
            50:  '#ecfdf5', 100: '#d1fae5', 200: '#a7f3d0', 300: '#6ee7b7',
            400: '#34d399', 500: '#10b981', 600: '#059669', 700: '#047857',
            800: '#065f46', 900: '#064e3b',
        };
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: BRAND[500], dark: BRAND[600], soft: BRAND[50], ...BRAND },
                        // Historic off-brand accents now map to brand emerald
                        purple: BRAND,
                        violet: BRAND,
                        indigo: BRAND,
                        orange: BRAND,
                        // Admin pages hardcode gray-* utilities; point them at
                        // the admin skin's cooler neutrals (admin-paygate.css).
                        gray: {
                            50: '#f8fafc', 100: '#f1f5f9', 200: '#e4e7ec', 300: '#d5dae1',
                            400: '#94a3b8', 500: '#64748b', 600: '#475569', 700: '#334155',
                            800: '#1e293b', 900: '#0f172a',
                        },
                    },
                    fontFamily: { sans: ['Poppins', 'Inter', 'system-ui', 'sans-serif'] },
                },
            },
        };
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    {{-- Cache-busted like the consultant shell: without a version the browser
         serves its cached copy and a CSS change appears to do nothing. --}}
    <link rel="stylesheet" href="{{ asset('css/app-shell.css') }}?v=20260902">
    {{-- Admin-only skin. Must load after app-shell.css: it re-points that
         file's tokens for this portal alone (scoped to body.admin-pg). --}}
    <link rel="stylesheet" href="{{ asset('css/admin-paygate.css') }}?v=20261013">
    @stack('head')
</head>
<body class="admin-pg">
    <div class="app-shell" x-data="{ sidebarOpen: false }">
        {{-- Mobile overlay --}}
        <div class="mobile-overlay"
             :class="{ 'is-open': sidebarOpen }"
             @click="sidebarOpen = false"
             aria-hidden="true"></div>

        {{-- Sidebar --}}
        <aside class="sidebar"
               :class="{ 'is-open': sidebarOpen }"
               @keydown.escape.window="sidebarOpen = false">
            <div class="sidebar-header">
                <a href="{{ route('admin.dashboard') }}" class="brand-logo">
                    <img src="{{ asset('images/menetzero.svg') }}" alt="MENetZero">
                </a>
                <span class="pg-portal-label">Super Admin console</span>
            </div>

            <nav class="flex-1 overflow-y-auto py-2">
                @include('admin.partials.nav')
            </nav>

            <div class="sidebar-footer">
                @php $admin = auth('admin')->user(); @endphp
                <div class="pg-user">
                    <span class="pg-avatar" aria-hidden="true">{{ strtoupper(mb_substr($admin?->name ?? 'A', 0, 1)) }}</span>
                    <span class="min-w-0">
                        <span class="pg-user-name truncate">{{ $admin?->name ?? 'Admin' }}</span>
                        <span class="pg-user-role">Super Admin</span>
                    </span>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="pg-icon-btn" title="Logout" aria-label="Logout">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main content --}}
        <div class="main-content">
            <header class="header">
                <button type="button"
                        class="mobile-menu-btn"
                        @click="sidebarOpen = !sidebarOpen"
                        :aria-expanded="sidebarOpen"
                        aria-label="Toggle navigation">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <span class="pg-crumb">Admin</span>
                <span class="pg-crumb-sep" aria-hidden="true">/</span>
                <h1 class="page-title truncate">@yield('page-title', 'Admin Dashboard')</h1>
                <span class="pg-topbar-chip">Super Admin</span>
            </header>

            <main class="content-area">
                @if(session('success') || session('error'))
                    <div class="flash-stack">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if(session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif
                    </div>
                @endif

                @yield('content')
            </main>

            @include('layouts.partials.site-footer')
        </div>
    </div>

    @stack('scripts')
</body>
</html>
