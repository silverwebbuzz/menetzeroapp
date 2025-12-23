@extends('admin.layouts.app')

@section('title', 'Edit Subscription Plan | MENetZero')
@section('page-title', 'Edit Subscription Plan')

@section('content')
    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" action="{{ route('admin.subscription-plans.update', $plan->id) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="plan_code" class="block text-sm font-medium text-gray-700 mb-1">Plan Code *</label>
                    <input type="text" name="plan_code" id="plan_code" value="{{ old('plan_code', $plan->plan_code) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    @error('plan_code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="plan_name" class="block text-sm font-medium text-gray-700 mb-1">Plan Name *</label>
                    <input type="text" name="plan_name" id="plan_name" value="{{ old('plan_name', $plan->plan_name) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    @error('plan_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="plan_category" class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                    <select name="plan_category" id="plan_category" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                        <option value="client" {{ old('plan_category', $plan->plan_category) == 'client' ? 'selected' : '' }}>Client</option>
                    </select>
                    @error('plan_category')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="price_annual" class="block text-sm font-medium text-gray-700 mb-1">Annual Price *</label>
                    <input type="number" name="price_annual" id="price_annual" value="{{ old('price_annual', $plan->price_annual) }}" step="0.01" min="0" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    @error('price_annual')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">Currency *</label>
                    <input type="text" name="currency" id="currency" value="{{ old('currency', $plan->currency) }}" maxlength="3" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    @error('currency')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="billing_cycle" class="block text-sm font-medium text-gray-700 mb-1">Billing Cycle *</label>
                    <select name="billing_cycle" id="billing_cycle" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                        <option value="annual" {{ old('billing_cycle', $plan->billing_cycle) == 'annual' ? 'selected' : '' }}>Annual</option>
                        <option value="monthly" {{ old('billing_cycle', $plan->billing_cycle) == 'monthly' ? 'selected' : '' }}>Monthly</option>
                    </select>
                    @error('billing_cycle')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $plan->sort_order) }}" min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}
                           class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">Active</label>
                </div>
            </div>

            <div class="mt-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" id="description" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">{{ old('description', $plan->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Features (JSON Array)</label>
                <textarea name="features" id="features" rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 font-mono text-sm">{{ old('features') ? json_encode(old('features'), JSON_PRETTY_PRINT) : json_encode($plan->features ?? [], JSON_PRETTY_PRINT) }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Enter as JSON array, e.g., ["basic_measurements", "reports"]</p>
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Limits (JSON Object)</label>
                <textarea name="limits" id="limits" rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 font-mono text-sm">{{ old('limits') ? json_encode(old('limits'), JSON_PRETTY_PRINT) : json_encode($plan->limits ?? [], JSON_PRETTY_PRINT) }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Enter as JSON object, e.g., {"locations": 5, "users": 3, "documents": 200}. Use -1 for unlimited.</p>
            </div>

            <div class="mt-6 flex items-center justify-end space-x-4">
                <a href="{{ route('admin.subscription-plans') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    Update Plan
                </button>
            </div>
        </form>
    </div>
@endsection
