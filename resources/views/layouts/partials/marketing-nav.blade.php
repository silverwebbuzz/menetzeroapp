@php
    $logoUrl = asset('images/menetzero.svg');
@endphp
<nav class="bg-white border-b border-gray-200 sticky top-0 z-50" x-data="{ menuOpen: false, companyAuthOpen: false, consultantAuthOpen: false }" @keydown.escape.window="menuOpen = false; companyAuthOpen = false; consultantAuthOpen = false">
    <div class="mkt-container">
        <div class="flex justify-between items-center h-16">
            <a href="{{ route('home') }}" class="mkt-brand-logo">
                <img src="{{ $logoUrl }}" alt="MIDDLE EAST NET Zero" onerror="this.src='https://app.menetzero.com/public/images/menetzero.svg'">
            </a>

            <div class="mkt-desktop-nav">
                <a href="{{ route('pricing') }}" class="mkt-nav-link {{ request()->routeIs('pricing') ? 'active' : '' }}">Company pricing</a>
                <a href="{{ route('consultant-list.index') }}" class="mkt-nav-link {{ request()->routeIs('consultant-list.*') ? 'active' : '' }}">Find consultants</a>
                <a href="{{ route('consultant.landing') }}" class="mkt-nav-link {{ request()->routeIs('consultant.landing') || request()->routeIs('consultant.register') || request()->routeIs('consultant.login') ? 'active' : '' }}">For consultants</a>
                <a href="{{ route('contact') }}" class="mkt-nav-link {{ request()->routeIs('contact') ? 'active' : '' }}">Contact</a>

                <div class="mkt-auth-menus">
                    <div class="mkt-auth-menu" @click.outside="companyAuthOpen = false">
                        <button type="button" class="mkt-auth-menu-trigger" @click="consultantAuthOpen = false; companyAuthOpen = !companyAuthOpen" :aria-expanded="companyAuthOpen">
                            Company
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div class="mkt-auth-menu-panel" x-show="companyAuthOpen" x-cloak>
                            <span class="mkt-auth-menu-label">For companies</span>
                            <a href="{{ route('login') }}" class="mkt-auth-menu-link">Sign in</a>
                            <a href="{{ route('register') }}" class="mkt-auth-menu-link mkt-auth-menu-link-primary">Sign up</a>
                        </div>
                    </div>
                    <div class="mkt-auth-menu" @click.outside="consultantAuthOpen = false">
                        <button type="button" class="mkt-auth-menu-trigger mkt-auth-menu-trigger-consultant" @click="companyAuthOpen = false; consultantAuthOpen = !consultantAuthOpen" :aria-expanded="consultantAuthOpen">
                            Consultant
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div class="mkt-auth-menu-panel" x-show="consultantAuthOpen" x-cloak>
                            <span class="mkt-auth-menu-label">For consultants</span>
                            <a href="{{ route('consultant.login') }}" class="mkt-auth-menu-link">Sign in</a>
                            <a href="{{ route('consultant.register') }}" class="mkt-auth-menu-link mkt-auth-menu-link-primary">Sign up</a>
                        </div>
                    </div>
                </div>
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
                <a href="{{ route('pricing') }}" class="mkt-nav-link px-1">Company pricing</a>
                <a href="{{ route('consultant-list.index') }}" class="mkt-nav-link px-1">Find consultants</a>
                <a href="{{ route('consultant.landing') }}" class="mkt-nav-link px-1">For consultants</a>
                <a href="{{ route('contact') }}" class="mkt-nav-link px-1">Contact</a>

                <div class="pt-3 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-400 px-1">Company</div>
                <div class="flex flex-col gap-2 px-1">
                    <a href="{{ route('login') }}" class="mkt-btn mkt-btn-outline mkt-btn-block mkt-btn-sm">Company sign in</a>
                    <a href="{{ route('register') }}" class="mkt-btn mkt-btn-primary mkt-btn-block mkt-btn-sm">Company sign up</a>
                </div>

                <div class="pt-3 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-400 px-1">Consultant</div>
                <div class="flex flex-col gap-2 px-1">
                    <a href="{{ route('consultant.login') }}" class="mkt-btn mkt-btn-outline mkt-btn-block mkt-btn-sm">Consultant sign in</a>
                    <a href="{{ route('consultant.register') }}" class="mkt-btn mkt-btn-primary mkt-btn-block mkt-btn-sm">Consultant sign up</a>
                </div>
            </div>
        </div>
    </div>
</nav>
