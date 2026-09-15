@props([
    'post' => null,
    'href' => '#',
    'negotiating' => false,
    'myNegotiation' => false,
    'negotiationUrl' => null,
])

@php
    $blocked = $negotiating && !$myNegotiation;
    $image = !empty($post->crop_photos) ? $post->crop_photos[0] : null;
    $name = $post->crop->name ?? $post->crop_type ?? 'Crop';
    $variety = $post->cropVariety->name ?? $post->variety ?? null;
    $hasPrice = (float) ($post->suggested_price_per_kg ?? 0) > 0;
    $total = (float) $post->quantity_kg;
    $left = (float) ($post->remaining_quantity_kg ?? 0);
    $hasLeft = $left > 0 && $left < $total;
    $fresh = $post->harvest_date && $post->harvest_date->diffInDays(now()) <= 7;
    $partial = $post->status->value === 'partially_sold';
@endphp

<div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm flex flex-col hover:shadow-md hover:border-harvest/40 dark:hover:border-harvest/40 transition duration-150 {{ $blocked ? 'opacity-60 grayscale pointer-events-none select-none' : '' }}">

    <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-4 sm:p-5">
        <a href="{{ $href }}" class="w-full h-32 sm:w-28 sm:h-28 shrink-0 rounded-xl overflow-hidden flex items-center justify-center bg-slate-100 dark:bg-slate-900">
            @if($image)
                <img src="{{ asset('storage/' . $image) }}" alt="{{ $name }}" class="w-full h-full object-cover">
            @else
                <x-icon name="folder" class="w-9 h-9 text-harvest/40 dark:text-harvest/30" />
            @endif
        </a>

        <div class="flex-1 min-w-0 flex flex-col gap-1.5">
            <div class="flex items-start justify-between gap-3">
                <a href="{{ $href }}" class="min-w-0 block">
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white heading-font leading-snug truncate">{{ $name }}</h3>
                    @if($variety)
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 truncate">{{ $variety }}</p>
                    @endif
                </a>
                <div class="flex items-center gap-1.5 shrink-0 flex-wrap justify-end">
                    @if($fresh)
                        <span class="text-[10px] font-bold text-rose-500 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/30 px-2 py-0.5 rounded border border-rose-200/50 dark:border-rose-700/30">FRESH</span>
                    @endif
                    @if($partial)
                        <span class="text-[10px] font-bold text-[var(--color-warning-text)] bg-[var(--color-warning-bg)] px-2 py-0.5 rounded border border-[var(--color-warning-border)]">PARTIAL SALE</span>
                    @endif
                    @if($blocked)
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-600">UNDER NEGOTIATION</span>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                @if($hasPrice)
                    <span class="text-base font-extrabold text-[#16283C] dark:text-[#D7BC7A] font-mono">₱{{ number_format($post->suggested_price_per_kg, 2) }}<span class="text-[10px] font-bold text-[#16283C]/60 dark:text-[#D7BC7A]/60">/kg</span></span>
                @else
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-700/60 px-2 py-0.5 rounded">Negotiable</span>
                @endif
                <span class="text-xs font-bold text-slate-600 dark:text-slate-300 font-mono">{{ number_format($total) }} kg</span>
                @if($hasLeft)
                    <span class="text-[11px] font-bold text-[var(--color-warning-text)]">{{ number_format($left, 0) }} kg left</span>
                @endif
                @if($partial && $post->sale_progress !== null)
                    <span class="text-[11px] font-bold text-[var(--color-warning-text)]">{{ $post->sale_progress }}% sold</span>
                @endif
            </div>

            @if($post->farmer)
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $post->farmer->name ?? 'Farmer' }}</p>
            @endif
        </div>
    </div>

    @if(!$blocked)
        <div class="flex items-center gap-2 border-t border-slate-100 dark:border-slate-700/60 px-4 sm:px-5 py-3">
            <a href="{{ $href }}" class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-3 bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <x-icon name="search" class="w-4 h-4" />
                Details
            </a>

            @if($myNegotiation && $negotiationUrl)
                <a href="{{ $negotiationUrl }}" class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-3 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] rounded-xl text-sm font-bold text-[var(--color-warning-text)] hover:opacity-85 transition-colors">
                    <x-icon name="chat" class="w-4 h-4" />
                    Continue
                </a>
            @else
                <form action="{{ route('negotiations.start') }}" method="POST" class="flex-1">
                    @csrf
                    <input type="hidden" name="harvest_id" value="{{ $post->id }}">
                    <button type="button" onclick="swalConfirm(this.closest('form'), {title:'Start Negotiation?', text:'Open a crop negotiation with this farmer?', icon:'question', confirmText:'Yes, start', cancelText:'Cancel', confirmColor:'#16283C'})" class="w-full inline-flex items-center justify-center gap-1.5 py-2.5 px-3 bg-harvest hover:bg-harvest-dark dark:bg-harvest dark:hover:bg-harvest-dark text-[#17202B] font-bold rounded-xl text-sm transition-colors cursor-pointer">
                        <x-icon name="plus" class="w-4 h-4" />
                        Initiate
                    </button>
                </form>
            @endif
        </div>
    @endif
</div>