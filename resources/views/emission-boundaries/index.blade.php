@extends('layouts.app')

@section('title', 'Emission Boundaries - MenetZero')
@section('page-title', 'Emission Boundaries')

@section('content')
<style>
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
    }
    .tab-button {
        padding: 12px 24px;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.2s;
    }
    .tab-button.active {
        background: #f97316;
        color: white;
        border-color: #f97316;
    }
    .tab-button:hover:not(.active) {
        background: #f3f4f6;
    }
    .tooltip-icon {
        display: inline-block;
        width: 16px;
        height: 16px;
        background: #3b82f6;
        color: white;
        border-radius: 50%;
        text-align: center;
        line-height: 16px;
        font-size: 10px;
        cursor: help;
        margin-left: 8px;
    }
    .category-group {
        margin-bottom: 24px;
    }
    .category-title {
        font-weight: 600;
        color: #374151;
        margin-bottom: 12px;
        padding: 8px 0;
        border-bottom: 1px solid #e5e7eb;
    }
    .form-check {
        margin-bottom: 12px;
    }
    .checkbox-container {
        display: flex;
        align-items: center;
    }
    .form-check-input {
        margin-right: 8px;
    }
    .form-check-label {
        font-weight: 500;
        color: #374151;
    }
</style>

<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Emission Boundaries</h2>
                <p class="text-gray-600 mt-1">Configure emission sources for {{ $location->name }}</p>
            </div>
            <a href="{{ route('locations.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                Back to Locations
            </a>
        </div>

        <!-- Tab Navigation -->
        <div class="flex space-x-1 mb-6">
            <button class="tab-button active" onclick="showTab('scope1')">
                Scope 1
            </button>
            <button class="tab-button" onclick="showTab('scope2')">
                Scope 2
            </button>
            <button class="tab-button" onclick="showTab('scope3')">
                Scope 3
            </button>
        </div>

        <form method="POST" action="{{ route('emission-boundaries.store', $location) }}" id="emission-boundaries-form">
            @csrf
            
            <!-- Scope 1 Tab -->
            <div id="scope1" class="tab-content active">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Stationary energy and fuels emissions</h3>
                    <p class="text-gray-600 mb-4">All stationary energy and fuels used in buildings, machinery or vehicles in the organisation's control (e.g. natural gas, fuels used in generators or vehicles) need to be included in your emission boundary.</p>
                    <h4 class="font-semibold text-gray-900 mb-3">Scope 1</h4>
                    <p class="text-sm text-gray-600 mb-4">Select all applicable emission sources.</p>
                </div>

                <div class="space-y-3">
                    @foreach($scope1Sources as $source)
                    <div class="form-check">
                        <div class="checkbox-container">
                            <input class="form-check-input" type="checkbox" name="emission_sources[]" 
                                   value="{{ $source->id }}" id="scope1_{{ $source->id }}"
                                   {{ in_array($source->id, $selectedBoundaries) ? 'checked' : '' }}>
                            <label class="form-check-label" for="scope1_{{ $source->id }}">
                                {{ $source->name }}
                            </label>
                        </div>
                        <span class="tooltip-icon" data-title="{{ $source->description }}">i</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Scope 2 Tab -->
            <div id="scope2" class="tab-content">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Stationary energy and fuels emissions</h3>
                    <p class="text-gray-600 mb-4">All stationary energy and fuels used in buildings, machinery or vehicles in the organisation's control (e.g. natural gas, fuels used in generators or vehicles) need to be included in your emission boundary.</p>
                    <h4 class="font-semibold text-gray-900 mb-3">Scope 2</h4>
                    <p class="text-sm text-gray-600 mb-4">Select all applicable emission sources.</p>
                </div>

                <div class="space-y-3">
                    @foreach($scope2Sources as $source)
                    <div class="form-check">
                        <div class="checkbox-container">
                            <input class="form-check-input" type="checkbox" name="emission_sources[]" 
                                   value="{{ $source->id }}" id="scope2_{{ $source->id }}"
                                   {{ in_array($source->id, $selectedBoundaries) ? 'checked' : '' }}>
                            <label class="form-check-label" for="scope2_{{ $source->id }}">
                                {{ $source->name }}
                            </label>
                        </div>
                        <span class="tooltip-icon" data-title="{{ $source->description }}">i</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Scope 3 Tab -->
            <div id="scope3" class="tab-content">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Indirect emissions as a result of your operations</h3>
                    <p class="text-gray-600 mb-4">All other emissions identified as a direct result of the organisation's operating must be assessed for relevance. This includes emissions outside the operational control (as defined) of the organisation.</p>
                    <h4 class="font-semibold text-gray-900 mb-3">Scope 3</h4>
                    <p class="text-sm text-gray-600 mb-4">Select all applicable emission sources.</p>
                </div>

                <!-- Upstream -->
                <h5 class="font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">Upstream</h5>
                
                @php
                    $upstreamCategories = $scope3Sources->where('type', 'upstream')->groupBy('category');
                @endphp
                
                @foreach($upstreamCategories as $category => $sources)
                <div class="category-group">
                    <h5 class="category-title">{{ $category }}</h5>
                    <div class="space-y-3">
                        @foreach($sources as $source)
                        <div class="form-check">
                            <div class="checkbox-container">
                                <input class="form-check-input" type="checkbox" name="emission_sources[]" 
                                       value="{{ $source->id }}" id="scope3_{{ $source->id }}"
                                       {{ in_array($source->id, $selectedBoundaries) ? 'checked' : '' }}>
                                <label class="form-check-label" for="scope3_{{ $source->id }}">
                                    {{ $source->name }}
                                </label>
                            </div>
                            <span class="tooltip-icon" data-title="{{ $source->description }}">i</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach

                <!-- Downstream -->
                <h5 class="font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2 mt-8">Downstream</h5>
                
                @php
                    $downstreamCategories = $scope3Sources->where('type', 'downstream')->groupBy('category');
                @endphp
                
                @foreach($downstreamCategories as $category => $sources)
                <div class="category-group">
                    <h5 class="category-title">{{ $category }}</h5>
                    <div class="space-y-3">
                        @foreach($sources as $source)
                        <div class="form-check">
                            <div class="checkbox-container">
                                <input class="form-check-input" type="checkbox" name="emission_sources[]" 
                                       value="{{ $source->id }}" id="scope3_{{ $source->id }}"
                                       {{ in_array($source->id, $selectedBoundaries) ? 'checked' : '' }}>
                                <label class="form-check-label" for="scope3_{{ $source->id }}">
                                    {{ $source->name }}
                                </label>
                            </div>
                            <span class="tooltip-icon" data-title="{{ $source->description }}">i</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-4 mt-8 pt-6 border-t">
                <a href="{{ route('locations.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                    Save Emission Boundaries
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    // Show selected tab content
    document.getElementById(tabName).classList.add('active');
    
    // Add active class to clicked button
    event.target.classList.add('active');
}

// Tooltip functionality
document.addEventListener('DOMContentLoaded', function() {
    const tooltips = document.querySelectorAll('.tooltip-icon');
    
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', function() {
            const title = this.getAttribute('data-title');
            if (title) {
                this.setAttribute('title', title);
            }
        });
    });
});
</script>
@endsection
