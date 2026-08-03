@extends('admin.layouts.app')

@section('title', 'Entity requests | MENetZero')
@section('page-title', 'Consultant entity requests')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif

    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        <a href="{{ route('admin.entity-requests.index') }}" class="px-3 py-1 rounded {{ !$status ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700' }}">All</a>
        @foreach(['new','contacted','quoted','activated','closed'] as $s)
            <a href="{{ route('admin.entity-requests.index', ['status' => $s]) }}" class="px-3 py-1 rounded {{ $status === $s ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700' }}">{{ ucfirst($s) }}</a>
        @endforeach
    </div>

    <div class="bg-white shadow rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Date</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Consultant org</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Requester</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Entities</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Flags</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Notes</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Update</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($requests as $req)
                    <tr>
                        <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $req->created_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-2">
                            {{ $req->consultantCompany?->name ?? '—' }}
                            <div class="text-xs text-gray-400">#{{ $req->consultant_company_id }}</div>
                        </td>
                        <td class="px-4 py-2">
                            {{ $req->user?->name ?? '—' }}
                            <div class="text-xs text-gray-400">{{ $req->user?->email }}</div>
                        </td>
                        <td class="px-4 py-2 font-semibold">{{ $req->entity_count }}</td>
                        <td class="px-4 py-2 text-xs text-gray-600">
                            @if($req->needs_sites_over_5)
                                <div>&gt;5 sites</div>
                            @endif
                            @if($req->wants_enterprise)
                                <div>Enterprise</div>
                            @endif
                            @if(!$req->needs_sites_over_5 && !$req->wants_enterprise)
                                —
                            @endif
                        </td>
                        <td class="px-4 py-2 text-xs text-gray-600 max-w-xs">
                            {{ \Illuminate\Support\Str::limit($req->message, 120) ?: '—' }}
                        </td>
                        <td class="px-4 py-2">
                            <form action="{{ route('admin.entity-requests.update', $req) }}" method="POST" class="space-y-2 min-w-[12rem]">
                                @csrf @method('PUT')
                                <select name="status" class="border border-gray-300 rounded text-xs px-2 py-1 w-full">
                                    @foreach(['new','contacted','quoted','activated','closed'] as $s)
                                        <option value="{{ $s }}" @selected($req->status === $s)>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                                <textarea name="admin_notes" rows="2" class="border border-gray-300 rounded text-xs px-2 py-1 w-full" placeholder="Admin notes">{{ $req->admin_notes }}</textarea>
                                <button type="submit" class="text-xs px-2 py-1 bg-gray-900 text-white rounded">Save</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">No entity requests yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
@endsection
