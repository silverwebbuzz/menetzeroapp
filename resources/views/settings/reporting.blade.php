@extends('layouts.app')

@section('title', 'Company Reporting Settings - MenetZero')
@section('page-title', 'Company Reporting Settings')

@section('content')
<div class="w-full">
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">{{ session('success') }}</div>
    @endif

    <div class="card mb-6">
        <div class="card-header">
            <div>
                <h3 class="card-title">GHG inventory methodology</h3>
                <p class="card-subtitle">Required for IFRS S2, MOCCAE/IEQT, and credible Scope 3 reporting.</p>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.reporting.update') }}" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reporting year</label>
                        <input type="number" name="fiscal_year" value="{{ old('fiscal_year', $fiscalYear) }}" min="2000" max="2100" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Base year</label>
                        <input type="number" name="base_year" value="{{ old('base_year', $settings->base_year) }}" min="1990" max="2100"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Organisational boundary</label>
                        <select name="organisational_boundary" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                            @foreach($boundaries as $value => $label)
                                <option value="{{ $value }}" @selected(old('organisational_boundary', $settings->organisational_boundary) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Consolidation approach</label>
                        <select name="consolidation_approach" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                            @foreach($boundaries as $value => $label)
                                <option value="{{ $value }}" @selected(old('consolidation_approach', $settings->consolidation_approach) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">GWP version</label>
                    <select name="gwp_version" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                        @foreach(['AR4', 'AR5', 'AR6'] as $gwp)
                            <option value="{{ $gwp }}" @selected(old('gwp_version', $settings->gwp_version) === $gwp)>IPCC {{ $gwp }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Base year rationale</label>
                    <textarea name="base_year_rationale" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2"
                              placeholder="Why this base year was chosen…">{{ old('base_year_rationale', $settings->base_year_rationale) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Recalculation policy</label>
                    <textarea name="recalculation_policy" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2"
                              placeholder="When and how you recalculate base-year emissions…">{{ old('recalculation_policy', $settings->recalculation_policy) }}</textarea>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Scope 3 category coverage (GHG Protocol / IFRS S2)</h4>
                    <p class="text-xs text-gray-500 mb-4">Tick categories you measure and report. Unchecked categories need a brief reason for exclusion.</p>
                    <div class="space-y-3">
                        @php
                            $policyByCat = collect($settings->scope3_category_policy ?? [])->keyBy('category');
                        @endphp
                        @foreach($scope3Categories as $cat => $label)
                            @php
                                $row = $policyByCat->get($cat, ['included' => false, 'reason' => '']);
                                $included = old('scope3_included') ? in_array($cat, old('scope3_included', [])) : ($row['included'] ?? false);
                            @endphp
                            <div class="flex flex-col sm:flex-row sm:items-start gap-2 p-3 border border-gray-200 rounded-lg">
                                <label class="flex items-start gap-2 sm:w-1/2">
                                    <input type="checkbox" name="scope3_included[]" value="{{ $cat }}" @checked($included) class="mt-1">
                                    <span class="text-sm text-gray-800">{{ $label }}</span>
                                </label>
                                <input type="text" name="scope3_reason[{{ $cat }}]" value="{{ old('scope3_reason.'.$cat, $row['reason'] ?? '') }}"
                                       placeholder="Reason if excluded"
                                       class="flex-1 text-sm border border-gray-300 rounded-lg px-3 py-2">
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">Save settings</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
