{{--
    MENetZero 2.0 - Consultant profile (Phase 6 body migration).

    REVENUE-FACING. Two separate booleans drive two separate blocks, and the
    controller currently sets BOTH from canRequestIntro():

        canBookPack     -> paid "Book a review pack" panel
        canRequestIntro -> free "Request introduction" form

    They are read as two independent flags here, exactly as the original does.
    Collapsing them into one condition would look equivalent TODAY but would
    silently break the moment the controller stops aliasing them - and the
    service already has a distinct canSeeFullProfile()/canSeeContact() pair at
    full/priority, so divergence is clearly anticipated.

    canBookPack is read as ($canBookPack ?? false) - the ORIGINAL guards it that
    way, and requestIntro()/checkout paths render this view too. Guard kept.

    THE "elseif" MATTERS. The intro block is:
        "if"($canRequestIntro) ... "elseif"($level === 'teaser') ... "endif"
    A teaser sees the upgrade prompt; a tier that is neither (should not occur,
    but is reachable if entitlements change) sees NOTHING rather than a broken
    form. Preserved as a real "elseif", not two sibling "ifs".

    CONTACT FIELDS: email / phone are already nulled server-side by
    presentForClient() for anything below full, so the "if" guards here are
    presentation-only and are safe to keep verbatim.

    Controller data: $consultant (presented array) $raw $canRequestIntro
                     $canBookPack $packTypes $consultantAddOns $level

    DELIBERATE OMISSION: $raw (the Eloquent model) is passed but the ORIGINAL
    never reads it - everything comes from the presented array, which is what
    applies the tier masking. Reading $raw here would BYPASS that masking, so
    it is deliberately left untouched.
--}}
@extends('layouts.app')

@section('title', $consultant['display_name'] . ' - MenetZero')
@section('page-title', 'Consultant')

@push('styles')
    <style>
        .cs-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 12px; }
        .cs-bio { font-size: 13px; color: var(--ink-2); margin-top: 16px;
            line-height: 1.65; white-space: pre-line; }
        .cs-facts { display: grid; gap: 14px; margin: 20px 0 0;
            grid-template-columns: repeat(2, minmax(0, 1fr)); }
        @media (max-width: 720px) { .cs-facts { grid-template-columns: 1fr; } }
        .cs-facts dt { font-size: 11px; text-transform: uppercase;
            letter-spacing: .06em; color: var(--ink-3); margin: 0 0 3px; }
        .cs-facts dd { font-size: 13px; color: var(--ink); margin: 0;
            font-weight: 500; }
        .cs-packs { display: grid; gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr)); }
        @media (max-width: 720px) { .cs-packs { grid-template-columns: 1fr; } }
        .cs-pack { display: block; border: 1px solid var(--line);
            background: var(--surface); padding: 14px; text-decoration: none;
            transition: border-color .12s ease; }
        .cs-pack:hover { border-color: var(--accent); }
        .cs-pack__name { font-size: 13px; font-weight: 600; color: var(--ink); }
        .cs-pack__desc { font-size: 12px; color: var(--ink-2); margin-top: 4px;
            line-height: 1.5; }
        .cs-pack__price { font-size: 13.5px; font-weight: 600;
            color: var(--accent); margin-top: 8px; }
        .cs-link { color: var(--accent); text-decoration: none; font-size: 12.5px; }
        .cs-link:hover { text-decoration: underline; }
        .cs-fine { font-size: 11.5px; color: var(--ink-3); margin-top: 12px;
            line-height: 1.55; }
    </style>
@endpush

@section('content')
<div class="mnz-stack" data-pillar="g">

    <div>
        <a href="{{ route('client.consultants.index') }}" class="cs-link">&larr; All consultants</a>
    </div>

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Governance · Assurance</div>
            <h1>{{ $consultant['display_name'] }}</h1>
        </div>
    </div>

    <div class="mnz-panel">
        <div class="mnz-panel__body">

            @if(!empty($consultant['specialties']))
                <div class="cs-tags">
                    @foreach($consultant['specialties'] as $spec)
                        <span class="mnz-chip">{{ $spec }}</span>
                    @endforeach
                </div>
            @endif

            @if($consultant['has_moccae_experience'])
                <div style="margin-top:10px">
                    <span class="mnz-chip mnz-chip--ok">MOCCAE experience</span>
                </div>
            @endif

            @if($consultant['bio'])
                <p class="cs-bio">{{ $consultant['bio'] }}</p>
            @endif

            <dl class="cs-facts">
                @if(!empty($consultant['emirates']))
                    <div>
                        <dt>Emirates</dt>
                        <dd>{{ implode(', ', $consultant['emirates']) }}</dd>
                    </div>
                @endif
                @if($consultant['experience_years'])
                    <div>
                        <dt>Experience</dt>
                        <dd>{{ $consultant['experience_years'] }} years</dd>
                    </div>
                @endif
                @if($consultant['email'])
                    <div>
                        <dt>Email</dt>
                        <dd><a href="mailto:{{ $consultant['email'] }}" class="cs-link">{{ $consultant['email'] }}</a></dd>
                    </div>
                @endif
                @if($consultant['phone'])
                    <div>
                        <dt>Phone</dt>
                        <dd>{{ $consultant['phone'] }}</dd>
                    </div>
                @endif
            </dl>

        </div>
    </div>

    {{-- PAID booking path. Independent of the free intro flag below. --}}
    @if($canBookPack ?? false)
        <div class="mnz-panel mnz-seam">
            <div class="mnz-panel__head">
                <h3 style="font-size:14px;font-weight:600;margin:0">Book a review pack</h3>
            </div>
            <div class="mnz-panel__body">
                <p style="margin:0 0 14px;font-size:12.5px;color:var(--ink-2);line-height:1.6">
                    Pay through MenetZero — funds held in escrow until the consultant delivers your review.
                </p>
                <div class="cs-packs">
                    @foreach($consultantAddOns as $addon)
                        <a href="{{ route('client.consultants.checkout', ['consultant' => $consultant['id'], 'pack' => $addon['pack_type']]) }}"
                           class="cs-pack">
                            <div class="cs-pack__name">{{ $addon['name'] }}</div>
                            <div class="cs-pack__desc">{{ $addon['description'] }}</div>
                            <div class="cs-pack__price">{{ $addon['price'] }}</div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- FREE intro path, with the original's "elseif" teaser fallback. --}}
    @if($canRequestIntro)
        <div class="mnz-panel mnz-seam">
            <div class="mnz-panel__head">
                <h3 style="font-size:14px;font-weight:600;margin:0">Request introduction (free)</h3>
            </div>
            <div class="mnz-panel__body">
                <form method="POST" action="{{ route('client.consultants.intro', $consultant['id']) }}">
                    @csrf
                    <div class="mnz-field">
                        <label class="mnz-label">Interested pack (optional)</label>
                        <select name="pack_type" class="mnz-select">
                            <option value="">General introduction</option>
                            @foreach($packTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mnz-field">
                        <label class="mnz-label">Message</label>
                        <textarea name="message" rows="3" class="mnz-textarea"
                                  placeholder="Briefly describe your reporting year, scope, and what you need reviewed."></textarea>
                    </div>
                    <button type="submit" class="mnz-btn mnz-btn--primary">Send request</button>
                </form>
                <p class="cs-fine">
                    MenetZero will facilitate the introduction.
                    Consultant review ≠ MOCCAE legal verification unless contracted.
                </p>
            </div>
        </div>
    @elseif($level === 'teaser')
        <div class="mnz-panel mnz-seam">
            <div class="mnz-panel__body" style="font-size:12.5px;color:var(--ink-2)">
                <a href="{{ route('subscriptions.upgrade') }}" class="cs-link" style="font-weight:600">Upgrade to Starter</a>
                to request introductions.
            </div>
        </div>
    @endif

</div>
@endsection
