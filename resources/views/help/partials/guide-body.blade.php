@php
    $guide = $guide ?? [];
    $intro = $guide['intro'] ?? [];
    $workflow = $guide['workflow'] ?? [];
    $sections = $guide['sections'] ?? [];
    $faq = $guide['faq'] ?? [];
@endphp

<div class="portal-guide">
    @if(!empty($intro))
        <div class="callout-panel callout-panel--brand mb-6">
            <h2 class="callout-panel__title">{{ $intro['title'] ?? 'Welcome' }}</h2>
            <p class="callout-panel__body">{{ $intro['body'] ?? '' }}</p>
            @if(!empty($intro['tips']))
                <ul class="portal-guide-list mt-3">
                    @foreach($intro['tips'] as $tip)
                        <li>{{ $tip }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    @if(!empty($workflow))
        <div class="card mb-6">
            <div class="card-header">
                <h2 class="card-title">Recommended workflow</h2>
                <p class="card-subtitle">Follow these steps in order when setting up emissions tracking for the first time.</p>
            </div>
            <div class="card-body">
                <ol class="portal-guide-steps">
                    @foreach($workflow as $index => $step)
                        <li class="portal-guide-step">
                            <span class="portal-guide-step__num">{{ $index + 1 }}</span>
                            <div>
                                <strong>{{ $step['title'] }}</strong>
                                <p class="portal-guide-step__text">{{ $step['body'] }}</p>
                                @if(!empty($step['route']))
                                    <a href="{{ route($step['route'], $step['route_params'] ?? []) }}" class="text-brand font-medium hover:underline">
                                        {{ $step['link_label'] ?? 'Open' }} →
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
        <h2 class="section-heading mb-4">Guide by area</h2>
        <div class="portal-guide-sections space-y-3 mb-6">
            @foreach($sections as $section)
                <details class="card portal-guide-details" id="{{ $section['id'] ?? \Illuminate\Support\Str::slug($section['title']) }}" {{ $loop->first ? 'open' : '' }}>
                    <summary class="card-header portal-guide-details__summary cursor-pointer">
                        <div>
                            <h3 class="card-title mb-0">{{ $section['title'] }}</h3>
                            @if(!empty($section['summary']))
                                <p class="card-subtitle mt-1 mb-0">{{ $section['summary'] }}</p>
                            @endif
                        </div>
                    </summary>
                    <div class="card-body border-t border-gray-100">
                        @if(!empty($section['image']))
                            @include('help.partials.guide-figure', [
                                'image' => $section['image'],
                                'portal' => $portal ?? 'company',
                            ])
                        @endif
                        @if(!empty($section['body']))
                            <p class="mb-4">{{ $section['body'] }}</p>
                        @endif
                        @if(!empty($section['steps']))
                            <ul class="portal-guide-list mb-4">
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
                            <div class="callout-panel callout-panel--locked mb-4">
                                <p class="callout-panel__title">Tips</p>
                                <ul class="portal-guide-list">
                                    @foreach($section['tips'] as $tip)
                                        <li>{{ $tip }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if(!empty($section['links']))
                            <div class="flex flex-wrap gap-2">
                                @foreach($section['links'] as $link)
                                    <a href="{{ route($link['route'], $link['params'] ?? []) }}" class="btn btn-secondary btn-sm">
                                        {{ $link['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>
    @endif

    @if(!empty($faq))
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Common questions</h2>
            </div>
            <div class="card-body divide-y divide-gray-100">
                @foreach($faq as $item)
                    <div class="py-4 first:pt-0 last:pb-0">
                        <p class="font-medium mb-1">{{ $item['q'] }}</p>
                        <p class="portal-text-secondary">{{ $item['a'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
