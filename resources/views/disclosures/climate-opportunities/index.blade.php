@extends('layouts.app')

@section('title', 'Climate Opportunities - IFRS S2')
@section('page-title', 'Climate Opportunities')

@section('content')
<div class="w-full">
    @include('disclosures.partials.header')

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">{{ session('success') }}</div>
    @endif

    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title">Add opportunity</h3>
            <p class="card-subtitle">Climate-related opportunities for {{ $fiscalYear }}.</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('disclosures.s2.climate-opportunities.store', ['fiscal_year' => $fiscalYear]) }}" class="space-y-4">
                @csrf
                <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                        <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <input type="text" name="category" placeholder="e.g. Resource efficiency, New markets" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Potential impact</label>
                    <textarea name="potential_impact" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Actions to realise</label>
                    <textarea name="actions" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Add opportunity</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Registered opportunities ({{ $opportunities->count() }})</h3>
        </div>
        <div class="card-body space-y-4">
            @forelse($opportunities as $opp)
                <details class="border border-gray-200 rounded-lg p-4">
                    <summary class="font-medium text-gray-900 cursor-pointer">
                        {{ $opp->name }}
                        @if($opp->category)
                            <span class="text-xs text-gray-500 ml-2">{{ $opp->category }}</span>
                        @endif
                    </summary>
                    <form method="POST" action="{{ route('disclosures.s2.climate-opportunities.update', ['climateOpportunity' => $opp, 'fiscal_year' => $fiscalYear]) }}" class="mt-4 space-y-3">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <input type="text" name="name" value="{{ $opp->name }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <input type="text" name="category" value="{{ $opp->category }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2">{{ $opp->description }}</textarea>
                        <textarea name="potential_impact" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Potential impact">{{ $opp->potential_impact }}</textarea>
                        <textarea name="actions" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Actions">{{ $opp->actions }}</textarea>
                        <button type="submit" class="btn btn-secondary btn-sm">Update</button>
                    </form>
                    <form method="POST" action="{{ route('disclosures.s2.climate-opportunities.destroy', ['climateOpportunity' => $opp, 'fiscal_year' => $fiscalYear]) }}" class="mt-2" onsubmit="return confirm('Remove this opportunity?')">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                        <button type="submit" class="text-sm text-red-600 hover:underline">Delete</button>
                    </form>
                </details>
            @empty
                <p class="text-gray-500 text-sm">No opportunities registered yet for {{ $fiscalYear }}.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
