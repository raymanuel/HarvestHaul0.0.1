@props(['message' => null])

@php($msg = $message ?? session('success'))
@if($msg)
    <div class="mb-6 bg-brand/10 border border-brand/20 text-brand dark:text-brand rounded-2xl p-5 text-sm font-semibold flex items-center gap-3 shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-brand dark:text-brand shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        {{ $msg }}
    </div>
@endif
