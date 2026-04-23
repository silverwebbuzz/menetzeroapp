<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌱</text></svg>">

    <title>{{ config('app.name', 'MenetZero') }} - @yield('title', 'Carbon Emissions Tracking')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CDN with brand theme extension -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Brand palette. We alias purple/violet/indigo to the brand colour so that
        // legacy templates that used purple/indigo classes automatically pick up
        // the correct MenetZero teal/emerald. Same for blue accents in buttons.
        const BRAND = {
            50:  '#ecfdf5',
            100: '#d1fae5',
            200: '#a7f3d0',
            300: '#6ee7b7',
            400: '#34d399',
            500: '#10b981',
            600: '#059669',
            700: '#047857',
            800: '#065f46',
            900: '#064e3b',
        };
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: BRAND[500], dark: BRAND[600], soft: BRAND[50], ...BRAND },
                        // Re-map the historic purple/violet/indigo usage to brand
                        purple: BRAND,
                        violet: BRAND,
                        indigo: BRAND,
                    },
                    fontFamily: {
                        sans: ['Inter', 'Poppins', 'system-ui', 'sans-serif'],
                    },
                },
            },
        };
    </script>

    <!-- App shell styles -->
    <link rel="stylesheet" href="{{ asset('css/app-shell.css') }}">

    @stack('styles')

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

    <!-- Alpine.js for dropdowns + sidebar -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('head')
</head>
<body class="antialiased">
    <div class="app-shell" x-data="{ sidebarOpen: false }">
        @auth('web')
            @php
                $user = auth('web')->user();
                $activeCompany = $user ? $user->getActiveCompany() : null;
                $accessibleCompanies = $user ? $user->getAccessibleCompanies() : collect([]);
                $hasCompany = $activeCompany !== null;
                $userInitial = strtoupper(substr($user->name ?? '?', 0, 1));
            @endphp

            <!-- Mobile overlay -->
            <div class="mobile-overlay"
                 :class="{ 'is-open': sidebarOpen }"
                 @click="sidebarOpen = false"
                 aria-hidden="true"></div>

            <!-- Sidebar -->
            <aside class="sidebar"
                   :class="{ 'is-open': sidebarOpen }"
                   @keydown.escape.window="sidebarOpen = false">
                <div class="sidebar-header">
                    <a href="{{ route('client.dashboard') }}" class="brand-logo">
                        <img src="{{ asset('images/menetzero.svg') }}" alt="MIDDLE EAST NET Zero">
                    </a>
                </div>

                <nav class="mt-2 px-1 flex-1 flex flex-col">
                    <div class="flex-1">
                        @include('layouts.partials.nav-client')
                    </div>
                </nav>
            </aside>

            <!-- Main content -->
            <div class="main-content">
                <!-- Top header -->
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

                    <h1 class="page-title truncate">@yield('page-title', 'Dashboard')</h1>

                    <div class="header-actions">
                        @if($accessibleCompanies->count() > 1)
                        <!-- Company switcher -->
                        <div class="relative" x-data="{ open: false }" @click.away="open = false">
                            <button type="button"
                                    class="header-btn"
                                    @click="open = !open"
                                    :aria-expanded="open">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <span class="header-btn-label truncate max-w-[12rem]">{{ $activeCompany ? $activeCompany->name : 'Select Company' }}</span>
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <div x-show="open" x-transition class="dropdown-menu" style="display: none;">
                                <div class="dropdown-heading">Companies</div>
                                @foreach($accessibleCompanies as $company)
                                    <form action="{{ route('account.switch') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="company_id" value="{{ $company['id'] }}">
                                        <button type="submit"
                                                class="dropdown-item {{ $activeCompany && $activeCompany->id == $company['id'] ? 'active' : '' }}">
                                            <div class="flex-1 min-w-0">
                                                <div class="font-medium truncate">{{ $company['name'] }}</div>
                                                <div class="text-xs text-gray-500 mt-0.5">
                                                    @if($company['is_owner'])
                                                        <span class="text-brand font-medium">Owner</span>
                                                    @else
                                                        {{ $company['role_name'] }}
                                                    @endif
                                                </div>
                                            </div>
                                            @if($activeCompany && $activeCompany->id == $company['id'])
                                                <svg class="w-4 h-4 text-brand flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- User menu -->
                        <div class="relative" x-data="{ open: false }" @click.away="open = false">
                            <button type="button"
                                    class="header-btn"
                                    @click="open = !open"
                                    :aria-expanded="open"
                                    aria-label="Account menu">
                                <span class="avatar">{{ $userInitial }}</span>
                                <span class="header-btn-label">{{ $user->name }}</span>
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <div x-show="open" x-transition class="dropdown-menu" style="display: none;">
                                <div class="px-3 py-2">
                                    <div class="font-medium text-sm truncate">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500 truncate">{{ $user->email }}</div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a href="{{ route('client.profile') }}" class="dropdown-item">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Profile
                                </a>
                                <div class="dropdown-divider"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item" style="color: #b91c1c;">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                        </svg>
                                        Sign out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page content -->
                <main class="content-area">
                    @if(session('success') || session('error'))
                        <div class="flash-stack">
                            @if(session('success'))
                                <x-alert type="success" dismissible="true">{{ session('success') }}</x-alert>
                            @endif
                            @if(session('error'))
                                <x-alert type="error" dismissible="true">{{ session('error') }}</x-alert>
                            @endif
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        @else
            <!-- Guest layout -->
            @yield('content')
        @endauth
    </div>

    <script>
        // Settings accordion in sidebar (used by nav-client.blade.php)
        function toggleSettingsMenu(button) {
            const menu = button.closest('.settings-menu');
            if (!menu) return;
            const content = menu.querySelector('.settings-content');
            const arrow = menu.querySelector('.settings-arrow');
            if (!content) return;

            const isOpen = content.style.display === 'block';
            content.style.display = isOpen ? 'none' : 'block';
            content.classList.toggle('hidden', isOpen);
            if (arrow) arrow.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Auto-expand the settings accordion if one of its links is active
            document.querySelectorAll('.settings-menu').forEach(menu => {
                const content = menu.querySelector('.settings-content');
                const arrow = menu.querySelector('.settings-arrow');
                if (content && content.querySelector('.nav-link.active')) {
                    content.style.display = 'block';
                    content.classList.remove('hidden');
                    if (arrow) arrow.style.transform = 'rotate(180deg)';
                }
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
