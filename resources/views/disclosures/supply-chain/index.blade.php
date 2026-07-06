@extends('layouts.app')

@section('title', 'Supply Chain')
@section('page-title', 'Sustainable Supply Chain')

@section('content')
<div class="w-full">
    @include('layouts.partials.nav-disclosures-esg-depth', ['fiscalYear' => $fiscalYear])

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card"><div class="card-body text-center"><div class="text-2xl font-bold">{{ $suppliers->count() }}</div><div class="text-sm text-gray-500">Suppliers</div></div></div>
        <div class="card"><div class="card-body text-center"><div class="text-2xl font-bold">{{ number_format($totalSpend, 0) }}</div><div class="text-sm text-gray-500">Total spend (AED)</div></div></div>
        <div class="card"><div class="card-body text-center"><div class="text-2xl font-bold">{{ $screenedCount }}</div><div class="text-sm text-gray-500">Screened / in progress</div></div></div>
    </div>

    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title">Add supplier</h3>
            <p class="card-subtitle">Scope 3 Category 1 — GRI 308 / 414</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('disclosures.supply-chain.store', ['fiscal_year' => $fiscalYear]) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Supplier name *</label>
                    <input type="text" name="supplier_name" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        @foreach(\App\Models\SupplyChainSupplier::CATEGORIES as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Spend (AED)</label>
                    <input type="number" step="0.01" name="spend_aed" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                    <input type="text" name="country" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Screening status</label>
                    <select name="screening_status" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        @foreach(\App\Models\SupplyChainSupplier::SCREENING as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-4 md:col-span-2">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="human_rights_assessed" value="1"> Human rights assessed</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="environmental_assessed" value="1"> Environmental assessed</label>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="btn btn-primary">Add supplier</button>
                    <a href="{{ route('disclosures.gri.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'supply_chain']) }}" class="btn btn-secondary ml-2">GRI supply chain narrative</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Supplier register</h3></div>
        <div class="card-body overflow-x-auto">
            @if($suppliers->isEmpty())
                <p class="text-sm text-gray-500">No suppliers recorded for {{ $fiscalYear }}.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-2">Supplier</th>
                            <th>Category</th>
                            <th class="text-right">Spend AED</th>
                            <th>Country</th>
                            <th>Screening</th>
                            <th>H.R.</th>
                            <th>Env.</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($suppliers as $s)
                            <tr class="border-b border-gray-50">
                                <td class="py-2 font-medium">{{ $s->supplier_name }}</td>
                                <td>{{ \App\Models\SupplyChainSupplier::CATEGORIES[$s->category] ?? $s->category }}</td>
                                <td class="text-right">{{ $s->spend_aed !== null ? number_format($s->spend_aed, 0) : '—' }}</td>
                                <td>{{ $s->country ?: '—' }}</td>
                                <td>{{ \App\Models\SupplyChainSupplier::SCREENING[$s->screening_status] ?? $s->screening_status }}</td>
                                <td>{{ $s->human_rights_assessed ? '✓' : '—' }}</td>
                                <td>{{ $s->environmental_assessed ? '✓' : '—' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('disclosures.supply-chain.destroy', ['supplyChainSupplier' => $s, 'fiscal_year' => $fiscalYear]) }}" onsubmit="return confirm('Remove supplier?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 text-xs">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
