{{--
    Rendered when something genuinely broke. Keep this page as close to
    dependency-free as possible: it is the last thing standing.
--}}
@extends('errors.layout')

@section('code', '500')
@section('heading', 'Something went wrong')
@section('message', 'An unexpected error occurred on our side. The issue has been logged. Please try again in a moment.')

@section('actions')
    <a class="err-btn err-btn--primary" href="{{ url('/') }}">Go to MENetZero</a>
@endsection
