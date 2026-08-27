{{--
    MENetZero 2.0 - Shared guide body (Phase 6 body migration).

    Rendered by all three help pages, in BOTH portals. Everything is driven by
    config('portal-guide-company') / config('portal-guide-consultant'); this
    partial renders whatever shape the config supplies and must stay tolerant
    of missing keys.

    EVERY BLOCK IS OPTIONAL. intro / workflow / sections / faq are each behind
    an !empty() guard, and inside sections every sub-block is too. A config
    entry with only a title must still render. All guards preserved verbatim.

    SUPPORT ROUTE IS COMPUTED, NOT PASSED:
        auth('consultant')->check() ? 'consultant.support' : 'client.support'
    It keys off the ACTIVE GUARD, not the portal flag - a consultant reading
    the company guide still gets consultant support. Do not swap it to $portal.

    NESTED INCLUDE: this file includes the themed guide-highlight via the
    registered 'theme-new::' namespace (ThemeServiceProvider), which is the
    convention every other themed include uses. A literal 'themes.new.*'
    dotted path also resolves today but hard-pins the include to one theme and
    bypasses the finder, so it is deliberately NOT used. That highlight then
    forwards to the SHARED guide-mock. Per the scope decision, the 23 mock
    variants are NOT re-skinned - they keep old-theme classes. Both themed
    shells load the same four stylesheets as their old counterparts, so those
    classes all resolve.

    LEGACY BRANCH: the "elseif" on $section["image"] is a deprecated
    single-image path superseded by 'highlights'. Kept - config entries in the
    wild may still use it, and it array_merges caption into title.

    STEPS ARE POLYMORPHIC: a step is either an array (title + optional body) or
    a bare string. Both branches preserved.

    <details> + $loop->first ? 'open' : '' means the first section is expanded
    on load. Native disclosure, no Alpine involved.

    Data: $guide $portal
--}}
@php
    $guide = $guide ?? [];
    $intro = $guide['intro'] ?? [];
    $workflow = $guide['workflow'] ?? [];
    $sections = $guide['sections'] ?? [];
    $faq = $guide['faq'] ?? [];
@endphp

@push('styles')
    <style>
        .hg-support { display: flex; align-items: center; justify-content: space-between;
            gap: 16px; flex-wrap: wrap; }
        .hg-steps { list-style: none; margin: 0; padding: 0; display: grid; gap: 14px; }
        .hg-step { display: flex; gap: 12px; align-items: flex-start; }
        .hg-step__num { flex-shrink: 0; width: 22px; height: 22px; font-size: 11px;
            font-weight: 600; display: flex; align-items: center; justify-content: center;
            background: var(--accent-tint); color: var(--accent);
            border: 1px solid var(--accent-line); }
        .hg-step__title { font-size: 13px; font-weight: 600; color: var(--ink); }
        .hg-step__text { font-size: 12.5px; color: var(--ink-2); margin: 3px 0 6px;
            line-height: 1.6; }
        .hg-list { margin: 0; padding-left: 18px; display: grid; gap: 5px; }
        .hg-list li { font-size: 12.5px; color: var(--ink-2); line-height: 1.6; }
        .hg-sec { border: 1px solid var(--line); background: var(--surface); }
        .hg-sec + .hg-sec { margin-top: 8px; }
        .hg-sec__summary { padding: 13px 16px; cursor: pointer; list-style: none;
            display: flex; align-items: center; gap: 8px; }
        .hg-sec__summary::-webkit-details-marker { display: none; }
        .hg-sec__summary::before { content: '▸'; color: var(--ink-3); font-size: 11px;
            flex-shrink: 0; }
        .hg-sec[open] > .hg-sec__summary::before { content: '▾'; }
        .hg-sec__title { font-size: 13.5px; font-weight: 600; color: var(--ink); margin: 0; }
        .hg-sec__sub { font-size: 12px; color: var(--ink-3); margin: 2px 0 0; }
        .hg-sec__body { padding: 16px; border-top: 1px solid var(--line); }
        .hg-body-text { font-size: 12.5px; color: var(--ink-2); line-height: 1.65;
            margin: 0 0 14px; }
        .hg-tips { background: var(--canvas-2); border: 1px solid var(--line);
            padding: 12px 14px; margin: 0 0 14px; }
        .hg-tips__title { font-size: 11px; text-transform: uppercase;
            letter-spacing: .06em; color: var(--ink-3); margin: 0 0 6px; }
        .hg-links { display: flex; flex-wrap: wrap; gap: 6px; }
        .hg-faq__q { font-size: 13px; font-weight: 600; color: var(--ink); margin: 0 0 3px; }
        .hg-faq__a { font-size: 12.5px; color: var(--ink-2); line-height: 1.6; margin: 0; }
        .hg-faq__item + .hg-faq__item { margin-top: 16px; padding-top: 16px;
            border-top: 1px solid var(--line); }
        /* Mirrors .portal-guide-highlights: multiple previews in one section
           sit side by side and wrap, rather than stacking single-column. */
        .hg-mocks { display: grid; gap: 12px; margin: 0 0 14px;
            grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr)); }
    </style>
@endpush

<div class="portal-guide mnz-stack">

    @php
        $supportRoute = auth('consultant')->check() ? 'consultant.support' : 'client.support';
    @endphp

    <div class="mnz-panel">
        <div class="mnz-panel__body hg-support">
            <div>
                <div style="font-size:13.5px;font-weight:600;color:var(--ink);margin-bottom:3px">Need help?</div>
                <p style="font-size:12.5px;color:var(--ink-2);margin:0;line-height:1.6">
                    Can&apos;t find what you need in this guide? Send a support request — it goes to {{ site_support_email() }}.
                </p>
            </div>
            <a href="{{ route($supportRoute) }}" class="mnz-btn mnz-btn--primary" style="flex-shrink:0">Email us for support</a>
        </div>
    </div>

    @if(!empty($intro))
        <div class="mnz-panel mnz-seam">
            <div class="mnz-panel__body">
                <div style="font-size:13.5px;font-weight:600;color:var(--ink);margin-bottom:5px">{{ $intro['title'] ?? 'Welcome' }}</div>
                <p class="hg-body-text" style="margin-bottom:0">{{ $intro['body'] ?? '' }}</p>
                @if(!empty($intro['tips']))
                    <ul class="hg-list" style="margin-top:12px">
                        @foreach($intro['tips'] as $tip)
                            <li>{{ $tip }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endif

    @if(!empty($workflow))
        <div class="mnz-panel">
            <div class="mnz-panel__head">
                <h2 style="font-size:14px;font-weight:600;margin:0">Recommended workflow</h2>
                <p style="font-size:12px;color:var(--ink-3);margin:3px 0 0">Follow these steps in order when setting up emissions tracking for the first time.</p>
            </div>
            <div class="mnz-panel__body">
                <ol class="hg-steps">
                    @foreach($workflow as $index => $step)
                        <li class="hg-step">
                            <span class="hg-step__num">{{ $index + 1 }}</span>
                            <div>
                                <div class="hg-step__title">{{ $step['title'] }}</div>
                                <p class="hg-step__text">{{ $step['body'] }}</p>
                                @if(!empty($step['route']))
                                    <a href="{{ route($step['route'], $step['route_params'] ?? []) }}"
                                       style="font-size:12.5px;font-weight:500;color:var(--accent);text-decoration:none">
                                        {{ $step['link_label'] ?? 'Open' }} &rarr;
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    @endif

    @if(!empty($sections))
        <div>
            <div class="mnz-kicker" style="margin-bottom:10px">Guide by area</div>
            <div class="portal-guide-sections">
                @foreach($sections as $section)
                    <details class="hg-sec" id="{{ $section['id'] ?? \Illuminate\Support\Str::slug($section['title']) }}" {{ $loop->first ? 'open' : '' }}>
                        <summary class="hg-sec__summary">
                            <div>
                                <h3 class="hg-sec__title">{{ $section['title'] }}</h3>
                                @if(!empty($section['summary']))
                                    <p class="hg-sec__sub">{{ $section['summary'] }}</p>
                                @endif
                            </div>
                        </summary>
                        <div class="hg-sec__body">
                            @if(!empty($section['body']))
                                <p class="hg-body-text">{{ $section['body'] }}</p>
                            @endif
                            @if(!empty($section['highlights']))
                                <div class="hg-mocks portal-guide-highlights">
                                    @foreach($section['highlights'] as $highlight)
                                        @include('theme-new::help.partials.guide-highlight', [
                                            'highlight' => $highlight,
                                            'portal' => $portal ?? 'company',
                                        ])
                                    @endforeach
                                </div>
                            @elseif(!empty($section['image']))
                                {{-- Legacy single image (deprecated - use highlights) --}}
                                @include('theme-new::help.partials.guide-highlight', [
                                    'highlight' => array_merge($section['image'], [
                                        'title' => $section['image']['caption'] ?? null,
                                    ]),
                                    'portal' => $portal ?? 'company',
                                ])
                            @endif
                            @if(!empty($section['steps']))
                                <ul class="hg-list" style="margin-bottom:14px">
                                    @foreach($section['steps'] as $step)
                                        <li>
                                            @if(is_array($step))
                                                <strong>{{ $step['title'] ?? '' }}</strong>
                                                @if(!empty($step['body']))
                                                    — {{ $step['body'] }}
                                                @endif
                                            @else
                                                {{ $step }}
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            @if(!empty($section['tips']))
                                <div class="hg-tips">
                                    <p class="hg-tips__title">Tips</p>
                                    <ul class="hg-list">
                                        @foreach($section['tips'] as $tip)
                                            <li>{{ $tip }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            @if(!empty($section['links']))
                                <div class="hg-links">
                                    @foreach($section['links'] as $link)
                                        <a href="{{ route($link['route'], $link['params'] ?? []) }}" class="mnz-btn mnz-btn--ghost">
                                            {{ $link['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </details>
                @endforeach
            </div>
        </div>
    @endif

    @if(!empty($faq))
        <div class="mnz-panel">
            <div class="mnz-panel__head">
                <h2 style="font-size:14px;font-weight:600;margin:0">Common questions</h2>
            </div>
            <div class="mnz-panel__body">
                @foreach($faq as $item)
                    <div class="hg-faq__item">
                        <p class="hg-faq__q">{{ $item['q'] }}</p>
                        <p class="hg-faq__a">{{ $item['a'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
