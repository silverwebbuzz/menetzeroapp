@extends('layouts.app')

@section('title', 'Consultant orders')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Consultant orders</h1>
            <p class="text-sm text-gray-600 mt-1">Review packs purchased through MenetZero escrow.</p>
        </div>
        <a href="{{ route('client.consultants.index') }}" class="text-sm text-teal-600 hover:underline">Browse partners</a>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Consultant</th>
                    <th class="px-4 py-3">Pack</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3">Escrow</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($orders as $order)
                    <tr>
                        <td class="px-4 py-3 text-gray-600">{{ $order->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 font-medium">{{ $order->consultant?->company_name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ \App\Data\ConsultantOptions::labelFor('pack', $order->pack_type ?? '') }}</td>
                        <td class="px-4 py-3">AED {{ number_format($order->amount_aed, 0) }}</td>
                        <td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $order->escrow_status) }}</td>
                        <td class="px-4 py-3 capitalize">{{ $order->order_status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No consultant orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
</div>
@endsection
