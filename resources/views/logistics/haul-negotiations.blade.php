<x-layout>
    <div class="w-full max-w-7xl mx-auto">

        <header class="pt-8 mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">Haul Negotiations</h1>
            </div>
        </header>

        <x-flash-success />
        <x-flash-error />

        @if($intents->isEmpty())
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-12 text-center shadow-sm">
                <p class="text-slate-500 dark:text-slate-400 text-sm font-semibold">No haul intents yet.</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">Find available harvests on the route-optimization map and express intent to haul them.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($intents as $intent)
                    @php $harvest = $intent->haulRequest?->harvest; @endphp
                    <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                    {{ $harvest?->crop?->name ?? '—' }}
                                </h3>
                                <span class="text-[10px] font-bold uppercase tracking-wide px-2.5 py-0.5 rounded-lg border
                                    @if($intent->status === 'pending') text-amber-700 bg-amber-50 dark:bg-amber-950/20 border-amber-500/10
                                    @elseif($intent->status === 'agreed') text-[#16283C] dark:text-[#D7BC7A] bg-[#16283C]/10 dark:bg-[#16283C]/10 border-[#16283C]/10 dark:border-[#16283C]/20
                                    @elseif($intent->status === 'accepted') text-purple-700 bg-purple-50 dark:bg-purple-950/20 border-purple-500/10
                                    @elseif($intent->status === 'declined') text-red-600 bg-red-50 dark:bg-red-950/20 border-red-500/10
                                    @else text-slate-500 bg-slate-50 dark:bg-slate-900/40 border-slate-300/50 @endif">
                                    {{ $intent->status }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                Farmer: <strong>{{ $intent->haulRequest?->farmer?->name ?? '—' }}</strong>
                                 Volume: {{ number_format($harvest?->quantity_kg ?? 0) }} kg
                                @if($intent->hauling_rate_php_per_kg)
                                     Agreed: ₱{{ number_format((float) $intent->hauling_rate_php_per_kg, 2) }}/kg
                                @elseif($intent->offer_rate_php_per_kg)
                                     Offer: ₱{{ number_format((float) $intent->offer_rate_php_per_kg, 2) }}/kg
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($intent->haulRequest?->status === 'booked' && $intent->status === 'accepted')
                                <span class="text-xs font-bold text-purple-700 bg-purple-50 dark:bg-purple-950/20 px-3 py-2 rounded-xl border border-purple-200/50 dark:border-purple-900/30">Booked — build the route on the map</span>
                            @else
                                <a href="{{ route('haul-negotiations.room', $intent->id) }}"
                                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#16283C] hover:bg-[#0E1620] text-white text-xs font-bold rounded-xl transition cursor-pointer">
                                    Open Chat
                                </a>
                            @endif
                            <a href="{{ route('route.optimization') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl transition cursor-pointer">
                                Route Map
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
