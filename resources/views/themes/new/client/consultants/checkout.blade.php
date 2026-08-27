{{--
    MENetZero 2.0 - Consultant pack checkout (Phase 6 body migration).

    MONEY PATH. This form starts a real escrow payment, so its contract is
    preserved byte-for-byte:

        POST client.consultants.checkout.process ($consultant)
        "csrf"
        <input type="hidden" name="pack_type" value="{{ $packType }}">
        <input type="radio"  name="gateway"   value="{{ $gw->gateway }}" required
               "checked"($loop->first)

    THREE THINGS THAT LOOK COSMETIC AND ARE NOT:
      1. "checked"($loop->first) pre-selects the first gateway. Without it the
         form has no default and `required` blocks submit until the user picks
         one - a silent conversion drop, not an error.
      2. `required` on the radio is what enforces a gateway at all.
      3. name="pack_type" carries WHICH pack is being bought. It is a separate
         hidden input from the route's {consultant} binding; both are needed.

    THE "if"/"else" IS A GUARD, NOT A LAYOUT CHOICE. When $enabledGateways is
    empty the form is not rendered AT ALL - it is replaced by the "contact
    support" line. Rendering an empty-radio form instead would produce a
    submittable payment request with no gateway.

    $consultant here is the ELOQUENT MODEL (->company_name), unlike index/show
    where it is the presented array. That is the original's shape; this page is
    only reachable at a tier that can book, so no masking is bypassed.

    Controller data: $consultant $pack $packType $commissionRate $enabledGateways
--}}
@extends('layouts.app')

@section('title', 'Book consultant pack - MenetZero')
@section('page-title', 'Book pack')

@push('styles')
    <style>
        .ck-wrap { max-width: 560px; }
        .ck-price { font-size: 26px; font-weight: 700; color: var(--ink);
            letter-spacing: -.02em; }
        .ck-desc { font-size: 12.5px; color: var(--ink-2); margin-top: 8px;
            line-height: 1.6; }
        .ck-fine { font-size: 11.5px; color: var(--ink-3); margin-top: 12px;
            line-height: 1.55; }
        .ck-gw { display: flex; align-items: center; gap: 8px; font-size: 13px;
            color: var(--ink); padding: 9px 0; cursor: pointer; }
        .ck-link { color: var(--accent); text-decoration: none; font-size: 12.5px; }
        .ck-link:hover { text-decoration: underline; }
    </style>
@endpush

@section('content')
<div class="mnz-stack ck-wrap" data-pillar="g">

    <div>
        <a href="{{ route('client.consultants.show', $consultant) }}" class="ck-link">&larr; Back to profile</a>
    </div>

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Governance · Assurance</div>
            <h1>Book {{ $pack['name'] }}</h1>
            <p class="mnz-lead">with <strong>{{ $consultant->company_name }}</strong></p>
        </div>
    </div>

    <div class="mnz-panel">
        <div class="mnz-panel__body">
            <div class="ck-price">{{ $pack['price'] }}</div>
            <p class="ck-desc">{{ $pack['description'] }}</p>
            <p class="ck-fine">
                Platform fee {{ number_format($commissionRate * 100, 0) }}% — remainder paid to consultant after delivery.
                Funds held in escrow until you confirm completion.
            </p>
        </div>
    </div>

    @if($enabledGateways->isEmpty())
        <div class="mnz-panel mnz-seam">
            <div class="mnz-panel__body" style="color:var(--bad);font-size:12.5px">
                No payment gateways are configured. Contact support.
            </div>
        </div>
    @else
        <div class="mnz-panel mnz-seam">
            <div class="mnz-panel__head">
                <h3 style="font-size:14px;font-weight:600;margin:0">Payment method</h3>
            </div>
            <div class="mnz-panel__body">
                <form method="POST" action="{{ route('client.consultants.checkout.process', $consultant) }}">
                    @csrf
                    <input type="hidden" name="pack_type" value="{{ $packType }}">
                    <div style="margin-bottom:16px">
                        @foreach($enabledGateways as $gw)
                            <label class="ck-gw">
                                <input type="radio" name="gateway" value="{{ $gw->gateway }}" @checked($loop->first) required>
                                {{ $gw->label }}
                            </label>
                        @endforeach
                    </div>
                    <button type="submit" class="mnz-btn mnz-btn--primary mnz-btn--lg" style="width:100%">
                        Continue to payment
                    </button>
                </form>
            </div>
        </div>
    @endif

</div>
@endsection
