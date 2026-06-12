@if(!empty($highlight))
@php
    $title = $highlight['title'] ?? null;
    $caption = $highlight['caption'] ?? null;
    $variant = $highlight['variant'] ?? 'default';
    $theme = $highlight['theme'] ?? ($portal ?? 'company');
    $src = $highlight['src'] ?? null;
    $assetPath = $src ? public_path($src) : null;
    $hasPhoto = $assetPath && is_file($assetPath);
@endphp

<div class="portal-guide-highlight portal-guide-highlight--{{ $theme }}">
    @if($title)
        <p class="portal-guide-highlight__title">{{ $title }}</p>
    @endif
    <div class="portal-guide-highlight__mock" aria-hidden="true">
        @if($hasPhoto)
            <img
                src="{{ asset($src) }}"
                alt="{{ $highlight['alt'] ?? $title ?? 'Guide illustration' }}"
                class="portal-guide-highlight__photo"
                loading="lazy"
                decoding="async"
            >
        @else
            @include('help.partials.guide-mock', [
                'variant' => $variant,
                'theme' => $theme,
            ])
        @endif
    </div>
    @if($caption)
        <p class="portal-guide-highlight__caption">{{ $caption }}</p>
    @endif
</div>
@endif
