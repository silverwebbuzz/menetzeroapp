{{--
    Emission boundaries — all three scopes on one page.

    The tabs this replaced were cosmetic in the same way the location wizard's
    were: all three panels already lived inside ONE <form>, and showTab() only
    toggled display:none. So nothing was ever lost on save — every checkbox in
    every scope posted together regardless of which tab was visible. But the
    Next/Save-and-Close pair implied a sequence that did not exist, and two
    thirds of the boundary was hidden at any moment, which makes it hard to
    judge whether the boundary as a whole is complete.

    Every source here comes from emission_sources_master (is_active = true) via
    EmissionBoundaryController::index(). Nothing on this page is hardcoded, so
    a source added to the table appears here automatically.

    Scope 3 keeps the upstream / downstream / untyped split. The "Other Scope 3"
    bucket is load-bearing: type was NULL on every row until the 2026_08_24
    backfill, and a source with an unparseable category still lands there rather
    than vanishing. Do not drop it.
--}}
@extends('layouts.app')

@section('title', 'Emission Boundaries - ' . $location->name)
@section('page-title', 'Emission Boundaries — ' . $location->name)

@section('content')
@php
    $upstreamCategories = $scope3Sources->where('type', 'upstream')->groupBy('category');
    $downstreamCategories = $scope3Sources->where('type', 'downstream')->groupBy('category');
    $untypedSources = $scope3Sources->filter(
        fn ($s) => !in_array($s->type, ['upstream', 'downstream'], true)
    );

    $selectedIn = fn ($sources) => $sources->whereIn('id', $selectedBoundaries)->count();

    $scopeMeta = [
        1 => [
            'sources'  => $scope1Sources,
            'title'    => 'Direct emissions from sources you own or control',
            'blurb'    => 'Fuel burned on site or in your own vehicles — natural gas, diesel generators, '
                        . 'company cars — plus refrigerant leaks and process emissions.',
            'accent'   => 'orange',
        ],
        2 => [
            'sources'  => $scope2Sources,
            // This heading previously read "Stationary energy and fuels
            // emissions" — copy-pasted from Scope 1, and wrong. Scope 2 is
            // purchased energy, not combustion.
            'title'    => 'Indirect emissions from the energy you purchase',
            'blurb'    => 'Electricity, and any purchased heat, steam or cooling. The emissions happen at '
                        . 'the power station, but they are yours to report.',
            'accent'   => 'blue',
        ],
    ];

    $totalSelected = count(array_unique($selectedBoundaries));
    $totalAvailable = $scope1Sources->count() + $scope2Sources->count() + $scope3Sources->count();
@endphp

<style>
    .src { padding: 10px 0; border-bottom: 1px solid #f3f4f6; }
    .src:last-child { border-bottom: none; }
    .src-row { display: flex; align-items: flex-start; gap: 10px; }
    .src-row input { margin-top: 3px; width: 16px; height: 16px; flex: none; cursor: pointer; }
    .src-row label { font-weight: 500; color: #374151; cursor: pointer; }
    .src-desc { margin-left: 26px; font-size: 13px; color: #6b7280; margin-top: 2px; }
    .cat-title {
        font-size: 13px; font-weight: 600; color: #4b5563;
        text-transform: uppercase; letter-spacing: .02em; margin-bottom: 6px;
    }
    .cat-group { margin-bottom: 20px; }
    .scope-anchor { scroll-margin-top: 90px; }
    .jump { position: sticky; top: 0; z-index: 20; background: #f9fafb;
            border-bottom: 1px solid #e5e7eb; padding: 10px 0; margin-bottom: 20px; }
</style>

<div class="max-w-4xl mx-auto">

    <div class="flex items-start justify-between mb-2">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Emission boundaries</h2>
            <p class="text-gray-600 mt-1">
                for <span class="font-semibold text-gray-900">{{ $location->name }}</span>
                @if($location->city || $location->country)
                    <span class="text-gray-500">· {{ collect([$location->city, $location->country])->filter()->join(', ') }}</span>
                @endif
            </p>
        </div>
        <a href="{{ route('locations.index') }}"
           class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition whitespace-nowrap">
            Back to Locations
        </a>
    </div>

    <p class="text-sm text-gray-600 mb-6">
        Tick every source that applies to this location. These choices decide which data-entry forms you
        see — you can change them at any time, and unticking a source never deletes data you have already entered.
    </p>

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="jump">
        <div class="flex items-center gap-2 text-sm">
            <span class="text-gray-500">Jump to:</span>
            <a href="#scope1" class="px-3 py-1 rounded-lg border border-gray-300 hover:bg-white">Scope 1</a>
            <a href="#scope2" class="px-3 py-1 rounded-lg border border-gray-300 hover:bg-white">Scope 2</a>
            <a href="#scope3" class="px-3 py-1 rounded-lg border border-gray-300 hover:bg-white">Scope 3</a>
            <span class="ml-auto text-gray-600" id="total-counter"
                  data-total="{{ $totalAvailable }}">{{ $totalSelected }} of {{ $totalAvailable }} selected</span>
        </div>
    </div>

    <form method="POST" action="{{ route('emission-boundaries.store', $location) }}" class="space-y-6">
        @csrf

        {{-- Scope 1 and 2 share a shape, so they share a loop. Scope 3 does not
             (it has the category grouping) and is written out below. --}}
        @foreach($scopeMeta as $number => $meta)
            <div id="scope{{ $number }}" class="scope-anchor bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-start justify-between mb-1">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Scope {{ $number }}
                        <span class="font-normal text-gray-500">— {{ $meta['title'] }}</span>
                    </h3>
                    <span class="text-sm text-gray-500 whitespace-nowrap ml-4 scope-count"
                          data-scope="{{ $number }}">
                        {{ $selectedIn($meta['sources']) }} of {{ $meta['sources']->count() }}
                    </span>
                </div>
                <p class="text-sm text-gray-600 mb-4">{{ $meta['blurb'] }}</p>

                @if($meta['sources']->isEmpty())
                    <p class="text-sm text-gray-500 italic">
                        No active Scope {{ $number }} sources are configured. Contact your administrator.
                    </p>
                @else
                    <div>
                        @foreach($meta['sources'] as $source)
                            <div class="src">
                                <div class="src-row">
                                    <input type="checkbox" name="emission_sources[]"
                                           value="{{ $source->id }}" id="src_{{ $source->id }}"
                                           data-scope="{{ $number }}" class="src-check"
                                           @checked(in_array($source->id, $selectedBoundaries))>
                                    <label for="src_{{ $source->id }}">{{ $source->name }}</label>
                                </div>
                                @if($source->description)
                                    <div class="src-desc">{{ $source->description }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

        {{-- Scope 3 --}}
        <div id="scope3" class="scope-anchor bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-start justify-between mb-1">
                <h3 class="text-lg font-semibold text-gray-900">
                    Scope 3
                    <span class="font-normal text-gray-500">— everything else in your value chain</span>
                </h3>
                <span class="text-sm text-gray-500 whitespace-nowrap ml-4 scope-count" data-scope="3">
                    {{ $selectedIn($scope3Sources) }} of {{ $scope3Sources->count() }}
                </span>
            </div>
            <p class="text-sm text-gray-600 mb-4">
                Emissions you cause but do not control — purchased goods, business travel, commuting, waste.
                Start with the categories where you already have data; you are not expected to tick everything.
            </p>

            @if($scope3Sources->isEmpty())
                <p class="text-sm text-gray-500 italic">
                    No active Scope 3 sources are configured. Contact your administrator.
                </p>
            @else
                @if($upstreamCategories->isNotEmpty())
                    <h4 class="font-semibold text-gray-900 mb-3 pb-2 border-b border-gray-200">Upstream</h4>
                    @foreach($upstreamCategories as $category => $sources)
                        <div class="cat-group">
                            <div class="cat-title">{{ $category ?: 'Uncategorised' }}</div>
                            @foreach($sources as $source)
                                <div class="src">
                                    <div class="src-row">
                                        <input type="checkbox" name="emission_sources[]"
                                               value="{{ $source->id }}" id="src_{{ $source->id }}"
                                               data-scope="3" class="src-check"
                                               @checked(in_array($source->id, $selectedBoundaries))>
                                        <label for="src_{{ $source->id }}">{{ $source->name }}</label>
                                    </div>
                                    @if($source->description)
                                        <div class="src-desc">{{ $source->description }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @endif

                @if($downstreamCategories->isNotEmpty())
                    <h4 class="font-semibold text-gray-900 mb-3 mt-6 pb-2 border-b border-gray-200">Downstream</h4>
                    @foreach($downstreamCategories as $category => $sources)
                        <div class="cat-group">
                            <div class="cat-title">{{ $category ?: 'Uncategorised' }}</div>
                            @foreach($sources as $source)
                                <div class="src">
                                    <div class="src-row">
                                        <input type="checkbox" name="emission_sources[]"
                                               value="{{ $source->id }}" id="src_{{ $source->id }}"
                                               data-scope="3" class="src-check"
                                               @checked(in_array($source->id, $selectedBoundaries))>
                                        <label for="src_{{ $source->id }}">{{ $source->name }}</label>
                                    </div>
                                    @if($source->description)
                                        <div class="src-desc">{{ $source->description }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @endif

                {{-- Sources whose type is neither upstream nor downstream. Before
                     the type backfill these rendered nowhere at all — present in
                     the database, invisible on this page. --}}
                @if($untypedSources->isNotEmpty())
                    <h4 class="font-semibold text-gray-900 mb-3 mt-6 pb-2 border-b border-gray-200">Other Scope 3</h4>
                    @foreach($untypedSources->groupBy('category') as $category => $sources)
                        <div class="cat-group">
                            <div class="cat-title">{{ $category ?: 'Uncategorised' }}</div>
                            @foreach($sources as $source)
                                <div class="src">
                                    <div class="src-row">
                                        <input type="checkbox" name="emission_sources[]"
                                               value="{{ $source->id }}" id="src_{{ $source->id }}"
                                               data-scope="3" class="src-check"
                                               @checked(in_array($source->id, $selectedBoundaries))>
                                        <label for="src_{{ $source->id }}">{{ $source->name }}</label>
                                    </div>
                                    @if($source->description)
                                        <div class="src-desc">{{ $source->description }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @endif
            @endif
        </div>

        <div class="flex justify-between items-center">
            <a href="{{ route('locations.index') }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                Save boundaries
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    // Live counts. Purely informational — the server recomputes from the
    // posted ids, so a stale count here can never affect what is saved.
    var boxes = document.querySelectorAll('.src-check');
    var totalEl = document.getElementById('total-counter');
    var total = parseInt(totalEl.getAttribute('data-total'), 10);

    function recount() {
        var perScope = {};
        var selected = 0;

        boxes.forEach(function (b) {
            var s = b.getAttribute('data-scope');
            if (!perScope[s]) { perScope[s] = 0; }
            if (b.checked) { perScope[s]++; selected++; }
        });

        document.querySelectorAll('.scope-count').forEach(function (el) {
            var s = el.getAttribute('data-scope');
            var totalForScope = document.querySelectorAll('.src-check[data-scope="' + s + '"]').length;
            el.textContent = (perScope[s] || 0) + ' of ' + totalForScope;
        });

        totalEl.textContent = selected + ' of ' + total + ' selected';
    }

    boxes.forEach(function (b) { b.addEventListener('change', recount); });
    recount();
})();
</script>
@endsection
