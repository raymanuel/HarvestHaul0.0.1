@props([
    'id' => null,
    'title' => '',
    'size' => 'md',
    'closeable' => true,
    'triggerLabel' => null,
    'triggerClass' => 'text-xs font-bold text-brand-700 dark:text-gold-light hover:underline',
])

@php
    $modalId = $id ?: 'modal-'.\Illuminate\Support\Str::random(8);
    $maxWidth = match($size) {
        'sm' => 'max-w-sm',
        'md' => 'max-w-lg',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
        default => 'max-w-lg',
    };
@endphp

@if($triggerLabel)
    <button type="button" onclick="openModal('{{ $modalId }}')" class="{{ $triggerClass }}">{{ $triggerLabel }}</button>
@endif

<div id="{{ $modalId }}" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 backdrop-blur-sm" onclick="if(event.target===this)closeModal('{{ $modalId }}')">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full {{ $maxWidth }} mx-4 border border-slate-100 dark:border-slate-700 p-7">
        @if($title || $closeable)
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-extrabold text-slate-800 dark:text-white heading-font">{{ $title }}</h3>
                @if($closeable)
                    <button onclick="closeModal('{{ $modalId }}')" aria-label="Close modal" class="text-slate-400 hover:text-slate-800 dark:hover:text-white text-lg leading-none transition cursor-pointer">&times;</button>
                @endif
            </div>
        @endif

        {{ $slot }}

        @if(isset($footer))
            <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
