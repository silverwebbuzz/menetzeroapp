{{--
    "Feeds:" data-lineage line for a register page (Phase D).

    Answers the question the old framework tab strips answered badly: WHY
    does this page exist, and where does what I type here end up?

    Previously a register lived inside a framework's tab strip
    (/disclosures/ifrs-s2/climate-risks), which implied the framework OWNED
    the data — so the same record had to be re-entered for every framework
    that wanted it. Now the register is owned by its pillar, and this line
    names the reports that READ it. One page, one owner, many consumers.

    The framework list comes from 'feeds' in config/navigation.php, which is
    verified against the report services' actual model imports — not
    guessed. Display only: it never gates anything, and an entry whose route
    no longer exists is silently dropped rather than throwing.

    Included by both themes; the classes exist in both stylesheets.
--}}
@php
    $lineage = \App\Support\NavigationMap::feedsFor();
@endphp

@if (! empty($lineage))
    <p class="mnz-lineage">
        <span class="mnz-lineage__label">Feeds</span>
        @foreach ($lineage as $feed)
            <a href="{{ $feed['url'] }}" class="mnz-lineage__link">{{ $feed['label'] }}</a>@if (! $loop->last)<span class="mnz-lineage__sep">·</span>@endif
        @endforeach
    </p>
@endif
