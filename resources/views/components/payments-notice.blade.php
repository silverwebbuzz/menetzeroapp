@props(['context' => 'company'])

@php
    $checkoutAvailable = \App\Models\PaymentGateway::checkoutAvailable();
@endphp

@if(!$checkoutAvailable)
    <div {{ $attributes->merge(['class' => 'mkt-payments-notice']) }}>
        <strong>Register free today.</strong>
        @if($context === 'consultant')
            Explore with one free managed client. When you need clean exports or more capacity, Request clients — MENetZero confirms pricing offline (no public AED list).
        @else
            Explore Scope 1 &amp; 2 and disclosure previews on Free. When you need clean exports, Upgrade your package from Plan &amp; billing — pricing is confirmed offline.
        @endif
    </div>
@endif
