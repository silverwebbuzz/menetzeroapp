@extends('admin.layouts.app')

@section('title', 'Intro requests | MENetZero')
@section('page-title', 'Consultant intro requests')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif

    <div class="mb-4">
        <a href="{{ route('admin.consultants.index') }}" class="text-sm text-brand hover:underline">&larr; Consultants</a>
    </div>

    <div class="bg-white shadow rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Date</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Client company</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Consultant</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Pack</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Status</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Update</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($requests as $req)
                    <tr>
                        <td class="px-4 py-2 text-gray-600">{{ $req->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-2">{{ $req->company?->name ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $req->consultant?->company_name ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $req->packLabel() }}</td>
                        <td class="px-4 py-2">{{ ucfirst($req->status) }}</td>
                        <td class="px-4 py-2">
                            <form action="{{ route('admin.consultants.intro-requests.update', $req) }}" method="POST" class="flex gap-2 items-center">
                                @csrf @method('PUT')
                                <select name="status" class="border border-gray-300 rounded text-xs px-2 py-1">
                                    @foreach(['new','contacted','converted','closed'] as $s)
                                        <option value="{{ $s }}" @selected($req->status === $s)>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="text-xs text-brand hover:underline">Save</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No intro requests yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $requests->links() }}</div>
@endsection
