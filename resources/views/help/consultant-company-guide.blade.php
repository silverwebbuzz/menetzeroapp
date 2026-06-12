@extends('consultant.layouts.app')

@section('title', 'Company Portal Guide')
@section('page-title', 'Company Portal Guide')

@section('content')
<div class="w-full max-w-4xl">
    <div class="cd-page-head mb-6">
        <div>
            <div class="cd-eyebrow">For client workspaces</div>
            <h2>Company portal guide</h2>
            <p class="cd-subtitle">How to set up locations, enter emissions, and run reports when working inside a client workspace.</p>
        </div>
        <div class="cd-page-actions">
            <a href="{{ route('consultant.help') }}" class="btn btn-secondary btn-sm">Consultant guide</a>
        </div>
    </div>

    @include('help.partials.guide-body', ['guide' => $guide])
</div>
@endsection
