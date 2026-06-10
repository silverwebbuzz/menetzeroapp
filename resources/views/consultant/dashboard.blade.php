@extends('consultant.layouts.app')

@section('title', 'Dashboard')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-1">Welcome, {{ $consultant->name }}</h1>
<p class="text-sm text-gray-600 mb-6">{{ $consultant->company_name }}</p>

@php
    $statusColors = [
        'draft' => 'bg-gray-100 text-gray-700',
        'pending_review' => 'bg-amber-100 text-amber-800',
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
        'suspended' => 'bg-red-100 text-red-800',
    ];
@endphp

@if(!empty($needsRenewal) && $renewalSubscription)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="text-sm text-amber-900">
            <strong>Renewal due:</strong> your {{ $renewalSubscription->plan?->plan_name }} pack ends
            {{ $renewalSubscription->expires_at->format('d M Y') }}.
            Select which clients continue into the next year.
        </div>
        <a href="{{ route('consultant.renewal.index') }}" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg whitespace-nowrap">
            Renew for {{ (int) $renewalSubscription->contract_year + 1 }}
        </a>
    </div>
@endif

{{-- Agency hub (same account — freelance or multi-consultant agency) --}}
<div class="mb-8">
    <h2 class="text-lg font-semibold text-gray-900 mb-3">Client workspaces</h2>
    <p class="text-sm text-gray-600 mb-4">
        Manage SME carbon accounts from one login. Freelancers typically start with <strong>Consultant 5</strong>; larger practices choose 10 / 25 / 50 slots.
    </p>
    <div class="grid sm:grid-cols-3 gap-4 mb-4">
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Agency pack</div>
            <div class="mt-1 font-semibold text-gray-900">
                @if($subscription)
                    {{ strtoupper(str_replace('consultant_', 'Consultant ', $subscription->plan_code)) }}
                    <span class="text-xs font-normal text-gray-500">· {{ $subscription->contract_year }}</span>
                @else
                    <span class="text-amber-700">No pack yet</span>
                @endif
            </div>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Slots used</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">
                {{ $slotSummary['used'] ?? 0 }}<span class="text-base font-normal text-gray-400"> / {{ $slotSummary['limit'] ?? 0 }}</span>
            </div>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Active clients</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $activeClients->count() }}</div>
        </div>
    </div>
    <div class="flex flex-wrap gap-3">
        @if(!$subscription)
            <a href="{{ route('consultant.packs.index') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">Purchase agency pack</a>
        @endif
        <a href="{{ route('consultant.clients.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Manage clients</a>
        <a href="{{ route('consultant.workspace.switcher') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Open workspace</a>
        <a href="{{ route('consultant.packs.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Agency packs</a>
    </div>
</div>

{{-- Directory listing (optional marketplace presence) --}}
<div class="mb-8">
    <h2 class="text-lg font-semibold text-gray-900 mb-3">Directory listing</h2>
    <p class="text-sm text-gray-600 mb-4">Optional public profile so MenetZero clients can find you and request introductions.</p>
    <div class="grid sm:grid-cols-3 gap-4 mb-4">
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Listing status</div>
            <div class="mt-1 font-semibold">
                <span class="px-2 py-0.5 rounded-full text-sm {{ $statusColors[$consultant->status] ?? 'bg-gray-100' }}">
                    {{ $consultant->statusLabel() }}
                </span>
            </div>
            @if($consultant->status === 'rejected' && $consultant->rejection_reason)
                <p class="text-xs text-red-600 mt-2">{{ $consultant->rejection_reason }}</p>
            @endif
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide">New leads</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $introCount }}</div>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Marketplace orders</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $orderCount }}</div>
            <p class="text-xs text-gray-400 mt-1">Escrow payments — Phase B</p>
        </div>
    </div>

    @if(in_array($consultant->status, ['draft', 'rejected']))
        <div class="bg-white border border-gray-200 rounded-xl p-6 mb-4">
            <h3 class="font-semibold text-gray-900 mb-3">Complete your directory application</h3>
            <ol class="space-y-2 text-sm text-gray-600 list-decimal list-inside">
                <li><a href="{{ route('consultant.profile.edit') }}" class="text-teal-600 hover:underline">Complete your profile</a> — bio, emirates, specialties</li>
                <li><a href="{{ route('consultant.documents.index') }}" class="text-teal-600 hover:underline">Upload documents</a> — trade license + CV required</li>
                <li>Submit for admin review</li>
            </ol>
            @if(!empty($missingDocs))
                <p class="text-xs text-amber-700 mt-3">Missing documents: {{ implode(', ', array_map(fn($t) => \App\Data\ConsultantOptions::labelFor('document', $t), $missingDocs)) }}</p>
            @endif
            @if($consultant->canSubmitForReview())
                <form action="{{ route('consultant.profile.submit') }}" method="POST" class="mt-4">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg">Submit for review</button>
                </form>
            @endif
        </div>
    @elseif($consultant->status === 'pending_review')
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 mb-4 text-sm text-amber-900">
            Your directory application is under review. You can still purchase an agency pack and add clients while you wait.
        </div>
    @elseif($consultant->status === 'approved')
        <div class="bg-green-50 border border-green-200 rounded-xl p-6 mb-4 text-sm text-green-900">
            You are listed in the MenetZero consultant directory. Clients on paid plans can request introductions.
        </div>
    @endif

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('consultant.profile.edit') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Edit profile</a>
        <a href="{{ route('consultant.documents.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Manage documents</a>
        <a href="{{ route('consultant.intro-requests.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50">View leads</a>
    </div>
</div>
@endsection
