@extends('errors.layout')

@section('code', '403')
@section('heading', 'You do not have access')
@section('message', 'Your account does not have permission to view this page. If you think this is a mistake, ask a workspace owner to review your role.')

@section('actions')
    @include('errors.partials.home-action')
@endsection
