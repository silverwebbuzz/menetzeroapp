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
    <style>
        body { 
            font-family: 'Inter', system-ui, -apple-system, sans-serif; 
            color: #111827 !important; 
            background-color: #f9fafb !important; 
        }
        .sidebar { 
            background-color: #ffffff; 
            border-right: 1px solid #e5e7eb; 
            width: 280px; 
            position: fixed; 
            left: 0; 
            top: 0; 
            bottom: 0;
            overflow-y: auto; 
            transform: translateX(0);
        }
        .sidebar-header { 
            padding: 1.5rem; 
            border-bottom: 1px solid #e5e7eb; 
            background-color: #0ea5a3; 
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
        .user-info { 
            color: #111827 !important; 
        }
        .user-company { 
            color: #6b7280 !important; 
            font-size: 0.875rem; 
        }
        .main-content { 
            margin-left: 280px; 
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
        
        /* Button Styles */
        .btn { 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            gap: 0.5rem; 
            padding: 0.75rem 1.5rem; 
            border-radius: 1rem; 
            font-weight: 500; 
            text-decoration: none; 
            border: 1px solid transparent; 
            cursor: pointer; 
            transition: all 0.15s ease-in-out; 
        }
        .btn-primary { 
            background-color: #0ea5a3; 
            color: white; 
            border-color: #0ea5a3; 
        }
        .btn-primary:hover { 
            background-color: #0d9488; 
            border-color: #0d9488; 
            transform: translateY(-1px); 
        }
        .btn-outline { 
            background-color: transparent; 
            color: #0ea5a3; 
            border-color: #0ea5a3; 
        }
        .btn-outline:hover { 
            background-color: #0ea5a3; 
            color: white; 
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar { 
                transform: translateX(-100%); 
                transition: transform 0.3s ease-in-out; 
                z-index: 1000;
                position: fixed !important;
                left: 0 !important;
                top: 0 !important;
                bottom: 0 !important;
                width: 280px !important;
            }
            .sidebar.open { 
                transform: translateX(0) !important; 
            }
            .main-content { 
                margin-left: 0 !important; 
            }
        }
        
        @media (min-width: 1025px) {
            .sidebar { 
                transform: translateX(0) !important; 
            }
        }
        
        @media (max-width: 768px) {
            .sidebar { 
                width: 100%; 
                max-width: 320px; 
            }
            .main-content { 
                margin-left: 0; 
            }
            .header { 
                padding: 1rem; 
            }
            .content-area { 
                padding: 1rem; 
            }
            .page-title { 
                font-size: 1.25rem; 
            }
        }
        
        @media (max-width: 640px) {
            .content-area { 
                padding: 0.75rem; 
            }
            .header { 
                padding: 0.75rem; 
            }
        }
        
        /* Mobile Overlay */
        .mobile-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }
        
        .mobile-overlay.active {
            display: block;
        }
        
        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.375rem;
            transition: background-color 0.2s;
        }
        
        .mobile-menu-btn:hover {
            background-color: #f3f4f6;
        }
        
        @media (max-width: 1024px) {
            .mobile-menu-btn {
                display: block;
            }
        }
        
        /* Mobile Button Improvements */
        @media (max-width: 640px) {
            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }
            .btn svg {
                width: 1rem;
                height: 1rem;
            }
        }
        
        /* Touch-friendly interactions */
        @media (hover: none) and (pointer: coarse) {
            .btn:hover {
                transform: none;
            }
            .nav-link:hover {
                background-color: transparent;
            }
        }
    </style>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Additional head content -->
    @stack('head')
    
    <script>
        // Simple global function
        window.toggleSidebar = function() {
            console.log('toggleSidebar called');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            console.log('Sidebar element:', sidebar);
            console.log('Overlay element:', overlay);
            console.log('Window width:', window.innerWidth);
            
            if (!sidebar || !overlay) {
                console.error('Elements not found');
                return;
            }
            
            const isOpen = sidebar.classList.contains('open');
            console.log('Current state - isOpen:', isOpen);
            console.log('Sidebar classes before:', sidebar.className);
            console.log('Current transform before:', sidebar.style.transform);
            
            if (isOpen) {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
                sidebar.style.transform = 'translateX(-100%)';
                console.log('Sidebar closed');
            } else {
                sidebar.classList.add('open');
                overlay.classList.add('active');
                sidebar.style.transform = 'translateX(0)';
                console.log('Sidebar opened');
            }
            
            console.log('Sidebar classes after:', sidebar.className);
            console.log('Overlay classes after:', overlay.className);
            console.log('Transform after:', sidebar.style.transform);
        };
        
        // Ensure DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, mobile menu ready');
            
            // Initialize sidebar state
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            console.log('Sidebar found on load:', sidebar);
            console.log('Overlay found on load:', overlay);
            
            if (sidebar) {
                console.log('Sidebar current classes:', sidebar.className);
                console.log('Window width:', window.innerWidth);
                
                // Set initial state based on screen size
                if (window.innerWidth <= 1024) {
                    sidebar.style.transform = 'translateX(-100%)';
                    console.log('Mobile: Sidebar hidden initially');
                } else {
                    sidebar.style.transform = 'translateX(0)';
                    console.log('Desktop: Sidebar visible initially');
                }
            }
            
            // Add multiple event listeners to ensure it works
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            console.log('Mobile menu button found:', mobileMenuBtn);
            
            if (mobileMenuBtn) {
                // Add click listener
                mobileMenuBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Button clicked via addEventListener');
                    window.toggleSidebar();
                });
                
                // Also add touchstart for mobile
                mobileMenuBtn.addEventListener('touchstart', function(e) {
                    e.preventDefault();
                    console.log('Button touched via touchstart');
                    window.toggleSidebar();
                });
            }
        });
        
        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth <= 1024 && 
                sidebar && overlay && menuBtn &&
                !sidebar.contains(event.target) && 
                !menuBtn.contains(event.target) &&
                sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            if (window.innerWidth > 1024 && sidebar && overlay) {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            }
        });
        
        // Test function - you can call this from console
        window.testSidebar = function() {
            console.log('Testing sidebar...');
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.style.transform = 'translateX(0)';
                sidebar.style.background = 'red';
                console.log('Sidebar should now be visible and red');
            } else {
                console.log('Sidebar not found!');
            }
        };
        
        /* Test CSS loading */
        window.testCSS = function() {
            console.log('Testing CSS...');
            const testDiv = document.createElement('div');
            testDiv.style.position = 'fixed';
            testDiv.style.top = '10px';
            testDiv.style.right = '10px';
            testDiv.style.background = 'red';
            testDiv.style.color = 'white';
            testDiv.style.padding = '10px';
            testDiv.style.zIndex = '9999';
            testDiv.innerHTML = 'CSS Test - Click to remove';
            testDiv.onclick = function() { this.remove(); };
            document.body.appendChild(testDiv);
            console.log('Red test div added to top-right corner');
        };
    </script>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div>
        @auth
            <!-- Mobile Overlay -->
            <div class="mobile-overlay" id="mobileOverlay" onclick="
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('mobileOverlay');
                sidebar.style.transform = 'translateX(-100%)';
                overlay.style.display = 'none';
            "></div>
            
            <!-- Sidebar -->
            <div class="sidebar" id="sidebar" style="transform: translateX(-100%);">
                <div class="sidebar-header">
                    <div class="brand-logo">
                        <div class="brand-logo-icon">ME</div>
                        <div>
                            <div class="brand-logo-text-small">MIDDLE EAST</div>
                            <div class="brand-logo-text-large">NET Zero</div>
                        </div>
                    </div>
                </div>
                
                <nav class="mt-8 px-4">
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
                
            </div>

            <!-- Main content -->
            <div class="main-content">
                <!-- Top navigation -->
                <header class="header">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <button class="mobile-menu-btn mr-4" onclick="
                                const sidebar = document.getElementById('sidebar');
                                const overlay = document.getElementById('mobileOverlay');
                                if (sidebar.style.transform === 'translateX(0px)' || sidebar.style.transform === '') {
                                    sidebar.style.transform = 'translateX(-100%)';
                                    overlay.style.display = 'none';
                                } else {
                                    sidebar.style.transform = 'translateX(0px)';
                                    overlay.style.display = 'block';
                                }
                            " type="button">
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
