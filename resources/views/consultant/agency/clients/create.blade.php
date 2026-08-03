@extends('consultant.layouts.app')

@section('title', 'Add Managed Client')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-1">Add managed client</h1>
<p class="text-sm text-gray-600 mb-6">Each new client uses <strong>1 managed client</strong> place from your capacity and is assigned a Primary Reporting Year (PRY).</p>

@if(!empty($slotSummary['is_trial']))
    <div class="cd-callout mb-6">
        <strong>Free trial workspace</strong> — this client gets Free rules: Scope 1 &amp; 2 full, Scope 3 (1 entry per category), disclosure form previews. Official PDF exports unlock after you request more clients and MENetZero activates paid capacity.
        <a href="{{ route('consultant.packs.index') }}" class="font-medium underline">Request clients</a>
    </div>
@endif

@if(!$subscription)
    <div class="cd-notice cd-notice--warning p-6 text-sm">
        No managed client capacity available. Your one free trial may already be in use — request additional clients from MENetZero.
        <a href="{{ route('consultant.packs.index') }}" class="font-medium underline">Request clients</a>
    </div>
@elseif($slotSummary['remaining'] < 1)
    <div class="cd-notice cd-notice--warning p-6 text-sm">
        All {{ $slotSummary['limit'] }} managed client places are in use. Archive a finished client or request more clients.
        <a href="{{ route('consultant.packs.index') }}" class="font-medium underline">Request clients</a>
    </div>
@else
    <form action="{{ route('consultant.clients.store') }}" method="POST" class="bg-white border border-gray-200 rounded-xl p-6 max-w-2xl space-y-5">
        @csrf

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
            <p class="text-xs text-gray-500 mt-1">
                @if(!empty($slotSummary['is_trial']))
                    Trial clients can enter data for this year; clean exports need paid capacity via Request clients.
                @else
                    Paid exports focus on this PRY. Request renewal / additional capacity when you need the next year unlocked.
                @endif
            </p>
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
