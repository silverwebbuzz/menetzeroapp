{{-- Input Component --}}
@props([
    'type' => 'text',
    'label' => null,
    'error' => null,
    'required' => false,
    'placeholder' => null
])

<div class="form-group">
    @if($label)
        <label class="form-label">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif
    
    <input 
        {{ $attributes->merge([
            'class' => 'form-input' . ($attributes->get('class') ? ' ' . $attributes->get('class') : ''),
            'type' => $type,
            'placeholder' => $placeholder,
            'required' => $required
        ]) }}
    >
    
    @if($error)
        <p class="form-error">{{ $error }}</p>
    @endif
</div>
