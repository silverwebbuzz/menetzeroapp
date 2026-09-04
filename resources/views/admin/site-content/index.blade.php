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
                        'support_email' => 'Support email (help@)',
                        'sales_email' => 'Sales email (hello@)',
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

                {{-- Invoice header. These appear on every issued tax invoice.
                     Leaving the TRN blank keeps VAT at 0% regardless of the
                     rate below -- see InvoiceService::taxRate(). --}}
                <div class="md:col-span-2 mt-2 pt-4 border-t border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900">Invoice details</h3>
                    <p class="text-xs text-gray-500 mt-1">
                        Printed on every tax invoice. VAT stays at 0% until a TRN is entered.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Billing legal name</label>
                    <input type="text" name="billing_legal_name" value="{{ old('billing_legal_name', $settings['billing_legal_name'] ?? '') }}"
                           placeholder="The entity that issues invoices"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    @error('billing_legal_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tax Registration Number (TRN)</label>
                    <input type="text" name="billing_trn" value="{{ old('billing_trn', $settings['billing_trn'] ?? '') }}"
                           placeholder="Leave blank if not VAT-registered"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    @error('billing_trn')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Billing address</label>
                    <textarea name="billing_address" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">{{ old('billing_address', $settings['billing_address'] ?? '') }}</textarea>
                    @error('billing_address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">VAT rate (%)</label>
                    <input type="number" name="billing_vat_rate" step="0.01" min="0" max="100"
                           value="{{ old('billing_vat_rate', $settings['billing_vat_rate'] ?? '0') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    <p class="mt-1 text-xs text-gray-500">UAE standard rate is 5. Ignored while the TRN is blank.</p>
                    @error('billing_vat_rate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand name on invoice</label>
                    <input type="text" name="billing_brand_name"
                           value="{{ old('billing_brand_name', $settings['billing_brand_name'] ?? '') }}"
                           placeholder="MENetZero"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    <p class="mt-1 text-xs text-gray-500">Trading name shown above the legal entity. Blank falls back to the legal name.</p>
                    @error('billing_brand_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Company registration no.</label>
                    <input type="text" name="billing_registration_no"
                           value="{{ old('billing_registration_no', $settings['billing_registration_no'] ?? '') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    <p class="mt-1 text-xs text-gray-500">Trade licence / CIN. Printed under the seller address.</p>
                    @error('billing_registration_no')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Billing contact email</label>
                    <input type="email" name="billing_email"
                           value="{{ old('billing_email', $settings['billing_email'] ?? '') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    <p class="mt-1 text-xs text-gray-500">Where billing queries go. Blank falls back to the support email.</p>
                    @error('billing_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Billing contact phone</label>
                    <input type="text" name="billing_phone"
                           value="{{ old('billing_phone', $settings['billing_phone'] ?? '') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    @error('billing_phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Default HSN / SAC code</label>
                    <input type="text" name="billing_hsn_sac"
                           value="{{ old('billing_hsn_sac', $settings['billing_hsn_sac'] ?? '') }}"
                           placeholder="998314"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    <p class="mt-1 text-xs text-gray-500">Service classification code. Leave blank to hide the column.</p>
                    @error('billing_hsn_sac')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Invoice terms &amp; notes</label>
                    <textarea name="billing_terms" rows="3"
                              placeholder="Printed at the foot of every invoice."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">{{ old('billing_terms', $settings['billing_terms'] ?? '') }}</textarea>
                    @error('billing_terms')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
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
