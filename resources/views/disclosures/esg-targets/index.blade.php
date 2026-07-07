@extends('layouts.app')

@section('title', 'ESG Targets')
@section('page-title', 'ESG Sustainability Targets')

@section('content')
<div class="w-full">
    @include('layouts.partials.nav-disclosures-esg-depth', ['fiscalYear' => $fiscalYear])

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">{{ session('success') }}</div>
    @endif

    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title">Add non-climate ESG target</h3>
            <p class="card-subtitle">Water, waste, diversity, social, governance — climate targets remain in IFRS S2 reduction targets.</p>
        </div>
        <div class="card-body">
            <x-field-help key="esg_depth.targets.intro" class="mb-4" />
            <form method="POST" action="{{ route('disclosures.esg-targets.store', ['fiscal_year' => $fiscalYear]) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Target name *</label>
                    <input type="text" name="name" required placeholder="e.g. Reduce water withdrawal 20% by 2030" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <x-field-help key="esg_depth.targets.name" class="mt-1" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                    <select name="target_category" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        @foreach(\App\Models\EsgSustainabilityTarget::CATEGORIES as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-field-help key="esg_depth.targets.target_category" class="mt-1" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Metric label</label>
                    <input type="text" name="metric_label" placeholder="e.g. Water withdrawal m³" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <x-field-help key="esg_depth.targets.metric_label" class="mt-1" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Baseline value</label>
                    <input type="number" step="any" name="baseline_value" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <x-field-help key="esg_depth.targets.baseline_value" class="mt-1" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Target value</label>
                    <input type="number" step="any" name="target_value" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <x-field-help key="esg_depth.targets.target_value" class="mt-1" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                    <input type="text" name="unit" placeholder="m³, %, tonnes" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <x-field-help key="esg_depth.targets.unit" class="mt-1" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Base year</label>
                    <input type="number" name="base_year" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <x-field-help key="esg_depth.targets.base_year" class="mt-1" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Target year *</label>
                    <input type="number" name="target_year" required value="{{ $fiscalYear + 5 }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <x-field-help key="esg_depth.targets.target_year" class="mt-1" />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                    <x-field-help key="esg_depth.targets.notes" class="mt-1" />
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="btn btn-primary">Save target</button>
                    <a href="{{ route('disclosures.s2.targets.index', ['fiscal_year' => $fiscalYear]) }}" class="btn btn-secondary ml-2">Climate reduction targets (IFRS S2)</a>
                </div>
            </form>
        </div>
    </div>

    @if($climateTargets->isNotEmpty())
        <div class="card mb-6">
            <div class="card-header"><h3 class="card-title">Climate targets (IFRS S2 — read only)</h3></div>
            <div class="card-body overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-gray-500 border-b"><th class="py-2">Target</th><th>Scope</th><th>Target year</th><th>Reduction %</th></tr></thead>
                    <tbody>
                        @foreach($climateTargets as $t)
                            <tr class="border-b border-gray-50">
                                <td class="py-2">{{ $t->name }}</td>
                                <td>{{ \App\Models\ReductionTarget::SCOPE_COVERAGE[$t->scope_coverage] ?? $t->scope_coverage }}</td>
                                <td>{{ $t->target_year }}</td>
                                <td>{{ $t->reduction_percent !== null ? $t->reduction_percent.'%' : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header"><h3 class="card-title">Non-climate ESG targets ({{ $targets->count() }})</h3></div>
        <div class="card-body overflow-x-auto">
            @if($targets->isEmpty())
                <p class="text-sm text-gray-500">No non-climate ESG targets defined yet.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-2">Target</th>
                            <th>Category</th>
                            <th>Baseline → Target</th>
                            <th>Years</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($targets as $t)
                            <tr class="border-b border-gray-50">
                                <td class="py-2">
                                    <div class="font-medium">{{ $t->name }}</div>
                                    @if($t->metric_label)<div class="text-xs text-gray-500">{{ $t->metric_label }}</div>@endif
                                </td>
                                <td>{{ \App\Models\EsgSustainabilityTarget::CATEGORIES[$t->target_category] ?? $t->target_category }}</td>
                                <td>
                                    {{ $t->baseline_value !== null ? number_format($t->baseline_value, 2) : '—' }}
                                    → {{ $t->target_value !== null ? number_format($t->target_value, 2) : '—' }}
                                    {{ $t->unit }}
                                </td>
                                <td>{{ $t->base_year ?? '—' }} → {{ $t->target_year }}</td>
                                <td>{{ \App\Models\EsgSustainabilityTarget::STATUSES[$t->status] ?? $t->status }}</td>
                                <td>
                                    <form method="POST" action="{{ route('disclosures.esg-targets.destroy', ['esgSustainabilityTarget' => $t, 'fiscal_year' => $fiscalYear]) }}" onsubmit="return confirm('Remove target?')">
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
