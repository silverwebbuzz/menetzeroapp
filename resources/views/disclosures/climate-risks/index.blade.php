@extends('layouts.app')

@section('title', 'Climate Risks - IFRS S2')
@section('page-title', 'Climate Risk Register')

@section('content')
<div class="max-w-5xl mx-auto">
    @include('disclosures.partials.header')

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">{{ session('success') }}</div>
    @endif

    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title">Add climate risk</h3>
            <p class="card-subtitle">Physical and transition risks for {{ $fiscalYear }} (IFRS S2 §10).</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('disclosures.s2.climate-risks.store', ['fiscal_year' => $fiscalYear]) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Risk name *</label>
                    <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                    <select name="risk_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        @foreach(\App\Models\ClimateRisk::TYPES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Time horizon *</label>
                    <select name="time_horizon" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        @foreach(\App\Models\ClimateRisk::HORIZONS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Likelihood</label>
                    <select name="likelihood" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">—</option>
                        @foreach(\App\Models\ClimateRisk::LIKELIHOODS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Owner</label>
                    <input type="text" name="owner" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Financial impact</label>
                    <textarea name="financial_impact" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mitigation</label>
                    <textarea name="mitigation" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="btn btn-primary">Add risk</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Registered risks ({{ $risks->count() }})</h3>
        </div>
        <div class="card-body space-y-4">
            @forelse($risks as $risk)
                <details class="border border-gray-200 rounded-lg p-4">
                    <summary class="font-medium text-gray-900 cursor-pointer flex justify-between items-center">
                        <span>{{ $risk->name }}</span>
                        <span class="text-xs text-gray-500">{{ ucfirst($risk->risk_type) }} · {{ \App\Models\ClimateRisk::HORIZONS[$risk->time_horizon] ?? $risk->time_horizon }}</span>
                    </summary>
                    <form method="POST" action="{{ route('disclosures.s2.climate-risks.update', ['climateRisk' => $risk, 'fiscal_year' => $fiscalYear]) }}" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                        <div class="md:col-span-2">
                            <input type="text" name="name" value="{{ $risk->name }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        <select name="risk_type" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            @foreach(\App\Models\ClimateRisk::TYPES as $value => $label)
                                <option value="{{ $value }}" @selected($risk->risk_type === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <select name="time_horizon" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            @foreach(\App\Models\ClimateRisk::HORIZONS as $value => $label)
                                <option value="{{ $value }}" @selected($risk->time_horizon === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <textarea name="description" rows="2" class="md:col-span-2 w-full border border-gray-300 rounded-lg px-3 py-2">{{ $risk->description }}</textarea>
                        <textarea name="financial_impact" rows="2" class="md:col-span-2 w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Financial impact">{{ $risk->financial_impact }}</textarea>
                        <textarea name="mitigation" rows="2" class="md:col-span-2 w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Mitigation">{{ $risk->mitigation }}</textarea>
                        <div class="md:col-span-2 flex gap-2">
                            <button type="submit" class="btn btn-secondary btn-sm">Update</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('disclosures.s2.climate-risks.destroy', ['climateRisk' => $risk, 'fiscal_year' => $fiscalYear]) }}" class="mt-2" onsubmit="return confirm('Remove this risk?')">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                        <button type="submit" class="text-sm text-red-600 hover:underline">Delete</button>
                    </form>
                </details>
            @empty
                <p class="text-gray-500 text-sm">No climate risks registered yet for {{ $fiscalYear }}.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
