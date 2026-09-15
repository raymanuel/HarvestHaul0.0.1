<x-layout>
    <div class="w-full max-w-7xl mx-auto pb-12">

        <div class="mb-6">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 hover:text-slate-700 dark:hover:text-slate-400 transition">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Dashboard
            </a>
        </div>

        <header class="pt-8 mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">My Logistics</h1>
            </div>
        </header>

        <x-flash-success />

        @if($harvests->isEmpty())
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-12 text-center shadow-sm">
                <p class="text-slate-500 dark:text-slate-400 text-sm font-semibold">No harvests in the logistics pipeline yet.</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">When you mark a harvest as sold or a logistics partner picks it up, it will appear here.</p>
            </div>
        @else
            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-800/50">
                                <th class="text-left px-6 py-4 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Crop</th>
                                <th class="text-left px-6 py-4 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Qty</th>
                                <th class="text-left px-6 py-4 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Status</th>
                                <th class="text-left px-6 py-4 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Logistics Progress</th>
                                <th class="text-center px-6 py-4 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                            @foreach($harvests as $harvest)
                                @php
                                    $poolingJob = $harvest->poolingJobs->first();
                                    $completedNegotiation = $harvest->completedNegotiation;

                                    $driverAccepted = $poolingJob && $poolingJob->accepted_at && $poolingJob->status->value === 'confirmed';

                                    $logisticsStatus = match (true) {
                                        $harvest->status->value === 'completed' => 'Delivered',
                                        $harvest->status->value === 'in_progress' => 'In Transit',
                                        $driverAccepted => 'Driver Assigned — Awaiting Pickup',
                                        $harvest->status->value === 'assigned' && $poolingJob && $poolingJob->status->value === 'confirmed' => 'Pickup Scheduled',
                                        $harvest->status->value === 'assigned' && $poolingJob && $poolingJob->status->value === 'pending' => 'Proposal Pending',
                                        $harvest->status->value === 'assigned' => 'Assigned to Route',
                                        $harvest->status->value === 'sold' && $poolingJob => 'Proposal Received',
                                        $harvest->status->value === 'sold' => 'Awaiting Logistics Partner',
                                        default => ucfirst($harvest->status->value),
                                    };

                                    $logisticsBadgeColor = match (true) {
                                        $harvest->status->value === 'completed' => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                                        $harvest->status->value === 'in_progress' => 'bg-orange-50 dark:bg-orange-950/20 text-orange-700 dark:text-orange-400 border-orange-500/20',
                                        $driverAccepted => 'bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border-[var(--color-warning-border)]',
                                        $harvest->status->value === 'assigned' => 'bg-purple-50 dark:bg-purple-950/20 text-purple-700 dark:text-purple-400 border-purple-500/20',
                                        $harvest->status->value === 'sold' && $poolingJob => 'bg-[var(--color-info-bg)] text-[var(--color-info-text)] border-[var(--color-info-border)]',
                                        $harvest->status->value === 'sold' => 'bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border-[var(--color-warning-border)]',
                                        default => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-bold text-slate-800 dark:text-white">
                                            {{ $harvest->crop->name ?? $harvest->crop_type ?? 'Unknown Crop' }}
                                        </p>
                                        @if($harvest->cropVariety)
                                            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 font-medium">{{ $harvest->cropVariety->name }}</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-xs font-mono font-bold text-slate-600 dark:text-slate-400">
                                        {{ number_format($harvest->quantity_kg) }} kg
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-[10px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-md border
                                            @if($harvest->status->value === 'sold') bg-indigo-50 dark:bg-indigo-950/20 text-indigo-700 dark:text-indigo-400 border-indigo-500/20
                                            @elseif($harvest->status->value === 'assigned') bg-purple-50 dark:bg-purple-950/20 text-purple-700 dark:text-purple-400 border-purple-500/20
                                            @elseif($harvest->status->value === 'in_progress') bg-orange-50 dark:bg-orange-950/20 text-orange-700 dark:text-orange-400 border-orange-500/20
                                            @elseif($harvest->status->value === 'completed') bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700
                                            @endif">
                                            {{ $harvest->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-block text-[10px] font-bold px-2.5 py-1 rounded-md border {{ $logisticsBadgeColor }}">
                                            {{ $logisticsStatus }}
                                        </span>
                                        @if($poolingJob && $poolingJob->driver)
                                            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1.5 font-medium">
                                                Driver: {{ $poolingJob->driver->name }}
                                            </p>
                                        @endif
                                        @if($poolingJob && $poolingJob->truck)
                                            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">
                                                Truck: {{ $poolingJob->truck->truck_name ?? $poolingJob->truck->plate_number ?? 'N/A' }}
                                            </p>
                                        @endif
                                        @if($completedNegotiation && $completedNegotiation->buyer)
                                            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 font-medium">
                                                Buyer: {{ $completedNegotiation->buyer->name }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($harvest->status->value === 'in_progress' && $poolingJob)
                                            <a href="{{ route('tracking.index') }}"
                                                class="inline-flex items-center gap-1 text-[10px] font-bold text-orange-700 dark:text-orange-400 bg-orange-50 dark:bg-orange-950/20 hover:bg-orange-100 dark:hover:bg-orange-950/40 px-2.5 py-1.5 rounded-xl transition cursor-pointer">
                                                Track Now
                                            </a>
                                        @elseif($harvest->status->value === 'assigned' && $poolingJob && $poolingJob->status->value === 'pending')
                                            <a href="{{ route('farmer.proposals') }}"
                                                class="inline-flex items-center gap-1 text-[10px] font-bold text-purple-700 dark:text-purple-400 bg-purple-50 dark:bg-purple-950/20 hover:bg-purple-100 dark:hover:bg-purple-950/40 px-2.5 py-1.5 rounded-xl transition cursor-pointer">
                                                Review Route Offer
                                            </a>
                                        @elseif($harvest->status->value === 'completed')
                                            <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Delivered</span>
                                        @else
                                            <span class="text-[10px] text-slate-500 dark:text-slate-400 select-none">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($harvests->hasPages())
                <div class="mt-6">
                    {{ $harvests->links() }}
                </div>
            @endif
        @endif
    </div>
</x-layout>
