@extends('layouts.public')

@section('title', 'Join as a Consultant Partner — MENetZero')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-14">
    <div class="text-center mb-10">
        <span class="inline-block px-3 py-1 rounded-full bg-teal-50 text-teal-700 text-xs font-semibold mb-4">Partner programme</span>
        <h1 class="text-4xl font-bold text-gray-900">Join the MENetZero consultant directory</h1>
        <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
            UAE carbon and sustainability professionals: list your practice, receive qualified SME leads,
            and deliver review &amp; sign-off on platform-prepared inventories and disclosures.
        </p>
        @if($partnerCount > 0)
            <p class="mt-3 text-sm text-gray-500">{{ $partnerCount }}+ verified partners already listed</p>
        @endif
    </div>

    <div class="grid md:grid-cols-3 gap-5 mb-12">
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <div class="text-2xl mb-2">📋</div>
            <h3 class="font-semibold text-gray-900">Apply with credentials</h3>
            <p class="text-sm text-gray-600 mt-2">Register, complete your profile, and upload trade license + CV for admin verification.</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <div class="text-2xl mb-2">🤝</div>
            <h3 class="font-semibold text-gray-900">Receive SME leads</h3>
            <p class="text-sm text-gray-600 mt-2">MenetZero clients on Starter and Growth plans can request introductions to your practice.</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <div class="text-2xl mb-2">💰</div>
            <h3 class="font-semibold text-gray-900">Marketplace payouts</h3>
            <p class="text-sm text-gray-600 mt-2">Paid consultant packs flow through MenetZero escrow (15% platform fee) — launching in Phase B.</p>
        </div>
    </div>

    <div class="bg-slate-900 text-white rounded-2xl p-8 text-center">
        <h2 class="text-2xl font-bold mb-3">Ready to join?</h2>
        <p class="text-slate-300 mb-6 max-w-xl mx-auto">
            Free listing for vetted partners at launch. Software prepares the data — you provide the human trust layer.
        </p>
        <div class="flex flex-wrap justify-center gap-3">
            <a href="{{ route('consultant.register') }}" class="px-6 py-3 bg-teal-500 hover:bg-teal-400 text-white font-medium rounded-lg">Apply as a partner</a>
            <a href="{{ route('consultant.login') }}" class="px-6 py-3 border border-slate-600 hover:border-slate-400 text-white font-medium rounded-lg">Partner sign in</a>
        </div>
    </div>

    <p class="mt-8 text-xs text-gray-400 text-center max-w-2xl mx-auto">
        MenetZero does not verify emissions calculations. Consultant engagements are separate professional services.
        Listing does not imply MOCCAE-authorised verification unless you hold that credential and contract for it.
    </p>
</div>
@endsection
