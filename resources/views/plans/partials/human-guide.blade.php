@php
    $guide = $guide ?? [];
    $show = $show ?? ['intro', 'how_it_works', 'examples', 'clarifications', 'faq'];
    $intro = $guide['intro'] ?? [];
    $examples = $guide['examples'] ?? [];
    $clarifications = $guide['clarifications'] ?? [];
    $faq = $guide['faq'] ?? [];
    $howItWorks = $guide['how_it_works'] ?? [];
@endphp

@if(in_array('intro', $show, true) && !empty($intro))
    <div class="callout-panel callout-panel--brand mb-6">
        <h2 class="callout-panel__title">{{ $intro['title'] ?? 'About these plans' }}</h2>
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

@if(in_array('how_it_works', $show, true) && !empty($howItWorks))
    <div class="card mb-6">
        <div class="card-header">
            <h2 class="card-title">How it works</h2>
            <p class="card-subtitle">The same flow most consultants and in-house teams follow.</p>
        </div>
        <div class="card-body">
            <ol class="portal-guide-steps">
                @foreach($howItWorks as $index => $step)
                    <li class="portal-guide-step">
                        <span class="portal-guide-step__num">{{ $index + 1 }}</span>
                        <div>
                            <strong>{{ $step['title'] ?? '' }}</strong>
                            <p class="portal-guide-step__text">{{ $step['body'] ?? '' }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
@endif

@if(in_array('examples', $show, true) && !empty($examples))
    <div class="mb-6">
        <h2 class="section-heading mb-2">Which pack is right for you?</h2>
        <p class="text-sm text-slate-600 mb-4">Real-world examples — pick the story closest to yours.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($examples as $example)
                <div class="card h-full">
                    <div class="card-body">
                        <span class="badge badge-neutral mb-2">{{ $example['plan'] ?? $example['pack'] ?? 'Plan' }}</span>
                        <p class="text-sm text-slate-800 mb-2"><strong>Example:</strong> {{ $example['scenario'] ?? '' }}</p>
                        <p class="text-sm text-slate-600 mb-0"><strong>You get:</strong> {{ $example['you_get'] ?? '' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

@if(in_array('clarifications', $show, true) && !empty($clarifications))
    <div class="card mb-6">
        <div class="card-header">
            <h2 class="card-title">Good to know</h2>
            <p class="card-subtitle">Short clarifications before you choose.</p>
        </div>
        <div class="card-body space-y-4">
            @foreach($clarifications as $item)
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 mb-1">{{ $item['title'] ?? '' }}</h3>
                    <p class="text-sm text-slate-600 mb-0">{{ $item['body'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </div>
@endif

@if(in_array('faq', $show, true) && !empty($faq))
    <div class="mb-6">
        <h2 class="section-heading mb-3">Frequently asked questions</h2>
        <div class="space-y-3">
            @foreach($faq as $index => $item)
                <details class="card portal-guide-details" {{ $index === 0 ? 'open' : '' }}>
                    <summary class="card-header portal-guide-details__summary cursor-pointer">
                        <h3 class="card-title mb-0">{{ $item['q'] ?? '' }}</h3>
                    </summary>
                    <div class="card-body border-t border-slate-100">
                        <p class="text-sm text-slate-600 mb-0">{{ $item['a'] ?? '' }}</p>
                    </div>
                </details>
            @endforeach
        </div>
    </div>
@endif
