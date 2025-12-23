@extends('admin.layouts.app')

@section('title', 'GWP Values | MENetZero')
@section('page-title', 'GWP Values')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-medium text-gray-900">GWP Values</h2>
            <a href="{{ route('admin.emissions.gwp-values.create') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                + New GWP Value
            </a>
        </div>

        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between text-sm">
            <form method="GET" class="flex items-center gap-3">
                <select name="gwp_version" class="border border-gray-300 rounded-md px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500">
                    <option value="">All Versions</option>
                    <option value="AR4" {{ request('gwp_version') === 'AR4' ? 'selected' : '' }}>AR4</option>
                    <option value="AR5" {{ request('gwp_version') === 'AR5' ? 'selected' : '' }}>AR5</option>
                    <option value="AR6" {{ request('gwp_version') === 'AR6' ? 'selected' : '' }}>AR6</option>
                </select>
                <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-md bg-purple-600 text-white text-sm font-medium hover:bg-purple-700">
                    Filter
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Gas Name</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Gas Code</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Version</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">GWP 100y</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Kyoto</th>
                        <th class="px-4 py-2 text-right font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($gwpValues as $value)
                        <tr>
                            <td class="px-4 py-2 text-gray-900">{{ $value->gas_name }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $value->gas_code ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $value->gwp_version }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ number_format($value->gwp_100_year, 2) }}</td>
                            <td class="px-4 py-2 text-gray-500">
                                <span class="px-2 py-1 text-xs rounded-full {{ $value->is_kyoto_protocol ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $value->is_kyoto_protocol ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('admin.emissions.gwp-values.edit', $value->id) }}" class="text-purple-600 hover:text-purple-900 text-sm">Edit</a>
                                    <form action="{{ route('admin.emissions.gwp-values.destroy', $value->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this GWP value?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 text-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-4 text-center text-gray-500">
                                No GWP values found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-gray-200">
            {{ $gwpValues->withQueryString()->links() }}
        </div>
    </div>
@endsection

