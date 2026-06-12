@extends('layouts.app')

@section('title', 'Help & Guide — MENetZero')
@section('page-title', 'Help & Guide')

@section('content')
<div class="w-full">
    <div class="page-header mb-6">
        <div>
            <h1>Company portal guide</h1>
            <p>Learn how to set up locations, enter emissions, run reports, and complete disclosures.</p>
        </div>
    </div>

    @include('help.partials.guide-body', ['guide' => $guide])
</div>
@endsection
