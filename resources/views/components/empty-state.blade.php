@props(['title' => 'No data yet', 'description' => ''])

<div class="p-12 text-center shadow-sm bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl">
    <svg class="mx-auto h-12 w-12 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
    </svg>
    <h3 class="mt-3 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $title }}</h3>
    @if($description)
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
