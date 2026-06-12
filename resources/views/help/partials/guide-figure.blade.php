@if(!empty($image))
@php
    $src = $image['src'] ?? null;
    $alt = $image['alt'] ?? 'Guide illustration';
    $caption = $image['caption'] ?? null;
    $variant = $image['variant'] ?? 'default';
    $theme = $image['theme'] ?? ($portal ?? 'company');
    $assetPath = $src ? public_path($src) : null;
    $hasPhoto = $assetPath && is_file($assetPath);
@endphp

<figure class="portal-guide-figure portal-guide-figure--{{ $theme }}">
    <div class="portal-guide-figure__frame">
        @if($hasPhoto)
            <img
                src="{{ asset($src) }}"
                alt="{{ $alt }}"
                class="portal-guide-figure__img"
                loading="lazy"
                decoding="async"
            >
        @else
            @include('help.partials.guide-wireframe', [
                'variant' => $variant,
                'theme' => $theme,
                'alt' => $alt,
            ])
        @endif
    </div>
    @if($caption)
        <figcaption class="portal-guide-figure__caption">{{ $caption }}</figcaption>
    @endif
</figure>
@endif
