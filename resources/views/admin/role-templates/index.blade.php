@extends('admin.layouts.app')

@section('title', 'Role Templates | MENetZero')
@section('page-title', 'Role Templates')

@section('content')
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-medium text-gray-900">Role Templates</h2>
            <a href="{{ route('admin.role-templates.create') }}"
               class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-purple-600 text-white hover:bg-purple-700">
                + New Role Template
            </a>
        </div>
        <div class="p-4 text-sm text-gray-500">
            This page will list all role templates used to seed company roles. (Initial stub view.)
        </div>
    </div>
@endsection


