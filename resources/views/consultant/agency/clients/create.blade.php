@extends('consultant.layouts.app')

@section('title', 'Add Managed Client')

@section('content')
@php
    $hasCapacity = ($slotSummary['remaining'] ?? 0) > 0 && !empty($capacityOptions);
    $needsPackagePick = count($capacityOptions ?? []) > 1;
@endphp

<h1 class="text-2xl font-bold text-gray-900 mb-1">Add managed client</h1>
<p class="text-sm text-gray-600 mb-6">Each new client uses <strong>1 place</strong> from a capacity package and is assigned a Primary Reporting Year (PRY).</p>

@if(session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
@endif

@if(!empty($slotSummary['buckets']))
    <div class="mb-6 bg-white border border-gray-200 rounded-xl p-4">
        <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Your capacity</h2>
        <ul class="space-y-1.5 text-sm">
            @foreach($slotSummary['buckets'] as $bucket)
                <li class="flex flex-wrap justify-between gap-2 {{ $bucket['remaining'] < 1 ? 'text-gray-400' : 'text-gray-800' }}">
                    <span>
                        {{ $bucket['plan_name'] }}
                        @if($bucket['is_trial']) <span class="text-xs text-teal-700">(Free)</span> @endif
                        @if($bucket['is_demo']) <span class="text-xs text-amber-700">(Demo QA)</span> @endif
                    </span>
                    <span class="font-medium tabular-nums">{{ $bucket['used'] }}/{{ $bucket['slot_limit'] }} used · {{ $bucket['remaining'] }} left</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif

@if(!$hasCapacity)
    <div class="cd-notice cd-notice--warning p-6 text-sm">
        No managed client places remaining. Archive a finished client or request more capacity.
        <a href="{{ route('consultant.packs.index') }}" class="font-medium underline">Request clients</a>
    </div>
@else
    @php $onlyTrial = collect($capacityOptions)->every(fn ($b) => !empty($b['is_trial'])); @endphp
    @if($onlyTrial)
        <div class="cd-callout mb-6">
            <strong>Free trial workspace</strong> — this client gets Free rules: Scope 1 &amp; 2 full, Scope 3 (1 entry per category), disclosure form previews. Official PDF exports unlock after paid capacity is activated.
            <a href="{{ route('consultant.packs.index') }}" class="font-medium underline">Request clients</a>
        </div>
    @endif

    <form action="{{ route('consultant.clients.store') }}" method="POST" class="bg-white border border-gray-200 rounded-xl p-6 max-w-2xl space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Package depth *
                @if(!$needsPackagePick)
                    <span class="font-normal text-gray-500">(only one with remaining places)</span>
                @endif
            </label>
            <div class="space-y-2">
                @foreach($capacityOptions as $bucket)
                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 hover:border-teal-400 cursor-pointer">
                        <input
                            type="radio"
                            name="consultant_subscription_id"
                            value="{{ $bucket['subscription_id'] }}"
                            class="mt-1 border-gray-300 text-teal-600 focus:ring-teal-500"
                            @checked((string) old('consultant_subscription_id', $defaultSubscriptionId) === (string) $bucket['subscription_id'])
                            @required($needsPackagePick)
                        >
                        <span>
                            <span class="block text-sm font-semibold text-gray-900">{{ $bucket['plan_name'] }}</span>
                            <span class="block text-xs text-gray-500 mt-0.5">
                                {{ $bucket['remaining'] }} of {{ $bucket['slot_limit'] }} places left
                                @if($bucket['expires_at']) · expires {{ $bucket['expires_at'] }} @endif
                                @if($bucket['client_package_code'])
                                    · <span class="font-mono">{{ $bucket['client_package_code'] }}</span>
                                @endif
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('consultant_subscription_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Legal / company name *</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="display_name" class="block text-sm font-medium text-gray-700 mb-1">Your label (optional)</label>
            <input type="text" name="display_name" id="display_name" value="{{ old('display_name') }}"
                placeholder="e.g. Al Noor — 2026 inventory"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <div>
            <label for="primary_reporting_year" class="block text-sm font-medium text-gray-700 mb-1">Primary Reporting Year (PRY) *</label>
            <input type="number" name="primary_reporting_year" id="primary_reporting_year" value="{{ old('primary_reporting_year', $defaultPry) }}" required min="2000" max="2100"
                class="w-full max-w-xs rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <p class="text-xs text-gray-500 mt-1">Paid exports focus on this PRY for the package depth you selected above.</p>
            @error('primary_reporting_year')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label for="emirate" class="block text-sm font-medium text-gray-700 mb-1">Emirate</label>
                <input type="text" name="emirate" id="emirate" value="{{ old('emirate') }}"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                <input type="text" name="country" id="country" value="{{ old('country', 'United Arab Emirates') }}"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label for="sector" class="block text-sm font-medium text-gray-700 mb-1">Sector</label>
                <input type="text" name="sector" id="sector" value="{{ old('sector') }}"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="industry" class="block text-sm font-medium text-gray-700 mb-1">Industry</label>
                <input type="text" name="industry" id="industry" value="{{ old('industry') }}"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>

        <div>
            <label for="contact_person" class="block text-sm font-medium text-gray-700 mb-1">Contact person</label>
            <input type="text" name="contact_person" id="contact_person" value="{{ old('contact_person') }}"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea name="description" id="description" rows="3"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn btn-primary">Create client</button>
            <a href="{{ route('consultant.clients.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@endif
@endsection
