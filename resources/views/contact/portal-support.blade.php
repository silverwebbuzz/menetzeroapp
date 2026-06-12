@extends(auth('consultant')->check() ? 'consultant.layouts.app' : 'layouts.app')

@section('title', 'Contact support')
@section('page-title', 'Contact support')

@section('content')
<div class="w-full max-w-2xl">
    <div class="mb-6">
        <a href="{{ route($backRoute) }}" class="text-sm text-brand hover:underline">&larr; Back to Help &amp; Guide</a>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <h2 class="card-title mb-0">Email us for support</h2>
            <p class="card-subtitle mt-1 mb-0">
                Describe your issue below. It goes to <strong>{{ site_support_email() }}</strong> and we reply to your account email.
            </p>
        </div>
        <div class="card-body">
            @include('contact.partials.form', [
                'action' => auth('consultant')->check() ? route('consultant.support.submit') : route('client.support.submit'),
                'showTopic' => false,
                'lockIdentity' => true,
                'defaultName' => $defaults['name'] ?? '',
                'defaultEmail' => $defaults['email'] ?? '',
                'defaultPhone' => $defaults['phone'] ?? '',
                'defaultSubject' => old('subject'),
                'submitLabel' => 'Send to support',
                'compact' => true,
            ])
        </div>
    </div>
</div>
@endsection
