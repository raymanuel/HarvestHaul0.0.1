@props([
    'badge' => '',
    'title' => '',
    'value' => '0',
    'unit' => '',
    'href' => '#',
    'linkText' => '',
    'locked' => false,
    'lockedText' => 'Locked',
    'subBadges' => [],
])

@php
    $clickable = ($href !== '#' && !$locked);
    $attrs = $attributes->merge([
        'role' => 'region',
        'aria-label' => $title,
        'class' => 'bg-surface-card dark:bg-surface-card-dark border border-slate-200/60 dark:border-dark-border rounded-2xl p-5 hover:border-slate-300 dark:hover:border-dark-border-light transition group flex flex-col justify-between min-h-[140px]',
    ]);
@endphp

@if($clickable)
<a href="{{ $href }}" {{ $attrs }}>
@else
<div {{ $attrs }}>
@endif
    <div>
        <div class="flex items-start justify-between mb-3">
            <h3 class="text-[10px] font-bold text-text-muted dark:text-text-dark-muted uppercase tracking-widest leading-none">{{ $title }}</h3>
            @if($badge)
                <span class="text-[10px] font-extrabold uppercase tracking-widest text-gold-600 dark:text-gold-light bg-gold-600/10 px-2 py-0.5 rounded border border-gold-600/10">{{ $badge }}</span>
            @endif
        </div>
        @if(isset($subBadges) && count($subBadges) > 0)
            <p class="text-2xl font-extrabold text-text dark:text-text-dark tracking-tight heading-font">{{ $value }}</p>
                <div class="flex flex-wrap gap-1.5 mt-3">
                @foreach($subBadges as $label => $count)
                    <span class="text-[10px] font-semibold text-text-muted bg-slate-100 dark:text-text-dark-muted dark:bg-dark-hover-bg border border-slate-200/60 dark:border-dark-border px-2 py-0.5 rounded-md">{{ $count }} {{ $label }}</span>
                @endforeach
            </div>
        @else
            <p class="text-2xl font-extrabold text-text dark:text-text-dark tracking-tight heading-font mt-1">
                {{ $value }} <span class="text-xs font-semibold text-text-muted dark:text-text-dark-muted">{{ $unit }}</span>
            </p>
        @endif
    </div>
    <div class="pt-3 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between mt-4">
        @if($locked)
            <span class="text-slate-400 dark:text-slate-500 font-bold text-xs select-none">{{ $lockedText }}</span>
        @else
            {{ $slot->isNotEmpty() ? $slot : '' }}
            @if($linkText)
                <span class="text-gold-600 dark:text-gold-light font-bold text-xs inline-flex items-center gap-1.5">
                    {{ $linkText }} <span>→</span>
                </span>
            @endif
        @endif
    </div>
@if($clickable)
</a>
@else
</div>
@endif
