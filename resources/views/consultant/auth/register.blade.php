@extends('layouts.public')

@section('title', 'Partner Registration — MENetZero')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Apply to the consultant directory</h1>
    <p class="text-sm text-gray-600 mb-6">Create your consultant account. You can upload documents after signing in.</p>

    <form method="POST" action="{{ route('consultant.register.post') }}" class="space-y-4 bg-white border border-gray-200 rounded-xl p-6">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Your name</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
            @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Practice / company name</label>
            <input type="text" name="company_name" value="{{ old('company_name') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
            @error('company_name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
            @error('email')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Phone (optional)</label>
            <input type="text" name="phone" value="{{ old('phone') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input type="password" name="password" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
            @error('password')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
            <input type="password" name="password_confirmation" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
        </div>
        <button type="submit" class="w-full py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-medium rounded-lg">Create consultant account</button>
    </form>

    <p class="text-center text-sm text-gray-500 mt-4">
        Already registered? <a href="{{ route('consultant.login') }}" class="text-teal-600 hover:underline">Sign in</a>
    </p>
</div>
@endsection
