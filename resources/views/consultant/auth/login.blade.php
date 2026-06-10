@extends('layouts.public')

@section('title', 'Partner Sign In — MENetZero')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Partner sign in</h1>
    <p class="text-sm text-gray-600 mb-6">Access your consultant dashboard, documents, and client leads.</p>

    <form method="POST" action="{{ route('consultant.login.post') }}" class="space-y-4 bg-white border border-gray-200 rounded-xl p-6">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
            @error('email')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input type="password" name="password" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="remember" value="1"> Remember me
        </label>
        <button type="submit" class="w-full py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-medium rounded-lg">Sign in</button>
    </form>

    <p class="text-center text-sm text-gray-500 mt-4">
        New consultant? <a href="{{ route('consultant.register') }}" class="text-teal-600 hover:underline">Apply here</a>
    </p>
</div>
@endsection
