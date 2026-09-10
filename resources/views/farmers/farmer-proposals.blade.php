<x-layout>
    <div class="w-full max-w-7xl mx-auto">

        <!-- Page Header -->
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
                    <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-5 flex flex-col justify-between hover:shadow-md hover:border-brand dark:hover:border-brand/50 transition duration-200 group">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-[10px] font-bold text-brand dark:text-brand-light bg-brand/10 dark:bg-brand/10 border border-brand/20 dark:border-brand/10 px-2.5 py-1 rounded-md uppercase tracking-wider font-mono">
                                    Proposal #{{ $proposal->id }}
                                </span>
                                <span class="text-[10px] font-bold uppercase tracking-wide px-2.5 py-0.5 rounded-md border
                                    @if($pivotStatus === 'accepted') text-brand bg-brand/10 dark:bg-brand/10 dark:text-brand-light border-brand/10
                                    @elseif($pivotStatus === 'rejected') text-[var(--color-error-text)] bg-[var(--color-error-bg)] border-[var(--color-error-border)]
                                    @else text-[var(--color-warning-text)] bg-[var(--color-warning-bg)] border-[var(--color-warning-border)] @endif">
                                    {{ $pivotStatus }}
                                </span>
                            </div>

                            <div class="mb-4">
                                <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-600 uppercase tracking-wider">Logistics Operator</h4>
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-0.5 flex items-center gap-1.5">
                                    {{ $proposal->logisticsProfile->company_name ?? 'Independent Fleet Coordinator' }}
                                </p>
                            </div>

                            <div class="mb-4 bg-slate-50 dark:bg-slate-900/40 rounded-xl p-4 border border-slate-100 dark:border-slate-800/60">
                                <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-600 uppercase tracking-wider mb-2.5">Your Included Cargo</h4>
                                @forelse($myHarvests as $myH)
                                    <div class="{{ !$loop->first ? 'mt-2.5 pt-2.5 border-t border-slate-200/60 dark:border-slate-700/40' : '' }}">
                                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                            {{ $myH->crop?->name }}
                                            <span class="text-xs font-normal text-slate-405 dark:text-slate-500">({{ $myH->cropVariety?->name ?? 'Standard' }})</span>
                                        </p>
                                        <p class="text-xs text-slate-700 dark:text-slate-400">Quantity: <b class="font-bold text-slate-800 dark:text-slate-300">{{ number_format((float) ($myH->pivot->quantity_kg ?? 0)) }} kg</b></p>
                                        <p class="text-xs text-slate-700 dark:text-slate-400 truncate">Target Drop-off: <b class="font-bold text-slate-800 dark:text-slate-300">{{ $myH->destination?->name ?? $myH->destination_address ?? 'Wholesale Market' }}</b></p>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400 dark:text-slate-500 italic">No crops found for this offer.</p>
                                @endforelse
                            </div>

                            <div class="space-y-2 mb-4 bg-slate-50 dark:bg-slate-900/40 rounded-xl p-4 border border-slate-100 dark:border-slate-800/60 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-slate-400">{{ $usesAgreedRate ? 'Agreed Hauling Rate' : 'Flat Hauling Rate' }}:</span>
                                    <span class="font-bold text-slate-700 dark:text-slate-300">₱{{ number_format($rate, 2) }}/kg</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Your Total Cargo Weight:</span>
                                    <span class="font-bold text-slate-700 dark:text-slate-300">{{ number_format($myKg) }} kg</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400">{{ $usesAgreedRate ? 'Your Share (rate × kg):' : 'Your Share (weight × distance):' }}</span>
                                    <span class="font-bold text-brand dark:text-brand-light">₱{{ number_format($yourCostShare, 2) }}</span>
                                </div>
                            </div>

                            <div class="border-t border-slate-100 dark:border-slate-700/60 pt-3.5 flex justify-between items-center">
                                <div>
                                    <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-600 uppercase tracking-wider">Your Hauling Cost</h4>
                                    <p class="text-lg font-extrabold text-brand dark:text-brand-light mt-0.5">₱{{ number_format($yourCostShare, 2) }}</p>
                                </div>
                                <div class="text-right">
                                    <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-600 uppercase tracking-wider">Total Route Price</h4>
                                    <p class="text-xs text-slate-700 dark:text-slate-400 font-bold mt-1">₱{{ number_format($proposal->negotiated_price ?? $proposal->price_reference, 2) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-700/60 space-y-3">
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

                                <p class="text-[9px] text-slate-500 dark:text-slate-400 mt-2 italic">{{ $usesAgreedRate ? 'Your share is set by the hauling rate agreed in your negotiation chat.' : 'Your share is weighted by cargo volume × haul distance.' }}</p>
                            @elseif($pivotStatus === 'accepted')
                                <div class="p-3 bg-brand/10 dark:bg-brand/10 text-brand-dark dark:text-brand-light border border-brand/20 text-center rounded-xl text-xs font-bold">
                                    Accepted — awaiting other farmers.
                                </div>
                            @elseif($pivotStatus === 'rejected')
                                <div class="p-3 bg-[var(--color-error-bg)] text-[var(--color-error-text)] border border-[var(--color-error-border)] text-center rounded-xl text-xs font-bold">
                                    Rejected — crop returned to haul board.
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
