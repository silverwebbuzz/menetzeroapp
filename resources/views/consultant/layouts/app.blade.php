<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌱</text></svg>">

    <title>{{ config('app.name', 'MenetZero') }} — @yield('title', 'Consultant Portal')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        const BRAND = {
            50:  '#eff6ff',
            100: '#dbeafe',
            200: '#bfdbfe',
            300: '#93c5fd',
            400: '#60a5fa',
            500: '#3b82f6',
            600: '#2563eb',
            700: '#1d4ed8',
            800: '#1e40af',
            900: '#1e3a8a',
        };
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: BRAND[600], dark: BRAND[700], soft: BRAND[50], ...BRAND },
                        indigo: BRAND,
                        purple: BRAND,
                        violet: BRAND,
                    },
                    fontFamily: {
                        sans: ['Inter', 'Poppins', 'system-ui', 'sans-serif'],
                    },
                },
            },
        };
    </script>

    <link rel="stylesheet" href="{{ asset('css/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/consultant-shell.css') }}">

    @stack('styles')

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('head')
</head>
<body class="antialiased">
    @php
        $consultant = auth('consultant')->user();
        $userInitial = strtoupper(substr($consultant->name ?? '?', 0, 1));
    @endphp

    <div class="app-shell consultant-shell" x-data="{ sidebarOpen: false }">
        <div class="mobile-overlay"
             :class="{ 'is-open': sidebarOpen }"
             @click="sidebarOpen = false"
             aria-hidden="true"></div>

        <aside class="sidebar"
               :class="{ 'is-open': sidebarOpen }"
               @keydown.escape.window="sidebarOpen = false">
            <div class="sidebar-header">
                <a href="{{ route('consultant.dashboard') }}" class="brand-logo">
                    <img src="{{ asset('images/menetzero.svg') }}" alt="MIDDLE EAST NET Zero">
                    <span>Consultant</span>
                </a>
            </div>

            <nav class="mt-2 px-1 flex-1 flex flex-col">
                <div class="flex-1">
                    @include('layouts.partials.nav-consultant')
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="min-w-0">
                    <div class="font-medium text-ink truncate">{{ $consultant->name }}</div>
                    <div class="text-xs text-gray-500 truncate">{{ $consultant->company_name }}</div>
                </div>
                <form action="{{ route('consultant.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs text-gray-500 hover:text-red-600 whitespace-nowrap">Sign out</button>
                </form>
            </div>
        </aside>

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

                @php
                    $headerTitle = trim($__env->yieldContent('page-title'));
                    if ($headerTitle === '') {
                        $headerTitle = trim($__env->yieldContent('title'));
                    }
                    if ($headerTitle === '') {
                        $headerTitle = 'Dashboard';
                    }
                @endphp
                <h1 class="page-title truncate">{{ $headerTitle }}</h1>

                <div class="header-actions">
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button type="button"
                                class="header-btn"
                                @click="open = !open"
                                :aria-expanded="open"
                                aria-label="Account menu">
                            <span class="avatar">{{ $userInitial }}</span>
                            <span class="header-btn-label">{{ $consultant->name }}</span>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="open" x-transition class="dropdown-menu" style="display: none;">
                            <div class="px-3 py-2">
                                <div class="font-medium text-sm truncate">{{ $consultant->name }}</div>
                                <div class="text-xs text-gray-500 truncate">{{ $consultant->email }}</div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="{{ route('consultant.profile.edit') }}" class="dropdown-item">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Profile
                            </a>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('consultant.logout') }}">
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

            <main class="content-area">
                @if(session('success') || session('error') || session('info'))
                    <div class="flash-stack">
                        @if(session('success'))
                            <x-alert type="success" dismissible="true">{{ session('success') }}</x-alert>
                        @endif
                        @if(session('error'))
                            <x-alert type="error" dismissible="true">{{ session('error') }}</x-alert>
                        @endif
                        @if(session('info'))
                            <x-alert type="info" dismissible="true">{{ session('info') }}</x-alert>
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
