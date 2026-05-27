<x-layout>
    <div class="w-full py-8">
        <header class="mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">🤝 Pooling Proposals</h1>
            <p class="text-gray-500 text-lg">Review pooled transportation offers and pricing splits from logistics partners.</p>
        </header>

        @if($proposals->isEmpty())
            <div class="bg-white border border-gray-200 rounded-xl p-12 text-center shadow-sm">
                <p class="text-gray-400 italic text-base">No active pooling proposals found for your harvests yet. When a logistics coordinator bundles your load, it will appear here.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($proposals as $proposal)
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 flex flex-col justify-between hover:border-gray-300 transition">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-xs font-bold text-green-700 bg-green-50 border border-green-200 px-2.5 py-1 rounded-full font-mono">
                                    Proposal #{{ $proposal->id }}
                                </span>
                                <span class="text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-100 px-2.5 py-0.5 rounded">
                                    Pending Review
                                </span>
                            </div>

                            <div class="mb-4">
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wide">Logistics Operator</h4>
                                <p class="text-sm font-semibold text-gray-800 mt-0.5">
                                    🏢 {{ $proposal->logisticsProfile->company_name ?? 'Independent Fleet Coordinator' }}
                                </p>
                            </div>

                            <div class="mb-4 bg-gray-50 rounded-lg p-3 border border-gray-100">
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">Your Included Cargo</h4>
                                @foreach($proposal->harvests as $harvest)
                                    <div class="space-y-1">
                                        <p class="text-sm font-bold text-slate-800">
                                            🌾 {{ $harvest->crop->name }}
                                            <span class="text-xs font-normal text-gray-500">({{ $harvest->cropVariety->name ?? 'Standard' }})</span>
                                        </p>
                                        <p class="text-xs text-gray-600">⚖️ Quantity: <b>{{ number_format($harvest->pivot->quantity_kg) }} kg</b></p>
                                        <p class="text-xs text-gray-600 truncate">📦 Target Drop-off: <b>{{ $harvest->destination->name ?? 'Wholesale Market' }}</b></p>
                                    </div>

                                    @php
                                        // Perfectly synchronized cost distribution ratio calculated dynamically
                                        $weightProportion = $proposal->total_kg > 0 ? ($harvest->pivot->quantity_kg / $proposal->total_kg) : 0;
                                        $yourCostShare = $proposal->price_reference * $weightProportion;
                                    @endphp
                                @endforeach
                            </div>

                            <div class="border-t border-gray-100 pt-3 flex justify-between items-center">
                                <div>
                                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wide">Your Fair Cost Share</h4>
                                    <p class="text-xl font-black text-green-700 mt-0.5">₱{{ number_format($yourCostShare, 2) }}</p>
                                </div>
                                <div class="text-right">
                                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wide">Vehicle Capacity</h4>
                                    <p class="text-xs text-gray-600 font-medium mt-1">🚛 {{ round($proposal->truck_capacity_kg) }} kg Max</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-gray-100">
                            <button class="w-full bg-slate-800 text-white text-xs font-bold py-2.5 rounded-lg hover:bg-black transition flex items-center justify-center gap-1">
                                💬 Open Negotiation Room Thread
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
