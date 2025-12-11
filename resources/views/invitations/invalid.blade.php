@extends('layouts.app')

@section('title', 'Invalid Invitation - MenetZero')

@section('content')
<div class="max-w-2xl mx-auto py-12">
    <div class="bg-white rounded-lg border border-gray-200 p-8 text-center">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
            <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>
        
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Invalid Invitation</h1>
        <p class="text-gray-600 mb-6">
            {{ $message ?? 'This invitation link is invalid or has already been used. Please contact the company administrator for assistance.' }}
        </p>
        
        <a href="{{ route('home') }}" class="inline-block px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
            Go to Home
        </a>
    </div>
</div>
@endsection

