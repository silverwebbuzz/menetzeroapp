<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MENetZero')</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌱</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    @php
        $portalVariant = $portalVariant ?? 'company';
        $isConsultant = $portalVariant === 'consultant';
        $brandPalette = $isConsultant
            ? ['50'=>'#eff6ff','100'=>'#dbeafe','200'=>'#bfdbfe','300'=>'#93c5fd','400'=>'#60a5fa','500'=>'#2563eb','600'=>'#1d4ed8','700'=>'#1e40af','800'=>'#1e3a8a','900'=>'#0f2b6b']
            : ['50'=>'#f0fdf4','100'=>'#dcfce7','200'=>'#bbf7d0','300'=>'#86efac','400'=>'#4ade80','500'=>'#16a34a','600'=>'#15803d','700'=>'#166534','800'=>'#14532d','900'=>'#052e16'];
    @endphp
    <script>
        const BRAND = @json($brandPalette);
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: BRAND[500], dark: BRAND[900], soft: BRAND[50], ...BRAND },
                        blue: BRAND,
                        emerald: BRAND,
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="{{ asset('css/app-shell.css') }}?v=20260621">
    @if($isConsultant)
        <link rel="stylesheet" href="{{ asset('css/consultant-shell.css') }}?v=20260621">
    @endif
    @stack('head')
    <link rel="stylesheet" href="{{ asset('css/portal-design-system.css') }}?v=20260621">
    @include('layouts.partials.google-analytics')
</head>
<body class="portal-auth portal-auth--{{ $portalVariant }}">
    <div class="portal-auth-shell min-h-screen flex flex-col">
    <div class="grid lg:grid-cols-2 flex-1">
        <div class="flex items-center justify-center p-6 sm:p-10 bg-white">
            <div class="w-full max-w-md auth-form">
                <div class="mb-8">
                    <a href="{{ $isConsultant ? route('consultant.landing') : url('/') }}" class="brand-logo inline-flex items-center gap-3">
                        <img src="{{ asset('images/menetzero.svg') }}" alt="MENetZero" class="h-8 w-auto">
                    </a>
                </div>
                @yield('content')
            </div>
        </div>

        <div class="relative brand-gradient text-white hidden lg:block">
            <div class="relative h-full w-full flex items-center justify-center p-10">
                <div class="glass p-8 w-full max-w-xl">
                    @hasSection('sidebar')
                        @yield('sidebar')
                    @else
                        @yield('sidebar-content')
                    @endif
                </div>
            </div>
        </div>
    </div>
    @include('layouts.partials.site-footer', ['variant' => 'auth'])
    </div>
</body>
</html>
