@props([
    'message' => 'Preview only — upgrade to download reports.',
    'upgradeLabel' => 'View plans',
    'showUpgrade' => true,
    'upgradeUrl' => null,
])

<div {{ $attributes->merge(['class' => 'mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3']) }}>
    <div class="flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm text-amber-900">{{ $message }}</p>
    </div>
    @if($showUpgrade)
        <a href="{{ $upgradeUrl ?? route('subscriptions.upgrade') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-white bg-amber-600 rounded-lg hover:bg-amber-700 whitespace-nowrap">
            {{ $upgradeLabel }}
        </a>
    @endif
</div>
