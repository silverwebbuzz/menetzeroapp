{{-- Brand Logo Component --}}
<div class="brand-logo">
    <div class="brand-logo-icon">ME</div>
    <div class="brand-logo-text">
        @if(isset($size) && $size === 'small')
            <div class="brand-logo-text-small">MIDDLE EAST</div>
            <div class="brand-logo-text-large">NET Zero</div>
        @else
            MIDDLE EAST NET Zero
        @endif
    </div>
</div>
