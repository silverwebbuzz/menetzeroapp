@extends('admin.layouts.app')

@section('title', 'Marketplace orders | MENetZero')
@section('page-title', 'Consultant marketplace orders')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('admin.consultants.index') }}" class="text-sm text-brand hover:underline">&larr; Consultants</a>
        <span class="text-xs text-gray-500">Escrow + 15% commission — Phase C10</span>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6 text-sm text-amber-900">
        Marketplace checkout and escrow release are not live yet. Orders created here will appear once C10 is implemented.
    </div>

    <div class="bg-white shadow rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">ID</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Client</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Consultant</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Amount</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Escrow</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($orders as $order)
                    <tr>
                        <td class="px-4 py-2">#{{ $order->id }}</td>
                        <td class="px-4 py-2">{{ $order->company?->name ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $order->consultant?->company_name ?? '—' }}</td>
                        <td class="px-4 py-2">{{ number_format($order->amount_aed, 0) }} AED</td>
                        <td class="px-4 py-2">{{ str_replace('_', ' ', $order->escrow_status) }}</td>
                        <td class="px-4 py-2">{{ $order->order_status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No marketplace orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
@endsection
