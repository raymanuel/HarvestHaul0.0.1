<x-layout>
    <div class="w-full max-w-7xl mx-auto">

        <!-- Page Header -->
        <div class="mb-6">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 hover:text-slate-700 dark:hover:text-slate-400 transition">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Dashboard
            </a>
        </div>
        <header class="pt-8 mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">Route Offers</h1>
            </div>
        </header>

        <x-flash-success />
        <x-flash-error />

        @if(Auth::user()->farmerProfile?->affiliation_type !== 'cooperative')
            <!-- Tab Switcher -->
            <div class="flex gap-1 mb-8 bg-slate-100 dark:bg-slate-800/60 rounded-xl p-1 w-fit border border-slate-200/70 dark:border-slate-700/80">
                <a href="{{ route('farmer.proposals') }}"
                   class="px-5 py-2.5 rounded-xl text-sm font-bold transition {{ request()->routeIs('farmer.proposals') && !request()->has('tab') ? 'bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}">
                    Route Offers
                </a>
                <a href="{{ route('farmer.haul-requests') }}"
                   class="px-5 py-2.5 rounded-xl text-sm font-bold transition {{ request()->routeIs('farmer.haul-requests') ? 'bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}">
                    Haul Requests
                </a>
            </div>
        @endif

        @if($proposals->isEmpty())
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-12 text-center shadow-sm">
                <p class="text-slate-500 dark:text-slate-400 text-sm font-semibold">No route offers for your harvests yet.</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">When your cooperative plans a shared truck route that includes your crop, it will appear here.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($proposals as $proposal)
                    @php
                        $myHarvests = $proposal->harvests->where('user_id', Auth::id());
                        $myKg = (float) $myHarvests->sum(fn($h) => (float) ($h->pivot->quantity_kg ?? 0));
                        $flatRate = (float) ($proposal->hauling_rate_per_kg ?? 0);

                        // Derive per-harvest rates; pick first agreement's rate if available
                        $firstNeg = $myHarvests->first()?->negotiations?->firstWhere('status', 'COMPLETED');
                        $agreedRate = $firstNeg?->hauling_rate_per_kg !== null
                            ? (float) $firstNeg->hauling_rate_per_kg
                            : null;
                        $rate = $agreedRate ?? $flatRate;
                        $usesAgreedRate = $agreedRate !== null && $agreedRate > 0;

                        $yourCostShare = (float) $myHarvests->sum(fn($h) => (float) ($h->pivot->cost_share ?? 0));

                        // Overall status: accepted if ALL of this farmer's harvests are accepted,
                        // rejected if ALL are rejected, otherwise pending
                        $pivotStatuses = $myHarvests->pluck('pivot.status')->unique()->values();
                        $hasPending = $pivotStatuses->contains('pending');
                        $pivotStatus = $hasPending
                            ? 'pending'
                            : ($pivotStatuses->every(fn($s) => $s === 'accepted') ? 'accepted' : 'rejected');
                    @endphp
                    <x-haul-card
                        title="Proposal #{{ $proposal->id }}"
                        :subtitle="$proposal->logisticsProfile->company_name ?? 'Transport Coordinator'"
                        status="{{ $pivotStatus }}"
                        :statusClass="$pivotStatus === 'accepted'
                            ? 'bg-brand/10 text-brand dark:text-brand-light border-brand/10'
                            : ($pivotStatus === 'rejected'
                                ? 'bg-[var(--color-error-bg)] text-[var(--color-error-text)] border-[var(--color-error-border)]'
                                : 'bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border-[var(--color-warning-border)]')"
                        :meta="[
                            $myHarvests->count() . ' ' . Str::plural('crop', $myHarvests->count()),
                            number_format($myKg) . ' kg',
                            '₱' . number_format($rate, 2) . '/kg',
                            'Your share: ₱' . number_format($yourCostShare, 2),
                        ]"
                    >
                        <div class="space-y-2">
                            @forelse($myHarvests as $myH)
                                <div class="flex items-center justify-between gap-2 text-xs">
                                    <span class="font-bold text-slate-700 dark:text-slate-300 truncate">
                                        {{ $myH->crop?->name }}
                                        @if($myH->cropVariety?->name)
                                            <span class="font-medium text-slate-400">({{ $myH->cropVariety->name }})</span>
                                        @endif
                                    </span>
                                    <span class="font-mono font-semibold text-slate-500 dark:text-slate-400 shrink-0">{{ number_format((float) ($myH->pivot->quantity_kg ?? 0)) }} kg</span>
                                </div>
                            @empty
                                <p class="text-xs text-slate-400 italic">No crops found for this offer.</p>
                            @endforelse
                            <p class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 truncate">
                                Drop-off: <span class="text-slate-600 dark:text-slate-300">{{ $myHarvests->first()?->destination?->name ?? $myHarvests->first()?->destination_address ?? 'Wholesale Market' }}</span>
                            </p>
                        </div>

                        <x-slot:actions>
                            @if($hasPending)
                                <div class="flex gap-2">
                                    <form action="{{ route('pooling.accept', $proposal->id) }}" method="POST" class="flex-1">
                                        @csrf
                                        <button type="button" onclick="swalConfirm(this.closest('form'), {title:'Accept Proposal?', text:'Book this delivery proposal for your farm?', icon:'question', confirmText:'Yes, accept', cancelText:'Cancel', confirmColor:'#16283C'})" class="w-full bg-brand hover:bg-brand-dark text-white dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] text-xs font-bold py-2.5 rounded-xl transition cursor-pointer text-center">
                                            Accept
                                        </button>
                                    </form>
                                    <form action="{{ route('pooling.reject', $proposal->id) }}" method="POST" class="flex-1">
                                        @csrf
                                        <button type="button" onclick="swalConfirm(this.closest('form'), {title:'Reject Proposal?', text:'This delivery proposal will be turned down.', icon:'warning', confirmText:'Yes, reject', cancelText:'Cancel', confirmColor:'#ef4444'})" class="w-full bg-[var(--color-error-text)] hover:opacity-90 text-white text-xs font-bold py-2.5 rounded-xl transition cursor-pointer text-center">
                                            Reject
                                        </button>
                                    </form>
                                </div>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 italic">{{ $usesAgreedRate ? 'Your share is set by the hauling rate agreed in your negotiation chat.' : 'Your share is weighted by cargo volume × haul distance.' }}</p>
                            @elseif($pivotStatus === 'accepted')
                                <div class="p-3 bg-brand/10 dark:bg-brand/10 text-brand-dark dark:text-brand-light border border-brand/20 text-center rounded-xl text-xs font-bold">
                                    Accepted — awaiting other farmers.
                                </div>
                            @elseif($pivotStatus === 'rejected')
                                <div class="p-3 bg-[var(--color-error-bg)] text-[var(--color-error-text)] border border-[var(--color-error-border)] text-center rounded-xl text-xs font-bold">
                                    Rejected — crop returned to haul board.
                                </div>
                            @endif
                        </x-slot:actions>
                    </x-haul-card>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
