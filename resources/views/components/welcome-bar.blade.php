@props(['message' => '', 'subtitle' => ''])

@php
    $now = \Carbon\Carbon::now();
@endphp

<h1 class="mt-10 text-3xl lg:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight heading-font mb-1">{{ $message }}</h1>
<p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-4">{{ $now->format('l') }}, {{ $now->format('F j, Y') }}</p>
@if($subtitle)
    <p class="text-sm text-slate-500 dark:text-slate-400 font-medium mb-6">{{ $subtitle }}</p>
@else
    <div class="mb-6"></div>
@endif
{{ $slot }}
