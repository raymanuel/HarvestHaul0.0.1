@props(['message' => null])

@php($msg = $message ?? session('error'))
@if($msg)
    <div class="mb-6 bg-[var(--color-error-bg)] border border-[var(--color-error-border)] text-[var(--color-error-text)] rounded-2xl p-5 text-sm font-semibold flex items-center gap-3 shadow-sm">
        <span class="w-6 h-6 rounded-full bg-[var(--color-error-border)] flex items-center justify-center text-[var(--color-error-text)] shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
        </span>
        <span class="flex-1">{{ $msg }}</span>
        @if(isset($slot) && $slot->isNotEmpty())
            <div class="shrink-0">{{ $slot }}</div>
        @endif
    </div>
@endif
