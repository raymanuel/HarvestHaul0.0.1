{{--
    Logistics Proposals List View
    
    PURPOSE:
    This view renders the proposal inbox for logistics partners to track and manage 
    negotiations for pending multi-farmer pooling runs.
--}}
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

                        <div class="mt-6 flex flex-col gap-2.5">
                            <a href="{{ route('pooling.cost-ledger', $proposal) }}"
                               class="w-full flex items-center justify-center gap-2 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 text-sm font-bold py-3 rounded-xl hover:border-emerald-500 dark:hover:border-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-400 transition-all duration-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                View Cost Ledger
                            </a>
                            <button class="w-full bg-gradient-to-tr from-emerald-600 to-teal-500 dark:from-emerald-500 dark:to-teal-400 text-white text-sm font-bold py-3.5 rounded-xl border border-emerald-600/20 dark:border-emerald-400/25 shadow-md shadow-emerald-600/15 hover:shadow-lg hover:shadow-emerald-600/25 hover:translate-y-[-1px] active:translate-y-0 transition-all duration-200">
                                💬 Open Chat Room Threads
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
