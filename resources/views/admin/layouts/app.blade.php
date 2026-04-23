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
                        // Historic purple usage across admin views now maps to brand
                        purple: BRAND,
                        violet: BRAND,
                        indigo: BRAND,
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
<body class="min-h-screen bg-slate-50">
    <div class="min-h-screen" x-data="{ sidebarOpen: false }">
        {{-- Mobile overlay --}}
        <div x-show="sidebarOpen"
             x-transition.opacity
             @click="sidebarOpen = false"
             class="lg:hidden fixed inset-0 bg-gray-900/50 z-30"
             style="display: none;"></div>

        {{-- Sidebar --}}
        <aside class="fixed lg:static inset-y-0 left-0 w-72 bg-white border-r border-gray-200 flex flex-col z-40 transform transition-transform duration-200 lg:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
            <div class="h-16 flex items-center px-4 border-b border-gray-200">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                    <img src="{{ asset('images/menetzero.svg') }}" alt="MENetZero" class="h-8 w-auto">
                    <div class="flex flex-col">
                        <span class="text-sm font-semibold text-gray-900">MIDDLE EAST NET Zero</span>
                        <span class="text-xs text-admin font-medium">Super Admin</span>
                    </div>
                </a>
            </div>

            <nav class="flex-1 overflow-y-auto py-4">
                @include('admin.partials.nav')
            </nav>

            <div class="border-t border-gray-200 p-4 text-xs text-gray-500">
                <div class="flex items-center justify-between gap-2">
                    <span class="truncate">
                        @php $admin = auth('admin')->user(); @endphp
                        {{ $admin?->name ?? 'Admin' }}
                    </span>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="text-xs text-red-600 hover:text-red-700 whitespace-nowrap">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Main content --}}
        <main class="lg:ml-72 min-h-screen flex flex-col">
            <header class="sticky top-0 z-20 h-16 bg-white border-b border-gray-200 flex items-center px-4 sm:px-6 shadow-sm">
                <button type="button"
                        @click="sidebarOpen = !sidebarOpen"
                        class="lg:hidden p-2 -ml-2 mr-2 rounded-md hover:bg-gray-100"
                        aria-label="Toggle navigation">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <h1 class="text-base sm:text-lg font-semibold text-gray-900 truncate">
                    @yield('page-title', 'Admin Dashboard')
                </h1>
            </header>

            <section class="flex-1 p-4 sm:p-6">
                @yield('content')
            </section>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
