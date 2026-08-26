@props(['message' => '', 'subtitle' => ''])

<h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font mb-{{ $subtitle ? '2' : '6' }}">{{ $message }}</h1>
@if($subtitle)
    <p class="text-sm text-slate-500 dark:text-slate-400 font-medium mb-6">{{ $subtitle }}</p>
@endif
