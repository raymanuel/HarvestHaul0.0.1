@props([
    'name' => '',
    'label' => '',
    'options' => [],
    'value' => '',
    'placeholder' => 'Select...',
    'error' => null,
    'required' => false,
    'disabled' => false,
])

@php
    $selectId = $name;
    $resolvedValue = old($name, $value);
@endphp

<div>
    @if($label)
        <label for="{{ $selectId }}" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-[var(--color-error-text)]">*</span>
            @endif
        </label>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $selectId }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        {{ $attributes->merge([
            'class' => 'w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-700/25 focus:border-brand-700 bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white transition'
        ]) }}
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" {{ $resolvedValue == $optionValue ? 'selected' : '' }}>
                {{ is_array($optionLabel) ? $optionLabel['label'] ?? '' : $optionLabel }}
            </option>
        @endforeach
    </select>

    @if($error)
        <p class="text-xs text-[var(--color-error-text)] mt-1">{{ $error }}</p>
    @endif
</div>
