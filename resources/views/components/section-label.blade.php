@props([
    'title' => '',
    'width' => 'w-20',
])

<div class="mb-4 flex items-center justify-between">
    <h2 class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ $title }}</h2>
    <span class="{{ $width }} h-px bg-slate-200 dark:bg-slate-700/80"></span>
</div>
