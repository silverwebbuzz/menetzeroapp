@props([
    'allowed' => false,
    'href' => '#',
    'message' => 'Upgrade to unlock',
    'class' => 'btn btn-primary',
    'lockedClass' => 'btn btn-secondary opacity-80',
    'lockedHref' => null,
    'lockedTitle' => null,
])

@php
    $planGate = \App\Support\PlanGate::forUser(auth('web')->user());
    $lockedTitle = $lockedTitle ?? $planGate->lockedFeatureMessage($message, 'This feature');
    $lockedHref = $lockedHref ?? $planGate->upgradeRoute();
@endphp

@if($allowed)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>
        {{ $slot }}
    </a>
@else
    <a href="{{ $lockedHref }}"
       title="{{ $lockedTitle }}"
       {{ $attributes->merge(['class' => $lockedClass]) }}>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4 inline-block mr-1 -mt-0.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
        {{ $slot }}
    </a>
@endif
