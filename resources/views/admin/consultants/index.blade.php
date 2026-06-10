@extends('admin.layouts.app')

@section('title', 'Consultants | MENetZero')
@section('page-title', 'Consultants')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.consultants.index') }}" class="px-3 py-1 rounded-full text-sm {{ !$status ? 'bg-brand text-white' : 'bg-white border border-gray-300 text-gray-600' }}">All</a>
        @foreach($statuses as $key => $label)
            <a href="{{ route('admin.consultants.index', ['status' => $key]) }}" class="px-3 py-1 rounded-full text-sm {{ $status === $key ? 'bg-brand text-white' : 'bg-white border border-gray-300 text-gray-600' }}">{{ $label }}</a>
        @endforeach
        <a href="{{ route('admin.consultants.intro-requests') }}" class="ml-auto text-sm text-brand hover:underline">Intro requests →</a>
        <a href="{{ route('admin.consultants.orders') }}" class="text-sm text-brand hover:underline">Marketplace orders →</a>
    </div>

    <div class="bg-white shadow rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Practice</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Contact</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Status</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Docs</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Leads</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Submitted</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($consultants as $c)
                    <tr>
                        <td class="px-4 py-2">
                            <div class="font-medium">{{ $c->company_name }}</div>
                            @if($c->is_featured)<span class="text-xs text-amber-600">Featured</span>@endif
                        </td>
                        <td class="px-4 py-2">
                            <div>{{ $c->name }}</div>
                            <div class="text-xs text-gray-500">{{ $c->email }}</div>
                        </td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100">{{ $c->statusLabel() }}</span>
                        </td>
                        <td class="px-4 py-2">{{ $c->documents_count }}</td>
                        <td class="px-4 py-2">{{ $c->intro_requests_count }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500">{{ $c->submitted_at?->format('d M Y') ?? '—' }}</td>
                        <td class="px-4 py-2">
                            <a href="{{ route('admin.consultants.show', $c) }}" class="text-brand hover:underline">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">No consultant applications yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $consultants->links() }}</div>
@endsection
