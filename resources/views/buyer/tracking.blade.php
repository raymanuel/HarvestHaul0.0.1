<x-layout>
    <div class="w-full max-w-5xl mx-auto pb-12">

        {{-- Header --}}
        <header class="mb-8 pt-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">
                        Deliveries
                    </h1>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wider text-[#0E1620] dark:text-[#E9EEF4] bg-[#0E1620]/10 dark:bg-[#0E1620]/10 px-3 py-1.5 rounded-lg border border-[#0E1620]/10 dark:border-[#0E1620]/20 self-start">
                    Buyer Portal
                </span>
            </div>
        </header>

        {{-- Flash Messages --}}
        <x-flash-success />
        <x-flash-error />

        {{-- Active Deliveries --}}
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Active Deliveries</h2>
            <span class="w-32 h-px bg-slate-200 dark:bg-slate-700/80"></span>
        </div>

        @if($activeDeliveries->isEmpty())
            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl p-10 text-center shadow-sm mb-10">
                <p class="text-slate-400 text-sm font-semibold">No active deliveries at this time.</p>
                <p class="text-slate-400/60 text-xs mt-1">When sellers dispatch your purchases, they'll appear here.</p>
            </div>
        @else
            <div class="space-y-4 mb-10">
                @foreach($activeDeliveries as $delivery)
                    <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition">
                        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 dark:border-slate-700/60">
                            <div>
                                <p class="text-sm font-bold text-slate-800 dark:text-white heading-font">
                                    Route #{{ $delivery->id }}
                                </p>
                                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">
                                    {{ $delivery->truck->plate_number ?? '—' }}
                                     Driver: {{ $delivery->driver->name ?? '—' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('tracking.index', ['job' => $delivery->id]) }}"
                                   class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider px-3 py-1.5 rounded-md border border-[#16283C]/30 dark:border-[#16283C]/40 bg-[#16283C]/10 dark:bg-[#16283C]/15 text-[#16283C] dark:text-[#D7BC7A] hover:bg-[#16283C]/20 dark:hover:bg-[#16283C]/30 transition">
                                    <x-icon name="pin" class="w-3.5 h-3.5" /> Track
                                </a>
                                @php
                                    $statusBadge = match($delivery->status->value) {
                                        'confirmed'               => ['bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border-[var(--color-warning-border)]', 'Driver Assigned — Trip Starts Soon'],
                                        'in_progress'             => ['bg-[#0E1620]/10 text-[#0E1620] border-[#0E1620]/20 dark:bg-[#0E1620]/10 dark:text-[#E9EEF4]', 'In Transit'],
                                        'awaiting_confirmation'   => ['bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border-[var(--color-warning-border)]', 'Awaiting Your Confirmation'],
                                        default                   => ['bg-slate-50 dark:bg-slate-700 text-slate-500 dark:text-slate-400 border-slate-200/50 dark:border-slate-600', ucfirst($delivery->status->value)],
                                    };
                                @endphp
                                <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-md border {{ $statusBadge[0] }}">
                                    {{ $statusBadge[1] }}
                                </span>
                            </div>
                        </div>

                        <div class="px-6 py-4">
                            {{-- Cargo Summary --}}
                            <div class="space-y-2 mb-4">
                                @foreach($delivery->harvests as $harvest)
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-slate-500 dark:text-slate-400 font-semibold">
                                            {{ $harvest->crop->name ?? $harvest->crop_type ?? '—' }}
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
                                        <span class="w-2 h-2 rounded-full bg-[#16283C]/100 animate-pulse"></span>
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
                            @if($delivery->status->value === 'awaiting_confirmation')
                                <form method="POST" action="{{ route('buyer.confirm-receipt', $delivery) }}">
                                    @csrf
                                    <x-button type="button" size="lg" full class="active:scale-[0.98]" onclick="swalConfirm(this.closest('form'), {title:'Confirm Receipt?', text:'Mark delivery #{{ $delivery->id }} as received?', icon:'question', confirmText:'Yes, confirm', cancelText:'Cancel', confirmColor:'#16283C'})">
                                        <x-icon name="check" class="w-4 h-4" /> Confirm Receipt — I received this delivery
                                    </x-button>
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
            <h2 class="text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Completed Deliveries</h2>
            <span class="w-32 h-px bg-slate-200 dark:bg-slate-700/80"></span>
        </div>

        @if($completedDeliveries->isEmpty())
            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl p-8 text-center shadow-sm">
                <p class="text-slate-400 text-sm font-semibold">No completed deliveries yet.</p>
            </div>
        @else
            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl shadow-sm">
                <div class="overflow-x-auto rounded-2xl">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900/30 border-b border-slate-100 dark:border-slate-700/60">
                            <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Route</th>
                            <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Cargo</th>
                            <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Truck</th>
                            <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40">
                        @foreach($completedDeliveries as $delivery)
                            <tr class="hover:bg-slate-50/40 dark:hover:bg-slate-900/10 transition">
                                <td class="px-5 py-3 font-bold text-slate-700 dark:text-slate-300">#{{ $delivery->id }}</td>
                                <td class="px-5 py-3 text-slate-500 dark:text-slate-400">
                                    @foreach($delivery->harvests as $h)
                                        {{ $h->crop->name ?? '—' }}{{ !$loop->last ? ', ' : '' }}
                                    @endforeach
                                </td>
                                <td class="px-5 py-3 font-mono text-slate-500 dark:text-slate-400">{{ $delivery->truck->plate_number ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md border bg-[#16283C]/10 text-[#16283C] border-[#16283C]/20 bg-[#16283C]/10 dark:bg-[#16283C]/10 text-[#16283C] dark:text-[#D7BC7A]">
                                        Completed
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        @endif

    </div>
</x-layout>
