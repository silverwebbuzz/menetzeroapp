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

<div class="bg-indigo-50 border border-indigo-200 rounded-xl p-5 mb-6 text-sm text-indigo-950">
    <h2 class="font-semibold text-indigo-900 mb-2">Agency packs (managed clients)</h2>
    <p class="text-indigo-800 mb-3">
        This page is the <strong>consultant directory</strong> (public listing &amp; leads). It is separate from
        <strong>agency packs</strong> — where you manage multiple client workspaces with wholesale pricing.
    </p>
    <ul class="list-disc list-inside space-y-1 text-indigo-800 mb-3">
        <li>Directory: complete profile + documents below, then submit for admin review</li>
        <li>Agency hub: log in at <a href="{{ url('/login') }}" class="underline font-medium">app.menetzero.com/login</a> with your <strong>partner company account</strong>, then open <a href="{{ route('partner.dashboard') }}" class="underline font-medium">/partner/dashboard</a></li>
    </ul>
    <p class="text-xs text-indigo-700">Partner accounts need <code class="bg-white/60 px-1 rounded">company_type = partner</code> and an agency pack before you can add clients. Ask admin to set this up, or purchase a pack after login.</p>
</div>

<div class="grid sm:grid-cols-3 gap-4 mb-8">
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
    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
        <h2 class="font-semibold text-gray-900 mb-3">Complete your application</h2>
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
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 mb-6 text-sm text-amber-900">
        Your application is under review. We typically respond within 2–3 business days.
    </div>
@elseif($consultant->status === 'approved')
    <div class="bg-green-50 border border-green-200 rounded-xl p-6 mb-6 text-sm text-green-900">
        You are listed in the MenetZero consultant directory. Clients on paid plans can request introductions.
    </div>
@endif

<div class="flex gap-3">
    <a href="{{ route('consultant.profile.edit') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Edit profile</a>
    <a href="{{ route('consultant.documents.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Manage documents</a>
    <a href="{{ route('consultant.intro-requests.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50">View leads</a>
</div>
@endsection
