<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">

    <!-- Ambient glow decoration -->
    <div class="absolute top-0 right-1/4 w-96 h-96 rounded-full bg-violet-500/5 blur-[120px] pointer-events-none z-0"></div>
    <div class="absolute top-1/3 left-1/3 w-[500px] h-[500px] rounded-full bg-indigo-500/5 blur-[150px] pointer-events-none z-0"></div>

    <div class="relative z-10">
        <!-- Page Header -->
        <header class="mb-8 pt-6">
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('dashboard') }}" class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1">
                    ← Dashboard
                </a>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-violet-600 dark:text-violet-400 bg-violet-500/10 dark:bg-violet-400/10 px-3 py-1 rounded-full border border-violet-500/20">Deals Workspace</span>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight heading-font mt-3">My B2B Negotiations</h1>
                    <p class="text-sm text-slate-505 dark:text-slate-400 mt-1 font-medium">Trace ongoing crop pricing offers, message logs, and close transaction deals.</p>
                </div>
            </div>
        </header>

        <!-- Negotiations List -->
        @if($negotiations->isEmpty())
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-12 text-center">
                
                <h3 class="text-lg font-bold text-slate-800 dark:text-white heading-font">No Negotiations Found</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto">You have not started any crop purchase deals yet. Head to the Crop Board to find fresh harvests.</p>
                <div class="mt-6">
                    <a href="{{ route('buyer.crop-board') }}" class="inline-flex items-center justify-center px-5 py-3 bg-violet-600 hover:bg-violet-700 dark:bg-violet-500 dark:hover:bg-violet-600 text-white font-bold rounded-xl text-xs transition duration-200 shadow-sm shadow-violet-500/10 cursor-pointer">
                        Browse Crop Board
                    </a>
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b border-slate-150 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/30">
                                <th class="p-5 text-[10px] font-bold text-slate-400 dark:text-slate-505 uppercase tracking-widest">Crop / Lot</th>
                                <th class="p-5 text-[10px] font-bold text-slate-400 dark:text-slate-550 uppercase tracking-widest">Farmer</th>
                                <th class="p-5 text-[10px] font-bold text-slate-400 dark:text-slate-550 uppercase tracking-widest text-right">Negotiated Price</th>
                                <th class="p-5 text-[10px] font-bold text-slate-400 dark:text-slate-550 uppercase tracking-widest text-right">Quantity</th>
                                <th class="p-5 text-[10px] font-bold text-slate-400 dark:text-slate-550 uppercase tracking-widest text-center">Status</th>
                                <th class="p-5 text-[10px] font-bold text-slate-400 dark:text-slate-550 uppercase tracking-widest text-center">Workspace</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100/50 dark:divide-slate-700/30">
                            @foreach($negotiations as $negotiation)
                                <tr class="group hover:bg-slate-50/30 dark:hover:bg-slate-900/20 transition duration-150">
                                    <td class="p-5 whitespace-nowrap">
                                        <div class="font-bold text-slate-800 dark:text-slate-200 text-xs">
                                            {{ $negotiation->harvest->crop->name ?? $negotiation->harvest->crop_type ?? 'Unknown Crop' }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-505 mt-0.5">
                                            Product #{{ $negotiation->harvest_id }} • {{ $negotiation->harvest->cropVariety->name ?? $negotiation->harvest->variety ?? 'Standard' }}
                                        </div>
                                    </td>
                                    <td class="p-5 text-slate-700 dark:text-slate-350 text-xs font-bold">
                                        {{ $negotiation->farmer->name ?? 'Farmer' }}
                                    </td>
                                    <td class="p-5 text-right font-mono font-extrabold text-slate-800 dark:text-white text-xs">
                                        ₱{{ number_format($negotiation->offered_price, 2) }} / kg
                                    </td>
                                    <td class="p-5 text-right font-mono font-bold text-slate-500 dark:text-slate-400 text-xs">
                                        {{ number_format($negotiation->quantity_kg) }} kg
                                    </td>
                                    <td class="p-5 text-center whitespace-nowrap">
                                        <span class="text-[9px] font-extrabold uppercase tracking-widest px-2.5 py-1 rounded border
                                            @if($negotiation->status === 'OPEN') text-violet-750 bg-violet-500/10 border-violet-500/10
                                            @elseif($negotiation->status === 'AGREED') text-[#3A7D44] bg-[#3A7D44]/10 border-[#3A7D44]/10
                                            @elseif($negotiation->status === 'COMPLETED') text-[#1F4D25] bg-[#1F4D25]/10 border-[#1F4D25]/10
                                            @else text-slate-500 bg-slate-500/10 border-slate-500/10 @endif">
                                            {{ $negotiation->status }}
                                        </span>
                                    </td>
                                    <td class="p-5 text-center whitespace-nowrap">
                                        <a href="{{ route('negotiations.room', $negotiation->id) }}" class="inline-flex items-center gap-1 text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline">
                                            Enter Room <span>→</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination Links -->
            <div class="mt-6">
                {{ $negotiations->links() }}
            </div>
        @endif
    </div>

</div>
</x-layout>
