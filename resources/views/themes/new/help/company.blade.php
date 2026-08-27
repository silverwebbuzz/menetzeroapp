{{--
    MENetZero 2.0 - Company portal guide (Phase 6 body migration).

    Thin shell around the shared guide body. All content comes from
    config('portal-guide-company') via PortalGuideController::company().

    PORTAL FLAG: 'company' is passed down and reaches guide-highlight, which
    forwards it to guide-mock as the mock's THEME. It selects which mock
    variants render in consultant styling. Not cosmetic - drop it and the
    company guide starts showing consultant-flavoured previews.

    Controller data: $guide
--}}
@extends('layouts.app')

@section('title', 'Help & Guide — MENetZero')
@section('page-title', 'Help & Guide')

@section('content')
<div class="mnz-stack" data-pillar="g">

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Governance · Documentation</div>
            <h1>Company portal guide</h1>
            <p class="mnz-lead">Learn how to set up locations, enter emissions, run reports, and complete disclosures.</p>
        </div>
    </div>

    @include('help.partials.guide-body', ['guide' => $guide, 'portal' => 'company'])

</div>
@endsection
