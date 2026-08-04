@extends('consultant.layouts.app')

@section('title', 'Move client — '.($engagement->display_name ?: $engagement->managedCompany?->name))

@section('content')
@php
    $company = $engagement->managedCompany;
    $fromPlan = $engagement->subscription?->plan?->plan_name ?? 'Current package';
    $fromCode = $engagement->subscription?->plan?->plan_code;
@endphp

<h1 class="text-2xl font-bold text-gray-900 mb-1">Move to another package</h1>
<p class="text-sm text-gray-600 mb-6">
    <strong>{{ $engagement->display_name ?: $company?->name }}</strong>
    is on <span class="font-medium text-gray-800">{{ $fromPlan }}</span>.
    Company emissions stay in place — only the capacity seat changes.
</p>

@if(session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
@endif

<div class="cd-notice cd-notice--warning p-4 mb-6 text-sm">
    <strong>Rules:</strong> Downgrades are blocked.
    Mid-term you may only <strong>upgrade</strong> to a higher tier (if you have spare seats).
    Moving onto another seat of the <strong>same</strong> package is allowed only in the renewal window (or if current capacity expired).
</div>

@if(empty($moveOptions))
    <div class="bg-white border border-gray-200 rounded-xl p-6 text-sm text-gray-600 mb-6">
        No eligible spare seats right now.
        <a href="{{ route('consultant.packs.index') }}" class="text-brand hover:underline">Request a higher-tier package</a>
        (or wait until renewal to assign same-tier seats).
    </div>
@else
    <form action="{{ route('consultant.clients.move.store', $engagement) }}" method="POST" class="bg-white border border-gray-200 rounded-xl p-6 max-w-2xl space-y-4">
        @csrf
        <div class="space-y-2">
            @foreach($moveOptions as $bucket)
                <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 hover:border-teal-400 cursor-pointer">
                    <input
                        type="radio"
                        name="consultant_subscription_id"
                        value="{{ $bucket['subscription_id'] }}"
                        class="mt-1 border-gray-300 text-teal-600 focus:ring-teal-500"
                        @checked((string) old('consultant_subscription_id') === (string) $bucket['subscription_id'])
                        required
                    >
                    <span>
                        <span class="block text-sm font-semibold text-gray-900">
                            {{ $bucket['plan_name'] }}
                            @if(($bucket['move_kind'] ?? '') === 'upgrade')
                                <span class="ml-1 text-[10px] uppercase tracking-wide text-teal-700 bg-teal-50 border border-teal-100 rounded px-1.5 py-0.5">Upgrade</span>
                            @else
                                <span class="ml-1 text-[10px] uppercase tracking-wide text-amber-800 bg-amber-50 border border-amber-100 rounded px-1.5 py-0.5">Same plan</span>
                            @endif
                        </span>
                        <span class="block text-xs text-gray-500 mt-0.5">
                            {{ $bucket['remaining'] }} of {{ $bucket['slot_limit'] }} places left
                            @if(!empty($bucket['expires_at'])) · expires {{ $bucket['expires_at'] }} @endif
                        </span>
                    </span>
                </label>
            @endforeach
        </div>
        @error('consultant_subscription_id')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

        <div class="flex flex-wrap gap-3 pt-2">
            <button type="submit" class="btn btn-primary">Confirm move</button>
            <a href="{{ route('consultant.clients.show', $engagement) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@endif

<div class="mt-6">
    <a href="{{ route('consultant.clients.show', $engagement) }}" class="text-sm text-brand hover:underline">← Back to client</a>
</div>
@endsection
