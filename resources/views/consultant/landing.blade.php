@extends('layouts.public')

@section('title', 'MENetZero Consultant — Consultants & Agencies')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-14">
    <div class="text-center mb-10">
        <span class="inline-block px-3 py-1 rounded-full bg-teal-50 text-teal-700 text-xs font-semibold mb-4">One consultant account</span>
        <h1 class="text-4xl font-bold text-gray-900">Freelance consultant or agency — same portal</h1>
        <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
            Manage SME carbon workspaces, purchase agency packs (5 / 10 / 25 / 50 slots),
            and optionally list in the MenetZero directory for qualified leads.
        </p>
        @if($consultantCount > 0)
            <p class="mt-3 text-sm text-gray-500">{{ $consultantCount }}+ verified consultants already listed</p>
        @endif
    </div>

    <div class="grid md:grid-cols-3 gap-5 mb-12">
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <div class="text-2xl mb-2">🏢</div>
            <h3 class="font-semibold text-gray-900">Client workspaces</h3>
            <p class="text-sm text-gray-600 mt-2">One login to run multiple SME accounts — emissions, MOCCAE, IFRS/GRI exports per client.</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <div class="text-2xl mb-2">📋</div>
            <h3 class="font-semibold text-gray-900">Directory listing</h3>
            <p class="text-sm text-gray-600 mt-2">Optional public profile — upload trade license + CV, get admin-approved, receive SME intro requests.</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <div class="text-2xl mb-2">💰</div>
            <h3 class="font-semibold text-gray-900">Wholesale packs</h3>
            <p class="text-sm text-gray-600 mt-2">Consultant 5 for solo practices from AED 6,495/yr. Add extra slots or year unlocks without upgrading pack size.</p>
        </div>
    </div>

    <div class="bg-slate-900 text-white rounded-2xl p-8 text-center">
        <h2 class="text-2xl font-bold mb-3">Get started</h2>
        <p class="text-slate-300 mb-6 max-w-xl mx-auto">
            Register once at <strong>/consultant</strong> — your agency hub and directory profile live on the same account.
        </p>
        <div class="flex flex-wrap justify-center gap-3">
            <a href="{{ route('consultant.register') }}" class="px-6 py-3 bg-teal-500 hover:bg-teal-400 text-white font-medium rounded-lg">Create consultant account</a>
            <a href="{{ route('consultant.login') }}" class="px-6 py-3 border border-slate-600 hover:border-slate-400 text-white font-medium rounded-lg">Sign in</a>
        </div>
    </div>

    <p class="mt-8 text-xs text-gray-400 text-center max-w-2xl mx-auto">
        MenetZero does not verify emissions calculations. Consultant engagements are separate professional services.
    </p>
</div>
@endsection
