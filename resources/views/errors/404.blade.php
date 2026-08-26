@extends('errors.layout')

@section('code', '404')
@section('heading', 'Page not found')
@section('message', 'The page you are looking for does not exist, or it may have been moved.')

@section('actions')
    @include('errors.partials.home-action')
@endsection
