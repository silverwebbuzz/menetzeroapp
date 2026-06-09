@extends('admin.layouts.app')

@section('title', 'Pricing Page Content | MENetZero')
@section('page-title', 'Pricing Page Content')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <p class="text-sm text-gray-500 mb-6">
        <strong>Public pricing</strong> and the in-app <strong>upgrade comparison tables</strong> are driven by plan entitlements
        (<a href="{{ route('admin.subscription-plans') }}" class="text-purple-600 hover:underline">Plans &amp; entitlements</a>).
        Use this section for optional <em>extra</em> rows on the legacy upgrade matrix, or
        <a href="{{ route('pricing') }}" target="_blank" class="text-purple-600 hover:underline">preview public pricing ↗</a>.
    </p>

    <!-- Feature comparison rows -->
    <div class="bg-white shadow rounded-lg mb-8">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-medium text-gray-900">Feature Comparison Rows</h2>
            <a href="{{ route('admin.pricing.feature-rows.create') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium">+ New Row</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Feature</th>
                        <th class="px-4 py-2 text-center font-medium text-gray-500 uppercase tracking-wider">Starter</th>
                        <th class="px-4 py-2 text-center font-medium text-gray-500 uppercase tracking-wider">Growth</th>
                        <th class="px-4 py-2 text-center font-medium text-gray-500 uppercase tracking-wider">Enterprise</th>
                        <th class="px-4 py-2 text-center font-medium text-gray-500 uppercase tracking-wider">Coming soon</th>
                        <th class="px-4 py-2 text-center font-medium text-gray-500 uppercase tracking-wider">Active</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($featureRows as $row)
                        <tr>
                            <td class="px-4 py-2 text-gray-500">{{ $row->sort_order }}</td>
                            <td class="px-4 py-2 text-gray-900 font-medium">{{ $row->label }}</td>
                            <td class="px-4 py-2 text-center text-gray-600">{{ $row->value_starter ?: '—' }}</td>
                            <td class="px-4 py-2 text-center text-gray-600">{{ $row->value_growth ?: '—' }}</td>
                            <td class="px-4 py-2 text-center text-gray-600">{{ $row->value_enterprise ?: '—' }}</td>
                            <td class="px-4 py-2 text-center">{{ $row->coming_soon ? 'Yes' : '—' }}</td>
                            <td class="px-4 py-2 text-center">
                                <span class="px-2 py-1 text-xs rounded-full {{ $row->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">{{ $row->is_active ? 'Yes' : 'No' }}</span>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('admin.pricing.feature-rows.edit', $row->id) }}" class="text-purple-600 hover:text-purple-900 text-sm">Edit</a>
                                    <form action="{{ route('admin.pricing.feature-rows.destroy', $row->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this feature row?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 text-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-4 text-center text-gray-500">No feature rows yet. <a href="{{ route('admin.pricing.feature-rows.create') }}" class="text-purple-600 hover:underline">Add one</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-2 text-xs text-gray-400 border-t border-gray-100">Cell values: type <code>yes</code> for a tick, leave blank or <code>no</code> for a dash, or any text (e.g. "Up to 10").</div>
    </div>

    <!-- Scope 3 add-ons -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-medium text-gray-900">Scope 3 Add-Ons</h2>
            <a href="{{ route('admin.pricing.addons.create') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium">+ New Add-On</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Items</th>
                        <th class="px-4 py-2 text-center font-medium text-gray-500 uppercase tracking-wider">Active</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($addons as $addon)
                        <tr>
                            <td class="px-4 py-2 text-gray-500">{{ $addon->sort_order }}</td>
                            <td class="px-4 py-2 text-gray-900 font-medium">{{ $addon->name }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ $addon->price_display }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ count($addon->items ?? []) }} item(s)</td>
                            <td class="px-4 py-2 text-center">
                                <span class="px-2 py-1 text-xs rounded-full {{ $addon->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">{{ $addon->is_active ? 'Yes' : 'No' }}</span>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('admin.pricing.addons.edit', $addon->id) }}" class="text-purple-600 hover:text-purple-900 text-sm">Edit</a>
                                    <form action="{{ route('admin.pricing.addons.destroy', $addon->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this add-on?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 text-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-4 text-center text-gray-500">No add-ons yet. <a href="{{ route('admin.pricing.addons.create') }}" class="text-purple-600 hover:underline">Add one</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
