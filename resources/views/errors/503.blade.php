@extends('errors.layout')

@section('code', '503')
@section('heading', 'Down for maintenance')
@section('message', 'MENetZero is briefly unavailable while we complete an update. Your data is safe. Please check back shortly.')

@section('actions')
    <a class="err-btn err-btn--primary" href="{{ url()->current() }}">Try again</a>
@endsection
