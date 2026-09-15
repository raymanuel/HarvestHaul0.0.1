@props([
    'title' => '',
    'subtitle' => '',
    'status' => '',
    'statusClass' => '',
    'url' => null,
    'meta' => [],
    'muted' => false,
])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm hover:shadow-md transition duration-150 flex flex-col p-5 ' . ($muted ? 'opacity-70 hover:opacity-100 ' : '')]) }}>

    <div class="flex items-center justify-between gap-3 mb-2.5">
        <div class="flex items-center gap-2 min-w-0">
            <span class="text-xs font-bold font-mono px-2.5 py-1 rounded-md bg-[var(--color-info-bg)] border border-[var(--color-info-border)] text-[var(--color-info-text)] shrink-0">{{ $title }}</span>
            @if($subtitle)
                <p class="text-sm font-bold text-slate-800 dark:text-slate-200 truncate heading-font">{{ $subtitle }}</p>
            @endif
        </div>
        @if($status)
            <span class="text-[10px] font-extrabold uppercase tracking-widest px-2.5 py-1 rounded border shrink-0 {{ $statusClass }}">{{ $status }}</span>
        @endif
    </div>

    @if($meta)
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] font-semibold mb-1">
            @foreach($meta as $i => $item)
                @if($i > 0)
                    <span class="text-slate-300 dark:text-slate-600 select-none">·</span>
                @endif
                <span class="text-slate-600 dark:text-slate-300 {{ Str::startsWith($item, '₱') ? 'font-bold text-brand dark:text-brand-light font-mono' : 'font-mono' }}">{{ $item }}</span>
            @endforeach
        </div>
    @endif

    @if(trim((string) $slot))
        <div class="mt-2.5 space-y-2">
            {{ $slot }}
        </div>
    @endif

    @if(isset($actions) && trim((string) $actions))
        <div class="mt-auto pt-4 border-t border-slate-100 dark:border-slate-700/60">
            {{ $actions }}
        </div>
    @endif
</div>