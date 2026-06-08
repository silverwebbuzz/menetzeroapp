@extends('admin.layouts.app')

@section('title', 'Coupons | MENetZero')
@section('page-title', 'Subscription Coupons')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif

    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-medium text-gray-900">Campaign coupons</h2>
                <p class="text-xs text-gray-500 mt-1">Percent off, fixed discount, or free plan codes for client checkout.</p>
            </div>
            <a href="{{ route('admin.coupons.create') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium">+ New coupon</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-500">Code</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500">Name</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500">Offer</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500">Plan</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500">Uses</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500">Valid</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500">Active</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($coupons as $coupon)
                        <tr>
                            <td class="px-4 py-2 font-mono font-semibold">{{ $coupon->code }}</td>
                            <td class="px-4 py-2">{{ $coupon->name }}</td>
                            <td class="px-4 py-2">{{ $coupon->typeLabel() }}</td>
                            <td class="px-4 py-2">{{ $coupon->plan?->plan_name ?? 'Any plan' }}</td>
                            <td class="px-4 py-2">{{ $coupon->used_count }}{{ $coupon->max_uses ? ' / ' . $coupon->max_uses : '' }}</td>
                            <td class="px-4 py-2 text-xs text-gray-600">
                                @if($coupon->starts_at) from {{ $coupon->starts_at->format('Y-m-d') }} @endif
                                @if($coupon->expires_at) until {{ $coupon->expires_at->format('Y-m-d') }} @endif
                                @if(!$coupon->starts_at && !$coupon->expires_at) No limit @endif
                            </td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-0.5 rounded-full text-xs {{ $coupon->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $coupon->is_active ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 space-x-2">
                                <a href="{{ route('admin.coupons.edit', $coupon) }}" class="text-purple-600 hover:underline">Edit</a>
                                <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST" class="inline" onsubmit="return confirm('Delete this coupon?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-6 text-center text-gray-500">No coupons yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
