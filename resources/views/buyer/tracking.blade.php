<x-layout>
    <div class="w-full max-w-5xl mx-auto pb-12">

        {{-- Header --}}
        <header class="mb-8 pt-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">
                        Delivery Tracking
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">
                        Monitor incoming shipments and confirm receipt of delivered goods
                    </p>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wider text-sky-700 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/20 px-3 py-1.5 rounded-lg border border-sky-500/10 dark:border-sky-500/20 self-start">
                    Buyer Portal
                </span>
            </div>
        </header>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200/60 text-emerald-800 text-xs font-bold heading-font rounded-xl px-4 py-3 mb-6">
                ✅ {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-rose-50 border border-rose-200/60 text-rose-800 text-xs font-bold heading-font rounded-xl px-4 py-3 mb-6">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        {{-- Active Deliveries --}}
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Active Deliveries</h2>
            <span class="w-32 h-px bg-slate-200 dark:bg-slate-700/80"></span>
        </div>

        @if($activeDeliveries->isEmpty())
            <div class="bg-white dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl p-10 text-center shadow-sm mb-10">
                <p class="text-slate-400 text-sm font-semibold">No active deliveries at this time.</p>
                <p class="text-slate-400/60 text-xs mt-1">When sellers dispatch your purchases, they'll appear here.</p>
            </div>
        @else
            <div class="space-y-4 mb-10">
                @foreach($activeDeliveries as $delivery)
                    <div class="bg-white dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition">
                        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 dark:border-slate-700/60">
                            <div>
                                <p class="text-sm font-bold text-slate-800 dark:text-white heading-font">
                                    Route #{{ $delivery->id }}
                                </p>
                                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">
                                    🚛 {{ $delivery->truck->plate_number ?? '—' }}
                                    · Driver: {{ $delivery->driver->name ?? '—' }}
                                </p>
                            </div>
                            @php
                                $statusBadge = match($delivery->status) {
                                    'in_progress'             => ['bg-sky-50 text-sky-700 border-sky-200/50 dark:bg-sky-950/30 dark:text-sky-400', 'In Transit'],
                                    'awaiting_confirmation'   => ['bg-amber-50 text-amber-700 border-amber-200/50 dark:bg-amber-950/30 dark:text-amber-400', 'Awaiting Your Confirmation'],
                                    default                   => ['bg-slate-50 text-slate-500 border-slate-200/50', ucfirst($delivery->status)],
                                };
                            @endphp
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-md border {{ $statusBadge[0] }}">
                                {{ $statusBadge[1] }}
                            </span>
                        </div>

                        <div class="px-6 py-4">
                            {{-- Cargo Summary --}}
                            <div class="space-y-2 mb-4">
                                @foreach($delivery->harvests as $harvest)
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-slate-500 dark:text-slate-400 font-semibold">
                                            🌾 {{ $harvest->crop->name ?? $harvest->crop_type ?? '—' }}
                                        </span>
                                        <span class="text-slate-700 dark:text-slate-300 font-bold bg-slate-100 dark:bg-slate-700/50 px-2 py-0.5 rounded-md">
                                            {{ number_format($harvest->quantity_kg, 1) }} kg
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Last Known Position --}}
                            @if($delivery->latestTracking)
                                <div class="bg-slate-50 dark:bg-slate-900/30 border border-slate-100 dark:border-slate-700/50 rounded-xl p-3 mb-4">
                                    <div class="flex items-center gap-2 text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Last Known Position
                                    </div>
                                    <p class="text-xs text-slate-600 dark:text-slate-300 font-mono">
                                        {{ $delivery->latestTracking->latitude }}, {{ $delivery->latestTracking->longitude }}
                                    </p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">
                                        Updated {{ $delivery->latestTracking->posted_at->diffForHumans() }}
                                    </p>
                                </div>
                            @endif

                            {{-- Confirm Receipt Action --}}
                            @if($delivery->status === 'awaiting_confirmation')
                                <form method="POST" action="{{ route('buyer.confirm-receipt', $delivery) }}">
                                    @csrf
                                    <button type="submit"
                                        class="w-full py-3 bg-gradient-to-tr from-emerald-600 to-teal-500 hover:shadow-emerald-600/10 rounded-xl text-xs font-bold text-white transition-all shadow-md active:scale-[0.98] cursor-pointer">
                                        ✅ Confirm Receipt — I received this delivery
                                    </button>
                                </form>
                            @else
                                <div class="text-[10px] text-slate-400 italic text-center py-2">
                                    Delivery is in transit. You'll be notified when it arrives.
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Completed Deliveries --}}
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Completed Deliveries</h2>
            <span class="w-32 h-px bg-slate-200 dark:bg-slate-700/80"></span>
        </div>

        @if($completedDeliveries->isEmpty())
            <div class="bg-white dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl p-8 text-center shadow-sm">
                <p class="text-slate-400 text-sm font-semibold">No completed deliveries yet.</p>
            </div>
        @else
            <div class="bg-white dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl overflow-hidden shadow-sm">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900/30 border-b border-slate-100 dark:border-slate-700/60">
                            <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Route</th>
                            <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Cargo</th>
                            <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Truck</th>
                            <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40">
                        @foreach($completedDeliveries as $delivery)
                            <tr class="hover:bg-slate-50/40 dark:hover:bg-slate-900/10 transition">
                                <td class="px-5 py-3 font-bold text-slate-700 dark:text-slate-300">#{{ $delivery->id }}</td>
                                <td class="px-5 py-3 text-slate-500">
                                    @foreach($delivery->harvests as $h)
                                        {{ $h->crop->name ?? '—' }}{{ !$loop->last ? ', ' : '' }}
                                    @endforeach
                                </td>
                                <td class="px-5 py-3 font-mono text-slate-400">{{ $delivery->truck->plate_number ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md border bg-emerald-50 text-emerald-700 border-emerald-200/50 dark:bg-emerald-950/30 dark:text-emerald-400">
                                        Completed
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>
</x-layout>
