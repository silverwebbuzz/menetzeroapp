{{--
    419 = expired CSRF token. By definition the session is gone, so this page
    must NOT link to an authenticated route — that would bounce straight back
    to login and lose the explanation. Sign-in is the only correct action.
--}}
@extends('errors.layout')

@section('code', '419')
@section('heading', 'Your session expired')
@section('message', 'You were away long enough that we signed you out to keep your account secure. Sign in again and your work will be where you left it.')

@section('actions')
    <a class="err-btn err-btn--primary" href="{{ route('login') }}">Sign in again</a>
    <a class="err-btn" href="{{ url()->previous() }}">Go back</a>
@endsection
