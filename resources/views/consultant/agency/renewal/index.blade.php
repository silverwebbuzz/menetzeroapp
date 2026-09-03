@extends('consultant.layouts.app')

@section('title', 'Renew capacity')

@section('content')
@php
    $boardCount = count($rows);
    $continueNeedHint = $boardCount; // if they kept everyone
@endphp

<h1 class="text-2xl font-bold text-gray-900 mb-1">Renew capacity</h1>
<p class="text-sm text-gray-600 mb-6 max-w-3xl">
    Decide for each client: <strong>Continue</strong> onto new/same-or-higher package seats, or
    <strong>Leave</strong> as history (read-only — no further edits or new reports).
    Empty seats after this stay available for brand-new clients.
</p>

@if(session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
@endif

<div class="cd-notice cd-notice--warning p-4 mb-6 text-sm max-w-3xl">
    <strong>Example:</strong> 8 clients this year → buy 10 seats → continue/upgrade 4 → leave 4 as history →
    6 open seats for new clients. History clients keep past data only.
</div>

@if($expiringSubscriptions->isNotEmpty())
    <div class="bg-white border border-gray-200 rounded-xl p-5 mb-6 max-w-3xl">
        <h2 class="text-sm font-semibold text-gray-900 mb-2">Packages in renewal window</h2>
        <ul class="space-y-1.5 text-sm text-gray-700">
            @foreach($expiringSubscriptions as $sub)
                <li class="flex flex-wrap justify-between gap-2">
                    <span>{{ $sub->plan?->plan_name ?? 'Capacity' }} · {{ $sub->slot_limit }} seats</span>
                    <span class="text-xs text-gray-500">expires {{ $sub->expires_at?->toDateString() }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif

<div class="flex flex-wrap items-center gap-3 mb-6 text-sm">
    <span class="text-gray-600">Spare seats available now: <strong class="text-gray-900">{{ $spareSeats }}</strong></span>
    <a href="{{ route('consultant.packs.index') }}" class="text-brand hover:underline">Buy capacity for {{ $nextYear }} →</a>
</div>

@if($boardCount === 0)
    <div class="bg-white border border-gray-200 rounded-xl p-8 text-sm text-gray-600 max-w-3xl">
        No active clients sit on expiring capacity. If you still need seats for {{ $nextYear }},
        <a href="{{ route('consultant.packs.index') }}" class="text-brand hover:underline">request clients</a>.
    </div>
@else
    <form action="{{ route('consultant.renewal.process') }}" method="POST" class="space-y-4 max-w-4xl" id="renew-board-form">
        @csrf

        @foreach($rows as $row)
            @php
                $engagement = $row['engagement'];
                $company = $engagement->managedCompany;
                $fromPlan = $engagement->subscription?->plan?->plan_name ?? 'Package';
                $eid = $engagement->id;
                $oldAction = old("decisions.$eid.action", 'continue');
            @endphp
            <div class="bg-white border border-gray-200 rounded-xl p-5 renew-row" data-engagement="{{ $eid }}">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-3">
                    <div>
                        <div class="font-semibold text-gray-900">{{ $engagement->display_name ?: $company?->name }}</div>
                        @if($engagement->display_name && $company?->name)
                            <div class="text-xs text-gray-500">{{ $company->name }}</div>
                        @endif
                        <div class="text-xs text-gray-500 mt-1">
                            {{ $fromPlan }} · PRY {{ $engagement->primary_reporting_year }}
                            @if($engagement->subscription?->expires_at)
                                · exp {{ $engagement->subscription->expires_at->toDateString() }}
                            @endif
                        </div>
                    </div>
                    <div class="flex gap-4 text-sm">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="decisions[{{ $eid }}][action]" value="continue" class="renew-action border-gray-300 text-teal-600" @checked($oldAction === 'continue') required>
                            <span class="font-medium text-gray-800">Continue</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="decisions[{{ $eid }}][action]" value="leave" class="renew-action border-gray-300 text-amber-600" @checked($oldAction === 'leave')>
                            <span class="font-medium text-gray-800">Leave (history)</span>
                        </label>
                    </div>
                </div>

                <div class="renew-continue-fields space-y-3 {{ $oldAction === 'leave' ? 'hidden' : '' }}">
                    @if(empty($row['targets']))
                        <p class="text-xs text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                            No spare same/higher seats yet.
                            <a href="{{ route('consultant.packs.index') }}" class="underline font-medium">Request capacity</a>
                            first, or mark Leave (history only).
                        </p>
                    @else
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Seat / package *</label>
                            <select name="decisions[{{ $eid }}][target_subscription_id]" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                <option value="">Select capacity…</option>
                                @foreach($row['targets'] as $bucket)
                                    <option value="{{ $bucket['subscription_id'] }}"
                                        @selected((string) old("decisions.$eid.target_subscription_id") === (string) $bucket['subscription_id'])>
                                        {{ $bucket['plan_name'] }}
                                        — {{ $bucket['remaining'] }}/{{ $bucket['slot_limit'] }} free
                                        @if(!empty($bucket['expires_at'])) · exp {{ $bucket['expires_at'] }} @endif
                                        @if(($bucket['move_kind'] ?? '') === 'upgrade') · upgrade @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">New PRY</label>
                            <input type="number" min="2000" max="2100"
                                name="decisions[{{ $eid }}][primary_reporting_year]"
                                value="{{ old("decisions.$eid.primary_reporting_year", $row['default_pry']) }}"
                                class="w-32 rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                        </div>
                    @endif
                </div>

                <p class="renew-leave-note text-xs text-gray-500 mt-2 {{ $oldAction === 'leave' ? '' : 'hidden' }}">
                    Will be archived: past inventory stays viewable read-only. No edits or new report downloads on this engagement.
                </p>
            </div>
        @endforeach

        <div class="flex flex-wrap gap-3 pt-2">
            <button type="submit" class="btn btn-primary">Apply renew decisions</button>
            <a href="{{ route('consultant.packs.index') }}" class="btn btn-secondary">Request more seats first</a>
            <a href="{{ route('consultant.dashboard') }}" class="text-sm text-brand hover:underline self-center">Cancel</a>
        </div>
    </form>
@endif

<div class="mt-8 text-sm">
    <a href="{{ route('consultant.dashboard') }}" class="text-brand hover:underline">← Consultant dashboard</a>
</div>

@if($boardCount > 0)
<script>
document.querySelectorAll('.renew-row').forEach(function (row) {
    const sync = () => {
        const action = row.querySelector('.renew-action:checked')?.value;
        const cont = row.querySelector('.renew-continue-fields');
        const leave = row.querySelector('.renew-leave-note');
        if (!cont) return;
        const isLeave = action === 'leave';
        cont.classList.toggle('hidden', isLeave);
        if (leave) leave.classList.toggle('hidden', !isLeave);
    };
    row.querySelectorAll('.renew-action').forEach(el => el.addEventListener('change', sync));
    sync();
});
</script>
@endif
@endsection
