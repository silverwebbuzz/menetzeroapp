@extends('consultant.layouts.app')

@section('title', 'Profile')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-6">Directory profile</h1>

<form method="POST" action="{{ route('consultant.profile.update') }}" class="bg-white border border-gray-200 rounded-xl p-6 space-y-5">
    @csrf @method('PUT')

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Your name</label>
            <input type="text" name="name" value="{{ old('name', $consultant->name) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Practice name</label>
            <input type="text" name="company_name" value="{{ old('company_name', $consultant->company_name) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
            <input type="text" name="phone" value="{{ old('phone', $consultant->phone) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Trade license no.</label>
            <input type="text" name="trade_license_number" value="{{ old('trade_license_number', $consultant->trade_license_number) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Years of experience</label>
            <input type="number" name="experience_years" min="0" max="50" value="{{ old('experience_years', $consultant->experience_years) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
            <input type="url" name="website" value="{{ old('website', $consultant->website) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">LinkedIn</label>
        <input type="url" name="linkedin" value="{{ old('linkedin', $consultant->linkedin) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Bio / summary</label>
        <textarea name="bio" rows="4" class="w-full border border-gray-300 rounded-lg px-3 py-2">{{ old('bio', $consultant->bio) }}</textarea>
        <p class="text-xs text-gray-400 mt-1">Describe your practice, credentials, and typical SME engagements.</p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Emirates covered</label>
        <div class="flex flex-wrap gap-3">
            @foreach($emirates as $key => $label)
                <label class="flex items-center gap-1.5 text-sm">
                    <input type="checkbox" name="emirates[]" value="{{ $key }}" @checked(in_array($key, old('emirates', $consultant->emirates ?? [])))>
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Languages</label>
        <div class="flex flex-wrap gap-3">
            @foreach($languages as $key => $label)
                <label class="flex items-center gap-1.5 text-sm">
                    <input type="checkbox" name="languages[]" value="{{ $key }}" @checked(in_array($key, old('languages', $consultant->languages ?? [])))>
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Specialties</label>
        <div class="grid sm:grid-cols-2 gap-2">
            @foreach($specialties as $key => $label)
                <label class="flex items-center gap-1.5 text-sm">
                    <input type="checkbox" name="specialties[]" value="{{ $key }}" @checked(in_array($key, old('specialties', $consultant->specialties ?? [])))>
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>

    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="has_moccae_experience" value="1" @checked(old('has_moccae_experience', $consultant->has_moccae_experience))>
        I have MOCCAE / UAE federal reporting experience
    </label>

    <button type="submit" class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg">Save profile</button>
</form>
@endsection
