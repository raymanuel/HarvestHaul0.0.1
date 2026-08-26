@props([
    'accent' => 'brand',
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
    $accentStyles = match($accent) {
        'brand' => [
            'bar' => 'border-l-brand-600',
            'text' => 'text-brand dark:text-brand',
            'bg' => 'bg-brand/10',
            'border' => 'border-brand/10',
            'hoverShadow' => 'hover:shadow-brand/5',
            'hoverBorder' => 'hover:border-brand/30 dark:hover:border-brand/30',
        ],
        'brand-dark' => [
            'bar' => 'border-l-brand-700',
            'text' => 'text-brand-700 dark:text-brand-700',
            'bg' => 'bg-brand-700/10',
            'border' => 'border-brand-700/10',
            'hoverShadow' => 'hover:shadow-brand-700/5',
            'hoverBorder' => 'hover:border-brand-700/30 dark:hover:border-brand-700/30',
        ],
        'harvest', 'harvest-dark' => [
            'bar' => 'border-l-harvest-500',
            'text' => 'text-harvest dark:text-harvest',
            'bg' => 'bg-harvest/10',
            'border' => 'border-harvest/10',
            'hoverShadow' => 'hover:shadow-harvest/5',
            'hoverBorder' => 'hover:border-harvest/30 dark:hover:border-harvest/30',
        ],
        'amber-500' => [
            'bar' => 'border-l-amber-500',
            'text' => 'text-amber-500 dark:text-amber-500',
            'bg' => 'bg-amber-500/10',
            'border' => 'border-amber-500/10',
            'hoverShadow' => 'hover:shadow-amber-500/5',
            'hoverBorder' => 'hover:border-amber-500/30 dark:hover:border-amber-500/30',
        ],
        default => [
            'bar' => 'border-l-slate-400',
            'text' => 'text-slate-400 dark:text-slate-400',
            'bg' => 'bg-slate-400/10',
            'border' => 'border-slate-400/10',
            'hoverShadow' => 'hover:shadow-slate-400/5',
            'hoverBorder' => 'hover:border-slate-400/30 dark:hover:border-slate-400/30',
        ],
    };
@endphp

<div {{ $attributes->merge([
    'class' => "{$accentStyles['bar']} border-l-4 bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl p-5 hover:-translate-y-1 hover:shadow-xl {$accentStyles['hoverShadow']} {$accentStyles['hoverBorder']} transition-all duration-300 group flex flex-col justify-between min-h-[140px]"
]) }} role="region" aria-label="{{ $title }}">
    <div>
        <div class="flex items-start justify-between mb-3">
            <h3 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest leading-none">{{ $title }}</h3>
            @if($badge)
                <span class="text-[10px] font-extrabold uppercase tracking-widest {{ $accentStyles['text'] }} {{ $accentStyles['bg'] }} px-2 py-0.5 rounded border {{ $accentStyles['border'] }}">{{ $badge }}</span>
            @endif
        </div>
        @if(isset($subBadges) && count($subBadges) > 0)
            <p class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight heading-font">{{ $value }}</p>
            <div class="flex flex-wrap gap-1.5 mt-3">
                @foreach($subBadges as $label => $count)
                    <span class="text-[9px] font-semibold text-slate-600 dark:text-slate-350 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 px-2 py-0.5 rounded-md">{{ $count }} {{ $label }}</span>
                @endforeach
            </div>
        @else
            <p class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight heading-font mt-1">
                {{ $value }} <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $unit }}</span>
            </p>
        @endif
    </div>
    <div class="pt-3 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between mt-4">
        @if($locked)
            <span class="text-slate-400 dark:text-slate-500 font-bold text-xs select-none">{{ $lockedText }}</span>
        @else
            {{ $slot->isNotEmpty() ? $slot : '' }}
            @if($linkText)
                <a href="{{ $href }}" class="{{ $accentStyles['text'] }} font-bold text-xs hover:underline transition inline-flex items-center gap-1.5 group-hover:translate-x-1 duration-200">
                    {{ $linkText }} <span>→</span>
                </a>
            @endif
        @endif
    </div>
</div>
