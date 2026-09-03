@props(['context' => 'company'])

@php
    $checkoutAvailable = \App\Models\PaymentGateway::checkoutAvailable();
@endphp

@if(!$checkoutAvailable)
    <div {{ $attributes->merge(['class' => 'mkt-payments-notice']) }}>
        <strong>Register free today.</strong>
        @if($context === 'consultant')
            Explore with one free managed client. When you need clean exports or more capacity, buy client slots from Agency packs.
        @else
            Explore Scope 1 &amp; 2 and disclosure previews on Free. When you need clean exports, upgrade your package from Plan &amp; billing.
        @endif
    </div>
@endif
