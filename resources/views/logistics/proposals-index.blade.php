<x-layout>
    <div class="w-full max-w-7xl mx-auto pb-12">
        <header class="pt-8 mb-6 border-b border-slate-200/80 dark:border-slate-700/80 pb-5">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font mt-2">Proposal Inbox</h1>
        </header>

        <x-flash-success />

        @if($proposals->isEmpty() && $cancelledProposals->isEmpty() && $readyForDispatch->isEmpty())
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-16 text-center shadow-sm">
                <div class="mb-4"><x-icon name="chat" class="w-10 h-10" /></div>
                <p class="text-slate-500 dark:text-slate-400 font-medium">No active delivery proposals open. Generate route pools from Route Planning to open negotiation rooms.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-10">
                @foreach($proposals as $proposal)
                    @php
                        $agreedRates = $proposal->harvests
                            ->map(fn($h) => $h->negotiations->first()?->hauling_rate_per_kg)
                            ->filter()
                            ->map(fn($r) => (float) $r)
                            ->values();
                        $hasAgreedRates = $agreedRates->isNotEmpty();
                        $rate = $hasAgreedRates && $isCoop
                            ? $agreedRates->avg()
                            : (float) ($proposal->hauling_rate_per_kg ?? 0);
                        $totalKg = $proposal->harvests->sum(fn($h) => (float) ($h->pivot->quantity_kg ?? 0));
                        $acceptedCount = $proposal->harvests->filter(fn($h) => $h->pivot->status === 'accepted')->count();
                        $farmCount = $proposal->harvests->count();
                    @endphp

                    <x-haul-card
                        title="Job #{{ $proposal->id }}"
                        :subtitle="$proposal->truck->truck_name ?? 'Transport Partner'"
                        status="Awaiting Approval"
                        statusClass="bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border-[var(--color-warning-border)]"
                        :meta="[
                            $farmCount . ' farms',
                            number_format($totalKg) . ' kg',
                            '₱' . number_format($rate, 2) . '/kg',
                            '₱' . number_format($proposal->negotiated_price ?? $proposal->price_reference, 2),
                            $acceptedCount . '/' . $farmCount . ' accepted',
                        ]"
                    >
                        <x-slot:actions>
                            <a href="{{ route('pooling.cost-ledger', $proposal) }}"
                               class="w-full flex items-center justify-center gap-2 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 text-xs font-bold py-2.5 rounded-xl hover:border-brand dark:hover:border-brand hover:text-brand dark:hover:text-brand-light transition-all duration-200">
                                View Cost Ledger
                            </a>
                        </x-slot:actions>
                    </x-haul-card>
                @endforeach
            </div>
        @endif

        @if($readyForDispatch->isNotEmpty())
            <div class="mb-10">
                <div class="flex items-center gap-2 mb-5">
                    <span class="text-xs font-bold uppercase tracking-wider text-[var(--color-success-text)] dark:text-[var(--color-success-text-dark)]">Ready for Dispatch</span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400">(all farmers accepted)</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($readyForDispatch as $job)
                        <x-haul-card
                            title="Job #{{ $job->id }}"
                            :subtitle="$job->truck->truck_name ?? 'Transport Partner'"
                            status="Ready"
                            statusClass="bg-[var(--color-success-bg)] text-[var(--color-success-text)] border-[var(--color-success-border)]"
                            :meta="[
                                $job->farm_count . ' farms',
                                number_format($job->total_kg) . ' kg',
                                '₱' . number_format($job->negotiated_price ?? $job->price_reference, 2),
                            ]"
                            class="border-[var(--color-success-border)] dark:border-[var(--color-success-border-dark)]"
                        >
                            <p class="text-[10px] font-semibold text-slate-500 dark:text-slate-400">All farmers accepted. Assign a driver to begin the delivery run.</p>

                            <x-slot:actions>
                                <a href="{{ route('pooling.cost-ledger', $job) }}"
                                   class="w-full flex items-center justify-center gap-2 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 text-xs font-bold py-2.5 rounded-xl hover:border-brand dark:hover:border-brand hover:text-brand dark:hover:text-brand-light transition-all duration-200">
                                    View Cost Ledger
                                </a>
                            </x-slot:actions>
                        </x-haul-card>
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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($cancelledProposals as $proposal)
                        <x-haul-card
                            title="Job #{{ $proposal->id }}"
                            :subtitle="$proposal->truck->truck_name ?? 'Transport Partner'"
                            status="Cancelled"
                            statusClass="bg-[var(--color-error-bg)] text-[var(--color-error-text)] border-[var(--color-error-border)]"
                            muted
                        >
                            @forelse($proposal->harvests as $harvest)
                                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/30 px-3 py-2 rounded-xl">
                                    <span class="truncate">{{ $harvest->farmer->name ?? 'Farmer' }}</span>
                                    <span class="text-slate-400 font-mono shrink-0">{{ number_format($harvest->pivot->quantity_kg) }} kg</span>
                                </div>
                            @empty
                                <p class="text-xs text-slate-400 italic">No farmers attached</p>
                            @endforelse
                            <p class="text-[10px] font-mono text-slate-500 dark:text-slate-400">Cancelled {{ $proposal->updated_at->diffForHumans() }}</p>
                        </x-haul-card>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layout>
