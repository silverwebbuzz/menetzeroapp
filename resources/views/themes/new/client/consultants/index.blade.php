{{--
    MENetZero 2.0 - Consultant directory (Phase 6 body migration).

    REVENUE-FACING. This page is the top of the consultant marketplace funnel,
    so its tier behaviour is preserved exactly rather than tidied.

    TWO INDEPENDENT LAYERS OF NAME PROTECTION - both are required:

      1. SERVER-SIDE. ConsultantDirectoryService::presentForClient() already
         replaces company_name with blurredName() ("A*****z") for the teaser
         level, and nulls bio / specialties / emirates / experience / contact.
         The teaser payload never contains the real name.
      2. CLIENT-SIDE. The original ALSO paints blur-sm on the heading. That is
         cosmetic only - it makes the row read as deliberately withheld rather
         than as corrupt data.

    Dropping layer 2 would not leak anything, but it would make the bullet
    string look like a bug instead of an upsell, so it is carried across as
    .cd-name--blur.

    TIER MAP (ConsultantDirectoryService):
        teaser                      -> names blurred, no intro, upsell CTA
        partial / full / priority   -> canRequestIntro() true
        full / priority             -> is_featured can be true, contact shown

    NOTE ON is_featured: presentForClient() only sets is_featured for
    full/priority. The "if" below is therefore already tier-correct, and no
    extra gate is added here.

    Controller data: $level $consultantCount $consultants (paginator)
                     $canRequestIntro $consultantAddOns $directoryLabel

    DELIBERATE OMISSION: $canRequestIntro is passed by the controller but the
    ORIGINAL index does not read it - the card CTA keys off $level instead.
    Carried across unread rather than inventing a use the old page never had.
--}}
@extends('layouts.app')

@section('title', 'Consultant directory - MenetZero')
@section('page-title', 'Consultants')

@push('styles')
    <style>
        .cd-grid { display: grid; gap: 12px;
            grid-template-columns: repeat(3, minmax(0, 1fr)); }
        @media (max-width: 1100px) { .cd-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 720px)  { .cd-grid { grid-template-columns: 1fr; } }
        .cd-card { border: 1px solid var(--line); background: var(--surface);
            padding: 16px; display: flex; flex-direction: column; }
        .cd-card--featured { border-color: var(--accent-line);
            box-shadow: inset 3px 0 0 var(--accent); }
        .cd-name { font-size: 15px; font-weight: 600; color: var(--ink);
            margin: 6px 0 0; }
        /* Cosmetic second layer only - the teaser name is already masked
           server-side by blurredName(). */
        .cd-name--blur { filter: blur(4px); user-select: none; }
        .cd-tags { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 8px; }
        .cd-bio { font-size: 12.5px; color: var(--ink-2); margin-top: 10px;
            line-height: 1.55; }
        .cd-cta { margin-top: auto; padding-top: 14px; }
        .cd-packs { display: grid; gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr)); }
        @media (max-width: 720px) { .cd-packs { grid-template-columns: 1fr; } }
        .cd-pack { border: 1px solid var(--line); background: var(--surface);
            padding: 14px; }
        .cd-pack__name { font-size: 13px; font-weight: 600; color: var(--ink); }
        .cd-pack__desc { font-size: 12px; color: var(--ink-2); margin-top: 4px;
            line-height: 1.5; }
        .cd-pack__price { font-size: 13.5px; font-weight: 600;
            color: var(--accent); margin-top: 8px; }
        .cd-link { color: var(--accent); text-decoration: none; }
        .cd-link:hover { text-decoration: underline; }
    </style>
@endpush

@section('content')
<div class="mnz-stack" data-pillar="g">

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Governance · Assurance</div>
            <h1>Verified consultants</h1>
            <p class="mnz-lead">
                Software prepares your data — consultants review, sign off, and support
                verification-style workflows.
                Your access: <strong>{{ $directoryLabel }}</strong>.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="mnz-panel mnz-seam">
            <div class="mnz-panel__body" style="color:var(--ok);font-size:12.5px">{{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="mnz-panel mnz-seam">
            <div class="mnz-panel__body" style="color:var(--bad);font-size:12.5px">{{ session('error') }}</div>
        </div>
    @endif

    {{-- Teaser upsell banner. Shown only at the lowest tier. --}}
    @if($level === 'teaser')
        <div class="mnz-panel">
            <div class="mnz-panel__body">
                <p style="margin:0;font-size:13px;color:var(--ink-2);line-height:1.6">
                    <strong style="color:var(--ink)">{{ max($consultantCount, 1) }}+ verified consultants</strong>
                    in the UAE directory.
                    Upgrade to <a href="{{ route('subscriptions.upgrade') }}" class="cd-link">Starter</a>
                    to see consultant names and request introductions.
                </p>
            </div>
        </div>
    @endif

    <div class="cd-grid">
        @forelse($consultants as $c)
            <div class="cd-card {{ ($c['is_featured'] ?? false) ? 'cd-card--featured' : '' }}">
                @if($c['is_featured'] ?? false)
                    <div class="mnz-kicker" style="color:var(--accent)">Featured consultant</div>
                @endif

                <h3 class="cd-name {{ $level === 'teaser' ? 'cd-name--blur' : '' }}">
                    {{ $c['display_name'] }}
                </h3>

                @if(!empty($c['specialties']))
                    <div class="cd-tags">
                        @foreach(array_slice($c['specialties'], 0, 3) as $spec)
                            <span class="mnz-chip">{{ $spec }}</span>
                        @endforeach
                    </div>
                @endif

                @if($c['bio'])
                    <p class="cd-bio">{{ $c['bio'] }}</p>
                @endif

                <div class="cd-cta">
                    @if($level !== 'teaser')
                        <a href="{{ route('client.consultants.show', $c['id']) }}" class="mnz-btn mnz-btn--ghost">View profile</a>
                    @else
                        <a href="{{ route('subscriptions.upgrade') }}" class="mnz-btn mnz-btn--accent">Upgrade to connect</a>
                    @endif
                </div>
            </div>
        @empty
            <div style="grid-column:1/-1">
                <div class="mnz-panel">
                    <div class="mnz-empty">
                        <div class="mnz-empty__title">Directory launching soon</div>
                        <div class="mnz-empty__text">Check back after our first consultants are approved.</div>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    @if($consultants->hasPages())
        <div>{{ $consultants->links() }}</div>
    @endif

    <div class="mnz-panel">
        <div class="mnz-panel__head">
            <h3 style="font-size:14px;font-weight:600;margin:0">Consultant review packs</h3>
        </div>
        <div class="mnz-panel__body">
            <div class="cd-packs">
                @foreach($consultantAddOns as $addon)
                    <div class="cd-pack">
                        <div class="cd-pack__name">{{ $addon['name'] }}</div>
                        <div class="cd-pack__desc">{{ $addon['description'] }}</div>
                        <div class="cd-pack__price">+{{ $addon['price'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="mnz-panel__foot">
            <span style="font-size:11.5px;color:var(--ink-3)">
                <a href="{{ route('client.consultants.orders') }}" class="cd-link">View your consultant orders</a>
                — payments held in escrow until delivery.
            </span>
        </div>
    </div>

</div>
@endsection
