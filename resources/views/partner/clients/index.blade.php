@extends('consultant.layouts.app')

@section('title', 'Managed Clients')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Managed clients</h1>
        <p class="text-sm text-gray-600 mt-1">{{ $slotSummary['used'] }} of {{ $slotSummary['limit'] }} slots used · {{ $slotSummary['remaining'] }} remaining</p>
    </div>
    @if($slotSummary['remaining'] > 0 && $slotSummary['limit'] > 0)
        <a href="{{ route('consultant.clients.create') }}" class="inline-flex justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">Add client</a>
    @endif
</div>

<div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left text-gray-500">
            <tr>
                <th class="px-4 py-3 font-medium">Client</th>
                <th class="px-4 py-3 font-medium">PRY</th>
                <th class="px-4 py-3 font-medium">Contract</th>
                <th class="px-4 py-3 font-medium">Status</th>
                <th class="px-4 py-3 font-medium"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($engagements as $engagement)
                <tr>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ $engagement->display_name ?: $engagement->managedCompany?->name }}</div>
                        <div class="text-xs text-gray-500">{{ $engagement->managedCompany?->emirate ?? $engagement->managedCompany?->country }}</div>
                    </td>
                    <td class="px-4 py-3">{{ $engagement->primary_reporting_year }}</td>
                    <td class="px-4 py-3">{{ $engagement->subscription?->contract_year ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if($engagement->status === 'active')
                            <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">Active</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600">Archived</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right space-x-3">
                        <a href="{{ route('consultant.clients.show', $engagement) }}" class="text-indigo-600 hover:underline">View</a>
                        @if($engagement->status === 'active')
                            <a href="{{ route('consultant.clients.edit', $engagement) }}" class="text-gray-600 hover:underline">Edit</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">No managed clients yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
