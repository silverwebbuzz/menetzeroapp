{{--
    ESG performance cards - the three E / S / G panels at the top of Overview.

    Pass 1 of the design-canvas Overview panel. SHARED BY BOTH THEMES: the
    markup is plain and the colours come from inline pillar tokens, so the
    same file renders correctly in the old shell and the new one. Neither
    theme's stylesheet needs to know about it.

    ADDITIVE. This renders ABOVE the existing dashboard; nothing below it was
    removed. $esgCards is null when the service failed or when the page is on
    the onboarding path, and the whole panel disappears rather than erroring.

    THE HEADLINE NUMBER IS DATA COMPLETENESS, NOT A RATING. It is the share of
    that pillar's disclosure checks that have data. It is labelled that way on
    purpose -- an unlabelled "82" invites the reader to treat it as an
    external ESG score, which it is not.

    "not collected" IS DELIBERATE. A metric with no data shows that phrase in
    amber, never 0 -- a zero would read as a measured result. The design does
    the same for LTIFR and "not disclosed".

    Data: $esgCards (from EsgPerformanceCardService::build)
--}}
@if (!empty($esgCards))
    @php
        $pillarInk = ['e' => '#0f7a4a', 's' => '#1a6c9e', 'g' => '#5b5aa8'];
        $ctx = $esgCards['context'];
        $bits = array_filter([
            $ctx['company'] ?? null,
            'FY ' . $ctx['fiscal_year'],
            $ctx['sites'] ? $ctx['sites'] . ' ' . \Illuminate\Support\Str::plural('site', $ctx['sites']) : null,
            $ctx['consolidation'] ?? null,
        ]);
    @endphp

    <section class="esg-cards" aria-label="ESG performance">
        <div class="esg-cards__head">
            <div>
                <h2 class="esg-cards__title">ESG performance</h2>
                <p class="esg-cards__ctx">{{ implode(' · ', $bits) }}</p>
            </div>
        </div>

        <div class="esg-cards__grid">
            @foreach ($esgCards['pillars'] as $pillar)
                @php $ink = $pillarInk[$pillar['pillar']] ?? '#14161a'; @endphp
                <div class="esg-card">
                    <div class="esg-card__top">
                        <span class="esg-card__code" style="color:{{ $ink }}">
                            <span class="esg-card__swatch" style="background:{{ $ink }}"></span>{{ $pillar['code'] }}
                        </span>
                        <div class="esg-card__score">
                            <span class="esg-card__pct">{{ $pillar['percent'] }}%</span>
                            <span class="esg-card__pctlabel">data complete</span>
                        </div>
                    </div>

                    <h3 class="esg-card__name">{{ $pillar['title'] }}</h3>
                    <p class="esg-card__sub">{{ $pillar['subtitle'] }}</p>

                    <div class="esg-card__bar" role="img"
                         aria-label="{{ $pillar['percent'] }}% of {{ $pillar['title'] }} disclosure fields collected">
                        <span style="width:{{ $pillar['percent'] }}%;background:{{ $ink }}"></span>
                    </div>

                    <dl class="esg-card__metrics">
                        @foreach ($pillar['metrics'] as $metric)
                            <div class="esg-card__row">
                                <dt>
                                    {{ $metric['label'] }}
                                    @if ($metric['code'])
                                        <span class="esg-card__code-tag">{{ $metric['code'] }}</span>
                                    @endif
                                </dt>
                                <dd class="{{ $metric['collected'] ? '' : 'is-missing' }}">{{ $metric['display'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endforeach
        </div>
    </section>
@endif
