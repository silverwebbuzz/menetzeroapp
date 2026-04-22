@extends('layouts.app')

@section('title', 'Password Reset Link Sent - MenetZero')
@section('page-title', 'Password Reset Link Sent')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-lg border border-gray-200 p-8">
        <div class="text-center mb-6">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="mt-4 text-2xl font-bold text-gray-900">Check your email</h1>
            <p class="mt-2 text-gray-600">If an account exists for <strong>{{ $email }}</strong>, a password reset link has been sent.</p>
            <p class="mt-2 text-sm text-gray-500">The link will expire in 60 minutes.</p>
        </div>

        <div class="flex items-center justify-center pt-6 border-t border-gray-200">
            <a href="{{ route('login') }}"
               class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                Back to Login
            </a>
        </div>
    </div>
</div>
@endsection
