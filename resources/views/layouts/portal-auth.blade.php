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
            ? ['50'=>'#eef4fe','100'=>'#dce9fd','200'=>'#b9d3fb','300'=>'#8cb8f8','400'=>'#5a97f2','500'=>'#1563eb','600'=>'#1254c9','700'=>'#0f459f','800'=>'#0c3678','900'=>'#021d71']
            : ['50'=>'#ecfdf5','100'=>'#d1fae5','200'=>'#a7f3d0','300'=>'#6ee7b7','400'=>'#34d399','500'=>'#10b981','600'=>'#059669','700'=>'#047857','800'=>'#065f46','900'=>'#064e3b'];
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
