@extends('layouts.app')

@section('title', 'Consultant partners')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Verified consultant partners</h1>
        <p class="text-sm text-gray-600 mt-1">
            Software prepares your data — consultants review, sign off, and support verification-style workflows.
            Your access: <strong>{{ $directoryLabel }}</strong>.
        </p>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    @if($level === 'teaser')
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-6 mb-6">
            <p class="text-gray-700">
                <strong>{{ max($partnerCount, 1) }}+ verified partners</strong> in the UAE directory.
                Upgrade to <a href="{{ route('subscriptions.upgrade') }}" class="text-teal-600 hover:underline">Starter</a>
                to see partner names and request introductions.
            </p>
        </div>
    @endif

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        @forelse($consultants as $c)
            <div class="bg-white border border-gray-200 rounded-xl p-5 {{ ($c['is_featured'] ?? false) ? 'ring-2 ring-teal-400' : '' }}">
                @if($c['is_featured'] ?? false)
                    <span class="text-[10px] font-semibold uppercase tracking-wide text-teal-600">Featured partner</span>
                @endif
                <h3 class="text-lg font-semibold text-gray-900 {{ $level === 'teaser' ? 'blur-sm select-none' : '' }}">
                    {{ $c['display_name'] }}
                </h3>
                @if(!empty($c['specialties']))
                    <div class="flex flex-wrap gap-1 mt-2">
                        @foreach(array_slice($c['specialties'], 0, 3) as $spec)
                            <span class="text-xs px-2 py-0.5 bg-gray-100 text-gray-600 rounded">{{ $spec }}</span>
                        @endforeach
                    </div>
                @endif
                @if($c['bio'])
                    <p class="text-sm text-gray-600 mt-3">{{ $c['bio'] }}</p>
                @endif
                <div class="mt-4 flex gap-2">
                    @if($level !== 'teaser')
                        <a href="{{ route('client.consultants.show', $c['id']) }}" class="text-sm text-teal-600 hover:underline">View profile</a>
                    @else
                        <a href="{{ route('subscriptions.upgrade') }}" class="text-sm text-teal-600 hover:underline">Upgrade to connect</a>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-2 bg-white border border-gray-200 rounded-xl p-8 text-center text-gray-500">
                Partner directory launching soon. Check back after our first consultants are approved.
            </div>
        @endforelse
    </div>

    @if($consultants->hasPages())
        <div class="mb-8">{{ $consultants->links() }}</div>
    @endif

    <div class="bg-gray-50 border border-gray-200 rounded-xl p-6">
        <h2 class="font-semibold text-gray-900 mb-3">Consultant review packs</h2>
        <div class="grid sm:grid-cols-2 gap-4">
            @foreach($consultantAddOns as $addon)
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="font-medium text-gray-900">{{ $addon['name'] }}</div>
                    <div class="text-sm text-gray-600 mt-1">{{ $addon['description'] }}</div>
                    <div class="text-teal-700 font-semibold mt-2">+{{ $addon['price'] }}</div>
                </div>
            @endforeach
        </div>
        <p class="text-xs text-gray-400 mt-4">
            <a href="{{ route('client.consultants.orders') }}" class="text-teal-600 hover:underline">View your consultant orders</a>
            — payments held in escrow until delivery.
        </p>
    </div>
</div>
@endsection
