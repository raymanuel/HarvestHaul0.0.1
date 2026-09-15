@props(['message' => '', 'subtitle' => '', 'name' => ''])

@php
    $now = \Carbon\Carbon::now();
    $userName = $name ?: Auth::user()?->name ?? '';
    if ($message) {
        $greeting = $message;
    } elseif ($userName) {
        $hour = (int) $now->format('G');
        $period = match(true) {
            $hour >= 17 || $hour < 5 => 'evening',
            $hour >= 12 => 'afternoon',
            default => 'morning',
        };
        $greeting = "Good {$period}, {$userName}.";
    } else {
        $greeting = '';
    }
@endphp

<header class="mt-4 mb-6">
    @if($greeting)
        <h1 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white tracking-tight heading-font mb-1">{{ $greeting }}</h1>
    @endif
    <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ $now->format('l') }}, {{ $now->format('F j, Y') }}</p>
    @if($subtitle)
        <p class="text-sm text-slate-500 dark:text-slate-400 font-medium mt-3">{{ $subtitle }}</p>
    @endif
    @if($slot->isNotEmpty())
        <div class="mt-3">{{ $slot }}</div>
    @endif
</header>