<x-layout>
    <div class="w-full max-w-4xl mx-auto pb-12">

        {{-- Header --}}
        <header class="mb-8 pt-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">Cost Ledger</h1>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wider text-[#16283C] dark:text-[#D7BC7A] bg-[#16283C]/10 dark:bg-[#16283C]/10 px-3 py-1.5 rounded-md border border-[#16283C]/10 dark:border-[#16283C]/20 self-start">
                    Cost Ledger
                </span>
            </div>
        </header>

        @if($jobs->isEmpty())
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-12 text-center shadow-sm">
                <div class="w-14 h-14 rounded-2xl bg-slate-50 dark:bg-slate-700 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-slate-300 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <p class="text-slate-600 dark:text-slate-400 font-bold text-sm heading-font">No confirmed jobs yet</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                    Cost ledgers are generated once a pooling job is confirmed. Go to Route Planning to create one.
                </p>
                <a href="{{ route('route.optimization') }}"
                   class="mt-5 inline-flex items-center gap-2 bg-[#16283C] hover:bg-[#0E1620] text-white dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] text-xs font-bold px-4 py-2.5 rounded-xl transition shadow-sm">
                    Open Route Planning →
                </a>
            </div>
        @else
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700/60">
                    <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 heading-font">All Pooling Jobs</h2>
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @foreach($jobs as $job)
                        @php
                            $statusColor = match($job->status->value) {
                                'completed'   => 'text-[#16283C] dark:text-[#D7BC7A] bg-[#16283C]/10 dark:bg-[#16283C]/10 border-[#16283C]/20 dark:border-[#16283C]/15',
                                'in_progress' => 'text-[#0E1620] dark:text-[#E9EEF4] bg-[#0E1620]/10 dark:bg-[#0E1620]/10 border-[#0E1620]/20 dark:border-[#0E1620]/15',
                                'confirmed'   => 'text-[var(--color-warning-text)] bg-[var(--color-warning-bg)] border-[var(--color-warning-border)]',
                                default       => 'text-slate-500 bg-slate-50 dark:bg-slate-900/30 border-slate-200/50',
                            };
                            $totalKg    = (float) $job->total_kg;
                            $sharesSum  = (float) $job->harvests->sum(fn($h) => (float) ($h->pivot->cost_share ?? 0));
                            $basePrice  = $sharesSum > 0 ? $sharesSum : (float) ($job->negotiated_price ?? $job->price_reference ?? 0);
                        @endphp
                        <a href="{{ route('pooling.cost-ledger', $job) }}"
                           class="flex items-center justify-between px-6 py-4 hover:bg-slate-50/60 dark:hover:bg-slate-700/20 transition group">

                            <div class="flex items-center gap-4 min-w-0">
                                {{-- Job ID badge --}}
                                <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center shrink-0">
                                    <span class="text-xs font-black text-slate-500 dark:text-slate-400 font-mono">#{{ $job->id }}</span>
                                </div>

                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200 group-hover:text-[#16283C] dark:group-hover:text-[#16283C] transition heading-font truncate">
                                             #{{ $job->id }} — {{ $job->created_at->format('M d, Y') }}
                                        </p>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md border {{ $statusColor }} capitalize shrink-0">
                                            {{ str_replace('_', ' ', $job->status->value) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-3 mt-1 text-[11px] text-slate-500 dark:text-slate-400 font-semibold flex-wrap">
                                        <span>{{ $job->harvests->count() }} {{ Str::plural('farm', $job->harvests->count()) }}</span>
                                        <span class="text-slate-300 dark:text-slate-600"></span>
                                        <span>{{ number_format($totalKg, 1) }} kg</span>
                                        @if($job->truck ?? false)
                                            <span class="text-slate-300 dark:text-slate-600"></span>
                                            <span>{{ $job->truck->truck_name ?? 'Unassigned' }}</span>
                                            @if($job->truck->plate_number ?? false)
                                                <span class="text-slate-300 dark:text-slate-600">·</span>
                                                <span class="font-mono">{{ $job->truck->plate_number }}</span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 shrink-0 ml-4">
                                {{-- Price --}}
                                <div class="text-right hidden sm:block">
                                    @if($basePrice > 0)
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $job->negotiated_price ? 'Negotiated' : 'Reference' }}</p>
                                        <p class="text-sm font-extrabold text-[#16283C] dark:text-[#D7BC7A] mt-0.5">₱{{ number_format($basePrice, 2) }}</p>
                                    @else
                                        <p class="text-xs text-slate-400 italic">Price TBD</p>
                                    @endif
                                </div>

                                {{-- Arrow --}}
                                <div class="text-slate-300 group-hover:text-[#16283C] dark:group-hover:text-[#16283C] transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($jobs->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700/60">
                        {{ $jobs->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-layout>
