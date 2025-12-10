@extends('layouts.app')

@section('title', 'Invitation Expired - MenetZero')

@section('content')
<div class="max-w-2xl mx-auto py-12">
    <div class="bg-white rounded-lg border border-gray-200 p-8 text-center">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
            <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>
        
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Invitation Expired</h1>
        <p class="text-gray-600 mb-6">
            This invitation has expired. Please contact the company administrator for a new invitation.
        </p>
        
        <a href="{{ route('home') }}" class="inline-block px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
            Go to Home
        </a>
    </div>
</div>
@endsection

