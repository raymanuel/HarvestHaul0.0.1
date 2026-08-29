@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => null,
    'full' => false,
    'tag' => 'button',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 font-bold rounded-xl transition-all duration-200 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40 disabled:opacity-40 disabled:cursor-not-allowed';

    $variants = [
        'primary'   => 'bg-brand-700 hover:bg-brand-dark text-white shadow-md shadow-brand-700/15 hover:shadow-lg',
        'harvest'   => 'bg-harvest hover:bg-harvest-dark text-[#17202B] shadow-md shadow-harvest/15 hover:shadow-lg',
        'secondary' => 'bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-200 border border-slate-200 dark:border-slate-600',
        'ghost'     => 'text-brand-700 dark:text-brand-light hover:bg-brand-50 dark:hover:bg-brand-900/30',
        'danger'    => 'bg-[var(--color-error-text)] hover:opacity-90 text-white shadow-md',
    ];

    $sizes = [
        'sm' => 'px-3.5 py-2 text-[11px]',
        'md' => 'px-5 py-2.5 text-xs',
        'lg' => 'px-6 py-3.5 text-sm',
    ];

    $classes = $base.' '.$variants[$variant].' '.$sizes[$size].($full ? ' w-full' : '');

    $extraAttrs = $tag === 'a'
        ? $attributes->merge(['class' => $classes])
        : $attributes->merge(['type' => $type ?? 'submit', 'class' => $classes]);
@endphp

<{{ $tag }} {{ $extraAttrs }}>
    {{ $slot }}
</{{ $tag }}>
