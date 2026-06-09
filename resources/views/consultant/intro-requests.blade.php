@extends('consultant.layouts.app')

@section('title', 'Client leads')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-6">Introduction requests</h1>

@if($consultant->status !== 'approved')
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-900 mb-6">
        Leads are visible once your profile is approved and listed.
    </div>
@endif

<div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
            <tr>
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Company</th>
                <th class="px-4 py-3">Pack</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Message</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($requests as $req)
                <tr>
                    <td class="px-4 py-3 text-gray-600">{{ $req->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3 font-medium">{{ $req->company?->name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $req->packLabel() }}</td>
                    <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full bg-gray-100 text-xs">{{ ucfirst($req->status) }}</span></td>
                    <td class="px-4 py-3 text-gray-600 max-w-xs truncate">{{ $req->message ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No leads yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $requests->links() }}</div>
@endsection
