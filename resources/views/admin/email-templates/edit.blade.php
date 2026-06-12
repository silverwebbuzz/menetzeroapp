@extends('admin.layouts.app')

@section('title', 'Edit Email Template | MeNetZero')
@section('page-title', 'Edit Email Template')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif

    <div class="mb-4">
        <a href="{{ route('admin.email-templates.index') }}" class="text-sm text-purple-700 hover:underline">&larr; All templates</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('admin.email-templates.update', $template) }}" class="bg-white shadow rounded-lg">
                @csrf
                @method('PUT')

                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">{{ $template->name }}</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $template->description }}</p>
                    <p class="text-xs text-gray-400 mt-1 font-mono">{{ $template->slug }}</p>
                </div>

                <div class="p-4 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Send from</label>
                            <select name="mailer" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                @foreach(['noreply' => 'noreply@ — automated', 'help' => 'help@ — support', 'hello' => 'hello@ — sales'] as $key => $label)
                                    <option value="{{ $key }}" @selected(old('mailer', $template->mailer) === $key)>{{ $label }} ({{ $addressLabels[$key] ?? $key }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reply-To (optional)</label>
                            <select name="reply_to" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">Same as sender</option>
                                @foreach(['help' => 'help@', 'hello' => 'hello@', 'noreply' => 'noreply@'] as $key => $label)
                                    <option value="{{ $key }}" @selected(old('reply_to', $template->reply_to) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                        <input type="text" name="subject" value="{{ old('subject', $template->subject) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">HTML body</label>
                        <textarea name="body_html" rows="18" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono">{{ old('body_html', $template->body_html) }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Use <code>{{ '{{' }}placeholder{{ '}}' }}</code> tags. Content is wrapped in the branded email layout automatically.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Plain text body (optional)</label>
                        <textarea name="body_text" rows="6" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono">{{ old('body_text', $template->body_text) }}</textarea>
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->is_active))>
                        Template active (send when triggered)
                    </label>
                </div>

                <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
                    <a href="{{ route('admin.email-templates.preview', $template) }}" target="_blank" class="text-sm text-purple-700 hover:underline">Preview with sample data</a>
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Save template</button>
                </div>
            </form>
        </div>

        <div>
            <div class="bg-white shadow rounded-lg p-4 sticky top-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-2">Available placeholders</h3>
                <ul class="text-xs text-gray-600 space-y-1 font-mono">
                    <li>{{ '{{' }}app_name{{ '}}' }}</li>
                    <li>{{ '{{' }}app_url{{ '}}' }}</li>
                    <li>{{ '{{' }}help_email{{ '}}' }}</li>
                    <li>{{ '{{' }}hello_email{{ '}}' }}</li>
                    <li>{{ '{{' }}user_name{{ '}}' }}</li>
                    <li>{{ '{{' }}user_email{{ '}}' }}</li>
                    @foreach($template->placeholders ?? [] as $placeholder)
                        @if(!in_array($placeholder, ['app_name', 'app_url', 'help_email', 'hello_email', 'user_name', 'user_email'], true))
                            <li>{{ '{{' }}{{ $placeholder }}{{ '}}' }}</li>
                        @endif
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endsection
