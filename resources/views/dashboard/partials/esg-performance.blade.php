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

    {{-- Emissions against the reduction pathway.

         INLINE SVG, NO CHART LIBRARY. Both shells load Chart.js, but this is
         two polylines and a handful of ticks -- drawing it inline keeps the
         partial theme-agnostic and avoids a second chart instance competing
         with the dashboard's own.

         The pathway ends at the TARGET's tonnage, not at zero, unless the
         target is itself zero. --}}
    @if (!empty($esgCards['pathway']))
        @php
            $pw = $esgCards['pathway'];
            $series = $pw['actual'];
            $required = $pw['required'];

            // One shared scale for both lines, so they are comparable.
            $allYears = array_unique(array_merge(array_keys($series), array_keys($required)));
            sort($allYears);
            $minYear = $allYears[0];
            $maxYear = $allYears[count($allYears) - 1];
            $maxVal = max(array_merge([1], array_values($series), array_values($required)));
            $span = max(1, $maxYear - $minYear);

            $W = 720; $H = 220; $padL = 52; $padR = 16; $padT = 12; $padB = 28;
            $plotW = $W - $padL - $padR;
            $plotH = $H - $padT - $padB;

            $xFor = fn ($year) => $padL + (($year - $minYear) / $span) * $plotW;
            $yFor = fn ($val) => $padT + $plotH - (($val / $maxVal) * $plotH);

            $pts = fn ($set) => implode(' ', array_map(
                fn ($y) => round($xFor($y), 1) . ',' . round($yFor($set[$y]), 1),
                array_keys($set)
            ));
        @endphp

        <section class="esg-path" aria-label="Emissions against pathway">
            <div class="esg-path__head">
                <div>
                    <h3 class="esg-path__title">Emissions against {{ $pw['target_year'] }} pathway</h3>
                    <p class="esg-path__sub">
                        tCO<sub>2</sub>e, absolute · baseline FY{{ $pw['base_year'] }} = {{ number_format($pw['baseline']) }}
                        @if ($pw['target_is_derived'])
                            <span class="esg-path__note" title="Target tonnage derived from the reduction percentage">derived target</span>
                        @endif
                    </p>
                </div>
                <div class="esg-path__tags">
                    @if ($pw['scope_label'])
                        <span class="esg-path__scope">{{ $pw['scope_label'] }}</span>
                    @endif
                    @if ($pw['sbti_aligned'])
                        <span class="esg-path__sbti" title="Target validated as SBTi-aligned">SBTi</span>
                    @endif
                </div>
            </div>

            <div class="esg-path__chart">
                <svg viewBox="0 0 {{ $W }} {{ $H }}" width="100%" height="{{ $H }}"
                     role="img" preserveAspectRatio="xMidYMid meet"
                     aria-label="Emissions from {{ $minYear }} to {{ $maxYear }} against the required reduction pathway">
                    @foreach ([0, 0.25, 0.5, 0.75, 1] as $t)
                        @php $gy = $padT + $plotH - ($t * $plotH); @endphp
                        <line x1="{{ $padL }}" y1="{{ round($gy, 1) }}" x2="{{ $W - $padR }}" y2="{{ round($gy, 1) }}"
                              stroke="#eeeeec" stroke-width="1"/>
                        <text x="{{ $padL - 8 }}" y="{{ round($gy + 3.5, 1) }}" text-anchor="end"
                              font-size="10" fill="#8b9199">{{ number_format($maxVal * $t) }}</text>
                    @endforeach

                    {{-- Required pathway first, so the actual line sits above it. --}}
                    <polyline points="{{ $pts($required) }}" fill="none" stroke="#a4a9ae"
                              stroke-width="1.5" stroke-dasharray="4 4"/>

                    @if (count($series) > 1)
                        <polyline points="{{ $pts($series) }}" fill="none" stroke="#0f7a4a" stroke-width="2"/>
                    @endif
                    @foreach ($series as $yr => $val)
                        <circle cx="{{ round($xFor($yr), 1) }}" cy="{{ round($yFor($val), 1) }}" r="3.5"
                                fill="#fff" stroke="#0f7a4a" stroke-width="2"/>
                    @endforeach

                    @foreach ($allYears as $yr)
                        <text x="{{ round($xFor($yr), 1) }}" y="{{ $H - 8 }}" text-anchor="middle"
                              font-size="10" fill="#8b9199">{{ $yr }}</text>
                    @endforeach
                </svg>

                <div class="esg-path__legend">
                    <span><i class="esg-path__key esg-path__key--actual"></i>Actual</span>
                    <span><i class="esg-path__key esg-path__key--req"></i>Required pathway</span>
                </div>
            </div>

            <dl class="esg-path__stats">
                <div>
                    <dt>Baseline FY{{ substr((string) $pw['base_year'], -2) }}</dt>
                    <dd>{{ number_format($pw['baseline']) }} t</dd>
                </div>
                <div>
                    <dt>Current FY{{ substr((string) $pw['current_year'], -2) }}</dt>
                    <dd>{{ $pw['current'] !== null ? number_format($pw['current']) . ' t' : '—' }}</dd>
                </div>
                <div>
                    <dt>Change vs baseline</dt>
                    <dd>{{ $pw['reduction_percent'] !== null ? number_format($pw['reduction_percent'], 1) . '%' : '—' }}</dd>
                </div>
                <div>
                    <dt>Reaches target</dt>
                    <dd>
                        @if ($pw['projection'])
                            {{ $pw['projection'] }} <span class="esg-path__qualifier">at current rate</span>
                        @else
                            <span class="esg-path__qualifier">not on current trend</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </section>
    @endif

    {{-- Framework readiness. Reuses the SAME weighted completeness the
         disclosure pages show, so a percent here can never disagree with the
         percent on the framework's own page. --}}
    @if (!empty($esgCards['frameworks']))
        <section class="esg-fw" aria-label="Framework readiness">
            <h3 class="esg-fw__title">Framework readiness</h3>

            @foreach ($esgCards['frameworks'] as $fw)
                @php $ink = $pillarInk[$fw['pillar']] ?? '#14161a'; @endphp
                <div class="esg-fw__row">
                    <div class="esg-fw__head">
                        <span class="esg-fw__label">
                            @if ($fw['url'])
                                <a href="{{ $fw['url'] }}">{{ $fw['label'] }}</a>
                            @else
                                {{ $fw['label'] }}
                            @endif
                            @if ($fw['note'])
                                <span class="esg-fw__note" title="{{ $fw['note'] }}">derived</span>
                            @endif
                        </span>
                        <span class="esg-fw__pct">{{ $fw['percent'] }}%</span>
                    </div>
                    <div class="esg-fw__bar" role="img"
                         aria-label="{{ $fw['label'] }} {{ $fw['percent'] }} percent ready">
                        <span style="width:{{ $fw['percent'] }}%;background:{{ $ink }}"></span>
                    </div>
                </div>
            @endforeach
        </section>
    @endif
@endif
