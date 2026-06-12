@extends('consultant.layouts.app')

@section('title', 'Help & Guide')
@section('page-title', 'Help & Guide')

@section('content')
<div class="w-full">
    <div class="cd-page-head mb-6">
        <div>
            <div class="cd-eyebrow">Documentation</div>
            <h2>Consultant portal guide</h2>
            <p class="cd-subtitle">Agency hub, client workspaces, packs, directory, and day-to-day operations.</p>
        </div>
    </div>

    @include('help.partials.guide-body', ['guide' => $guide, 'portal' => 'consultant'])
</div>
@endsection
