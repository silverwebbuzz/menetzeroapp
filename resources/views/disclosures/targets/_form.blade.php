@php
    $actions = $target?->transitionActions ?? collect();
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Target name *</label>
        <input type="text" name="name" value="{{ old('name', $target->name ?? '') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
        <select name="target_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
            <option value="absolute" @selected(old('target_type', $target->target_type ?? 'absolute') === 'absolute')>Absolute</option>
            <option value="intensity" @selected(old('target_type', $target->target_type ?? '') === 'intensity')>Intensity</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Scope coverage *</label>
        <select name="scope_coverage" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
            @foreach(\App\Models\ReductionTarget::SCOPE_COVERAGE as $value => $label)
                <option value="{{ $value }}" @selected(old('scope_coverage', $target->scope_coverage ?? 'scope12') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Base year</label>
        <input type="number" name="base_year" value="{{ old('base_year', $target->base_year ?? '') }}" min="1990" max="2100" class="w-full border border-gray-300 rounded-lg px-3 py-2">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Target year *</label>
        <input type="number" name="target_year" value="{{ old('target_year', $target->target_year ?? now()->year + 5) }}" required min="2000" max="2100" class="w-full border border-gray-300 rounded-lg px-3 py-2">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Baseline (tCO₂e)</label>
        <input type="number" step="0.0001" name="baseline_tco2e" value="{{ old('baseline_tco2e', $target->baseline_tco2e ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Target (tCO₂e)</label>
        <input type="number" step="0.0001" name="target_tco2e" value="{{ old('target_tco2e', $target->target_tco2e ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Reduction %</label>
        <input type="number" step="0.01" name="reduction_percent" value="{{ old('reduction_percent', $target->reduction_percent ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
    </div>
    <div class="flex items-center gap-2 pt-6">
        <input type="checkbox" name="sbti_aligned" value="1" id="sbti-{{ $prefix }}" @checked(old('sbti_aligned', $target->sbti_aligned ?? false))>
        <label for="sbti-{{ $prefix }}" class="text-sm text-gray-700">SBTi-aligned target</label>
    </div>
</div>

<div class="mt-4">
    <h4 class="text-sm font-semibold text-gray-900 mb-2">Transition actions</h4>
    <div class="space-y-3">
        @for($i = 0; $i < max(1, $actions->count()); $i++)
            @php $action = $actions[$i] ?? null; @endphp
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2 p-3 bg-gray-50 rounded-lg">
                <input type="text" name="actions[{{ $i }}][title]" value="{{ $action->title ?? '' }}" placeholder="Action title" class="md:col-span-3 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <input type="number" name="actions[{{ $i }}][planned_year]" value="{{ $action->planned_year ?? '' }}" placeholder="Year" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <input type="number" step="0.01" name="actions[{{ $i }}][capex_aed]" value="{{ $action->capex_aed ?? '' }}" placeholder="CAPEX (AED)" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <input type="number" step="0.0001" name="actions[{{ $i }}][expected_reduction_tco2e]" value="{{ $action->expected_reduction_tco2e ?? '' }}" placeholder="Expected reduction tCO₂e" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <textarea name="actions[{{ $i }}][description]" rows="2" placeholder="Description" class="md:col-span-3 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ $action->description ?? '' }}</textarea>
            </div>
        @endfor
        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 p-3 bg-gray-50 rounded-lg border border-dashed border-gray-300">
            <input type="text" name="actions[{{ max(1, $actions->count()) }}][title]" placeholder="Additional action (optional)" class="md:col-span-3 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <input type="number" name="actions[{{ max(1, $actions->count()) }}][planned_year]" placeholder="Year" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <input type="number" step="0.01" name="actions[{{ max(1, $actions->count()) }}][capex_aed]" placeholder="CAPEX (AED)" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <input type="number" step="0.0001" name="actions[{{ max(1, $actions->count()) }}][expected_reduction_tco2e]" placeholder="Expected reduction tCO₂e" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
    </div>
</div>
