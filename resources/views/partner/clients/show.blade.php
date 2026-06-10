@extends('partner.layouts.app')

@section('title', $engagement->display_name ?: $engagement->managedCompany?->name)

@section('content')
@php $company = $engagement->managedCompany; @endphp

<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $engagement->display_name ?: $company?->name }}</h1>
        @if($engagement->display_name)
            <p class="text-sm text-gray-600">{{ $company?->name }}</p>
        @endif
    </div>
    <div class="flex gap-2">
        @if($engagement->isActive())
            <a href="{{ route('partner.clients.edit', $engagement) }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Edit</a>
            <form action="{{ route('partner.clients.destroy', $engagement) }}" method="POST" onsubmit="return confirm('Archive this client? The slot will be freed but data stays read-only.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 border border-red-200 text-red-700 rounded-lg text-sm hover:bg-red-50">Archive</button>
            </form>
        @endif
    </div>
</div>

<div class="grid sm:grid-cols-3 gap-4 mb-8">
    <div class="bg-white border border-gray-200 rounded-xl p-5">
        <div class="text-xs text-gray-500 uppercase">PRY</div>
        <div class="mt-1 text-xl font-bold">{{ $engagement->primary_reporting_year }}</div>
    </div>
    <div class="bg-white border border-gray-200 rounded-xl p-5">
        <div class="text-xs text-gray-500 uppercase">Status</div>
        <div class="mt-1 font-semibold capitalize">{{ $engagement->status }}</div>
    </div>
    <div class="bg-white border border-gray-200 rounded-xl p-5">
        <div class="text-xs text-gray-500 uppercase">Contract year</div>
        <div class="mt-1 text-xl font-bold">{{ $engagement->subscription?->contract_year ?? '—' }}</div>
    </div>
</div>

<div class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
    <h2 class="font-semibold text-gray-900 mb-4">Client details</h2>
    <dl class="grid sm:grid-cols-2 gap-4 text-sm">
        <div><dt class="text-gray-500">Emirate</dt><dd class="font-medium">{{ $company?->emirate ?? '—' }}</dd></div>
        <div><dt class="text-gray-500">Country</dt><dd class="font-medium">{{ $company?->country ?? '—' }}</dd></div>
        <div><dt class="text-gray-500">Sector</dt><dd class="font-medium">{{ $company?->sector ?? '—' }}</dd></div>
        <div><dt class="text-gray-500">Industry</dt><dd class="font-medium">{{ $company?->industry ?? '—' }}</dd></div>
        <div><dt class="text-gray-500">Contact</dt><dd class="font-medium">{{ $company?->contact_person ?? '—' }}</dd></div>
        <div><dt class="text-gray-500">Workspace slug</dt><dd class="font-medium text-gray-600">{{ $company?->slug }}</dd></div>
    </dl>
    @if($company?->description)
        <p class="text-sm text-gray-600 mt-4">{{ $company->description }}</p>
    @endif
</div>

@if($engagement->isActive())
    <form action="{{ route('partner.workspace.enter', $engagement) }}" method="POST" class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        @csrf
        <p class="text-sm text-indigo-900">Open this workspace to enter emissions, disclosures, and exports (PRY {{ $engagement->primary_reporting_year }}).</p>
        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg whitespace-nowrap">Open workspace</button>
    </form>
@else
    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-sm text-gray-600">
        Archived {{ $engagement->archived_at?->format('d M Y') ?? '' }} — read-only access when workspace switch is enabled.
    </div>
@endif

<div class="mt-6">
    <a href="{{ route('partner.clients.index') }}" class="text-sm text-indigo-600 hover:underline">← Back to clients</a>
</div>
@endsection
