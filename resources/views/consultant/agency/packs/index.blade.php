@extends('consultant.layouts.app')

@section('title', 'Agency Packs')
@section('page-title', 'Agency packs')

@section('content')
@php
    $checkoutAvailable = \App\Models\PaymentGateway::checkoutAvailable();
    $isTrial = $subscription?->isFreeTrial() ?? false;
    $packMeta = \App\Data\ConsultantAgencyPlanMatrix::packDefinitions();
    $planGuide = config('plans-consultant');
    $packHints = $planGuide['pack_hints'] ?? [];
@endphp

<div class="cd-page-head">
    <div>
        <h1 class="ent-page-title">Agency packs</h1>
        <p class="ent-page-lead">Wholesale pricing for the client workspaces you manage · contract through 31 Dec {{ $contractYear }}</p>
        @if($subscription)
            <div class="cd-eyebrow mt-2">
                Active: {{ $subscription->plan?->plan_name }} · {{ $slotSummary['used'] }}/{{ $slotSummary['limit'] }} slots used
            </div>
        @endif
    </div>
    <div class="cd-page-actions">
        <a href="{{ route('consultant.clients.create') }}" class="btn btn-primary btn-sm">+ Add client</a>
        <a href="{{ route('consultant.dashboard') }}" class="btn btn-ghost btn-sm">← Dashboard</a>
    </div>
</div>

@include('plans.partials.human-guide', [
    'guide' => $planGuide,
    'show' => ['intro', 'how_it_works', 'examples', 'clarifications'],
])

@if(!$checkoutAvailable)
    <div class="cd-notice">
        <span><strong>Checkout coming soon.</strong> Review packs and pricing below. Use your free trial client now — purchase here when online payments go live.</span>
    </div>
@endif

@if($subscription && !$isTrial)
    <div class="card mb-4">
        <div class="card-header">
            <div>
                <h3 class="card-title">Extra client slots</h3>
                <p class="card-subtitle">Add capacity without changing pack size</p>
            </div>
        </div>
        <div class="card-body">
            <p class="text-sm text-slate-600 mb-3">
                Need one or two more clients without upgrading pack size?
                <strong>AED {{ number_format(\App\Data\ConsultantAgencyPlanMatrix::EXTRA_SLOT_PRICE_AED) }}</strong> per slot through 31 Dec {{ $contractYear }} (pro-rata if you buy mid-year).
            </p>
            @if($extraSlotQuote)
                @if($checkoutAvailable)
                    <form action="{{ route('consultant.packs.extra-slots') }}" method="POST" class="flex flex-col sm:flex-row sm:items-end gap-3 max-w-lg">
                        @csrf
                        <div class="flex-1">
                            <label class="block text-xs text-slate-500 mb-1">Quantity</label>
                            <select name="quantity" class="form-select" required>
                                @for($q = 1; $q <= 10; $q++)
                                    <option value="{{ $q }}">{{ $q }} slot{{ $q > 1 ? 's' : '' }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs text-slate-500 mb-1">Payment</label>
                            <select name="gateway" class="form-select" required>
                                <option value="cashfree">Cashfree</option>
                                <option value="razorpay">Razorpay (INR)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm whitespace-nowrap">Buy extra slots</button>
                    </form>
                @else
                    <p class="text-xs text-slate-500 mb-2">From AED {{ number_format($extraSlotQuote['charge_amount'], 0) }} for 1 slot (pro-rata).</p>
                    <span class="cd-btn-coming-soon" style="max-width:12rem;">Coming soon</span>
                @endif
            @endif
        </div>
    </div>
@elseif($isTrial)
    <div class="cd-notice" style="margin-bottom:1.25rem;">
        <span>You're on the <strong>free trial</strong> — one client, data entry only (no PDF exports). Pick a paid pack below when checkout opens to unlock Growth-level reports for each client.</span>
        <a href="{{ route('consultant.clients.create') }}" class="btn btn-primary btn-sm">Add trial client</a>
    </div>
@endif

<h2 class="section-heading mb-3">Available packs</h2>
<div class="cd-pack-grid">
    @foreach($plans as $plan)
        @php
            $slots = \App\Data\ConsultantAgencyPlanMatrix::slotCountForPlanCode($plan->plan_code);
            $quote = $planQuotes[$plan->id] ?? ['charge_amount' => 0, 'pro_rata' => false];
            $meta = $packMeta[$plan->plan_code] ?? [];
            $isCurrent = $subscription && $subscription->subscription_plan_id === $plan->id;
        @endphp
        <div class="cd-pack-card {{ $isCurrent ? 'is-current' : '' }}">
            @if($isCurrent)
                <span class="badge badge-success mb-2" style="align-self:flex-start;">Current plan</span>
            @endif
            <div class="cd-pack-name">{{ $plan->plan_name }}</div>
            <div class="cd-pack-slots">{{ $slots }} client {{ $slots === 1 ? 'slot' : 'slots' }} · Growth exports per client</div>
            @if(!empty($packHints[$plan->plan_code]))
                <p class="text-xs text-slate-600 mb-2">{{ $packHints[$plan->plan_code] }}</p>
            @endif
            <div class="cd-pack-price">AED {{ number_format($quote['charge_amount'], 0) }}</div>
            <div class="cd-pack-note">
                @if($quote['pro_rata'])
                    Pro-rata through 31 Dec {{ $contractYear }}
                @else
                    Full year {{ $contractYear }}
                @endif
            </div>
            <ul class="cd-pack-features">
                <li>{{ $meta['description'] ?? 'Managed SME workspaces' }}</li>
                <li>Each client: GHG, IFRS &amp; GRI report downloads (paid pack)</li>
                <li>Contract through 31 Dec {{ $contractYear }}</li>
                <li>Extra slots available if you grow mid-year</li>
            </ul>
            <div class="mt-auto">
                @if($checkoutAvailable)
                    <form action="{{ route('consultant.packs.checkout') }}" method="POST" class="space-y-2">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <select name="gateway" class="form-select" required>
                            <option value="cashfree">Cashfree</option>
                            <option value="razorpay">Razorpay (INR)</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm w-full">
                            {{ $subscription ? 'Upgrade / change' : 'Purchase pack' }}
                        </button>
                    </form>
                @else
                    <span class="cd-btn-coming-soon">Coming soon</span>
                @endif
            </div>
        </div>
    @endforeach
</div>

@if(!$checkoutAvailable)
    <p class="text-xs text-slate-500 text-center mb-4">All pack purchases will be available here when checkout opens. Continue with your free trial client in the meantime.</p>
@endif

<p class="text-xs text-slate-500 mb-6">
    Need 50+ slots? <a href="{{ route('contact') }}" class="text-brand font-medium hover:underline">Contact MenetZero</a> for Enterprise agency pricing and invoicing.
</p>

@include('plans.partials.human-guide', [
    'guide' => $planGuide,
    'show' => ['faq'],
])
@endsection
