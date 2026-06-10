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
                    <li><a href="mailto:{{ \App\Models\SiteSetting::get('support_email', 'support@menetzero.com') }}" class="hover:text-white break-all">{{ \App\Models\SiteSetting::get('support_email', 'support@menetzero.com') }}</a></li>
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
