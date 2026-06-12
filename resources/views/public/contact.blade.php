@extends('layouts.public')

@section('title', 'Contact Us — ' . ($settings['brand_name'] ?? 'MENetZero'))

@section('content')
<section class="mkt-hero">
    <div class="mkt-container max-w-3xl">
        <div class="mkt-tagline">Get in touch</div>
        <h1>Contact Us</h1>
        <p class="mkt-lead">Questions about pricing, consultants, or your carbon compliance workflow? We're here to help.</p>
    </div>
</section>

<section class="mkt-section pt-0">
    <div class="mkt-container max-w-3xl">
        @if($page)
            <div class="mkt-prose mb-10">
                {!! $page->renderedBody() !!}
            </div>
        @endif

        <div class="grid sm:grid-cols-2 gap-6">
            <div class="mkt-info-card">
                <h2 class="font-semibold text-gray-900 mb-3">Get in touch</h2>
                <ul class="space-y-3 text-gray-700 text-sm">
                    <li>
                        <div class="text-gray-500">Support (help@)</div>
                        <a href="mailto:{{ site_support_email() }}" class="text-teal-600 font-medium">{{ site_support_email() }}</a>
                    </li>
                    <li>
                        <div class="text-gray-500">Sales (hello@)</div>
                        <a href="mailto:{{ site_sales_email() }}" class="text-teal-600 font-medium">{{ site_sales_email() }}</a>
                    </li>
                    <li>
                        <div class="text-gray-500">Phone</div>
                        <a href="tel:{{ preg_replace('/\s+/', '', site_support_phone()) }}" class="text-teal-600 font-medium">{{ site_support_phone() }}</a>
                    </li>
                </ul>
            </div>
            <div class="mkt-info-card">
                <h2 class="font-semibold text-gray-900 mb-3">{{ $settings['company_legal_name'] ?? ($settings['brand_name'] ?? 'MENetZero') }}</h2>
                <address class="not-italic text-gray-700 text-sm space-y-1">
                    @if(!empty($settings['address_line']))<div>{{ $settings['address_line'] }}</div>@endif
                    <div>{{ trim(($settings['city'] ?? '') . (!empty($settings['city']) && !empty($settings['country']) ? ', ' : '') . ($settings['country'] ?? '')) }}</div>
                </address>
                @if(!empty($settings['business_hours']))
                    <div class="mt-4 text-sm">
                        <div class="text-gray-500">Business hours</div>
                        <div class="text-gray-700">{{ $settings['business_hours'] }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
