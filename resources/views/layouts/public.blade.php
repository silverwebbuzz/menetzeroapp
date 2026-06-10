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
    @stack('styles')
</head>
<body class="mkt-body">
    @php
        $brand = \App\Models\SiteSetting::get('brand_name', 'MENetZero');
        $cur = \App\Services\CurrencyService::displayCurrency();
        $logoUrl = asset('images/menetzero.svg');
    @endphp

    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 max-w-7xl mx-auto">
                <a href="{{ route('home') }}" class="mkt-brand-logo">
                    <img src="{{ $logoUrl }}" alt="MIDDLE EAST NET Zero" onerror="this.src='https://app.menetzero.com/public/images/menetzero.svg'">
                </a>
                <div class="flex items-center gap-3 sm:gap-5 text-sm font-medium">
                    <a href="{{ route('pricing') }}" class="hidden sm:inline text-gray-600 hover:text-teal-600">Pricing</a>
                    <a href="{{ route('consultant-list.index') }}" class="hidden md:inline text-gray-600 hover:text-teal-600">Find consultants</a>
                    <a href="{{ route('consultant.landing') }}" class="hidden sm:inline text-gray-600 hover:text-teal-600">For consultants</a>
                    <a href="{{ route('contact') }}" class="hidden lg:inline text-gray-600 hover:text-teal-600">Contact</a>
                    <span class="hidden sm:inline-flex items-center rounded-lg border border-gray-200 overflow-hidden text-xs">
                        <a href="{{ route('currency.switch', 'AED') }}" class="px-2.5 py-1 {{ $cur === 'AED' ? 'bg-teal-600 text-white' : 'text-gray-600' }}">AED</a>
                        <a href="{{ route('currency.switch', 'INR') }}" class="px-2.5 py-1 {{ $cur === 'INR' ? 'bg-teal-600 text-white' : 'text-gray-600' }}">INR</a>
                    </span>
                    <a href="{{ route('login') }}" class="mkt-btn mkt-btn-outline hidden sm:inline-flex" style="padding:0.5rem 1rem;font-size:0.875rem;">Login</a>
                    <a href="{{ route('register') }}" class="mkt-btn mkt-btn-primary hidden sm:inline-flex" style="padding:0.5rem 1rem;font-size:0.875rem;">Sign Up</a>
                </div>
            </div>
        </div>
    </nav>

    <main>
        @if(session('success'))
            <div class="max-w-6xl mx-auto px-4 pt-6">
                <div class="mkt-alert-success">{{ session('success') }}</div>
            </div>
        @endif
        @if(session('error'))
            <div class="max-w-6xl mx-auto px-4 pt-6">
                <div class="mkt-alert-error">{{ session('error') }}</div>
            </div>
        @endif
        @yield('content')
    </main>

    <footer class="bg-gray-900 text-white py-12 px-4 mt-0">
        <div class="max-w-6xl mx-auto">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-sm mb-8">
                <div class="col-span-2 md:col-span-1">
                    <a href="{{ route('home') }}" class="mkt-brand-logo mb-4">
                        <img src="{{ $logoUrl }}" alt="MIDDLE EAST NET Zero" class="brightness-0 invert" onerror="this.style.display='none'">
                    </a>
                    <p class="text-gray-400">Comprehensive carbon emissions tracking for businesses in the Middle East.</p>
                </div>
                <div>
                    <div class="font-semibold text-white mb-3">Product</div>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="{{ route('pricing') }}" class="hover:text-white">Pricing</a></li>
                        <li><a href="{{ route('consultant-list.index') }}" class="hover:text-white">Find consultants</a></li>
                        <li><a href="{{ route('consultant.landing') }}" class="hover:text-white">Join as consultant</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-white">Sign Up</a></li>
                    </ul>
                </div>
                <div>
                    <div class="font-semibold text-white mb-3">Legal</div>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="{{ route('terms') }}" class="hover:text-white">Terms &amp; Conditions</a></li>
                        <li><a href="{{ route('refunds') }}" class="hover:text-white">Refunds &amp; Cancellations</a></li>
                        <li><a href="{{ route('privacy') }}" class="hover:text-white">Privacy Policy</a></li>
                    </ul>
                </div>
                <div>
                    <div class="font-semibold text-white mb-3">Contact</div>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="{{ route('contact') }}" class="hover:text-white">Contact Us</a></li>
                        <li><a href="mailto:{{ \App\Models\SiteSetting::get('support_email', 'support@menetzero.com') }}" class="hover:text-white">{{ \App\Models\SiteSetting::get('support_email', 'support@menetzero.com') }}</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-gray-500">
                <span>&copy; {{ date('Y') }} {{ \App\Models\SiteSetting::get('company_legal_name', $brand) }}. All rights reserved.</span>
                <div class="flex gap-4">
                    <a href="{{ route('login') }}" class="hover:text-white">Login</a>
                    <a href="{{ route('register') }}" class="hover:text-white">Sign Up</a>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
