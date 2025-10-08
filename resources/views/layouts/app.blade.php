<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'CarbonTracker') }} - @yield('title', 'Carbon Emissions Tracking')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700" rel="stylesheet" />

    <!-- Styles/Scripts (CDN, no Vite needed) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}">
    <style>
        body { 
            font-family: 'Inter', system-ui, -apple-system, sans-serif; 
            color: #111827 !important; 
            background-color: #f9fafb !important; 
        }
        .sidebar { 
            background-color: #ffffff; 
            border-right: 1px solid #e5e7eb; 
            height: 100vh; 
            width: 280px; 
            position: fixed; 
            left: 0; 
            top: 0; 
            overflow-y: auto; 
            display: flex;
            flex-direction: column;
        }
        .sidebar-header { 
            padding: 2rem 1.5rem; 
            border-bottom: 1px solid #e5e7eb; 
            background-color: #0ea5a3; 
            min-height: 80px;
            display: flex;
            align-items: center;
        }
        .brand-logo { 
            display: flex; 
            align-items: center; 
            gap: 0.75rem; 
        }
        .brand-logo-icon { 
            width: 2.5rem; 
            height: 2.5rem; 
            background-color: white; 
            border-radius: 0.5rem; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: #0ea5a3; 
            font-weight: 700; 
            font-size: 1rem; 
            flex-shrink: 0;
        }
        .brand-logo-text { 
            color: white !important; 
            font-weight: 600; 
            font-size: 1.125rem; 
        }
        .brand-logo-text-small { 
            font-size: 0.75rem; 
            font-weight: 500; 
            line-height: 1.2; 
            color: white !important; 
        }
        .brand-logo-text-large { 
            font-size: 0.875rem; 
            font-weight: 700; 
            line-height: 1.2; 
            color: white !important; 
        }
        .nav-link { 
            display: flex; 
            align-items: center; 
            padding: 0.75rem 1.5rem; 
            font-size: 0.875rem; 
            font-weight: 500; 
            border-radius: 0.5rem; 
            margin: 0.25rem 1rem; 
            transition: all 0.15s ease-in-out; 
            color: #6b7280; 
            text-decoration: none; 
        }
        .nav-link:hover { 
            background-color: #f3f4f6; 
            color: #111827; 
        }
        .nav-link.active { 
            background-color: #0ea5a3; 
            color: white; 
        }
        .nav-link svg { 
            margin-right: 0.75rem; 
            width: 1.25rem; 
            height: 1.25rem; 
        }
        .nav-link.active svg { 
            color: white; 
        }
        .nav-link:not(.active) svg { 
            color: #6b7280; 
        }
        .sidebar-footer { 
            padding: 1.5rem; 
            border-top: 1px solid #e5e7eb; 
            margin-top: auto; 
            flex-shrink: 0;
        }
        .user-info { 
            color: #111827 !important; 
        }
        .user-company { 
            color: #6b7280 !important; 
            font-size: 0.875rem; 
        }
        .main-content { 
            margin-left: 280px; 
            min-height: 100vh; 
            background-color: #f9fafb;
        }
        .header { 
            background-color: white; 
            border-bottom: 1px solid #e5e7eb; 
            padding: 1rem 2rem; 
        }
        .page-title { 
            color: #111827 !important; 
            font-size: 1.5rem; 
            font-weight: 600; 
        }
        .content-area { 
            padding: 2rem; 
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar { 
                transform: translateX(-100%); 
                transition: transform 0.3s ease-in-out; 
            }
            .sidebar.open { 
                transform: translateX(0); 
            }
            .main-content { 
                margin-left: 0; 
            }
        }
    </style>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Additional head content -->
    @stack('head')
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen">
        @auth
            <!-- Sidebar -->
            <div class="sidebar" id="sidebar">
                <div class="sidebar-header">
                    <div class="brand-logo">
                        <div class="brand-logo-icon">ME</div>
                        <div>
                            <div class="brand-logo-text-small">MIDDLE EAST</div>
                            <div class="brand-logo-text-large">NET Zero</div>
                        </div>
                    </div>
                </div>
                
                <nav class="flex-1 py-4">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                </svg>
                Dashboard
            </a>
            
            <a href="{{ route('locations.index') }}" class="nav-link {{ request()->routeIs('locations.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Locations
            </a>
                        
                        <a href="{{ route('measurements.index') }}" class="nav-link {{ request()->routeIs('measurements.*') ? 'active' : '' }}">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Measurements
                        </a>
                        
                        <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Reports
                        </a>
                        
                        <a href="{{ route('profile.index') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            My Profile
                        </a>
                        
                        @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Admin
                        </a>
                        @endif
                    </div>
                </nav>
                
                <div class="sidebar-footer">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background-color: #0ea5a3;">
                                <span class="text-white text-sm font-medium">{{ substr(auth()->user()->name, 0, 1) }}</span>
                            </div>
                        </div>
                        <div class="ml-3">
                            <p class="user-info">{{ auth()->user()->name }}</p>
                            <p class="user-company">{{ auth()->user()->company->name ?? 'No Company' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <div class="main-content">
                <!-- Top navigation -->
                <header class="header">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <button class="lg:hidden mr-4" onclick="toggleSidebar()">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>
                            <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
                        </div>
                        
                        <div class="flex items-center space-x-4">
                            <div class="relative">
                                <button class="flex items-center text-sm" style="color: #374151;">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5v-5a7.5 7.5 0 00-15 0v5h5l-5 5-5-5h5v-5a7.5 7.5 0 0115 0v5z"></path>
                                    </svg>
                                    Notifications
                                </button>
                            </div>
                            
                            <div class="relative">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex items-center text-sm" style="color: #374151;">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                        </svg>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page content -->
                <main class="content-area">
                    @if(session('success'))
                        <x-alert type="success" dismissible="true">
                            {{ session('success') }}
                        </x-alert>
                    @endif

                    @if(session('error'))
                        <x-alert type="error" dismissible="true">
                            {{ session('error') }}
                        </x-alert>
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
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
        }
    </script>

    @stack('scripts')
</body>
</html>
