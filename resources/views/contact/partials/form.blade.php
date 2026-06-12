@php
    $showTopic = $showTopic ?? true;
    $defaultTopic = old('topic', $defaultTopic ?? 'support');
    $compact = $compact ?? false;
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4 {{ $compact ? '' : 'mkt-feature-card' }}" @if(!$compact) style="padding:1.5rem;" @endif>
    @csrf

    @if($showTopic)
        <div>
            <label for="contact_topic" class="block text-sm font-medium text-gray-700 mb-1">I want to contact *</label>
            <select name="topic" id="contact_topic" required class="mkt-form-input">
                <option value="support" @selected($defaultTopic === 'support')>Support — help@ (billing, technical, account)</option>
                <option value="sales" @selected($defaultTopic === 'sales')>Sales — hello@ (pricing, demos, partnerships)</option>
            </select>
            @error('topic')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
    @endif

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="contact_name" class="block text-sm font-medium text-gray-700 mb-1">Your name *</label>
            <input type="text" name="name" id="contact_name" value="{{ old('name', $defaultName ?? '') }}" required class="mkt-form-input" @if(!empty($lockIdentity)) readonly @endif>
            @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
            <input type="email" name="email" id="contact_email" value="{{ old('email', $defaultEmail ?? '') }}" required class="mkt-form-input" @if(!empty($lockIdentity)) readonly @endif>
            @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    @if($showTopic)
        <div>
            <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-1">Phone (optional)</label>
            <input type="tel" name="phone" id="contact_phone" value="{{ old('phone', $defaultPhone ?? '') }}" class="mkt-form-input" placeholder="+91 …">
            @error('phone')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
    @endif

    <div>
        <label for="contact_subject" class="block text-sm font-medium text-gray-700 mb-1">{{ $showTopic ? 'Subject (optional)' : 'What do you need help with? *' }}</label>
        <input type="text" name="subject" id="contact_subject" value="{{ old('subject', $defaultSubject ?? '') }}" class="mkt-form-input" placeholder="{{ $showTopic ? 'Brief summary' : 'e.g. Cannot export report, billing question…' }}" @if(!$showTopic) required @endif>
        @error('subject')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="contact_message" class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
        <textarea name="message" id="contact_message" rows="{{ $showTopic ? 5 : 6 }}" required class="mkt-form-input" placeholder="Tell us how we can help…">{{ old('message') }}</textarea>
        @error('message')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <button type="submit" class="{{ $showTopic ? 'mkt-btn mkt-btn-primary' : 'btn btn-primary' }} {{ $showTopic ? 'mkt-btn-block' : '' }}">
        {{ $submitLabel ?? 'Send message' }}
    </button>
</form>
