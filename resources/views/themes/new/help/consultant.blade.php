{{--
    MENetZero 2.0 - Consultant portal guide (Phase 6 body migration).

    Extends the CONSULTANT shell, not the client one. Both themed shells load
    the same four stylesheets as their old counterparts, including
    consultant-shell.css - which is what defines .cd-pack-card, used by the
    shared mock partial's consultant-only variants.

    PORTAL FLAG: 'consultant' selects consultant-flavoured mock previews.

    Controller data: $guide
--}}
@extends('consultant.layouts.app')

@section('title', 'Help & Guide')
@section('page-title', 'Help & Guide')

@section('content')
<div class="mnz-stack" data-pillar="g">

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Documentation</div>
            <h1>Consultant portal guide</h1>
            <p class="mnz-lead">Agency hub, client workspaces, packs, directory, and day-to-day operations.</p>
        </div>
    </div>

    @include('help.partials.guide-body', ['guide' => $guide, 'portal' => 'consultant'])

</div>
@endsection
