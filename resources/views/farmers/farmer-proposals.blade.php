{{--
    Farmer Proposals List View (Negotiation Hub)
    
    PURPOSE:
    Allows farmers to review pending pooling proposals containing their active harvest lots.
    Displays the fair proportional cost share estimated for their portion of the load.
    
    FORMULA:
    Farmer Cost Share = (Farmer Harvest quantity_kg / Total Job total_kg) * Suggested Job Price Reference
--}}
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
                    <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-5 flex flex-col justify-between hover:shadow-md hover:border-emerald-500 dark:hover:border-emerald-500/50 transition duration-200 group">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-250/20 dark:border-emerald-500/10 px-2.5 py-1 rounded-lg uppercase tracking-wider font-mono">
                                    Proposal #{{ $proposal->id }}
                                </span>
                                <span class="text-[10px] font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/20 border border-amber-250/20 dark:border-amber-800/20 px-2.5 py-0.5 rounded-lg uppercase tracking-wide">
                                    Pending Review
                                </span>
                            </div>

                            <div class="mb-4">
                                <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Logistics Operator</h4>
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-0.5 flex items-center gap-1.5">
                                    <span>🏢</span> {{ $proposal->logisticsProfile->company_name ?? 'Independent Fleet Coordinator' }}
                                </p>
                            </div>

                            <div class="mb-4 bg-slate-50 dark:bg-slate-900/40 rounded-xl p-4 border border-slate-100 dark:border-slate-800/60">
                                <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2.5">Your Included Cargo</h4>
                                @foreach($proposal->harvests as $harvest)
                                    <div class="space-y-1">
                                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                            🌾 {{ $harvest->crop->name }}
                                            <span class="text-xs font-normal text-slate-400 dark:text-slate-500">({{ $harvest->cropVariety->name ?? 'Standard' }})</span>
                                        </p>
                                        <p class="text-xs text-slate-650 dark:text-slate-400">⚖️ Quantity: <b class="font-bold text-slate-800 dark:text-slate-300">{{ number_format($harvest->pivot->quantity_kg) }} kg</b></p>
                                        <p class="text-xs text-slate-650 dark:text-slate-400 truncate">📦 Target Drop-off: <b class="font-bold text-slate-800 dark:text-slate-300">{{ $harvest->destination->name ?? 'Wholesale Market' }}</b></p>
                                    </div>

                                    @php
                                        // Perfectly synchronized cost distribution ratio calculated dynamically
                                        $weightProportion = $proposal->total_kg > 0 ? ($harvest->pivot->quantity_kg / $proposal->total_kg) : 0;
                                        $yourCostShare = $proposal->price_reference * $weightProportion;
                                    @endphp
                                @endforeach
                            </div>

                            <div class="border-t border-slate-100 dark:border-slate-700/60 pt-3.5 flex justify-between items-center">
                                <div>
                                    <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Your Fair Cost Share</h4>
                                    <p class="text-lg font-extrabold text-emerald-600 dark:text-emerald-400 mt-0.5">₱{{ number_format($yourCostShare, 2) }}</p>
                                </div>
                                <div class="text-right">
                                    <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Vehicle Capacity</h4>
                                    <p class="text-xs text-slate-600 dark:text-slate-400 font-bold mt-1.5">🚛 {{ round($proposal->truck_capacity_kg) }} kg Max</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                            <button class="w-full bg-slate-800 dark:bg-slate-700 text-white text-xs font-bold py-3 rounded-xl hover:bg-slate-900 dark:hover:bg-slate-600 transition flex items-center justify-center gap-1.5 cursor-pointer shadow-sm">
                                💬 Open Negotiation Room Thread
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
