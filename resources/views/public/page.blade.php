@extends('layouts.public')

@section('title', $page->title . ' — ' . \App\Models\SiteSetting::get('brand_name', 'MENetZero'))

@section('content')
<section class="mkt-hero">
    <div class="mkt-container max-w-3xl">
        <h1>{{ $page->title }}</h1>
    </div>
</section>

<section class="mkt-section pt-0">
    <div class="mkt-container max-w-3xl">
        <div class="mkt-prose">
            {!! $page->renderedBody() !!}
        </div>
    </div>
</section>
@endsection
