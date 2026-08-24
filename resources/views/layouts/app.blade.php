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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CDN with brand theme extension -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Brand palette. We alias purple/violet/indigo to the brand colour so that
        // legacy templates that used purple/indigo classes automatically pick up
        // the correct MenetZero teal/emerald. Same for blue accents in buttons.
        const BRAND = {
            50:  '#f0fdf4',
            100: '#dcfce7',
            200: '#bbf7d0',
            300: '#86efac',
            400: '#4ade80',
            500: '#16a34a',
            600: '#15803d',
            700: '#166534',
            800: '#14532d',
            900: '#052e16',
        };
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: BRAND[500], dark: BRAND[600], soft: BRAND[50], ...BRAND },
                        // Re-map historic non-brand accents to brand
                        purple: BRAND,
                        violet: BRAND,
                        indigo: BRAND,
                        orange: BRAND,
                    },
                    fontFamily: {
                        sans: ['Inter', 'Poppins', 'system-ui', 'sans-serif'],
                    },
                },
            },
        };
    </script>

    <!-- App shell styles (portal-design-system loads last so typography always wins) -->
    <link rel="stylesheet" href="{{ asset('css/app-shell.css') }}?v=20260824">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/portal-design-system.css') }}?v=20260630">
    <link rel="stylesheet" href="{{ asset('css/portal-enterprise.css') }}?v=20260630">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

    <!-- Alpine.js for dropdowns + sidebar -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('head')
    @include('layouts.partials.google-analytics')
</head>
<body class="antialiased company-portal">
    <div class="app-shell" x-data="{ sidebarOpen: false }">
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
                <div class="portal-shell-inner">
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

                    @include('layouts.partials.header-context', ['portal' => 'company'])

                    <div class="header-actions">
                        @if(Route::has('client.zero-ai'))
                        <!-- Zero AI — free ESG assistant -->
                            <a href="{{ route('client.zero-ai') }}"
                               class="header-btn header-btn--ai {{ request()->routeIs('client.zero-ai') ? 'is-active' : '' }}"
                               aria-label="Open Zero AI assistant">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3l1.9 5.2L19 10l-5.1 1.8L12 17l-1.9-5.2L5 10l5.1-1.8L12 3z"></path>
                                </svg>
                                <span class="header-btn-label">Zero AI</span>
                            </a>
                        @endif

                        @if($isConsultantActing)
                        <form action="{{ route('consultant.workspace.exit') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="header-btn text-indigo-700">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                <span class="header-btn-label">Back to Agency Hub</span>
                            </button>
                        </form>
                        @if($consultantSwitchableClients->count() > 1)
                        <div class="relative" x-data="{ open: false }" @click.away="open = false">
                            <button type="button" class="header-btn" @click="open = !open" :aria-expanded="open">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                                <span class="header-btn-label truncate max-w-[12rem]">{{ $activeCompany?->name }}</span>
                            </button>
                            <div x-show="open" x-transition class="dropdown-menu" style="display: none;">
                                <div class="dropdown-heading">Managed clients</div>
                                @foreach($consultantSwitchableClients as $engagement)
                                    <form action="{{ route('consultant.workspace.enter', $engagement) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dropdown-item {{ $activeCompany && $activeCompany->id == $engagement->managed_company_id ? 'active' : '' }}">
                                            <div class="flex-1 min-w-0 text-left">
                                                <div class="font-medium truncate">{{ $engagement->display_name ?: $engagement->managedCompany?->name }}</div>
                                                <div class="text-xs text-gray-500">PRY {{ $engagement->primary_reporting_year }}</div>
                                            </div>
                                        </button>
                                    </form>
                                @endforeach
                                <div class="dropdown-divider"></div>
                                <a href="{{ route('consultant.workspace.switcher') }}" class="dropdown-item text-sm">All workspaces…</a>
                            </div>
                        </div>
                        @endif
                        @elseif($accessibleCompanies->count() > 1)
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

                    @if(!empty($companyRenewalNudge['show']))
                        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <span>
                                <strong>Renewal window</strong> —
                                {{ $companyRenewalNudge['plan_name'] }}
                                expires {{ $companyRenewalNudge['expires_at']->format('d M Y') }}
                                ({{ $companyRenewalNudge['days_left'] }} days).
                                Pricing is confirmed offline.
                            </span>
                            <a href="{{ $companyRenewalNudge['request_url'] }}" class="inline-flex justify-center px-3 py-1.5 rounded-md bg-orange-600 text-white text-xs font-semibold hover:bg-orange-700 whitespace-nowrap">
                                Request a package
                            </a>
                        </div>
                    @endif

                    @yield('content')
                </main>

                @include('layouts.partials.site-footer')
                </div>
            </div>
        @else
            <!-- Guest layout -->
            @yield('content')
            @include('layouts.partials.site-footer')
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

    {{-- ElevenLabs voice help assistant ("Misi").
         Company + consultant portals only — platform admins have their own tooling
         and the knowledge base is written for customers, not internal staff.
         Agent config lives in the ElevenLabs dashboard; the knowledge base files it
         serves are in documentation/elevenlabs-voice-agent/. --}}
    @auth
        {{-- Not on the Zero AI page: the floating bubble is fixed bottom-right and
             covers the chat composer, and a voice assistant is redundant where the
             user is already typing to one. --}}
        @if(config('services.elevenlabs.agent_id') && !auth()->user()->isAdmin() && !request()->routeIs('client.zero-ai'))
            {{-- The widget fixes itself to the bottom-right corner and builds its
                 call UI inside its own shadow DOM. We deliberately do NOT override
                 that positioning — doing so collapses the in-call panel. Instead
                 .content-area reserves bottom padding (app-shell.css) so the bubble
                 never covers buttons or text at the end of a page. --}}
            <elevenlabs-convai agent-id="{{ config('services.elevenlabs.agent_id') }}"></elevenlabs-convai>
            <script src="https://unpkg.com/@elevenlabs/convai-widget-embed" async type="text/javascript"></script>
        @endif
    @endauth
</body>
</html>
