@extends('admin.layouts.app')

@section('title', 'Edit ' . $page->title . ' | MENetZero')
@section('page-title', 'Edit: ' . $page->title)

@section('content')
    <div class="bg-white shadow rounded-lg p-6 max-w-4xl">
        <form method="POST" action="{{ route('admin.site-content.pages.update', $page->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Page title *</label>
                <input type="text" name="title" value="{{ old('title', $page->title) }}" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Body (HTML)</label>
                <textarea name="body" rows="22"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 font-mono text-sm">{{ old('body', $page->body) }}</textarea>
                <p class="mt-1 text-xs text-gray-500">
                    You can use HTML (<code>&lt;h2&gt;</code>, <code>&lt;p&gt;</code>, <code>&lt;ul&gt;&lt;li&gt;</code>) and these auto-filled tokens:
                    <code>{{ '{{company_legal_name}}' }}</code>, <code>{{ '{{brand_name}}' }}</code>, <code>{{ '{{support_email}}' }}</code>, <code>{{ '{{support_phone}}' }}</code>.
                </p>
            </div>

            <div class="flex items-center mb-6">
                <input type="checkbox" name="is_published" id="is_published" value="1" {{ $page->is_published ? 'checked' : '' }}
                       class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                <label for="is_published" class="ml-2 block text-sm text-gray-700">Published (visible on the website)</label>
            </div>

            <div class="flex items-center justify-between">
                <a href="/{{ $page->slug }}" target="_blank" class="text-sm text-purple-600 hover:underline">Preview /{{ $page->slug }} ↗</a>
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.site-content.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Update Page</button>
                </div>
            </div>
        </form>
    </div>
@endsection
