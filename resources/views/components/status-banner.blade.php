@props([
    'variant' => 'unverified',
    'title' => '',
    'message' => '',
])

@php
    $finalTitle = $title ?: ($variant === 'missing-location' ? 'Location Required' : 'Pending Verification');
@endphp

@if($variant === 'missing-location')
    <div class="mb-8 p-5 rounded-2xl bg-warning-bg border border-warning-border text-warning-text">
        <div class="flex items-start gap-3">
            <div class="w-9 h-9 rounded-[10px] bg-warning-bg flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <div>
                <h3 class="text-sm font-extrabold mb-0.5">{{ $finalTitle }}</h3>
                @if($slot->isNotEmpty())
                    <div class="text-xs leading-relaxed font-medium">{{ $slot }}</div>
                @else
                    <p class="text-xs leading-relaxed font-medium">{{ $message }}</p>
                @endif
            </div>
        </div>
    </div>
@else
    <div class="mb-8 bg-warning-bg border border-warning-border rounded-2xl px-5 py-4 flex gap-3.5 items-start shadow-sm">
        <span class="text-warning-text mt-0.5 select-none"><x-icon name="document" class="w-5 h-5" /></span>
        <div>
            <p class="text-sm font-bold text-warning-text heading-font">{{ $finalTitle }}</p>
            @if($slot->isNotEmpty())
                <div class="text-xs text-warning-text mt-1 leading-relaxed font-medium">{{ $slot }}</div>
            @else
                <p class="text-xs text-warning-text mt-1 leading-relaxed font-medium">{{ $message }}</p>
            @endif
        </div>
    </div>
@endif