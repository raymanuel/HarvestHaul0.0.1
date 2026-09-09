<x-layout>
    <div class="w-full max-w-7xl mx-auto pb-12">
        <header class="pt-8 mb-6 border-b border-slate-200/80 dark:border-slate-700/80 pb-5">
            <span class="text-xs font-bold uppercase tracking-wider text-brand dark:text-brand-light bg-brand/10 dark:bg-brand/10 px-3 py-1.5 rounded-md border border-brand/10 dark:border-brand/20 self-start">Proposals</span>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight heading-font mt-2">Proposal Inbox</h1>
        </header>

        <x-flash-success />

        @if($proposals->isEmpty() && $cancelledProposals->isEmpty() && $readyForDispatch->isEmpty())
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-16 text-center shadow-sm">
                <div class="mb-4"><x-icon name="chat" class="w-10 h-10" /></div>
                <p class="text-slate-500 dark:text-slate-400 font-medium">No active delivery proposals open. Generate route pools from Route Planning to open negotiation rooms.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                @foreach($proposals as $proposal)
                    <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6 flex flex-col justify-between hover:shadow-md transition">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-xs font-bold text-[var(--color-info-text)] bg-[var(--color-info-bg)] border border-[var(--color-info-border)] px-2.5 py-1 rounded-md font-mono">Job #{{ $proposal->id }}</span>
                                <x-badge color="amber" label="Awaiting Farmer Approval" />
                            </div>

                            <h3 class="text-base font-bold text-slate-800 dark:text-slate-200 mb-1 heading-font">{{ $proposal->truck->truck_name ?? 'Fleet Hauler' }}</h3>

                            <div class="space-y-1.5 mb-4 text-xs font-semibold">
                                @php
                                    $agreedRates = $proposal->harvests
                                        ->map(fn($h) => $h->negotiations->first()?->hauling_rate_per_kg)
                                        ->filter()
                                        ->map(fn($r) => (float) $r)
                                        ->values();
                                    $hasAgreedRates = $agreedRates->isNotEmpty();
                                @endphp
                                @if($isCoop && $hasAgreedRates)
                                    <div class="flex items-start justify-between gap-2">
                                        <span class="text-slate-400">Agreed Hauling Rates (₱/kg):</span>
                                        <span class="text-slate-500 dark:text-slate-400 font-mono text-right">{{ $agreedRates->map(fn($r) => number_format($r, 2))->implode(', ') }}</span>
                                    </div>
                                @else
                                    <div class="flex justify-between">
                                        <span class="text-slate-400">Hauling Rate:</span>
                                        <span class="text-slate-500 dark:text-slate-400 font-mono">₱{{ number_format($proposal->hauling_rate_per_kg, 2) }}/kg</span>
                                    </div>
                                @endif
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Total Haul Price (auto):</span>
                                    <span class="text-brand dark:text-brand-light font-bold">₱{{ number_format($proposal->negotiated_price ?? $proposal->price_reference, 2) }}</span>
                                </div>
                                @if($isCoop && $hasAgreedRates)
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 italic">Each farmer's share = rate agreed in chat × their kg.</p>
                                @else
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 italic">Split by cargo weight × haul distance.</p>
                                @endif
                            </div>

                            <div class="border-t border-slate-100 dark:border-slate-700/60 pt-4 space-y-2.5">
                                <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Per-Farmer Cost Share</p>
                                <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-1.5">
                                    @foreach($proposal->harvests as $harvest)
                                        @php
                                            $share = (float) ($harvest->pivot->cost_share ?? 0);
                                        @endphp
                                        <li class="flex items-center justify-between bg-slate-50 dark:bg-slate-900/40 px-3 py-2 rounded-xl border border-slate-100/50 dark:border-slate-700/20">
                                            <div class="flex items-center gap-2 truncate max-w-[150px]">
                                                <span class="font-medium text-slate-700 dark:text-slate-330">{{ $harvest->farmer->name ?? 'Farmer' }}</span>
                                                <span class="text-[8px] font-bold uppercase px-1.5 py-0.5 rounded border
                                                @if($harvest->pivot->status === 'accepted') text-brand bg-brand/10 border-brand/10 dark:text-brand-light dark:bg-brand/10 dark:border-brand/10
                                                @elseif($harvest->pivot->status === 'rejected') text-[var(--color-error-text)] bg-[var(--color-error-bg)] border-[var(--color-error-border)]
                                                @else text-[var(--color-warning-text)] bg-[var(--color-warning-bg)] border-[var(--color-warning-border)] @endif">
                                                    {{ $harvest->pivot->status }}
                                                </span>
                                            </div>
                                            <div class="text-right">
                                                <span class="font-bold text-brand dark:text-brand-light font-mono">{{ number_format($harvest->pivot->quantity_kg) }} kg</span>
                                                <span class="block text-[10px] text-slate-400 font-mono">₱{{ number_format($share, 2) }}</span>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <div class="mt-6 space-y-3">
                            <a href="{{ route('pooling.cost-ledger', $proposal) }}"
                               class="w-full flex items-center justify-center gap-2 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 text-xs font-bold py-2.5 rounded-xl hover:border-brand dark:hover:border-brand hover:text-brand dark:hover:text-brand-light transition-all duration-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                View Cost Ledger
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($readyForDispatch->isNotEmpty())
            <div class="mb-10">
                <div class="flex items-center gap-2 mb-5">
                    <span class="text-xs font-bold uppercase tracking-wider text-[var(--color-success-text)] dark:text-[var(--color-success-text-dark)]">Ready for Dispatch</span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400">(all farmers accepted)</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($readyForDispatch as $job)
                        <div class="bg-white dark:bg-slate-800 border border-[var(--color-success-border)] dark:border-[var(--color-success-border-dark)] rounded-2xl shadow-sm p-6 flex flex-col justify-between hover:shadow-md transition">
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-xs font-bold text-[var(--color-info-text)] bg-[var(--color-info-bg)] border border-[var(--color-info-border)] px-2.5 py-1 rounded-md font-mono">Job #{{ $job->id }}</span>
                                    <x-badge color="green" label="Ready for Dispatch" />
                                </div>

                                <h3 class="text-base font-bold text-slate-800 dark:text-slate-200 mb-1 heading-font">{{ $job->truck->truck_name ?? 'Fleet Hauler' }}</h3>

                                <div class="space-y-1.5 mb-4 text-xs font-semibold">
                                    <div class="flex justify-between">
                                        <span class="text-slate-400">Farms:</span>
                                        <span class="text-slate-500 dark:text-slate-400 font-mono">{{ $job->farm_count }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-400">Total Weight:</span>
                                        <span class="text-slate-500 dark:text-slate-400 font-mono">{{ number_format($job->total_kg) }} kg</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-400">Haul Price:</span>
                                        <span class="text-brand dark:text-brand-light font-bold">₱{{ number_format($job->negotiated_price ?? $job->price_reference, 2) }}</span>
                                    </div>
                                </div>

                                <p class="text-[10px] text-slate-500 dark:text-slate-400 border-t border-slate-100 dark:border-slate-700/60 pt-3">All farmers accepted. Assign a driver to begin the delivery run.</p>
                            </div>

                            <div class="mt-5">
                                <a href="{{ route('pooling.cost-ledger', $job) }}"
                                   class="w-full flex items-center justify-center gap-2 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 text-xs font-bold py-2.5 rounded-xl hover:border-brand dark:hover:border-brand hover:text-brand dark:hover:text-brand-light transition-all duration-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    View Cost Ledger
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($cancelledProposals->isNotEmpty())
            <div class="mt-10 pt-8 border-t border-slate-200 dark:border-slate-700/60">
                <div class="flex items-center gap-2 mb-5">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Recently Cancelled</span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400">(last 24h)</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($cancelledProposals as $proposal)
                        <div class="bg-white dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/40 rounded-2xl shadow-sm p-6 opacity-70 hover:opacity-100 transition">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-xs font-bold text-slate-500 font-mono">Job #{{ $proposal->id }}</span>
                                <x-badge color="red" label="Cancelled" />
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mb-2">
                                 {{ $proposal->truck->truck_name ?? 'Fleet Hauler' }}
                            </p>
                            @foreach($proposal->harvests as $harvest)
                                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/30 px-3 py-2 rounded-xl mb-1">
                                    <span>{{ $harvest->farmer->name ?? 'Farmer' }}</span>
                                    <span class="text-slate-400 font-mono">{{ number_format($harvest->pivot->quantity_kg) }} kg</span>
                                </div>
                            @endforeach
                            @if($proposal->harvests->isEmpty())
                                <p class="text-xs text-slate-400 italic">No farmers attached</p>
                            @endif
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-2 font-mono">
                                Cancelled {{ $proposal->updated_at->diffForHumans() }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layout>
