@extends('layouts.app')

@section('title', 'Emissions')
@section('page-title', 'Emissions')

@section('content')
<div class="grid grid-cols-1 gap-6">
    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold">Recent Emissions</h2>
            <a href="{{ route('emissions.create') }}" class="btn-primary px-4 py-2 rounded text-white">Add Emission</a>
        </div>
        <p class="text-gray-600">No data yet. Add your first emission entry.</p>
    </div>
</div>
@endsection


