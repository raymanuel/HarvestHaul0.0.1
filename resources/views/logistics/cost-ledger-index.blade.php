<x-layout>
    <div class="w-full max-w-4xl mx-auto pb-12">

        {{-- Header --}}
        <header class="mb-8 pt-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">Cost Ledger</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">
                        Select a job to view per-farmer proportional freight cost breakdown
                    </p>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wider text-[#3A7D44] dark:text-[#3A7D44] bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 px-3 py-1.5 rounded-lg border border-[#3A7D44]/10 dark:border-[#3A7D44]/20 self-start">
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
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 leading-relaxed">
                    Cost ledgers are generated once a pooling job is confirmed. Go to Route Planning to create one.
                </p>
                <a href="{{ route('route.optimization') }}"
                   class="mt-5 inline-flex items-center gap-2 bg-[#3A7D44] hover:bg-[#2E6336] text-white text-xs font-bold px-4 py-2.5 rounded-xl transition shadow-sm">
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
                            $statusColor = match($job->status) {
                                'completed'   => 'text-[#3A7D44] dark:text-[#3A7D44] bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 border-[#3A7D44]/20 dark:border-[#3A7D44]/15',
                                'in_progress' => 'text-[#1F4D25] dark:text-[#1F4D25] bg-[#1F4D25]/10 dark:bg-[#1F4D25]/10 border-[#1F4D25]/20 dark:border-[#1F4D25]/15',
                                'confirmed'   => 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/20 border-amber-200/50 dark:border-amber-800/30',
                                default       => 'text-slate-500 bg-slate-50 dark:bg-slate-900/30 border-slate-200/50',
                            };
                            $totalKg    = (float) $job->total_kg;
                            $basePrice  = (float) ($job->negotiated_price ?? $job->price_reference ?? 0);
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
                                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200 group-hover:text-[#3A7D44] dark:group-hover:text-[#3A7D44] transition heading-font truncate">
                                             {{ $job->truck->truck_name ?? 'Fleet Hauler' }}
                                        </p>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md border {{ $statusColor }} capitalize shrink-0">
                                            {{ str_replace('_', ' ', $job->status) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-3 mt-1 text-[11px] text-slate-400 dark:text-slate-500 font-semibold flex-wrap">
                                        <span>{{ $job->harvests->count() }} {{ Str::plural('farm', $job->harvests->count()) }}</span>
                                        <span class="text-slate-300 dark:text-slate-600">·</span>
                                        <span>{{ number_format($totalKg, 1) }} kg</span>
                                        @if($job->truck->plate_number ?? false)
                                            <span class="text-slate-300 dark:text-slate-600">·</span>
                                            <span class="font-mono">{{ $job->truck->plate_number }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 shrink-0 ml-4">
                                {{-- Price --}}
                                <div class="text-right hidden sm:block">
                                    @if($basePrice > 0)
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $job->negotiated_price ? 'Negotiated' : 'Reference' }}</p>
                                        <p class="text-sm font-extrabold text-[#3A7D44] dark:text-[#3A7D44] mt-0.5">₱{{ number_format($basePrice, 2) }}</p>
                                    @else
                                        <p class="text-xs text-slate-400 italic">Price TBD</p>
                                    @endif
                                </div>

                                {{-- Arrow --}}
                                <div class="text-slate-300 group-hover:text-[#3A7D44] dark:group-hover:text-[#3A7D44] transition">
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
