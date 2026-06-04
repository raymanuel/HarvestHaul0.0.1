<x-layout>
    <div class="w-full max-w-7xl mx-auto pb-12">
        <header class="pt-8 mb-6 border-b border-slate-200/80 dark:border-slate-700/80 pb-5">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 px-3 py-1.5 rounded-lg border border-emerald-500/10 dark:border-emerald-500/20 self-start">Proposal Control</span>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight heading-font mt-2">Proposal Inbox</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium">Manage negotiable delivery options and track farmer consensus channels.</p>
        </header>

        @if($proposals->isEmpty())
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-16 text-center shadow-sm">
                <p class="text-4xl mb-4">💬</p>
                <p class="text-slate-500 dark:text-slate-400 font-medium">No active delivery proposals open. Generate route pools from the Dispatch Console to open negotiation rooms.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                @foreach($proposals as $proposal)
                    <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6 flex flex-col justify-between hover:shadow-md transition">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-xs font-bold text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/20 border border-blue-200/50 dark:border-blue-900/30 px-2.5 py-1 rounded-lg font-mono">Job #{{ $proposal->id }}</span>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/20 px-2.5 py-1 rounded-lg border border-amber-200/40 dark:border-amber-800/40">Pending Negotiation</span>
                            </div>

                            <h3 class="text-base font-bold text-slate-800 dark:text-slate-200 mb-1 heading-font">🚛 {{ $proposal->truck->truck_name ?? 'Fleet Hauler' }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-mono mb-4">Base reference: ₱{{ number_format($proposal->price_reference, 2) }}</p>

                            <div class="border-t border-slate-100 dark:border-slate-700/60 pt-4 space-y-2.5">
                                <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Pooled Harvest Manifest</p>
                                <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-1.5">
                                    @foreach($proposal->harvests as $harvest)
                                        <li class="flex items-center justify-between bg-slate-50 dark:bg-slate-900/40 px-3 py-2 rounded-xl border border-slate-100/50 dark:border-slate-700/20">
                                            <span class="font-medium truncate max-w-[150px] text-slate-700 dark:text-slate-300">{{ $harvest->farmer->name ?? 'Farmer' }}</span>
                                            <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">{{ number_format($harvest->pivot->quantity_kg) }} kg</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <button class="w-full mt-6 bg-gradient-to-tr from-emerald-600 to-teal-500 dark:from-emerald-500 dark:to-teal-400 text-white dark:text-white text-sm font-bold py-3.5 rounded-xl border border-emerald-600/20 dark:border-emerald-400/25 shadow-md shadow-emerald-600/15 dark:shadow-emerald-900/30 hover:shadow-lg hover:shadow-emerald-600/25 dark:hover:shadow-emerald-400/30 hover:translate-y-[-1px] active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:focus:ring-emerald-400/40 transition-all duration-200" style="background-color: #059669; text-shadow: 0 1px 2px rgba(0,0,0,0.15);">
                            💬 Open Chat Room Threads
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
