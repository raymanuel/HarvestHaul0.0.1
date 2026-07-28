@props([
    'id' => 'modal',
    'title' => '',
    'size' => 'md',
    'closeable' => true,
])

@php
    $maxWidth = match($size) {
        'sm' => 'max-w-sm',
        'md' => 'max-w-lg',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
        default => 'max-w-lg',
    };
@endphp

<div id="{{ $id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 backdrop-blur-sm" onclick="if(event.target===this)closeModal('{{ $id }}')">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full {{ $maxWidth }} mx-4 border border-slate-100 dark:border-slate-700 p-7">
        @if($title || $closeable)
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-extrabold text-slate-800 dark:text-white heading-font">{{ $title }}</h3>
                @if($closeable)
                    <button onclick="closeModal('{{ $id }}')" class="text-slate-400 hover:text-slate-800 dark:hover:text-white text-lg leading-none transition cursor-pointer">&times;</button>
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
