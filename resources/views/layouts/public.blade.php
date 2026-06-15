<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'MENetZero')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌱</text></svg>">
    <link rel="stylesheet" href="{{ asset('css/marketing.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('styles')
    @include('layouts.partials.google-analytics')
</head>
<body class="mkt-body">
    @include('layouts.partials.marketing-nav')

    <main>
        @if(session('success'))
            <div class="mkt-container pt-6">
                <div class="mkt-alert-success">{{ session('success') }}</div>
            </div>
        @endif
        @if(session('error'))
            <div class="mkt-container pt-6">
                <div class="mkt-alert-error">{{ session('error') }}</div>
            </div>
        @endif
        @yield('content')
    </main>

    @include('layouts.partials.marketing-footer')
    @stack('scripts')
</body>
</html>
