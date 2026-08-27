{{--
    MENetZero 2.0 - Company guide, consultant view (Phase 6 body migration).

    A consultant reading the COMPANY guide while working inside a client
    workspace. Extends the consultant shell but passes portal => 'company',
    so the previews match what the client actually sees.

    That mismatch is deliberate and is the whole point of this page: shell is
    consultant, content is company. Do not "fix" it to 'consultant'.

    Keeps the cross-link back to the consultant guide, which the plain
    help.company view does NOT have.

    Controller data: $guide
--}}
@extends('consultant.layouts.app')

@section('title', 'Company Portal Guide')
@section('page-title', 'Company Portal Guide')

@section('content')
<div class="mnz-stack" data-pillar="g">

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">For client workspaces</div>
            <h1>Company portal guide</h1>
            <p class="mnz-lead">How to set up locations, enter emissions, and run reports when working inside a client workspace.</p>
        </div>
        <div class="mnz-pagehead__actions">
            <a href="{{ route('consultant.help') }}" class="mnz-btn mnz-btn--ghost">Consultant guide</a>
        </div>
    </div>

    @include('help.partials.guide-body', ['guide' => $guide, 'portal' => 'company'])

</div>
@endsection
