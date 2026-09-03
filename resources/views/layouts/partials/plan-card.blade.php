{{--
    Sidebar plan card (old / current theme).

    The new theme renders the SAME PlanGate::planSummary() data from its own
    nav-client partial, so the two cannot drift on WHAT they show -- only on
    presentation, which is the point of having two themes.

    Data comes from the gate, not a fresh query: this renders on every portal
    page, and planSummary() memoises the subscription lookup that
    PlanGate already performs several times per render.

    Three states, matching the three billing situations:
      - direct company  -> plan, days left when paid, Upgrade/Renew action
      - managed client  -> plan only, no action. Billing belongs to the
                           consultant agency and RestrictManagedClientBilling
                           redirects these users away from the billing pages,
                           so a button here would lead somewhere they are
                           bounced out of.
      - no company yet  -> nothing rendered at all
--}}
@php
    $planCard = isset($gate) ? $gate->planSummary() : null;
@endphp

@if ($planCard)
    <div class="plan-card {{ $planCard['expired'] ? 'is-expired' : ($planCard['expiring'] ? 'is-expiring' : '') }}">
        <div class="plan-card__company" title="{{ $planCard['company'] }}">{{ $planCard['company'] }}</div>

        <div class="plan-card__meta">
            <span class="plan-card__tier">{{ $planCard['plan'] }}</span>
            @if ($planCard['expired'])
                <span class="plan-card__days">· expired</span>
            @elseif ($planCard['days_left'] !== null)
                <span class="plan-card__days">· {{ $planCard['days_left'] }} {{ \Illuminate\Support\Str::plural('day', $planCard['days_left']) }} left</span>
            @endif
        </div>

        @if ($planCard['action_url'])
            <a href="{{ $planCard['action_url'] }}" class="plan-card__btn">{{ $planCard['action_label'] }}</a>
        @endif
    </div>
@endif
