@extends('layouts.app')

@section('title', 'Sustainability Risks - IFRS S1')
@section('page-title', 'Sustainability Risk Register')

@section('content')
<div class="max-w-5xl mx-auto">
    @include('disclosures.partials.header', ['framework' => 'ifrs_s1'])

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">{{ session('success') }}</div>
    @endif

    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title">Add sustainability risk</h3>
            <p class="card-subtitle">Broader than climate — water, workforce, supply chain, etc.</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('disclosures.s1.sustainability-risks.store', ['fiscal_year' => $fiscalYear]) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Risk name *</label>
                    <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Topic *</label>
                    <select name="topic" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        @foreach($topics as $key => $meta)
                            <option value="{{ $key }}">{{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Time horizon *</label>
                    <select name="time_horizon" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        @foreach(\App\Models\SustainabilityRisk::HORIZONS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <textarea name="description" rows="2" placeholder="Description" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="btn btn-primary">Add risk</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Registered risks ({{ $risks->count() }})</h3></div>
        <div class="card-body space-y-4">
            @forelse($risks as $risk)
                <details class="border border-gray-200 rounded-lg p-4">
                    <summary class="font-medium cursor-pointer flex justify-between">
                        <span>{{ $risk->name }}</span>
                        <span class="text-xs text-gray-500">{{ $risk->topicLabel() }}</span>
                    </summary>
                    <form method="POST" action="{{ route('disclosures.s1.sustainability-risks.update', ['sustainabilityRisk' => $risk, 'fiscal_year' => $fiscalYear]) }}" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                        @csrf @method('PUT')
                        <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                        <input type="text" name="name" value="{{ $risk->name }}" required class="md:col-span-2 w-full border border-gray-300 rounded-lg px-3 py-2">
                        <select name="topic" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            @foreach($topics as $key => $meta)
                                <option value="{{ $key }}" @selected($risk->topic === $key)>{{ $meta['label'] }}</option>
                            @endforeach
                        </select>
                        <select name="time_horizon" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            @foreach(\App\Models\SustainabilityRisk::HORIZONS as $value => $label)
                                <option value="{{ $value }}" @selected($risk->time_horizon === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <textarea name="description" rows="2" class="md:col-span-2 w-full border border-gray-300 rounded-lg px-3 py-2">{{ $risk->description }}</textarea>
                        <button type="submit" class="btn btn-secondary btn-sm md:col-span-2">Update</button>
                    </form>
                    <form method="POST" action="{{ route('disclosures.s1.sustainability-risks.destroy', ['sustainabilityRisk' => $risk, 'fiscal_year' => $fiscalYear]) }}" class="mt-2" onsubmit="return confirm('Remove?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:underline">Delete</button>
                    </form>
                </details>
            @empty
                <p class="text-gray-500 text-sm">No sustainability risks for {{ $fiscalYear }} yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
