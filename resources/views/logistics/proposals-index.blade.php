<x-layout>
    <div class="w-full max-w-7xl mx-auto pb-12">
        <header class="pt-8 mb-6 border-b border-slate-200/80 dark:border-slate-700/80 pb-5">
            <span class="text-xs font-bold uppercase tracking-wider text-[#3A7D44] dark:text-[#3A7D44] bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 px-3 py-1.5 rounded-lg border border-[#3A7D44]/10 dark:border-[#3A7D44]/20 self-start">Proposal Control</span>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight heading-font mt-2">Proposal Inbox</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium">Manage negotiable delivery options and track farmer consensus channels.</p>
        </header>

        @if(session('success'))
            <div class="mb-6 bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 border border-[#3A7D44]/20 dark:border-[#3A7D44]/20 text-[#3A7D44] dark:text-[#3A7D44] rounded-xl px-5 py-4 text-sm font-semibold flex items-center gap-2 shadow-sm">
                <span>✅</span> {{ session('success') }}
            </div>
        @endif

        @if($proposals->isEmpty() && $cancelledProposals->isEmpty())
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-16 text-center shadow-sm">
                <p class="text-4xl mb-4">💬</p>
                <p class="text-slate-500 dark:text-slate-400 font-medium">No active delivery proposals open. Generate route pools from the Dispatch Console to open negotiation rooms.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                @foreach($proposals as $proposal)
                    @php
                        $hasCounter = false;
                        $totalKg = (float) $proposal->total_kg;
                        foreach ($proposal->harvests as $h) {
                            $originalShare = $proposal->price_reference * ((float) ($h->pivot->quantity_kg ?? 0) / max($totalKg, 1));
                            $currentShare = (float) ($h->pivot->cost_share ?? $originalShare);
                            if (abs($currentShare - $originalShare) > 0.01) {
                                $hasCounter = true;
                                break;
                            }
                        }
                    @endphp
                    <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6 flex flex-col justify-between hover:shadow-md transition">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-xs font-bold text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/20 border border-blue-200/50 dark:border-blue-900/30 px-2.5 py-1 rounded-lg font-mono">Job #{{ $proposal->id }}</span>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/20 px-2.5 py-1 rounded-lg border border-amber-200/40 dark:border-amber-800/40">Pending Consensus</span>
                            </div>

                            <h3 class="text-base font-bold text-slate-800 dark:text-slate-200 mb-1 heading-font">🚛 {{ $proposal->truck->truck_name ?? 'Fleet Hauler' }}</h3>
                            
                            <div class="space-y-1.5 mb-4 text-xs font-semibold">
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Base Reference:</span>
                                    <span class="text-slate-500 dark:text-slate-400">₱{{ number_format($proposal->price_reference, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Current Proposed Price:</span>
                                    <span class="text-[#3A7D44] dark:text-[#3A7D44] font-bold">₱{{ number_format($proposal->negotiated_price ?? $proposal->price_reference, 2) }}</span>
                                </div>
                            </div>

                            @if($hasCounter)
                                <div class="mb-4 p-3 bg-amber-50 dark:bg-amber-950/20 text-amber-800 dark:text-amber-400 border border-amber-250/20 rounded-xl text-xs leading-relaxed font-bold">
                                    ⚠️ Farmer Counter-Offer received: <b>₱{{ number_format($proposal->negotiated_price, 2) }}</b>
                                </div>
                            @endif

                            <div class="border-t border-slate-100 dark:border-slate-700/60 pt-4 space-y-2.5">
                                <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Farmer Approvals</p>
                                <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-1.5">
                                    @foreach($proposal->harvests as $harvest)
                                        <li class="flex items-center justify-between bg-slate-50 dark:bg-slate-900/40 px-3 py-2 rounded-xl border border-slate-100/50 dark:border-slate-700/20">
                                            <div class="flex items-center gap-2 truncate max-w-[150px]">
                                                <span class="font-medium text-slate-700 dark:text-slate-330">{{ $harvest->farmer->name ?? 'Farmer' }}</span>
                                                <span class="text-[8px] font-bold uppercase px-1.5 py-0.5 rounded border
                                                    @if($harvest->pivot->status === 'accepted') text-[#3A7D44] bg-[#3A7D44]/10 border-[#3A7D44]/10
                                                    @elseif($harvest->pivot->status === 'rejected') text-red-700 bg-red-50 border-red-500/10
                                                    @else text-amber-700 bg-amber-50 border-amber-500/10 @endif">
                                                    {{ $harvest->pivot->status }}
                                                </span>
                                            </div>
                                            <span class="font-bold text-[#3A7D44] dark:text-[#3A7D44] font-mono">{{ number_format($harvest->pivot->quantity_kg) }} kg</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <div class="mt-6 space-y-3">
                            {{-- Action buttons for counter proposal --}}
                            @if($hasCounter)
                                <div class="flex gap-2">
                                    <form action="{{ route('pooling.logistics-accept', $proposal->id) }}" method="POST" class="flex-1">
                                        @csrf
                                        <button type="submit" class="w-full bg-[#3A7D44] hover:bg-[#2E6336] text-white text-xs font-bold py-2.5 rounded-xl transition cursor-pointer text-center">
                                            Accept Bid
                                        </button>
                                    </form>
                                </div>
                            @endif

                            {{-- Counter Offer Form --}}
                            <form action="{{ route('pooling.logistics-counter', $proposal->id) }}" method="POST" class="pt-2 border-t border-slate-100 dark:border-slate-700">
                                @csrf
                                <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Submit New Route Bid</label>
                                <div class="flex gap-2">
                                    <input type="number" step="0.01" name="negotiated_price" required placeholder="New Bid (₱)"
                                        class="flex-1 px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-white font-mono focus:outline-none focus:border-[#3A7D44] focus:ring-1 focus:ring-[#3A7D44] transition" />
                                    <button type="submit" class="px-3 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl text-[10px] transition cursor-pointer">
                                        Send Bid
                                    </button>
                                </div>
                            </form>

                            <a href="{{ route('pooling.cost-ledger', $proposal) }}"
                               class="w-full flex items-center justify-center gap-2 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 text-xs font-bold py-2.5 rounded-xl hover:border-[#3A7D44] dark:hover:border-[#3A7D44] hover:text-[#3A7D44] dark:hover:text-[#3A7D44] transition-all duration-200">
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

        @if($cancelledProposals->isNotEmpty())
            <div class="mt-10 pt-8 border-t border-slate-200 dark:border-slate-700/60">
                <div class="flex items-center gap-2 mb-5">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Recently Cancelled</span>
                    <span class="text-[10px] text-slate-400 dark:text-slate-500">(last 24h)</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($cancelledProposals as $proposal)
                        <div class="bg-white dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/40 rounded-2xl shadow-sm p-6 opacity-70 hover:opacity-100 transition">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-xs font-bold text-slate-500 font-mono">Job #{{ $proposal->id }}</span>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/20 px-2.5 py-1 rounded-lg border border-red-200/40 dark:border-red-800/40">Cancelled</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mb-2">
                                🚛 {{ $proposal->truck->truck_name ?? 'Fleet Hauler' }}
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
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-2 font-mono">
                                Cancelled {{ $proposal->updated_at->diffForHumans() }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layout>
