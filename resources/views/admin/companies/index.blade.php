@extends('admin.layouts.app')

@section('title', 'Companies | MENetZero')
@section('page-title', 'Companies')

@section('content')
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-medium text-gray-900">All Companies</h2>

            <form method="GET" class="flex items-center gap-3 text-sm">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search by name or email..."
                    class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500"
                >
                <select
                    name="type"
                    class="border border-gray-300 rounded-md px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500"
                >
                    <option value="">All types</option>
                    <option value="client" {{ request('type') === 'client' ? 'selected' : '' }}>Client</option>
                    <option value="partner" {{ request('type') === 'partner' ? 'selected' : '' }}>Partner</option>
                </select>
                <button
                    type="submit"
                    class="inline-flex items-center px-3 py-1.5 rounded-md bg-purple-600 text-white text-sm font-medium hover:bg-purple-700"
                >
                    Filter
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-4 py-2 text-right font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($companies as $company)
                        <tr>
                            <td class="px-4 py-2 text-gray-900">
                                {{ $company->name }}
                            </td>
                            <td class="px-4 py-2 text-gray-500">
                                {{ $company->email }}
                            </td>
                            <td class="px-4 py-2 text-gray-500">
                                {{ $company->company_type ?? 'client' }}
                            </td>
                            <td class="px-4 py-2 text-gray-500">
                                {{ optional($company->created_at)->format('Y-m-d') }}
                            </td>
                            <td class="px-4 py-2 text-right">
                                <a
                                    href="{{ route('admin.companies.show', $company->id) }}"
                                    class="text-purple-600 hover:text-purple-800 font-medium"
                                >
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center text-gray-500">
                                No companies found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-gray-200">
            {{ $companies->withQueryString()->links() }}
        </div>
    </div>
@endsection


