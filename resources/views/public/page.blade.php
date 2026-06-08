@extends('layouts.public')

@section('title', $page->title . ' — ' . \App\Models\SiteSetting::get('brand_name', 'MENetZero'))

@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">{{ $page->title }}</h1>
    <div class="prose prose-teal max-w-none text-gray-700 leading-relaxed space-y-4
                [&_h2]:text-xl [&_h2]:font-semibold [&_h2]:text-gray-900 [&_h2]:mt-8 [&_h2]:mb-2
                [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:space-y-1 [&_p]:mb-3">
        {!! $page->renderedBody() !!}
    </div>
</div>
@endsection
