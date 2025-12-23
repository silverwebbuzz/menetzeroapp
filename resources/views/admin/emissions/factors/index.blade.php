@extends('admin.layouts.app')

@section('title', 'Emission Factors | MENetZero')
@section('page-title', 'Emission Factors')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-medium text-gray-900">Emission Factors</h2>
            <a href="{{ route('admin.emissions.factors.create') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                + New Factor
            </a>
        </div>

        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between text-sm">
            <form method="GET" class="flex items-center gap-3">
                <select name="source_id" class="border border-gray-300 rounded-md px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500">
                    <option value="">All Sources</option>
                    @foreach($sources as $source)
                        <option value="{{ $source->id }}" {{ request('source_id') == $source->id ? 'selected' : '' }}>{{ $source->name }}</option>
                    @endforeach
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
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Source</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Factor Value</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Region</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">CO2e Factor</th>
                        <th class="px-4 py-2 text-right font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($factors as $factor)
                        <tr>
                            <td class="px-4 py-2 text-gray-900">{{ $factor->emissionSource->name ?? 'N/A' }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ number_format($factor->factor_value, 6) }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $factor->unit }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $factor->region ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $factor->total_co2e_factor ? number_format($factor->total_co2e_factor, 6) : '—' }}</td>
                            <td class="px-4 py-2 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('admin.emissions.factors.edit', $factor->id) }}" class="text-purple-600 hover:text-purple-900 text-sm">Edit</a>
                                    <form action="{{ route('admin.emissions.factors.destroy', $factor->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this emission factor?');">
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
                                No emission factors found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-gray-200">
            {{ $factors->withQueryString()->links() }}
        </div>
    </div>
@endsection

