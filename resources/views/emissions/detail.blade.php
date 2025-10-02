@extends('layouts.app')

@section('title', 'Emission Report Details')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ $emission->company_name }}</h1>
                    <p class="mt-2 text-lg text-gray-600">Emission Report - {{ $emission->reporting_year }}</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('emissions.management') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Reports
                    </a>
                    @if($emission->status === 'draft')
                        <a href="{{ route('emissions.edit', $emission) }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Edit Report
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Status and Info -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Status Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Report Status</h3>
                <div class="flex items-center">
                    @switch($emission->status)
                        @case('draft')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                                Draft
                            </span>
                            @break
                        @case('submitted')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                Submitted
                            </span>
                            @break
                        @case('reviewed')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                Reviewed
                            </span>
                            @break
                    @endswitch
                </div>
                <p class="mt-2 text-sm text-gray-600">
                    Created: {{ $emission->created_at->format('M d, Y') }}<br>
                    Updated: {{ $emission->updated_at->format('M d, Y') }}
                </p>
            </div>

            <!-- Company Info -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Company Information</h3>
                <div class="space-y-2 text-sm">
                    <div><span class="font-medium text-gray-700">Sector:</span> {{ $emission->sector }}</div>
                    <div><span class="font-medium text-gray-700">Location:</span> {{ $emission->location }}</div>
                    <div><span class="font-medium text-gray-700">Reporting Year:</span> {{ $emission->reporting_year }}</div>
                </div>
            </div>

            <!-- Total Emissions -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Total Emissions</h3>
                <div class="text-3xl font-bold text-emerald-600">
                    {{ number_format($emission->grand_total ?? 0, 2) }}
                </div>
                <p class="text-sm text-gray-600">kg CO₂e</p>
            </div>
        </div>

        <!-- Emissions Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Scope 1 -->
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-amber-800 mb-4">Scope 1: Direct Emissions</h3>
                <div class="text-2xl font-bold text-amber-900 mb-4">
                    {{ number_format($emission->scope1_total ?? 0, 2) }} kg CO₂e
                </div>
                <div class="space-y-2 text-sm">
                    @if($emission->diesel_litres)
                        <div class="flex justify-between">
                            <span class="text-amber-700">Diesel Fuel:</span>
                            <span class="text-amber-900">{{ number_format($emission->diesel_litres, 2) }} L</span>
                        </div>
                    @endif
                    @if($emission->petrol_litres)
                        <div class="flex justify-between">
                            <span class="text-amber-700">Petrol:</span>
                            <span class="text-amber-900">{{ number_format($emission->petrol_litres, 2) }} L</span>
                        </div>
                    @endif
                    @if($emission->natural_gas_m3)
                        <div class="flex justify-between">
                            <span class="text-amber-700">Natural Gas:</span>
                            <span class="text-amber-900">{{ number_format($emission->natural_gas_m3, 2) }} m³</span>
                        </div>
                    @endif
                    @if($emission->refrigerant_kg)
                        <div class="flex justify-between">
                            <span class="text-amber-700">Refrigerant:</span>
                            <span class="text-amber-900">{{ number_format($emission->refrigerant_kg, 2) }} kg</span>
                        </div>
                    @endif
                    @if($emission->other_emissions)
                        <div class="flex justify-between">
                            <span class="text-amber-700">Other:</span>
                            <span class="text-amber-900">{{ number_format($emission->other_emissions, 2) }} kg CO₂e</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Scope 2 -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-green-800 mb-4">Scope 2: Purchased Energy</h3>
                <div class="text-2xl font-bold text-green-900 mb-4">
                    {{ number_format($emission->scope2_total ?? 0, 2) }} kg CO₂e
                </div>
                <div class="space-y-2 text-sm">
                    @if($emission->electricity_kwh)
                        <div class="flex justify-between">
                            <span class="text-green-700">Electricity:</span>
                            <span class="text-green-900">{{ number_format($emission->electricity_kwh, 2) }} kWh</span>
                        </div>
                    @endif
                    @if($emission->district_cooling_kwh)
                        <div class="flex justify-between">
                            <span class="text-green-700">District Cooling:</span>
                            <span class="text-green-900">{{ number_format($emission->district_cooling_kwh, 2) }} kWh</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Scope 3 -->
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-purple-800 mb-4">Scope 3: Other Indirect</h3>
                <div class="text-2xl font-bold text-purple-900 mb-4">
                    {{ number_format($emission->scope3_total ?? 0, 2) }} kg CO₂e
                </div>
                <div class="space-y-2 text-sm">
                    @if($emission->business_travel_flights_km)
                        <div class="flex justify-between">
                            <span class="text-purple-700">Business Travel:</span>
                            <span class="text-purple-900">{{ number_format($emission->business_travel_flights_km, 2) }} km</span>
                        </div>
                    @endif
                    @if($emission->car_hire_km)
                        <div class="flex justify-between">
                            <span class="text-purple-700">Car Hire:</span>
                            <span class="text-purple-900">{{ number_format($emission->car_hire_km, 2) }} km</span>
                        </div>
                    @endif
                    @if($emission->waste_tonnes)
                        <div class="flex justify-between">
                            <span class="text-purple-700">Waste:</span>
                            <span class="text-purple-900">{{ number_format($emission->waste_tonnes, 2) }} tonnes</span>
                        </div>
                    @endif
                    @if($emission->water_m3)
                        <div class="flex justify-between">
                            <span class="text-purple-700">Water:</span>
                            <span class="text-purple-900">{{ number_format($emission->water_m3, 2) }} m³</span>
                        </div>
                    @endif
                    @if($emission->purchased_goods)
                        <div class="flex justify-between">
                            <span class="text-purple-700">Purchased Goods:</span>
                            <span class="text-purple-900">{{ number_format($emission->purchased_goods, 2) }} kg CO₂e</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Supporting Documents -->
        @if($emission->uploaded_files && count($emission->uploaded_files) > 0)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Supporting Documents</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($emission->uploaded_files as $file)
                        <div class="flex items-center p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <svg class="w-8 h-8 text-gray-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $file['original_name'] }}</p>
                                <p class="text-xs text-gray-500">{{ number_format($file['size'] / 1024, 2) }} KB</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
