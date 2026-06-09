<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'MENetZero')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌱</text></svg>">
    <style>body { font-family: 'Poppins', system-ui, sans-serif; }</style>
</head>
<body class="bg-white text-gray-900">
    @php $brand = \App\Models\SiteSetting::get('brand_name', 'MENetZero'); $cur = \App\Services\CurrencyService::displayCurrency(); @endphp
    <!-- Nav -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <img src="https://app.menetzero.com/public/images/menetzero.svg" alt="{{ $brand }}" class="h-8 w-auto" onerror="this.style.display='none'">
                    <span class="font-semibold text-lg text-teal-600">{{ $brand }}</span>
                </a>
                <div class="flex items-center gap-3 sm:gap-5 text-sm">
                    <a href="{{ route('pricing') }}" class="text-gray-600 hover:text-teal-600">Pricing</a>
                    <a href="{{ route('consultant.landing') }}" class="hidden sm:inline text-gray-600 hover:text-teal-600">For consultants</a>
                    <a href="{{ route('contact') }}" class="hidden sm:inline text-gray-600 hover:text-teal-600">Contact</a>
                    <span class="hidden sm:inline-flex items-center rounded-lg border border-gray-200 overflow-hidden">
                        <a href="{{ route('currency.switch', 'AED') }}" class="px-2.5 py-1 {{ $cur === 'AED' ? 'bg-teal-600 text-white' : 'text-gray-600' }}">AED</a>
                        <a href="{{ route('currency.switch', 'INR') }}" class="px-2.5 py-1 {{ $cur === 'INR' ? 'bg-teal-600 text-white' : 'text-gray-600' }}">INR</a>
                    </span>
                    <a href="{{ route('login') }}" class="text-gray-600 hover:text-teal-600">Login</a>
                    <a href="{{ route('register') }}" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">Sign Up</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="min-h-[60vh]">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 mt-16">
        <div class="max-w-6xl mx-auto px-4 py-12">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-sm">
                <div class="col-span-2 md:col-span-1">
                    <div class="font-semibold text-white text-lg mb-2">{{ $brand }}</div>
                    <p class="text-gray-400">Carbon emissions accounting for businesses across the Middle East.</p>
                </div>
                <div>
                    <div class="font-semibold text-white mb-3">Product</div>
                    <ul class="space-y-2">
                        <li><a href="{{ route('pricing') }}" class="hover:text-white">Pricing</a></li>
                        <li><a href="{{ route('consultant.landing') }}" class="hover:text-white">Join as consultant</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-white">Sign Up</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-white">Login</a></li>
                    </ul>
                </div>
                <div>
                    <div class="font-semibold text-white mb-3">Legal</div>
                    <ul class="space-y-2">
                        <li><a href="{{ route('terms') }}" class="hover:text-white">Terms &amp; Conditions</a></li>
                        <li><a href="{{ route('refunds') }}" class="hover:text-white">Refunds &amp; Cancellations</a></li>
                        <li><a href="{{ route('privacy') }}" class="hover:text-white">Privacy Policy</a></li>
                    </ul>
                </div>
                <div>
                    <div class="font-semibold text-white mb-3">Contact</div>
                    <ul class="space-y-2">
                        <li><a href="{{ route('contact') }}" class="hover:text-white">Contact Us</a></li>
                        <li><a href="mailto:{{ \App\Models\SiteSetting::get('support_email', 'support@menetzero.com') }}" class="hover:text-white">{{ \App\Models\SiteSetting::get('support_email', 'support@menetzero.com') }}</a></li>
                        <li>{{ \App\Models\SiteSetting::get('support_phone', '') }}</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-6 text-xs text-gray-500">
                &copy; {{ date('Y') }} {{ \App\Models\SiteSetting::get('company_legal_name', $brand) }}. All rights reserved.
            </div>
        </div>
    </footer>
</body>
</html>
