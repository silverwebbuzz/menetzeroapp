{{--
    MENetZero 2.0 - Plan & billing (Phase 6 body migration).

    SCOPE: this page only. The rest of the client/subscriptions cluster
    (upgrade, checkout, current-plan, payment-history, request-package, index)
    stays on old bodies inside the new shell, which renders correctly today.
    Only subscriptions.billing is reachable from the nav; the others sit deeper
    in the upgrade funnel. Decision recorded in redesign.md section 35.

    REVENUE-CRITICAL ELEMENTS, all preserved:
      - subscriptions.cancel   POST + csrf + the confirm() text, which names the
        exact date the plan stays active until
      - subscriptions.resume   POST + csrf ("Keep my plan")
      - subscriptions.request-package, subscriptions.upgrade,
        client.consultants.index
      - the entitlement lists, which tell the user what they have paid for

    NO TABS: this page had a showTab() pair (Payment history / Billing methods)
    and included the shared billing-method-modal. Billing methods was removed
    entirely -- its Add-card form posted a full card number to this application
    to store only the last four digits, and nothing ever read the result, since
    Razorpay collects card details on its own checkout. The tab script and the
    modal went with it, so no global showTab() is defined here any more.

    Controller data: $subscription $company $paymentHistory
    $scheduledPlan $scheduledDowngradeWarnings $isPaidPlan $cancellationScheduled
    $isComplimentary $provisionLabel $gate $usageMeters $dataEntitlements
    $downloadEntitlements $consultantDirectoryLabel $daysRemaining
--}}
@extends('layouts.app')

@section('title', 'Plan & Billing - MenetZero')
@section('page-title', 'Plan & Billing')

@push('styles')
    <style>
        .bl-meters { display: grid; gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
        .bl-meter { border: 1px solid var(--line); background: var(--canvas); padding: 14px 16px; }
        .bl-bar { height: 6px; background: var(--line-2); overflow: hidden; margin-top: 8px; }
        .bl-bar span { display: block; height: 100%; background: var(--ok); }
        .bl-bar span.is-high { background: var(--bad); }
        .bl-ent { display: flex; align-items: center; justify-content: space-between;
            gap: 12px; padding: 7px 0; font-size: 12.5px; }
        .bl-ent + .bl-ent { border-top: 1px solid var(--line-2); }
        .bl-facts { display: grid; gap: 14px;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); margin-top: 14px; }
        .bl-facts .k { font: 500 10.5px var(--mono); letter-spacing: .08em;
            text-transform: uppercase; color: var(--ink-4); }
        .bl-facts .v { font-size: 13px; font-weight: 600; margin-top: 4px; }
        .bl-side { display: flex; flex-direction: column; gap: 8px; min-width: 200px; }
        .bl-side form { margin: 0; }
        .bl-side button { width: 100%; }
        /* Tab contract: the script toggles `hidden` on .tab-content. */
        .tab-content.hidden { display: none; }
        .tab-button { height: 38px; padding: 0 18px; font-size: 12.5px; font-weight: 500;
            background: none; border: 0; border-bottom: 2px solid transparent;
            color: var(--ink-3); cursor: pointer; }
        .tab-button.active { border-bottom-color: var(--accent); color: var(--accent); }
    </style>
@endpush

@section('content')
<div class="mnz-stack" data-pillar="neutral">

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Settings · Billing</div>
            <h1>Plan &amp; billing</h1>
            <p class="mnz-lead">
                Your subscription, usage, and entitlements. Paid packages are requested
                here.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="mnz-panel" style="border-color:var(--ok-line);background:var(--ok-tint)">
            <div class="mnz-panel__body" style="color:var(--ok);font-size:12.5px">{{ session('success') }}</div>
        </div>
    @endif
    @if(session('info'))
        <div class="mnz-panel" style="border-color:var(--accent-line);background:var(--accent-tint)">
            <div class="mnz-panel__body" style="color:var(--accent);font-size:12.5px">{{ session('info') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="mnz-panel" style="border-color:var(--bad-line);background:var(--bad-tint)">
            <div class="mnz-panel__body" style="color:var(--bad);font-size:12.5px">{{ session('error') }}</div>
        </div>
    @endif

    {{-- Plan header --}}
    <div class="mnz-panel">
        <div class="mnz-panel__body">
            <div style="display:flex;gap:24px;flex-wrap:wrap;align-items:flex-start">
                <div style="flex:1;min-width:min(100%,340px)">
                    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px">
                        <h2 style="font-size:19px;font-weight:600;margin:0">{{ $subscription->plan->plan_name ?? 'Free' }}</h2>
                        <span class="mnz-chip {{ $subscription->status === 'active' ? 'mnz-chip--ok' : '' }}">{{ ucfirst($subscription->status) }}</span>
                        @if(!empty($cancellationScheduled))
                            <span class="mnz-chip mnz-chip--bad">Cancels {{ $subscription->expires_at->format('M d, Y') }}</span>
                        @elseif($subscription->auto_renew)
                            <span class="mnz-chip">Renewal reminder on</span>
                        @endif
                    </div>
                    <p style="font-size:12.5px;color:var(--ink-3);margin:8px 0 0">{{ $subscription->plan->description ?? '' }}</p>

                    <div class="bl-facts">
                        <div>
                            <div class="k">Renews / expires</div>
                            <div class="v">{{ $subscription->expires_at->format('M d, Y') }}</div>
                        </div>
                        <div>
                            <div class="k">Days left</div>
                            <div class="v" @if($daysRemaining < 30) style="color:var(--bad)" @endif>{{ $daysRemaining }} days</div>
                        </div>
                        <div>
                            <div class="k">Billing</div>
                            <div class="v">
                                @if(!empty($isComplimentary) || empty($isPaidPlan))
                                    Complimentary / Free
                                @else
                                    {{ ucfirst($subscription->billing_cycle) }} · Confirmed offline
                                @endif
                            </div>
                        </div>
                        <div>
                            <div class="k">Term started</div>
                            <div class="v">{{ $subscription->started_at->format('M d, Y') }}</div>
                        </div>
                    </div>
                </div>

                <div class="bl-side">
                    {{-- See the old-theme twin: primary action is now Plans
                         (self-serve checkout), not the offline request form. --}}
                    <a href="{{ route('subscriptions.upgrade') }}" class="mnz-btn mnz-btn--accent" style="text-align:center">
                        {{ ($daysRemaining ?? 999) <= 45 && !empty($isPaidPlan) ? 'Renew plan' : 'Upgrade plan' }}
                    </a>
                    {{-- Only UI entry point to the request form (Enterprise /
                         invoice buyers); it is not in the nav. --}}
                    <a href="{{ route('subscriptions.request-package') }}"
                       style="font-size:11px;color:var(--ink-4);text-decoration:underline">
                        Need Enterprise, or prefer an invoice?
                    </a>

                    @if(!empty($cancellationScheduled))
                        <form action="{{ route('subscriptions.resume') }}" method="POST">
                            @csrf
                            <button type="submit" class="mnz-btn" style="border-color:var(--ok-line);color:var(--ok)">
                                Keep my plan
                            </button>
                        </form>
                    @elseif(!empty($isPaidPlan))
                        <form action="{{ route('subscriptions.cancel') }}" method="POST" onsubmit="return confirm('Your plan stays active until {{ $subscription->expires_at->format('F d, Y') }} and will not renew. Continue?')">
                            @csrf
                            <button type="submit" class="mnz-btn">Cancel at renewal</button>
                        </form>
                    @endif
                </div>
            </div>

            @if(!empty($provisionLabel))
                <div class="mnz-panel" style="border-color:var(--accent-line);background:var(--accent-tint);margin-top:16px">
                    <div class="mnz-panel__body" style="font-size:12.5px">{{ $provisionLabel }}</div>
                </div>
            @endif

            @if(!empty($scheduledPlan))
                <div class="mnz-panel" style="border-color:var(--warn-line);background:var(--warn-tint);margin-top:16px">
                    <div class="mnz-panel__body" style="font-size:12.5px;color:var(--warn)">
                        <strong>Scheduled at renewal:</strong> switches to {{ $scheduledPlan->plan_name }} on {{ $subscription->expires_at->format('F d, Y') }}.
                        @foreach($scheduledDowngradeWarnings ?? [] as $warning)
                            <p style="margin:6px 0 0;color:var(--bad)">⚠ {{ $warning }}</p>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Usage --}}
    <div class="mnz-panel">
        <div class="mnz-panel__head">
            <h3 style="font-size:14px;font-weight:600;margin:0">Usage</h3>
        </div>
        <div class="mnz-panel__body">
            <div class="bl-meters">
                @foreach($usageMeters as $key => $meter)
                    @php
                        $limit = $meter['limit'];
                        $used = $meter['used'];
                        $pct = ($limit && $limit > 0) ? min(100, round(($used / $limit) * 100)) : null;
                        $limitLabel = $limit === null ? '∞' : ($limit === 0 ? 'Locked' : $limit);
                    @endphp
                    <div class="bl-meter">
                        <div style="display:flex;justify-content:space-between;font-size:12.5px">
                            <span style="font-weight:500">{{ $meter['label'] }}</span>
                            <span style="font-weight:600">{{ $used }} / {{ $limitLabel }}</span>
                        </div>
                        @if($pct !== null && $limit > 0)
                            <div class="bl-bar"><span class="{{ $pct >= 90 ? 'is-high' : '' }}" style="width: {{ $pct }}%"></span></div>
                        @elseif($limit === 0)
                            <p style="font-size:11px;color:var(--accent);margin:8px 0 0">Available on Starter+</p>
                        @else
                            <p style="font-size:11px;color:var(--ink-3);margin:8px 0 0">Unlimited on your plan</p>
                        @endif
                    </div>
                @endforeach
            </div>

            <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin-top:16px">
                <span style="font-size:11px;color:var(--ink-4);margin-right:4px">Exports:</span>
                @foreach($downloadEntitlements as $item)
                    <span class="mnz-chip {{ $item['allowed'] ? 'mnz-chip--ok' : '' }}">
                        {{ $item['label'] }}
                        @if(!$item['allowed'] && $item['hint'])
                            · {{ $item['hint'] }}
                        @endif
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Entitlements --}}
    <div class="mnz-seam mnz-seam--2">
        <div>
            <div class="mnz-panel__head" style="border-bottom:1px solid var(--line)">
                <h3 style="font-size:14px;font-weight:600;margin:0">Data &amp; operations</h3>
            </div>
            <div class="mnz-panel__body">
                @foreach($dataEntitlements as $item)
                    <div class="bl-ent">
                        <span>{{ $item['label'] }}</span>
                        <span style="display:flex;align-items:center;gap:8px">
                            @if($item['hint'])
                                <span style="font-size:11px;color:var(--ink-4)">{{ $item['hint'] }}</span>
                            @endif
                            @if($item['allowed'])
                                <svg style="width:16px;height:16px;color:var(--ok)" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            @else
                                <span style="color:var(--ink-4)">—</span>
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <div class="mnz-panel__head" style="border-bottom:1px solid var(--line)">
                <h3 style="font-size:14px;font-weight:600;margin:0">Report downloads</h3>
            </div>
            <div class="mnz-panel__body">
                @foreach($downloadEntitlements as $item)
                    <div class="bl-ent">
                        <span>{{ $item['label'] }}</span>
                        <span style="display:flex;align-items:center;gap:8px">
                            @if(!$item['allowed'] && $item['hint'])
                                <a href="{{ route('subscriptions.upgrade') }}" style="font-size:11px;color:var(--accent)">{{ $item['hint'] }}</a>
                            @endif
                            @if($item['allowed'])
                                <svg style="width:16px;height:16px;color:var(--ok)" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            @else
                                <span style="color:var(--ink-4)">—</span>
                            @endif
                        </span>
                    </div>
                @endforeach
                <p style="font-size:11px;color:var(--ink-3);margin:14px 0 0">
                    Disclosure PDF export typically needs Scope Pro or an ESG package.
                    Clean GHG / IEQT exports need Scope Basic or higher.
                </p>
            </div>
        </div>
    </div>

    {{-- Consultant marketplace --}}
    <div class="mnz-panel">
        <div class="mnz-panel__body" style="display:flex;gap:16px;align-items:center;justify-content:space-between;flex-wrap:wrap">
            <div style="min-width:min(100%,320px)">
                <h3 style="font-size:14px;font-weight:600;margin:0">Consultant marketplace</h3>
                <p style="font-size:12.5px;color:var(--ink-3);margin:5px 0 0">
                    Your plan: <strong>{{ $consultantDirectoryLabel }}</strong>.
                    Verified UAE consultants for review and sign-off on your reports.
                </p>
            </div>
            <a href="{{ route('client.consultants.index') }}" class="mnz-btn mnz-btn--primary">Browse consultants</a>
        </div>
    </div>

    {{-- Payment history. The "Billing methods" tab beside this was removed with
         its modal, routes and controller methods: its Add-card form posted a
         full card number to this app to store only the last four digits, and
         nothing ever read the result -- Razorpay collects card details on its
         own checkout. One section left means nothing to tab between. --}}
    <div class="mnz-panel">
        <div class="mnz-panel__head">
            <div>
                <h3>Payment history</h3>
                <p class="mnz-muted">Your past payments and their status. Invoices are emailed and downloadable.</p>
            </div>
        </div>

        <div class="mnz-panel__body">
            <div>
                <p style="font-size:11.5px;color:var(--ink-3);margin:0 0 14px">
                    Every payment you have made, with its status.
                </p>
                @if($paymentHistory && $paymentHistory->count() > 0)
                    <div style="overflow-x:auto">
                        <table class="mnz-table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Document</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($paymentHistory as $transaction)
                                    <tr>
                                        <td style="white-space:nowrap">{{ $transaction->created_at->format('M d, Y') }}</td>
                                        <td>{{ $transaction->description ?? 'Package payment' }}</td>
                                        <td><span class="mnz-chip">{{ ucfirst($transaction->status ?? 'pending') }}</span></td>
                                        <td>
                                            {{-- Prefer the issued invoice; invoice_url is the
                                                 legacy gateway-hosted link kept for older rows. --}}
                                            @if($transaction->invoice)
                                                <a href="{{ route('invoices.download', $transaction->invoice) }}">{{ $transaction->invoice->invoice_number }}</a>
                                            @elseif($transaction->invoice_url)
                                                <a href="{{ $transaction->invoice_url }}" target="_blank">Download</a>
                                            @else
                                                <span style="color:var(--ink-4)">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="mnz-empty">
                        <div class="mnz-empty__text">
                            @if(!empty($isComplimentary) || empty($isPaidPlan))
                                No payment records yet — Free / complimentary plans do not show listed amounts here.
                            @else
                                Payment activity appears here after MENetZero records offline activation.
                            @endif
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>


@endsection
