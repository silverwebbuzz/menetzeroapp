@php
    $brand = \App\Models\SiteSetting::get('brand_name', 'MENetZero');
    $logoUrl = asset('images/menetzero.svg');
@endphp
<footer class="bg-gray-900 text-white py-12 px-4">
    <div class="mkt-container">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-sm mb-8">
            <div class="col-span-2 md:col-span-1">
                <a href="{{ route('home') }}" class="mkt-brand-logo mb-4 inline-flex">
                    <img src="{{ $logoUrl }}" alt="MIDDLE EAST NET Zero" class="brightness-0 invert" onerror="this.style.display='none'">
                </a>
                <p class="text-gray-400 text-sm leading-relaxed">Comprehensive carbon emissions tracking for businesses in the Middle East.</p>
            </div>
            <div>
                <div class="font-semibold text-white mb-3">Product</div>
                <ul class="space-y-2 text-gray-400">
                    <li><a href="{{ route('pricing') }}" class="hover:text-white">Company pricing</a></li>
                    <li><a href="{{ route('consultant-list.index') }}" class="hover:text-white">Find consultants</a></li>
                    <li><a href="{{ route('consultant.landing') }}" class="hover:text-white">Join as consultant</a></li>
                    <li><a href="{{ route('register') }}" class="hover:text-white">Company sign up</a></li>
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
                    <li><a href="mailto:{{ site_support_email() }}" class="hover:text-white break-all">{{ site_support_email() }}</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-800 pt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-gray-500">
            @include('layouts.partials.site-copyright', ['variant' => 'dark'])
            <div class="flex flex-wrap gap-x-4 gap-y-1">
                <a href="{{ route('login') }}" class="hover:text-white">Company sign in</a>
                <a href="{{ route('consultant.login') }}" class="hover:text-white">Consultant sign in</a>
            </div>
        </div>
    </div>
</footer>
