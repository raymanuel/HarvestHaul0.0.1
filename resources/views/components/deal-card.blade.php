@props([
    'type' => 'crop',
    'title' => '',
    'counterpart' => '',
    'status' => '',
    'statusClass' => '',
    'url' => '#',
    'volume' => null,
    'price' => null,
    'rate' => null,
    'activity' => '',
])

@php
    $initial = $counterpart ? mb_strtoupper(mb_substr($counterpart, 0, 1)) : '?';
    $avatar = $type === 'haul'
        ? 'bg-soil/10 text-soil dark:text-soil-light'
        : 'bg-gold/10 text-gold-700 dark:text-gold-light';
    $tagClass = $type === 'haul'
        ? 'bg-slate-100 text-slate-500 dark:bg-slate-700/60 dark:text-slate-300'
        : 'bg-gold/10 text-gold-700 dark:bg-gold-light/15 dark:text-gold-light';
@endphp

<a href="{{ $url }}"
   class="group flex items-start gap-4 bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm hover:border-gold/40 dark:hover:border-gold-light/40 transition">
    <div class="w-11 h-11 rounded-xl flex items-center justify-center text-sm font-bold shrink-0 {{ $avatar }}">{{ $initial }}</div>
    <div class="min-w-0 flex-1">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm font-bold text-slate-800 dark:text-slate-200 truncate">
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider mr-1.5 align-middle {{ $tagClass }}">{{ $type === 'haul' ? 'Haul' : 'Crop' }}</span>
                <span class="align-middle">{{ $title }}</span>
            </p>
            <span class="text-[10px] font-extrabold uppercase tracking-widest px-2.5 py-1 rounded border shrink-0 {{ $statusClass }}">{{ $status }}</span>
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $counterpart }}</p>
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1.5 text-[11px]">
            @if($volume)<span class="font-mono font-semibold text-slate-600 dark:text-slate-300">{{ $volume }}</span>@endif
            @if($price)<span class="font-mono font-semibold text-slate-600 dark:text-slate-300">{{ $price }}</span>@endif
            @if($rate)<span class="font-mono font-semibold text-slate-600 dark:text-slate-300">{{ $rate }}</span>@endif
            @if($activity)<span class="text-slate-400 dark:text-slate-500">{{ $activity }}</span>@endif
        </div>
    </div>
    <span class="text-[#16283C] dark:text-[#D7BC7A] font-bold mt-1">→</span>
</a>