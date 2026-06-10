@extends('consultant.layouts.app')

@section('title', 'Client leads')
@section('page-title', 'Client leads')

@section('content')
@if($consultant->status !== 'approved')
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-900 mb-6">
        Leads are routed once your directory profile is approved and listed.
    </div>
@endif

<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title">Introduction requests</h3>
            <p class="card-subtitle">From MenetZero clients and the public directory — contact details included for you to follow up</p>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-wrap">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Source</th>
                        <th>Enquirer</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr>
                            <td class="text-sm text-gray-600">{{ $req['date']->format('d M Y') }}</td>
                            <td>
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $req['type'] === 'public' ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $req['pack'] }}
                                </span>
                            </td>
                            <td class="font-medium text-sm">{{ $req['name'] }}</td>
                            <td class="text-sm text-gray-600 max-w-[10rem] truncate" title="{{ $req['contact'] }}">{{ $req['contact'] ?? '—' }}</td>
                            <td><span class="badge badge-neutral text-xs">{{ ucfirst($req['status']) }}</span></td>
                            <td class="text-sm text-gray-600 max-w-xs truncate">{{ $req['message'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-gray-500">
                                No leads yet. Complete your directory profile to get listed on
                                <a href="{{ route('consultant-list.index') }}" class="text-brand hover:underline" target="_blank">/consultant-list</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
