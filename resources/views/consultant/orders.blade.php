@extends('consultant.layouts.app')

@section('title', 'Marketplace orders')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-6">Paid engagements</h1>

<div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
            <tr>
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Client</th>
                <th class="px-4 py-3">Pack</th>
                <th class="px-4 py-3">Your payout</th>
                <th class="px-4 py-3">Escrow</th>
                <th class="px-4 py-3">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($orders as $order)
                <tr>
                    <td class="px-4 py-3">{{ $order->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3">{{ $order->company?->name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ \App\Data\ConsultantOptions::labelFor('pack', $order->pack_type ?? '') }}</td>
                    <td class="px-4 py-3 font-medium">AED {{ number_format($order->payout_aed, 0) }}</td>
                    <td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $order->escrow_status) }}</td>
                    <td class="px-4 py-3 capitalize">{{ $order->order_status }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No paid orders yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
