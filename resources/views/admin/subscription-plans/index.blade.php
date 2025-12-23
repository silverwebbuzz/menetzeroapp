@extends('admin.layouts.app')

@section('title', 'Subscription Plans | MENetZero')
@section('page-title', 'Subscription Plans')

@section('content')
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-medium text-gray-900">Subscription Plans</h2>
            <a href="{{ route('admin.subscription-plans.create') }}"
               class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-purple-600 text-white hover:bg-purple-700">
                + New Plan
            </a>
        </div>
        <div class="p-4 text-sm text-gray-500">
            This page will show all subscription plans. (Initial stub view.)
        </div>
    </div>
@endsection


