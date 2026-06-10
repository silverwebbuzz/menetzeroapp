@extends('partner.layouts.app')

@section('title', 'Edit Client')

@section('content')
@php $company = $engagement->managedCompany; @endphp

<h1 class="text-2xl font-bold text-gray-900 mb-6">Edit client</h1>

<form action="{{ route('partner.clients.update', $engagement) }}" method="POST" class="bg-white border border-gray-200 rounded-xl p-6 max-w-2xl space-y-5">
    @csrf
    @method('PUT')

    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Legal / company name *</label>
        <input type="text" name="name" id="name" value="{{ old('name', $company?->name) }}" required
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="display_name" class="block text-sm font-medium text-gray-700 mb-1">Your label</label>
        <input type="text" name="display_name" id="display_name" value="{{ old('display_name', $engagement->display_name) }}"
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-600">
        PRY <strong>{{ $engagement->primary_reporting_year }}</strong> cannot be changed here. Use renewal or reporting year unlock (P20/P19).
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="emirate" class="block text-sm font-medium text-gray-700 mb-1">Emirate</label>
            <input type="text" name="emirate" id="emirate" value="{{ old('emirate', $company?->emirate) }}"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
            <input type="text" name="country" id="country" value="{{ old('country', $company?->country) }}"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="sector" class="block text-sm font-medium text-gray-700 mb-1">Sector</label>
            <input type="text" name="sector" id="sector" value="{{ old('sector', $company?->sector) }}"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label for="industry" class="block text-sm font-medium text-gray-700 mb-1">Industry</label>
            <input type="text" name="industry" id="industry" value="{{ old('industry', $company?->industry) }}"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
    </div>

    <div>
        <label for="contact_person" class="block text-sm font-medium text-gray-700 mb-1">Contact person</label>
        <input type="text" name="contact_person" id="contact_person" value="{{ old('contact_person', $company?->contact_person) }}"
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
        <textarea name="description" id="description" rows="3"
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $company?->description) }}</textarea>
    </div>

    <div class="flex gap-3 pt-2">
        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">Save changes</button>
        <a href="{{ route('partner.clients.show', $engagement) }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
    </div>
</form>
@endsection
