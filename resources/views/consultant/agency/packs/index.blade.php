@extends('consultant.layouts.app')

@section('title', 'Request entities')
@section('page-title', 'Request entities')

@section('content')
@php
    $isTrial = $subscription?->isFreeTrial() ?? false;
@endphp

<div class="cd-page-head">
    <div>
        <h1 class="ent-page-title">Request managed-client entities</h1>
        <p class="ent-page-lead">
            Free includes one managed client. Request more entities when you need them —
            MENetZero confirms preferential rates offline and activates after payment.
        </p>
        @if($subscription)
            <div class="cd-eyebrow mt-2">
                Active: {{ $subscription->plan?->plan_name }} · {{ $slotSummary['used'] }}/{{ $slotSummary['limit'] }} slots used
                · contract year {{ $contractYear }}
            </div>
        @endif
    </div>
    <div class="cd-page-actions">
        <a href="{{ route('consultant.clients.create') }}" class="btn btn-primary btn-sm">+ Add client</a>
        <a href="{{ route('consultant.dashboard') }}" class="btn btn-ghost btn-sm">← Dashboard</a>
    </div>
</div>

@if(session('info'))
    <div class="cd-notice mb-4"><span>{{ session('info') }}</span></div>
@endif

<div class="card mb-4">
    <div class="card-header">
        <div>
            <h3 class="card-title">How paid entities work</h3>
            <p class="card-subtitle">No self-serve pack checkout on this page</p>
        </div>
    </div>
    <div class="card-body space-y-3 text-sm text-slate-700">
        <ul class="list-disc pl-5 space-y-2">
            <li><strong>Free:</strong> 1 managed client entity (watermarked / Free limits).</li>
            <li><strong>Paid entity:</strong> Standard package profile — Scope 1 &amp; 2, up to 5 sites, clean GHG / MOCCAE / Excel / IEQT (not full ESG by default).</li>
            <li><strong>Pricing:</strong> confirmed offline (intro band per entity / year). ≥10 companies in 12 months is a sales preference only.</li>
            <li><strong>Enterprise:</strong> white-label / custom — talk to MENetZero.</li>
        </ul>
        <p class="pt-2">
            A structured “Request entities” form arrives in a later release. Until then, email or
            <a href="{{ route('contact') }}" class="text-brand font-medium hover:underline">contact MENetZero</a>
            with how many entities you need and we’ll activate after offline payment.
        </p>
        @if($isTrial)
            <p class="text-slate-600">
                You’re on the free trial — you can add your first trial client now while a paid quote is arranged.
            </p>
        @endif
    </div>
</div>

<div class="flex flex-wrap gap-3 mb-6">
    <a href="{{ route('contact') }}" class="btn btn-primary btn-sm">Contact to request entities</a>
    <a href="mailto:{{ \App\Models\SiteSetting::get('support_email', 'support@menetzero.com') }}" class="btn btn-ghost btn-sm">Email support</a>
</div>

<p class="text-xs text-slate-500 mb-6">
    Need white-label or &gt;5 sites per entity?
    <a href="{{ route('contact') }}" class="text-brand font-medium hover:underline">Contact MenetZero</a> for Enterprise.
</p>
@endsection
