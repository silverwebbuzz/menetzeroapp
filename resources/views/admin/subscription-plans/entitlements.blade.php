@extends('admin.layouts.app')

@section('title', 'Plan Entitlements | MENetZero')
@section('page-title', 'Plan Entitlements')

@section('content')
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-gray-900">{{ $plan->plan_name }}</h2>
            <p class="text-sm text-gray-500"><code>{{ $plan->plan_code }}</code> · AED {{ number_format($plan->price_annual, 0) }}/year</p>
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
            <a href="{{ route('admin.subscription-plans.edit', $plan->id) }}" class="px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50">Edit plan &amp; price</a>
            <a href="{{ route('admin.subscription-plans') }}" class="px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50">All plans</a>
            <a href="{{ route('pricing') }}" target="_blank" class="px-3 py-1.5 text-purple-700 border border-purple-200 rounded-lg hover:bg-purple-50">View public pricing ↗</a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.subscription-plans.entitlements.update', $plan->id) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Limits</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Locations</label>
                    <input type="number" name="locations" value="{{ old('locations', $form['locations']) }}" min="-1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <p class="text-xs text-gray-500 mt-1">Use -1 for unlimited.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Users</label>
                    <input type="number" name="users" value="{{ old('users', $form['users']) }}" min="-1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Data &amp; operations</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Scope 3 mode</label>
                    <select name="scope3_mode" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        @foreach($scope3Modes as $value => $label)
                            <option value="{{ $value }}" @selected(old('scope3_mode', $form['scope3_mode']) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Help guide level</label>
                    <select name="help_level" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        @foreach($helpLevels as $value => $label)
                            <option value="{{ $value }}" @selected(old('help_level', $form['help_level']) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Consultant directory</label>
                    <select name="consultant_directory" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        @foreach($consultantLevels as $value => $label)
                            <option value="{{ $value }}" @selected(old('consultant_directory', $form['consultant_directory']) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Export regeneration</label>
                    <select name="export_regen" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        @foreach($exportRegenModes as $value => $label)
                            <option value="{{ $value }}" @selected(old('export_regen', $form['export_regen']) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-6">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="bulk_import" value="1" @checked(old('bulk_import', $form['bulk_import'])) class="rounded border-gray-300 text-purple-600">
                    Bulk CSV/XLS import
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="bulk_export" value="1" @checked(old('bulk_export', $form['bulk_export'])) class="rounded border-gray-300 text-purple-600">
                    Bulk data export
                </label>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Disclosures</h3>
            <div class="flex flex-wrap gap-6">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="disclosures_access" value="1" @checked(old('disclosures_access', $form['disclosures_access'])) class="rounded border-gray-300 text-purple-600">
                    Form access (IFRS / GRI / ESG)
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="disclosures_export" value="1" @checked(old('disclosures_export', $form['disclosures_export'])) class="rounded border-gray-300 text-purple-600">
                    PDF / content index export
                </label>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Report exports</h3>
            <p class="text-sm text-gray-500 mb-4">Controls download routes gated by PlanEntitlementService.</p>
            <label class="inline-flex items-center gap-2 text-sm font-medium mb-4">
                <input type="checkbox" name="exports_all" value="1" id="exports_all" @checked(old('exports_all', $form['exports_all'])) class="rounded border-gray-300 text-purple-600">
                All exports (Enterprise wildcard)
            </label>
            <div id="export-checkboxes" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($exportOptions as $code => $label)
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="exports[]" value="{{ $code }}"
                               @checked(in_array($code, old('exports', $form['exports']), true))
                               class="export-item rounded border-gray-300 text-purple-600">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium">
                Save entitlements
            </button>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.subscription-plans.entitlements.reset', $plan->id) }}" class="mt-4"
          onsubmit="return confirm('Reset entitlements to Commercial Plan v1 defaults for this plan code?');">
        @csrf
        <button type="submit" class="px-4 py-2 text-sm border border-amber-300 text-amber-800 rounded-lg hover:bg-amber-50">
            Reset to Commercial Plan v1 defaults
        </button>
    </form>

    <script>
        const allBox = document.getElementById('exports_all');
        const items = document.querySelectorAll('.export-item');
        function syncExports() {
            const disabled = allBox.checked;
            items.forEach(cb => { cb.disabled = disabled; if (disabled) cb.checked = true; });
        }
        allBox?.addEventListener('change', syncExports);
        syncExports();
    </script>
@endsection
