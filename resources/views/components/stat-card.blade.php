@props([
    'accent' => 'brand',
    'icon' => '',
    'badge' => '',
    'title' => '',
    'value' => '0',
    'unit' => '',
    'href' => '#',
    'linkText' => '',
    'height' => 'h-52',
    'locked' => false,
    'lockedText' => 'Locked',
    'subBadges' => [],
])

@php
    $accentClasses = match($accent) {
        'brand' => 'text-brand bg-brand-50 border-brand-200',
        'brand-dark' => 'text-brand-dark dark:text-brand bg-brand-50 border-brand-200',
        'harvest' => 'text-harvest bg-harvest-50 border-harvest/20',
        'harvest-dark' => 'text-harvest-dark dark:text-harvest bg-harvest-50 border-harvest/20',
        default => "text-{$accent} dark:text-{$accent} bg-{$accent}/10 border-{$accent}/10",
    };
@endphp

<div {{ $attributes->merge([
    'class' => "bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:-translate-y-1 hover:shadow-xl hover:shadow-{$accent}/5 hover:border-{$accent}/30 dark:hover:border-{$accent}/30 transition-all duration-300 group flex flex-col justify-between {$height}"
]) }}>
    <div>
        <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 rounded-2xl bg-{{ $accent }}/10 border border-{{ $accent }}/15 flex items-center justify-center text-{{ $accent }} dark:text-{{ $accent }} shrink-0 shadow-sm">
                {!! $icon !!}
            </div>
            @if($badge)
                <span class="text-[9px] font-extrabold uppercase tracking-widest text-{{ $accent }} dark:text-{{ $accent }} bg-{{ $accent }}/10 px-2 py-0.5 rounded border border-{{ $accent }}/10">{{ $badge }}</span>
            @endif
        </div>
        <h3 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none">{{ $title }}</h3>
        @if(isset($subBadges) && count($subBadges) > 0)
            <p class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight heading-font mt-2">{{ $value }}</p>
            <div class="flex flex-wrap gap-1.5 mt-3">
                @foreach($subBadges as $label => $count)
                    <span class="text-[9px] font-semibold text-slate-600 dark:text-slate-350 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 px-2 py-0.5 rounded-md">{{ $count }} {{ $label }}</span>
                @endforeach
            </div>
        @else
            <p class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight heading-font mt-2.5">
                {{ $value }} <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">{{ $unit }}</span>
            </p>
        @endif
    </div>
    <div class="pt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
        @if($locked)
            <span class="text-slate-300 dark:text-slate-400 font-bold text-xs select-none">{{ $lockedText }}</span>
        @else
            {{ $slot->isNotEmpty() ? $slot : '' }}
            @if($linkText)
                <a href="{{ $href }}" class="text-{{ $accent }} dark:text-{{ $accent }} font-bold text-xs hover:underline transition inline-flex items-center gap-1.5 group-hover:translate-x-1 duration-200">
                    {{ $linkText }} <span>→</span>
                </a>
            @endif
        @endif
    </div>
</div>
