@props([
    'name' => '',
    'label' => '',
    'type' => 'text',
    'value' => '',
    'placeholder' => '',
    'error' => null,
    'required' => false,
    'disabled' => false,
    'showToggle' => false,
    'step' => null,
    'autocomplete' => null,
])

@php
    $inputId = $name;
    $isPassword = $type === 'password';
    $resolvedType = $isPassword && $showToggle ? 'password' : $type;
@endphp

<div>
    @if($label)
        <label for="{{ $inputId }}" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-red-400">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <input
            type="{{ $resolvedType }}"
            name="{{ $name }}"
            id="{{ $inputId }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($step) step="{{ $step }}" @endif
            @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            {{ $attributes->merge([
                'class' => 'w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white transition'
            ]) }}
        />

        @if($isPassword && $showToggle)
            <button type="button" onclick="togglePassword('{{ $inputId }}')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition">
                <svg id="{{ $inputId }}-eye-open" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <svg id="{{ $inputId }}-eye-closed" class="w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
            </button>
        @endif
    </div>

    @if($error)
        <p class="text-xs text-red-500 mt-1">{{ $error }}</p>
    @endif
</div>
