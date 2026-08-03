@extends('layouts.app')

@section('title', 'Request a package - MenetZero')
@section('page-title', 'Request a package')

@section('content')
@php
    $selectedPackage = old('package_code', 'client_scope_basic');
@endphp
<div class="w-full max-w-6xl">
    <div class="mb-6">
        <a href="{{ route('subscriptions.billing') }}" class="text-sm text-brand hover:underline">&larr; Plan &amp; billing</a>
        <h1 class="text-3xl font-bold text-gray-900 mt-2">Request a package</h1>
        <p class="mt-2 text-gray-600">
            Compare capabilities across packages, select one, then add extras if needed.
            MENetZero confirms the quote offline and activates when payment clears — no prices shown here.
        </p>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc pl-4">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('subscriptions.request-package.store') }}" method="POST" class="space-y-6">
        @csrf

        @include('partials.package-request-matrix', [
            'matrix' => $matrix,
            'packages' => $packages,
            'selectedPackage' => $selectedPackage,
        ])

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-1">Optional extras</h2>
            <p class="text-xs text-gray-500 mb-3">
                Tick only what you may need beyond the package defaults. If something is already included in your selection, MENetZero will ignore the duplicate when quoting.
            </p>
            <div class="grid sm:grid-cols-2 gap-2">
                @foreach($extraOptions as $key => $label)
                    <label class="flex items-start gap-2 text-sm text-gray-700">
                        <input
                            type="checkbox"
                            name="extras[]"
                            value="{{ $key }}"
                            class="mt-1 rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                            @checked(in_array($key, old('extras', []), true))
                        >
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <label for="message" class="block text-sm font-semibold text-gray-900 mb-2">Notes for MENetZero</label>
            <textarea
                id="message"
                name="message"
                rows="4"
                class="w-full rounded-lg border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                placeholder="Sites, reporting year, urgency, or anything else we should know…"
            >{{ old('message') }}</textarea>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="px-5 py-2.5 bg-orange-600 text-white text-sm font-semibold rounded-lg hover:bg-orange-700">
                Submit request
            </button>
            <a href="{{ route('subscriptions.billing') }}" class="px-5 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
                Cancel
            </a>
        </div>
    </form>

    @if($recent->isNotEmpty())
        <div class="mt-10">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">Recent requests</h2>
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden text-sm">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50 text-left text-xs text-gray-500">
                        <tr>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Package</th>
                            <th class="px-4 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($recent as $req)
                            <tr>
                                <td class="px-4 py-2 text-gray-600">{{ $req->created_at->format('d M Y') }}</td>
                                <td class="px-4 py-2">{{ $req->packageLabel() }}</td>
                                <td class="px-4 py-2">{{ ucfirst($req->status) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
