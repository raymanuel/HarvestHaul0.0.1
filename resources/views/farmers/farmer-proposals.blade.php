<x-layout>
    <div class="w-full max-w-7xl mx-auto">
        
        <!-- Page Header -->
        <header class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">Pooling Proposals</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium font-semibold">Review pooled transportation offers and pricing splits from logistics partners</p>
            </div>
            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 px-3 py-1.5 rounded-lg border border-emerald-500/10 dark:border-emerald-500/20 self-start">Negotiation Hub</span>
        </header>

        @if(session('success'))
            <div class="mb-6 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 rounded-xl px-5 py-4 text-sm font-semibold flex items-center gap-2 shadow-sm">
                <span>✅</span> {{ session('success') }}
            </div>
        @endif

        @if($proposals->isEmpty())
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-12 text-center shadow-sm">
                <svg class="w-10 h-10 text-slate-300 dark:text-slate-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <p class="text-slate-400 dark:text-slate-500 text-sm font-semibold">No active pooling proposals found for your harvests yet.</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 leading-relaxed">When a logistics coordinator bundles your load, it will appear here.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($proposals as $proposal)
                    @php
                        $myHarvest = $proposal->harvests->first();
                        $pivotStatus = $myHarvest?->pivot?->status ?? 'pending';
                        $totalKg = (float) $proposal->total_kg;
                        $myKg = (float) ($myHarvest?->pivot?->quantity_kg ?? 0);
                        $proportion = $totalKg > 0 ? ($myKg / $totalKg) : 0;
                        $yourCostShare = ($proposal->negotiated_price ?? $proposal->price_reference) * $proportion;
                    @endphp
                    <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-5 flex flex-col justify-between hover:shadow-md hover:border-emerald-500 dark:hover:border-emerald-500/50 transition duration-200 group">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-250/20 dark:border-emerald-500/10 px-2.5 py-1 rounded-lg uppercase tracking-wider font-mono">
                                    Proposal #{{ $proposal->id }}
                                </span>
                                <span class="text-[10px] font-bold uppercase tracking-wide px-2.5 py-0.5 rounded-lg border
                                    @if($pivotStatus === 'accepted') text-emerald-700 bg-emerald-50 dark:bg-emerald-950/20 border-emerald-500/10
                                    @elseif($pivotStatus === 'rejected') text-red-700 bg-red-50 dark:bg-red-950/20 border-red-500/10
                                    @else text-amber-700 bg-amber-50 dark:bg-amber-950/20 border-amber-500/10 @endif">
                                    {{ $pivotStatus }}
                                </span>
                            </div>

                            <div class="mb-4">
                                <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-550 uppercase tracking-wider">Logistics Operator</h4>
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-0.5 flex items-center gap-1.5">
                                    <span>🏢</span> {{ $proposal->logisticsProfile->company_name ?? 'Independent Fleet Coordinator' }}
                                </p>
                            </div>

                            <div class="mb-4 bg-slate-50 dark:bg-slate-900/40 rounded-xl p-4 border border-slate-100 dark:border-slate-800/60">
                                <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-550 uppercase tracking-wider mb-2.5">Your Included Cargo</h4>
                                <div class="space-y-1">
                                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                        🌾 {{ $myHarvest?->crop?->name }}
                                        <span class="text-xs font-normal text-slate-405 dark:text-slate-500">({{ $myHarvest?->cropVariety?->name ?? 'Standard' }})</span>
                                    </p>
                                    <p class="text-xs text-slate-650 dark:text-slate-400">⚖️ Quantity: <b class="font-bold text-slate-800 dark:text-slate-300">{{ number_format($myKg) }} kg</b></p>
                                    <p class="text-xs text-slate-650 dark:text-slate-400 truncate">📦 Target Drop-off: <b class="font-bold text-slate-800 dark:text-slate-300">{{ $myHarvest?->destination?->name ?? $myHarvest?->destination_address ?? 'Wholesale Market' }}</b></p>
                                </div>
                            </div>

                            <div class="space-y-2 mb-4 bg-slate-50 dark:bg-slate-900/40 rounded-xl p-4 border border-slate-100 dark:border-slate-800/60 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Estimated Cost Split:</span>
                                    <span class="font-bold text-slate-700 dark:text-slate-300">₱{{ number_format(($proposal->price_reference * $proportion), 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Current Proposed Cost:</span>
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400">₱{{ number_format($yourCostShare, 2) }}</span>
                                </div>
                            </div>

                            <div class="border-t border-slate-100 dark:border-slate-700/60 pt-3.5 flex justify-between items-center">
                                <div>
                                    <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-550 uppercase tracking-wider">Your Price Share</h4>
                                    <p class="text-lg font-extrabold text-emerald-600 dark:text-emerald-400 mt-0.5">₱{{ number_format($yourCostShare, 2) }}</p>
                                </div>
                                <div class="text-right">
                                    <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-550 uppercase tracking-wider">Total Route Price</h4>
                                    <p class="text-xs text-slate-700 dark:text-slate-350 font-bold mt-1">₱{{ number_format($proposal->negotiated_price ?? $proposal->price_reference, 2) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-700/60 space-y-3">
                            @if($pivotStatus === 'pending')
                                <div class="flex gap-2">
                                    <form action="{{ route('pooling.accept', $proposal->id) }}" method="POST" class="flex-1">
                                        @csrf
                                        <button type="submit" class="w-full bg-emerald-650 hover:bg-emerald-700 text-white text-xs font-bold py-2.5 rounded-xl transition cursor-pointer text-center">
                                            Accept
                                        </button>
                                    </form>
                                    <form action="{{ route('pooling.reject', $proposal->id) }}" method="POST" class="flex-1">
                                        @csrf
                                        <button type="submit" class="w-full bg-red-650 hover:bg-red-700 text-white text-xs font-bold py-2.5 rounded-xl transition cursor-pointer text-center">
                                            Reject
                                        </button>
                                    </form>
                                </div>

                                {{-- Counter Propose Form --}}
                                <form action="{{ route('pooling.counter', $proposal->id) }}" method="POST" class="pt-2 border-t border-slate-100 dark:border-slate-750">
                                    @csrf
                                    <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Counter Overall Route Price</label>
                                    <div class="flex gap-2">
                                        <input type="number" step="0.01" name="counter_price" required placeholder="New Total Haul Price (₱)"
                                            class="flex-1 px-3 py-2 bg-slate-50 dark:bg-slate-750 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-white font-mono focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition" />
                                        <button type="submit" class="px-3 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl text-[10px] transition cursor-pointer">
                                            Submit Bid
                                        </button>
                                    </div>
                                </form>
                            @elseif($pivotStatus === 'accepted')
                                <div class="p-3 bg-emerald-50 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-400 border border-emerald-250/20 text-center rounded-xl text-xs font-bold">
                                    ✓ Accepted. Awaiting other farmers' consensus.
                                </div>
                            @elseif($pivotStatus === 'rejected')
                                <div class="p-3 bg-red-50 dark:bg-red-950/20 text-red-800 dark:text-red-400 border border-red-250/20 text-center rounded-xl text-xs font-bold">
                                    ❌ Rejected. Crop returned to haul board.
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
