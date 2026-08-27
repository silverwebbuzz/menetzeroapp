{{--
    MENetZero 2.0 - Guide highlight frame (Phase 6 body migration).

    Wraps ONE preview: either a real screenshot, or - when no screenshot exists
    on disk - a generated mock from the shared guide-mock partial.

    THE FILESYSTEM CHECK IS THE WHOLE POINT:
        assetPath = src ? public_path(src) : null
        hasPhoto  = assetPath && is_file(assetPath)

    is_file() runs at RENDER TIME against the real filesystem. A config entry
    can name a screenshot that has not been uploaded yet, and the guide still
    renders - it silently falls back to the mock. Replacing this with a plain
    !empty(src) check would emit broken images the moment a path is wrong.
    Preserved exactly, public_path() included.

    THEME RESOLUTION, three-level fallback:
        highlight.theme  ??  portal  ??  'company'
    An individual highlight can override the portal - a company-guide section
    can show a consultant-flavoured preview where that is what the reader
    would actually see. Preserved.

    SHARED MOCK, DELIBERATE: this forwards to help.partials.guide-mock, NOT a
    themed copy. Per the scope decision the 23 mock variants keep old-theme
    classes rather than being duplicated and kept in sync forever. Both themed
    shells load the same stylesheets as their old counterparts, so every class
    the mocks use resolves.

    aria-hidden on the frame: these are decorative previews, not content, and
    are correctly hidden from screen readers. The alt text on a real photo is
    kept anyway for when a browser ignores the attribute.

    Data: $highlight $portal
--}}
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

@push('styles')
    <style>
        .hgh { border: 1px solid var(--line); background: var(--canvas-2); }
        .hgh__title { font-size: 11px; text-transform: uppercase;
            letter-spacing: .06em; color: var(--ink-3); margin: 0;
            padding: 9px 12px; border-bottom: 1px solid var(--line); }
        .hgh__mock { padding: 14px; overflow-x: auto; }
        .hgh__photo { display: block; max-width: 100%; height: auto;
            border: 1px solid var(--line); }
        .hgh__caption { font-size: 11.5px; color: var(--ink-3); margin: 0;
            padding: 0 12px 10px; line-height: 1.55; }
    </style>
@endpush

<div class="hgh portal-guide-highlight portal-guide-highlight--{{ $theme }}">
    @if($title)
        <p class="hgh__title">{{ $title }}</p>
    @endif
    <div class="hgh__mock portal-guide-highlight__mock" aria-hidden="true">
        @if($hasPhoto)
            <img
                src="{{ asset($src) }}"
                alt="{{ $highlight['alt'] ?? $title ?? 'Guide illustration' }}"
                class="hgh__photo portal-guide-highlight__photo"
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
        <p class="hgh__caption">{{ $caption }}</p>
    @endif
</div>
@endif
