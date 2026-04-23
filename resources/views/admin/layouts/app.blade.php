<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin | MENetZero')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                    },
                    fontFamily: { sans: ['Inter', 'Poppins', 'system-ui', 'sans-serif'] },
                },
            },
        };
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="{{ asset('css/app-shell.css') }}">
    @stack('head')
</head>
<body class="bg-slate-50">
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
            <div class="sidebar-header" style="flex-direction: column; align-items: flex-start; gap: 0.125rem; padding-top: 0.75rem; padding-bottom: 0.75rem; height: auto; min-height: var(--header-height);">
                <a href="{{ route('admin.dashboard') }}" class="brand-logo">
                    <img src="{{ asset('images/menetzero.svg') }}" alt="MENetZero">
                </a>
                <span class="text-[10px] font-semibold text-brand-dark uppercase tracking-widest pl-1">Super Admin</span>
            </div>

            <nav class="flex-1 overflow-y-auto py-2">
                @include('admin.partials.nav')
            </nav>

            <div class="sidebar-footer">
                @php $admin = auth('admin')->user(); @endphp
                <span class="truncate">{{ $admin?->name ?? 'Admin' }}</span>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="text-red-600 hover:text-red-700 whitespace-nowrap font-medium">
                        Logout
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
                <h1 class="page-title truncate">@yield('page-title', 'Admin Dashboard')</h1>
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
        </div>
    </div>

    @stack('scripts')
</body>
</html>
