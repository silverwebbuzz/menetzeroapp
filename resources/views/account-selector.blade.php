@extends('layouts.app')

@section('title', 'Select Workspace')

@section('page-title', 'Select a Workspace')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Select a Workspace</h2>
            <p class="text-gray-600">Choose which company workspace you'd like to access</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($accessibleCompanies as $company)
            <form action="{{ route('account.switch') }}" method="POST" class="block">
                @csrf
                <input type="hidden" name="company_id" value="{{ $company['id'] }}">
                <button type="submit" class="w-full text-left p-6 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all duration-200 group">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 mb-1 group-hover:text-blue-700">
                                {{ $company['name'] }}
                            </h3>
                            <p class="text-sm text-gray-600 mb-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    {{ $company['type'] === 'partner' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ ucfirst($company['type']) }}
                                </span>
                            </p>
                            <p class="text-sm text-gray-500">
                                @if($company['is_owner'])
                                    <span class="font-medium text-green-600">You're the Owner</span>
                                @else
                                    You're a <span class="font-medium">{{ $company['role_name'] }}</span>
                                @endif
                            </p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </button>
            </form>
            @endforeach
        </div>

        @if($accessibleCompanies->count() === 0)
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No workspaces available</h3>
            <p class="mt-1 text-sm text-gray-500">You don't have access to any companies yet.</p>
            <div class="mt-6">
                <a href="{{ route('company.setup') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    Create Your Company
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

