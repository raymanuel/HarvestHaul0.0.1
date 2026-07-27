@props([
    'variant' => 'dashboard',
    'portal' => null,
    'title' => '',
    'subtitle' => '',
    'badge' => null,
    'backHref' => null,
    'backLabel' => '← Back to Dashboard',
    'showDate' => false,
])

<header class="mb-8 {{ in_array($variant, ['dashboard']) ? 'pt-6' : '' }}">
    @if($variant === 'back-link' && $backHref)
        <a href="{{ $backHref }}" class="text-sm text-slate-400 hover:text-slate-650 dark:hover:text-slate-350 mb-4 inline-block font-semibold transition">
            {{ $backLabel }}
        </a>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            @if($portal)
                <span class="text-[10px] font-bold uppercase tracking-widest text-brand bg-brand-50 px-3 py-1 rounded-full border border-brand-200">{{ $portal }}</span>
            @endif

            @if($variant === 'breadcrumb' && isset($breadcrumb))
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-550 mb-1">{{ $breadcrumb }}</p>
            @endif

            <h1 class="{{ $variant === 'admin' ? 'text-2xl font-extrabold' : 'text-3xl font-extrabold' }} text-slate-900 dark:text-white {{ $portal ? 'mt-3' : '' }} tracking-tight heading-font">{{ $title }}</h1>
            @if($subtitle)
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="flex items-center gap-3">
            @if($badge)
                <span class="text-[10px] font-bold uppercase tracking-widest text-brand dark:text-brand bg-brand-50 dark:bg-brand-50 px-3 py-1.5 rounded-lg border border-brand-200 self-start">{{ $badge }}</span>
            @endif
            @if($showDate)
                <span class="text-xs font-bold font-mono text-slate-400 dark:text-slate-500">{{ now()->format('l, M d, Y') }}</span>
            @endif
            {{ $slot }}
        </div>
    </div>
</header>
