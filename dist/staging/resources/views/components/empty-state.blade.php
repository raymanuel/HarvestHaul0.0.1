@props([
    'type' => 'first-use',
    'title' => null,
    'description' => null,
    'action' => null,
])

@php
    $config = match($type) {
        'no-results' => [
            'aria' => 'search',
            'paths' => '<circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" />',
            'defaultTitle' => 'No results found',
            'defaultDescription' => 'Try adjusting your search or filters.',
        ],
        'cleared' => [
            'aria' => 'check',
            'paths' => '<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
            'defaultTitle' => 'All caught up',
            'defaultDescription' => 'Nothing needs your attention right now.',
        ],
        'error' => [
            'aria' => 'alert',
            'paths' => '<path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />',
            'defaultTitle' => 'Could not load this content',
            'defaultDescription' => 'Check your connection and try again.',
        ],
        default => [
            'aria' => 'box',
            'paths' => '<path d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />',
            'defaultTitle' => 'Nothing here yet',
            'defaultDescription' => 'This is a good place to start.',
        ],
    };

    $colorClass = $type === 'error'
        ? 'text-[var(--color-error-text)]'
        : ($type === 'no-results' ? 'text-harvest dark:text-harvest-light' : 'text-slate-400 dark:text-slate-500');
@endphp

<div class="p-12 text-center shadow-sm bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl">
    <svg class="mx-auto h-12 w-12 {{ $colorClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $config['paths'] }}" />
    </svg>
    <h3 class="mt-3 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $title ?? $config['defaultTitle'] }}</h3>
    @if($description || $config['defaultDescription'])
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $description ?? $config['defaultDescription'] }}</p>
    @endif
    @if($action)
        <div class="mt-4 inline-flex">{{ $action }}</div>
    @endif
    {{ $slot }}
</div>
