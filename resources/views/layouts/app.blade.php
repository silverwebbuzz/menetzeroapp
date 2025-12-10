<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌱</text></svg>">

    <title>{{ config('app.name', 'CarbonTracker') }} - @yield('title', 'Carbon Emissions Tracking')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700" rel="stylesheet" />

    <!-- Styles/Scripts (CDN, no Vite needed) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Suppress Tailwind CDN warning
        window.tailwind = { config: { theme: { extend: {} } } };
    </script>
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
        }
        .brand-logo { 
            display: flex; 
            align-items: center; 
            gap: 0.75rem; 
        }
        .brand-logo img {
            height: 2rem;
            width: auto;
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    
    <!-- Additional head content -->
    @stack('head')
    
    <script>
        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            if (window.innerWidth > 1024 && sidebar && overlay) {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            }
        });
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
                              <img src="https://app.menetzero.com/public/images/menetzero.svg" alt="MIDDLE EAST NET Zero" class="h-8 w-auto">
                          </div>
                </div>
                
                <nav class="mt-8 px-4">
            @php
                $user = auth()->user();
                $hasCompany = $user && $user->company_id;
                
                // ALWAYS use the direct company relationship - this is the user's primary company
                // This ensures we get the correct company type for the user's own company
                $companyType = 'client'; // Default to client
                
                if ($user && $user->company_id) {
                    // Load the company directly - ensure it's loaded
                    $userCompany = \App\Models\Company::find($user->company_id);
                    
                    if ($userCompany && isset($userCompany->company_type)) {
                        // Get the company_type value
                        $type = $userCompany->company_type;
                        
                        // Only set to 'partner' if explicitly 'partner', otherwise default to 'client'
                        if ($type === 'partner') {
                            $companyType = 'partner';
                        } else {
                            $companyType = 'client';
                        }
                    }
                }
            @endphp
            
            @if($companyType === 'partner')
                @include('layouts.partials.nav-partner')
            @else
                @include('layouts.partials.nav-client')
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
