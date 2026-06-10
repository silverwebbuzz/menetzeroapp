@php
    $cur = \App\Services\CurrencyService::displayCurrency();
    $logoUrl = asset('images/menetzero.svg');
@endphp
<nav class="bg-white border-b border-gray-200 sticky top-0 z-50" x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false">
    <div class="mkt-container">
        <div class="flex justify-between items-center h-16">
            <a href="{{ route('home') }}" class="mkt-brand-logo">
                <img src="{{ $logoUrl }}" alt="MIDDLE EAST NET Zero" onerror="this.src='https://app.menetzero.com/public/images/menetzero.svg'">
            </a>

            <div class="mkt-desktop-nav">
                <a href="{{ route('pricing') }}" class="mkt-nav-link {{ request()->routeIs('pricing') ? 'active' : '' }}">Pricing</a>
                <a href="{{ route('consultant-list.index') }}" class="mkt-nav-link {{ request()->routeIs('consultant-list.*') ? 'active' : '' }}">Find consultants</a>
                <a href="{{ route('consultant.landing') }}" class="mkt-nav-link {{ request()->routeIs('consultant.landing') || request()->routeIs('consultant.register') || request()->routeIs('consultant.login') ? 'active' : '' }}">For consultants</a>
                <a href="{{ route('contact') }}" class="mkt-nav-link {{ request()->routeIs('contact') ? 'active' : '' }}">Contact</a>
                <span class="mkt-currency-toggle">
                    <a href="{{ route('currency.switch', 'AED') }}" class="{{ $cur === 'AED' ? 'active' : '' }}">AED</a>
                    <a href="{{ route('currency.switch', 'INR') }}" class="{{ $cur === 'INR' ? 'active' : '' }}">INR</a>
                </span>
                <a href="{{ route('login') }}" class="mkt-btn mkt-btn-outline mkt-btn-sm">Login</a>
                <a href="{{ route('register') }}" class="mkt-btn mkt-btn-primary mkt-btn-sm">Sign Up</a>
            </div>

            <button type="button" class="mkt-nav-toggle lg:hidden" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen" aria-label="Toggle menu">
                <svg x-show="!menuOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg x-show="menuOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="mkt-mobile-menu lg:hidden" :class="{ 'is-open': menuOpen }">
            <div class="flex flex-col gap-1 pb-4">
                <a href="{{ route('pricing') }}" class="mkt-nav-link px-1">Pricing</a>
                <a href="{{ route('consultant-list.index') }}" class="mkt-nav-link px-1">Find consultants</a>
                <a href="{{ route('consultant.landing') }}" class="mkt-nav-link px-1">For consultants</a>
                <a href="{{ route('contact') }}" class="mkt-nav-link px-1">Contact</a>
                <div class="flex items-center gap-2 py-2">
                    <span class="mkt-currency-toggle">
                        <a href="{{ route('currency.switch', 'AED') }}" class="{{ $cur === 'AED' ? 'active' : '' }}">AED</a>
                        <a href="{{ route('currency.switch', 'INR') }}" class="{{ $cur === 'INR' ? 'active' : '' }}">INR</a>
                    </span>
                </div>
                <div class="flex flex-col gap-2 pt-2">
                    <a href="{{ route('login') }}" class="mkt-btn mkt-btn-outline mkt-btn-block">Login</a>
                    <a href="{{ route('register') }}" class="mkt-btn mkt-btn-primary mkt-btn-block">Sign Up</a>
                </div>
            </div>
        </div>
    </div>
</nav>
