@extends('admin.layouts.app')

@section('title', 'Marketplace orders | MENetZero')
@section('page-title', 'Consultant marketplace orders')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
    @endif

    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('admin.consultants.index') }}" class="text-sm text-brand hover:underline">&larr; Consultants</a>
        <span class="text-xs text-gray-500">Commission rate: {{ number_format($commissionRate * 100, 1) }}%</span>
    </div>

    <div class="bg-white shadow rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">ID</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Client</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Consultant</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Pack</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Amount</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Payout</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Escrow</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Status</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($orders as $order)
                    <tr>
                        <td class="px-4 py-2">#{{ $order->id }}</td>
                        <td class="px-4 py-2">{{ $order->company?->name ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $order->consultant?->company_name ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs">{{ \App\Data\ConsultantOptions::labelFor('pack', $order->pack_type ?? '') }}</td>
                        <td class="px-4 py-2">{{ number_format($order->amount_aed, 0) }} AED</td>
                        <td class="px-4 py-2 text-xs">{{ number_format($order->payout_aed, 0) }} AED</td>
                        <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $order->escrow_status) }}</td>
                        <td class="px-4 py-2 capitalize">{{ $order->order_status }}</td>
                        <td class="px-4 py-2 space-x-1 whitespace-nowrap">
                            @if($order->order_status === 'active')
                                <form action="{{ route('admin.consultants.orders.deliver', $order) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs text-brand hover:underline">Delivered</button>
                                </form>
                            @endif
                            @if($order->escrow_status === 'held' && in_array($order->order_status, ['delivered', 'active']))
                                <form action="{{ route('admin.consultants.orders.release', $order) }}" method="POST" class="inline" onsubmit="return confirm('Release escrow to consultant?')">
                                    @csrf
                                    <button type="submit" class="text-xs text-green-700 hover:underline">Release</button>
                                </form>
                            @endif
                            @if($order->escrow_status === 'held')
                                <form action="{{ route('admin.consultants.orders.refund', $order) }}" method="POST" class="inline" onsubmit="return confirm('Refund escrow to client?')">
                                    @csrf
                                    <button type="submit" class="text-xs text-red-600 hover:underline">Refund</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-6 text-center text-gray-500">No marketplace orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
@endsection
