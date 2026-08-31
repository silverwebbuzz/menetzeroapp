@extends('layouts.app')

@section('title', 'Climate Risks - IFRS S2')
@section('page-title', 'Climate Risk Register')

@section('content')
@php
    $totalRisks   = $risks->count();
    $highRisks    = $risks->where('likelihood', 'high')->count();
    $quantified   = $risks->filter(fn ($r) => filled($r->financial_impact))->count();
    $withoutOwner = $risks->filter(fn ($r) => blank($r->owner))->count();

    $likelihoodBadge = [
        'high'   => 'bg-red-50 text-red-700 border-red-200',
        'medium' => 'bg-amber-50 text-amber-700 border-amber-200',
        'low'    => 'bg-green-50 text-green-700 border-green-200',
    ];
@endphp
<div class="w-full">
    @include('disclosures.partials.header', ['context' => 'register'])

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="card"><div class="card-body">
            <div class="text-xs uppercase tracking-wide text-gray-500">Total risks</div>
            <div class="text-2xl font-semibold mt-1">{{ $totalRisks }}</div>
        </div></div>
        <div class="card"><div class="card-body">
            <div class="text-xs uppercase tracking-wide text-gray-500">High likelihood</div>
            <div class="text-2xl font-semibold mt-1 {{ $highRisks > 0 ? 'text-red-600' : '' }}">{{ $highRisks }}</div>
        </div></div>
        <div class="card"><div class="card-body">
            <div class="text-xs uppercase tracking-wide text-gray-500">Quantified</div>
            <div class="text-2xl font-semibold mt-1">{{ $quantified }} <span class="text-sm font-normal text-gray-500">of {{ $totalRisks }}</span></div>
        </div></div>
        <div class="card"><div class="card-body">
            <div class="text-xs uppercase tracking-wide text-gray-500">Without owner</div>
            <div class="text-2xl font-semibold mt-1 {{ $withoutOwner > 0 ? 'text-amber-600' : '' }}">{{ $withoutOwner }}</div>
        </div></div>
    </div>

    <div class="card mb-6">
        <div class="card-header"><h3 class="card-title">Registered risks ({{ $totalRisks }})</h3></div>
        @if($totalRisks)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500">
                            <th class="text-left px-4 py-2">Risk</th>
                            <th class="text-left px-4 py-2">Type</th>
                            <th class="text-left px-4 py-2">Horizon</th>
                            <th class="text-right px-4 py-2">Likelihood</th>
                            <th class="text-right px-4 py-2">Financial effect</th>
                            <th class="text-right px-4 py-2">Owner</th>
                            <th class="text-right px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($risks as $risk)
                            <tr class="border-b border-gray-100">
                                <td class="px-4 py-3 font-medium">{{ $risk->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ \App\Models\ClimateRisk::TYPES[$risk->risk_type] ?? $risk->risk_type }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ \App\Models\ClimateRisk::HORIZONS[$risk->time_horizon] ?? $risk->time_horizon }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if($risk->likelihood)
                                        <span class="inline-block text-xs px-2 py-0.5 rounded border {{ $likelihoodBadge[$risk->likelihood] ?? 'bg-gray-50 text-gray-600 border-gray-200' }}">
                                            {{ \App\Models\ClimateRisk::LIKELIHOODS[$risk->likelihood] ?? $risk->likelihood }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">&mdash;</span>
                                    @endif
                                </td>
                                {{-- financial_impact is free text, never parsed into a figure. --}}
                                <td class="px-4 py-3 text-right {{ filled($risk->financial_impact) ? 'font-medium' : 'text-gray-400' }}">
                                    {{ $risk->financial_impact ?: 'Not quantified' }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ $risk->owner ?: 'Unassigned' }}</td>
                                <td class="px-4 py-3 text-right"><a href="#risk-{{ $risk->id }}" class="text-sm text-blue-600 hover:underline">Open</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="card-body"><p class="text-gray-500 text-sm">No climate risks registered yet for {{ $fiscalYear }}.</p></div>
        @endif
    </div>

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
                    <input type="text" name="name" required value="{{ old('name') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                    <select name="risk_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        @foreach(\App\Models\ClimateRisk::TYPES as $value => $label)
                            <option value="{{ $value }}" @selected(old('risk_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Time horizon *</label>
                    <select name="time_horizon" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        @foreach(\App\Models\ClimateRisk::HORIZONS as $value => $label)
                            <option value="{{ $value }}" @selected(old('time_horizon') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Likelihood</label>
                    <select name="likelihood" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">&mdash;</option>
                        @foreach(\App\Models\ClimateRisk::LIKELIHOODS as $value => $label)
                            <option value="{{ $value }}" @selected(old('likelihood') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Owner</label>
                    <input type="text" name="owner" value="{{ old('owner') }}" placeholder="Role or team, e.g. CFO" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2">{{ old('description') }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Financial effect</label>
                    <textarea name="financial_impact" rows="2" placeholder="Anticipated effect on financial position, performance or cash flows" class="w-full border border-gray-300 rounded-lg px-3 py-2">{{ old('financial_impact') }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">IFRS S2 asks for the anticipated financial effect. A range is acceptable.</p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mitigation</label>
                    <textarea name="mitigation" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2">{{ old('mitigation') }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="btn btn-primary">Add risk</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($risks as $risk)
        <details id="risk-{{ $risk->id }}" class="card mb-3">
            <summary class="card-body font-medium text-gray-900 cursor-pointer flex justify-between items-center">
                <span>{{ $risk->name }}</span>
                <span class="text-xs text-gray-500">{{ \App\Models\ClimateRisk::TYPES[$risk->risk_type] ?? $risk->risk_type }} · {{ \App\Models\ClimateRisk::HORIZONS[$risk->time_horizon] ?? $risk->time_horizon }}</span>
            </summary>
            <div class="card-body border-t border-gray-100">
                <form method="POST" action="{{ route('disclosures.s2.climate-risks.update', ['climateRisk' => $risk, 'fiscal_year' => $fiscalYear]) }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @csrf @method('PUT')
                    <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                    <input type="text" name="name" value="{{ $risk->name }}" required class="md:col-span-2 w-full border border-gray-300 rounded-lg px-3 py-2">
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
                    <select name="likelihood" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">&mdash; Likelihood</option>
                        @foreach(\App\Models\ClimateRisk::LIKELIHOODS as $value => $label)
                            <option value="{{ $value }}" @selected($risk->likelihood === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="owner" value="{{ $risk->owner }}" placeholder="Owner" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <textarea name="description" rows="2" placeholder="Description" class="md:col-span-2 w-full border border-gray-300 rounded-lg px-3 py-2">{{ $risk->description }}</textarea>
                    <textarea name="financial_impact" rows="2" placeholder="Financial effect" class="md:col-span-2 w-full border border-gray-300 rounded-lg px-3 py-2">{{ $risk->financial_impact }}</textarea>
                    <textarea name="mitigation" rows="2" placeholder="Mitigation" class="md:col-span-2 w-full border border-gray-300 rounded-lg px-3 py-2">{{ $risk->mitigation }}</textarea>
                    <div class="md:col-span-2 flex gap-2">
                        <button type="submit" class="btn btn-secondary btn-sm">Update</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('disclosures.s2.climate-risks.destroy', ['climateRisk' => $risk, 'fiscal_year' => $fiscalYear]) }}" class="mt-2" onsubmit="return confirm('Remove this risk?')">
                    @csrf @method('DELETE')
                    <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                    <button type="submit" class="text-sm text-red-600 hover:underline">Delete</button>
                </form>
            </div>
        </details>
    @endforeach
</div>
@endsection
