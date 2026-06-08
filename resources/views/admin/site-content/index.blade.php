@extends('admin.layouts.app')

@section('title', 'Site Content | MENetZero')
@section('page-title', 'Site Content')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif

    <p class="text-sm text-gray-500 mb-6">Manage the public website details, currency display, and the policy pages used for payment gateway onboarding.</p>

    <!-- Company / Contact details + currency -->
    <div class="bg-white shadow rounded-lg mb-8">
        <div class="px-5 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Company &amp; Contact Details</h2>
        </div>
        <form method="POST" action="{{ route('admin.site-content.settings') }}" class="p-5">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @php
                    $fields = [
                        'company_legal_name' => 'Company legal name',
                        'brand_name' => 'Brand name',
                        'support_email' => 'Support email',
                        'sales_email' => 'Sales email',
                        'support_phone' => 'Support phone',
                        'address_line' => 'Address line',
                        'city' => 'City',
                        'country' => 'Country',
                    ];
                @endphp
                @foreach($fields as $key => $label)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                        <input type="text" name="{{ $key }}" value="{{ old($key, $settings[$key] ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                        @error($key)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Business hours</label>
                    <input type="text" name="business_hours" value="{{ old('business_hours', $settings['business_hours'] ?? '') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Default display currency</label>
                    <select name="default_currency" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                        <option value="AED" {{ ($settings['default_currency'] ?? 'AED') === 'AED' ? 'selected' : '' }}>AED</option>
                        <option value="INR" {{ ($settings['default_currency'] ?? 'AED') === 'INR' ? 'selected' : '' }}>INR</option>
                    </select>
                </div>
                <div class="flex items-center mt-7">
                    <input type="checkbox" name="currency_auto_detect" id="currency_auto_detect" value="1" {{ ($settings['currency_auto_detect'] ?? '1') === '1' ? 'checked' : '' }}
                           class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                    <label for="currency_auto_detect" class="ml-2 block text-sm text-gray-700">Auto-detect currency by visitor country (India → INR, UAE → AED)</label>
                </div>
            </div>
            <div class="mt-5 flex justify-end">
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Save Details</button>
            </div>
        </form>
    </div>

    <!-- Policy pages -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-5 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Policy Pages</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Page</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">URL</th>
                        <th class="px-4 py-2 text-center font-medium text-gray-500 uppercase tracking-wider">Published</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($pages as $page)
                        <tr>
                            <td class="px-4 py-2 text-gray-900 font-medium">{{ $page->title }}</td>
                            <td class="px-4 py-2 text-gray-500">/{{ $page->slug }}</td>
                            <td class="px-4 py-2 text-center">
                                <span class="px-2 py-1 text-xs rounded-full {{ $page->is_published ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $page->is_published ? 'Yes' : 'No' }}</span>
                            </td>
                            <td class="px-4 py-2">
                                <a href="{{ route('admin.site-content.pages.edit', $page->id) }}" class="text-purple-600 hover:text-purple-900">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
