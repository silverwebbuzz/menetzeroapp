{{--
    MENetZero 2.0 - Consultant orders (Phase 6 body migration).

    Read-only escrow ledger. No gating, no forms, no POST - verified against
    the original, which has no gate calls and no csrf tokens.

    PRESERVED VERBATIM (each would silently change what the row says):
      - \App\Data\ConsultantOptions::labelFor('pack', $order->pack_type ?? '')
        including the ?? '' fallback for a null pack_type.
      - number_format($order->amount_aed, 0) with the literal "AED " prefix.
        This column is NOT run through CurrencyService - consultant orders are
        AED-denominated at the escrow layer, unlike the subscription
        transactions in payment-checkout, which ARE currency-aware.
      - str_replace('_', ' ', $order->escrow_status) + capitalize, which turns
        "in_escrow" into "In escrow".

    $order->consultant?->company_name ?? '—' keeps BOTH the nullsafe operator
    and the ?? fallback: the relation can be missing (deleted consultant) AND
    company_name can be null.

    Pagination is unconditional here - the ORIGINAL calls $orders->links()
    without a hasPages() guard, unlike the directory index. Kept as-is.

    Controller data: $orders (paginator)
--}}
@extends('layouts.app')

@section('title', 'Consultant orders - MenetZero')
@section('page-title', 'Consultant orders')

@push('styles')
    <style>
        .co-cap { text-transform: capitalize; }
        .co-link { color: var(--accent); text-decoration: none; font-size: 12.5px; }
        .co-link:hover { text-decoration: underline; }
    </style>
@endpush

@section('content')
<div class="mnz-stack" data-pillar="g">

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Governance · Assurance</div>
            <h1>Consultant orders</h1>
            <p class="mnz-lead">Review packs purchased through MenetZero escrow.</p>
        </div>
        <div class="mnz-pagehead__actions">
            <a href="{{ route('client.consultants.index') }}" class="mnz-btn mnz-btn--ghost">Browse consultants</a>
        </div>
    </div>

    @if(session('success'))
        <div class="mnz-panel mnz-seam">
            <div class="mnz-panel__body" style="color:var(--ok);font-size:12.5px">{{ session('success') }}</div>
        </div>
    @endif

    <div class="mnz-panel">
        <table class="mnz-table" style="width:100%">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Consultant</th>
                    <th>Pack</th>
                    <th>Amount</th>
                    <th>Escrow</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td style="color:var(--ink-2)">{{ $order->created_at->format('d M Y') }}</td>
                        <td style="font-weight:500">{{ $order->consultant?->company_name ?? '—' }}</td>
                        <td>{{ \App\Data\ConsultantOptions::labelFor('pack', $order->pack_type ?? '') }}</td>
                        <td class="mnz-num">AED {{ number_format($order->amount_aed, 0) }}</td>
                        <td class="co-cap">{{ str_replace('_', ' ', $order->escrow_status) }}</td>
                        <td class="co-cap">{{ $order->order_status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="mnz-empty">
                                <div class="mnz-empty__title">No consultant orders yet</div>
                                <div class="mnz-empty__text">Packs you book will appear here with their escrow status.</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mnz-panel__foot">{{ $orders->links() }}</div>
    </div>

</div>
@endsection
