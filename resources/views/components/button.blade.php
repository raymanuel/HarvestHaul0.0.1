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
        'primary'   => 'bg-brand-700 hover:bg-brand-900 text-white dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] shadow-sm',
        'harvest'   => 'bg-harvest-500 hover:bg-harvest-700 text-[#17202B] border border-[#17202B]/20 shadow-sm',
        'secondary' => 'bg-white text-brand-700 border-2 border-brand-700/40 hover:border-brand-700 hover:bg-brand-50 dark:bg-surface-card-dark dark:text-gold-light dark:border-gold-light/40 dark:hover:border-gold-light dark:hover:bg-gold/10 shadow-sm',
        'ghost'     => 'text-brand-700 dark:text-brand-light border border-transparent hover:border-brand-700/30 hover:bg-brand-50 dark:hover:bg-brand-900/30',
        'danger'    => 'bg-[var(--color-error-text)] hover:opacity-90 text-white shadow-sm',
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
