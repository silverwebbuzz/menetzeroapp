@extends('layouts.app')

@section('title', 'Emission Data Collection - Step ' . $step)

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Carbon Emission Data Collection</h1>
            <p class="mt-2 text-lg text-gray-600">Step {{ $step }}: {{ ucfirst(str_replace(['scope1', 'scope2', 'scope3'], ['Scope 1', 'Scope 2', 'Scope 3'], $step)) }}</p>
        </div>

        <!-- Progress Bar -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Progress</span>
                <span class="text-sm font-medium text-gray-700">{{ $progress }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-emerald-600 h-2 rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
            </div>
        </div>

        <!-- Form Card -->
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">
                    @switch($step)
                        @case('company')
                            Company Information
                            @break
                        @case('scope1')
                            Scope 1: Direct Emissions
                            @break
                        @case('scope2')
                            Scope 2: Purchased Energy
                            @break
                        @case('scope3')
                            Scope 3: Other Indirect Emissions
                            @break
                        @case('evidence')
                            Supporting Evidence
                            @break
                        @case('review')
                            Review & Submit
                            @break
                    @endswitch
                </h2>
            </div>

            <div class="p-6">
                @if(session('success'))
                    <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Please correct the following errors:</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Form Content -->
                @if($step === 'review')
                    <form method="POST" action="{{ route('emission-form.submit') }}" id="emission-form">
                @else
                    <form method="POST" action="{{ route('emission-form.store', $step) }}" enctype="multipart/form-data" id="emission-form">
                @endif
                    @csrf
                    
                    @include('emission-form.sections.' . $step, ['emissionSource' => $emissionSource])

                    <!-- Navigation Buttons -->
                    <div class="flex justify-between pt-6 border-t border-gray-200">
                        <div>
                            @if($step !== 'company')
                                @php
                                    $steps = ['company', 'scope1', 'scope2', 'scope3', 'evidence', 'review'];
                                    $currentIndex = array_search($step, $steps);
                                    $previousStep = $currentIndex > 0 ? $steps[$currentIndex - 1] : null;
                                @endphp
                                @if($previousStep)
                                    <a href="{{ route('emission-form.step', ['step' => $previousStep]) }}" 
                                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                        </svg>
                                        Back
                                    </a>
                                @endif
                            @endif
                        </div>
                        
                        <div>
                            @if($step === 'review')
                                <button type="submit" 
                                        class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Submit Data
                                </button>
                            @else
                                <button type="submit" 
                                        class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                                    Next
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Help Section -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-medium text-blue-900 mb-4">Need Help?</h3>
            <div class="text-sm text-blue-800">
                <p class="mb-2">This form collects your organization's carbon emission data for reporting purposes.</p>
                <ul class="list-disc list-inside space-y-1">
                    <li><strong>Scope 1:</strong> Direct emissions from owned or controlled sources</li>
                    <li><strong>Scope 2:</strong> Indirect emissions from purchased energy</li>
                    <li><strong>Scope 3:</strong> All other indirect emissions in your value chain</li>
                </ul>
                <p class="mt-3">For detailed guidance, please refer to the GHG Protocol standards.</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto-save form data on input change
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('emission-form');
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            // Auto-save functionality can be added here
            console.log('Form data changed:', this.name, this.value);
        });
    });
});
</script>
@endpush
@endsection
