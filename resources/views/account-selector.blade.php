@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Select a Company
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            You have access to multiple companies. Please select one to continue.
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            <div class="space-y-4">
                @foreach($accessibleCompanies as $company)
                <form action="{{ route('account.switch') }}" method="POST" class="block">
                    @csrf
                    <input type="hidden" name="company_id" value="{{ $company['id'] }}">
                    <button type="submit" class="w-full text-left p-4 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all {{ auth()->user()->getActiveCompany() && auth()->user()->getActiveCompany()->id == $company['id'] ? 'border-blue-500 bg-blue-50' : '' }}">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 min-w-0">
                                <p class="text-lg font-semibold text-gray-900 truncate">{{ $company['name'] }}</p>
                                <div class="mt-1 flex items-center space-x-2">
                                    @if($company['is_owner'])
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                            </svg>
                                            Owner
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                            </svg>
                                            {{ $company['role_name'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @if(auth()->user()->getActiveCompany() && auth()->user()->getActiveCompany()->id == $company['id'])
                            <svg class="w-5 h-5 text-blue-600 ml-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            @else
                            <svg class="w-5 h-5 text-gray-400 ml-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                            @endif
                        </div>
                    </button>
                </form>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

