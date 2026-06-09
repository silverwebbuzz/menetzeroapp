@extends('layouts.app')

@section('title', 'Book consultant pack')

@section('content')
<div class="max-w-lg mx-auto">
    <a href="{{ route('client.consultants.show', $consultant) }}" class="text-sm text-teal-600 hover:underline">&larr; Back to profile</a>

    <div class="bg-white border border-gray-200 rounded-xl p-6 mt-4">
        <h1 class="text-xl font-bold text-gray-900">Book {{ $pack['name'] }}</h1>
        <p class="text-sm text-gray-600 mt-1">with <strong>{{ $consultant->company_name }}</strong></p>

        <div class="bg-gray-50 rounded-lg p-4 mt-4 text-sm">
            <div class="text-2xl font-bold text-gray-900">{{ $pack['price'] }}</div>
            <p class="text-gray-600 mt-2">{{ $pack['description'] }}</p>
            <p class="text-xs text-gray-400 mt-3">
                Platform fee {{ number_format($commissionRate * 100, 0) }}% — remainder paid to consultant after delivery.
                Funds held in escrow until you confirm completion.
            </p>
        </div>

        @if($enabledGateways->isEmpty())
            <p class="mt-4 text-sm text-red-600">No payment gateways are configured. Contact support.</p>
        @else
            <form method="POST" action="{{ route('client.consultants.checkout.process', $consultant) }}" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="pack_type" value="{{ $packType }}">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment method</label>
                    @foreach($enabledGateways as $gw)
                        <label class="flex items-center gap-2 mb-2 text-sm">
                            <input type="radio" name="gateway" value="{{ $gw->gateway }}" @checked($loop->first) required>
                            {{ $gw->label }}
                        </label>
                    @endforeach
                </div>
                <button type="submit" class="w-full py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-medium rounded-lg">
                    Continue to payment
                </button>
            </form>
        @endif
    </div>
</div>
@endsection
