@props(['context' => 'company'])

@php
    $checkoutAvailable = \App\Models\PaymentGateway::checkoutAvailable();
@endphp

@if(!$checkoutAvailable)
    <div {{ $attributes->merge(['class' => 'mkt-payments-notice']) }}>
        <strong>Register free today.</strong>
        @if($context === 'consultant')
            Paid agency packs and upgrades will be available here once online payments go live — explore features and add your free trial client in the meantime.
        @else
            Explore Scope 1 &amp; 2 and disclosure previews on the Free plan. Paid upgrades (Starter, Growth, Enterprise) are coming soon — you can switch plans from your account when checkout opens.
        @endif
    </div>
@endif
