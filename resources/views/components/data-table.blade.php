@props([
    'emptyMessage' => 'No records found',
    'emptyIcon' => true,
    'compact' => false,
])

<div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-slate-100 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/40">
                    {{ $header }}
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-700/40">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if(!isset($slot) || $slot->isEmpty())
        @if(isset($empty))
            {{ $empty }}
        @else
            <div class="{{ $compact ? 'px-6 py-8' : 'px-6 py-16' }} text-center">
                <div class="flex flex-col items-center gap-2">
                    @if($emptyIcon)
                        <svg class="w-10 h-10 text-slate-200 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    @endif
                    <span class="text-slate-400 dark:text-slate-500 text-sm font-semibold">{{ $emptyMessage }}</span>
                </div>
            </div>
        @endif
    @endif
</div>
