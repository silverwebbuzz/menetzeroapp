@extends('admin.layouts.app')

@section('title', 'Company Details | MENetZero')
@section('page-title', 'Company Details')

@section('content')
    <div class="space-y-6">
        <div class="bg-white shadow rounded-lg p-4">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Company Information</h2>
            @isset($company)
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="font-medium text-gray-500">Name</dt>
                        <dd class="text-gray-900">{{ $company->name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Email</dt>
                        <dd class="text-gray-900">{{ $company->email }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Type</dt>
                        <dd class="text-gray-900">{{ $company->company_type ?? 'client' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Created At</dt>
                        <dd class="text-gray-900">{{ optional($company->created_at)->format('Y-m-d') }}</dd>
                    </div>
                </dl>
            @else
                <p class="text-gray-500 text-sm">Company data not available.</p>
            @endisset
        </div>
    </div>
@endsection


