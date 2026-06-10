@extends('consultant.layouts.app')

@section('title', 'Add Managed Client')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-1">Add managed client</h1>
<p class="text-sm text-gray-600 mb-6">Each new client consumes <strong>1 slot</strong> and is assigned a Primary Reporting Year (PRY).</p>

@if(!$subscription)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-sm text-amber-900">
        You need an active agency pack before adding clients. Contact admin to grant a pack, or purchase online when checkout is available (P19).
    </div>
@elseif($slotSummary['remaining'] < 1)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-sm text-amber-900">
        All {{ $slotSummary['limit'] }} slots are in use. Archive a finished client or purchase extra slots.
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
            <p class="text-xs text-gray-500 mt-1">Full Growth exports apply to this year only. Next year is preview until renewal or year unlock.</p>
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
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">Create client</button>
            <a href="{{ route('consultant.clients.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
        </div>
    </form>
@endif
@endsection
