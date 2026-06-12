@extends('layouts.public')

@section('title', 'Contact Us — ' . ($settings['brand_name'] ?? 'MENetZero'))

@section('content')
<section class="mkt-hero">
    <div class="mkt-container max-w-3xl">
        <div class="mkt-tagline">Get in touch</div>
        <h1>Contact Us</h1>
        <p class="mkt-lead">Questions about pricing, consultants, or your carbon compliance workflow? Send us a message — we typically reply within one business day.</p>
    </div>
</section>

<section class="mkt-section pt-0">
    <div class="mkt-container max-w-2xl">
        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
        @endif

        @if($page)
            <div class="mkt-prose mb-8">
                {!! $page->renderedBody() !!}
            </div>
        @endif

        <h2 class="text-xl font-semibold text-gray-900 mb-4">Send a message</h2>

        @include('contact.partials.form', [
            'action' => route('contact.submit'),
            'showTopic' => true,
            'defaultTopic' => old('topic', request('topic', 'support')),
        ])

        <p class="mt-6 text-sm text-gray-500 text-center">
            Prefer email?
            <a href="mailto:{{ site_support_email() }}" class="text-teal-600 hover:underline">{{ site_support_email() }}</a>
            (support)
            ·
            <a href="mailto:{{ site_sales_email() }}" class="text-teal-600 hover:underline">{{ site_sales_email() }}</a>
            (sales)
            ·
            <a href="tel:{{ preg_replace('/\s+/', '', site_support_phone()) }}" class="text-teal-600 hover:underline">{{ site_support_phone() }}</a>
        </p>
    </div>
</section>
@endsection
