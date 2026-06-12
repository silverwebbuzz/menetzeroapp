@props([
    'tier' => 'paid',
    'highlight' => false,
    'block' => true,
    'registerRoute' => 'register',
    'label' => null,
])

@php
    $checkoutAvailable = \App\Models\PaymentGateway::checkoutAvailable();
    $blockClass = $block ? 'mkt-btn-block' : '';
@endphp

@if($tier === 'free')
    <a href="{{ route($registerRoute) }}" {{ $attributes->merge(['class' => 'mkt-btn ' . $blockClass . ' ' . ($highlight ? 'mkt-btn-primary' : 'mkt-btn-dark')]) }}>
        {{ $label ?? 'Register free' }}
    </a>
@elseif(!$checkoutAvailable)
    <span {{ $attributes->merge(['class' => 'mkt-btn mkt-btn-coming-soon ' . $blockClass, 'role' => 'status', 'aria-disabled' => 'true']) }}>
        {{ $label ?? 'Coming soon' }}
    </span>
@elseif($tier === 'enterprise')
    <a href="mailto:{{ site_sales_email() }}?subject=Enterprise%20enquiry"
       {{ $attributes->merge(['class' => 'mkt-btn mkt-btn-dark ' . $blockClass]) }}>
        {{ $label ?? 'Contact sales' }}
    </a>
@else
    <a href="{{ route($registerRoute) }}" {{ $attributes->merge(['class' => 'mkt-btn ' . $blockClass . ' ' . ($highlight ? 'mkt-btn-primary' : 'mkt-btn-dark')]) }}>
        {{ $label ?? 'Get started' }}
    </a>
@endif
