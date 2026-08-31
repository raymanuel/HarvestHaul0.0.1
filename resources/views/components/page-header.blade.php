@props([
    'variant' => 'dashboard',
    'title' => '',
    'backHref' => null,
    'backLabel' => '← Back to Dashboard',
    'showDate' => false,
])

<header class="pt-8 mb-8">
    @if($variant === 'back-link' && $backHref)
        <a href="{{ $backHref }}" class="text-sm text-slate-400 hover:text-slate-700 dark:hover:text-slate-400 mb-4 inline-block font-semibold transition">
            {{ $backLabel }}
        </a>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            @if($variant === 'breadcrumb' && isset($breadcrumb))
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-1">{{ $breadcrumb }}</p>
            @endif

            <h1 class="{{ $variant === 'admin' ? 'text-2xl font-extrabold' : 'text-3xl font-extrabold' }} text-slate-900 dark:text-white tracking-tight heading-font">{{ $title }}</h1>
        </div>
        <div class="flex items-center gap-3">
            @if($showDate)
                <span class="text-xs font-bold font-mono text-slate-500 dark:text-slate-400">{{ now()->format('l, M d, Y') }}</span>
            @endif
            {{ $slot }}
        </div>
    </div>
</header>
