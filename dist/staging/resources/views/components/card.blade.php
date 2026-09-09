@props(['padding' => 'p-6'])

<div {{ $attributes->merge(['class' => "bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm {$padding}"]) }}>
    {{ $slot }}
</div>
