<x-layout>
<div class="w-full max-w-5xl mx-auto pb-12">

    {{-- Back link --}}
    <div class="mb-6 pt-8">
        <a href="{{ route('harvests.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-[#16283C] dark:text-[#D7BC7A] hover:underline">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to My Posts
        </a>
    </div>

    <x-flash-success />
    <x-flash-error />

    @php
        $status = $harvest->status->value ?? 'active';
        $cropName = $harvest->crop->name ?? $harvest->crop_type ?? 'Crop';
        $variety = $harvest->cropVariety->name ?? $harvest->variety ?? null;
    @endphp

    {{-- Crop Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">
                {{ $cropName }}@if($variety)<span class="text-sm font-semibold text-slate-500 dark:text-slate-400"> · {{ $variety }}</span>@endif
            </h1>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-semibold">
                Posted {{ $harvest->created_at->format('M d, Y') }}
                @if($activeNegotiation) · Negotiating with <span class="text-slate-700 dark:text-slate-200 font-bold">{{ $activeNegotiation->buyer->name ?? 'Buyer' }}</span>@endif
            </p>
        </div>
        <x-harvest-status-badge :status="$status" class="self-start" />
    </div>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-8">
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-4 shadow-sm">
            <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-600 uppercase tracking-wider">Quantity</h4>
            <p class="text-lg font-extrabold text-slate-800 dark:text-white mt-1">{{ number_format($harvest->quantity_kg, 2) }} <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">kg</span></p>
            @if($harvest->remaining_quantity_kg && (float)$harvest->remaining_quantity_kg < (float)$harvest->quantity_kg)
                <p class="text-[10px] text-[var(--color-warning-text)] font-bold mt-0.5">{{ number_format($harvest->remaining_quantity_kg, 2) }} kg remaining</p>
            @endif
        </div>
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-4 shadow-sm">
            <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-600 uppercase tracking-wider">Target Price</h4>
            <p class="text-lg font-extrabold text-slate-800 dark:text-white mt-1">@if($harvest->suggested_price_per_kg)<span class="text-sm">₱</span>{{ number_format($harvest->suggested_price_per_kg, 2) }} <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">/kg</span>@else<span class="text-xs text-slate-400 dark:text-slate-600">—</span>@endif</p>
        </div>
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-4 shadow-sm">
            <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-600 uppercase tracking-wider">Destination</h4>
            <p class="text-sm font-bold text-slate-800 dark:text-white mt-1 truncate">{{ $harvest->destination_label }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-4 shadow-sm">
            <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-600 uppercase tracking-wider">Harvest Date</h4>
            <p class="text-sm font-bold text-slate-800 dark:text-white mt-1">{{ $harvest->harvest_date ? $harvest->harvest_date->format('M d, Y') : '—' }}</p>
        </div>
    </div>

    {{-- Status Timeline --}}
    @php
        $timelineSteps = [
            ['key' => 'active',      'label' => 'Posted',      'icon' => 'seedling'],
            ['key' => 'negotiating', 'label' => 'Negotiating', 'icon' => 'chat'],
            ['key' => 'sold',        'label' => 'Sold',        'icon' => 'check-circle'],
            ['key' => 'assigned',    'label' => 'Assigned',    'icon' => 'users'],
            ['key' => 'in_progress', 'label' => 'In Transit',  'icon' => 'truck'],
            ['key' => 'completed',   'label' => 'Completed',   'icon' => 'check'],
        ];
        $stepKeys = array_column($timelineSteps, 'key');
        $currentIdx = array_search($status, $stepKeys);
        if ($currentIdx === false) $currentIdx = 0;
        // partially_sold and booked sit between negotiating and sold
        if (in_array($status, ['partially_sold', 'booked'])) $currentIdx = 2;
        if ($status === 'cancelled') $currentIdx = -1;

        $progressSummary = match ($status) {
            'active'         => 'Your crop is listed on the board — buyers will reach out to negotiate.',
            'negotiating'    => 'You are in negotiation — open the deal room to continue.',
            'partially_sold' => 'Part of your crop sold — ' . number_format((float) ($harvest->remaining_quantity_kg ?? 0), 2) . ' kg is still listed on the board.',
            'sold'           => 'Your crop sold — a delivery proposal will come next for you to accept.',
            'booked'         => 'Your delivery is booked — it will be assigned for pickup next.',
            'assigned'       => 'A driver is assigned to pick up your crop.',
            'in_progress'    => 'Your crop is on the way — track the shipment.',
            'completed'      => 'Your crop was delivered — view the cost ledger for the charges.',
            'cancelled'      => 'This post was cancelled.',
            default          => 'Your crop is being processed.',
        };
    @endphp
    <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm mb-8">
        <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-600 uppercase tracking-wider mb-1">Progress</h4>
        <p class="text-xs font-semibold {{ $status === 'cancelled' ? 'text-red-600 dark:text-red-400' : 'text-slate-600 dark:text-slate-300' }} mb-4">{{ $progressSummary }}</p>
        <div class="flex items-center justify-between gap-1 overflow-x-auto pb-1">
            @foreach($timelineSteps as $idx => $step)
                @php
                    $isActive = $idx <= $currentIdx && $currentIdx >= 0;
                    $isCurrent = $idx === $currentIdx && $currentIdx >= 0;
                @endphp
                <div class="flex flex-col items-center min-w-0 flex-1">
                    <div class="relative">
                        @if($isCurrent)
                            <span class="absolute -inset-1.5 rounded-full border-[3px] border-[#D7BC7A] dark:border-[#0E1620]"></span>
                        @endif
                        <div class="relative w-8 h-8 rounded-full flex items-center justify-center border-2 transition
                            {{ $isCurrent
                                ? 'bg-[#16283C] dark:bg-[#D7BC7A] border-[#16283C] dark:border-[#D7BC7A] text-white dark:text-[#17202B] scale-110'
                                : ($isActive
                                    ? 'bg-[#16283C] dark:bg-[#D7BC7A] border-[#16283C] dark:border-[#D7BC7A] text-white dark:text-[#17202B]'
                                    : 'bg-slate-100 dark:bg-slate-700 border-slate-200 dark:border-slate-600 text-slate-400 dark:text-slate-500') }}">
                            @if($isCurrent)
                                <span class="absolute inset-0 rounded-full bg-[#D7BC7A]/60 dark:bg-[#0E1620]/50 animate-ping"></span>
                            @endif
                            <x-icon :name="$step['icon']" class="relative w-4 h-4" />
                        </div>
                    </div>
                    <p class="text-[10px] font-bold mt-1.5 text-center leading-tight
                        {{ $isCurrent ? 'text-[#16283C] dark:text-[#D7BC7A]' : ($isActive ? 'text-slate-600 dark:text-slate-300' : 'text-slate-400 dark:text-slate-600') }}">
                        {{ $step['label'] }}
                    </p>
                    @if($isCurrent)
                        <span class="mt-1 text-[8px] font-extrabold uppercase tracking-wider px-1.5 py-0.5 rounded-full bg-[#D7BC7A] text-[#17202B]">● Now</span>
                    @endif
                </div>
                @if(!$loop->last)
                    <div class="h-0.5 flex-1 max-w-8 mt-[-12px] rounded
                        {{ $isActive && $currentIdx >= 0 ? 'bg-[#16283C] dark:bg-[#D7BC7A]' : 'bg-slate-200 dark:bg-slate-700' }}"></div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Action Cards --}}
    @if($status === 'negotiating' && $activeNegotiation)
        {{-- Negotiation in progress --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm mb-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Active Negotiation</h4>
                    <p class="text-sm font-bold text-slate-800 dark:text-white mt-1">
                        Deal with <span class="text-[#16283C] dark:text-[#D7BC7A]">{{ $activeNegotiation->buyer->name ?? 'Buyer' }}</span>
                    </p>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 font-semibold">
                        Last activity {{ $activeNegotiation->last_activity_at?->diffForHumans() }}
                    </p>
                </div>
                <a href="{{ route('farmer.deal-room', $activeNegotiation->id) }}"
                    class="bg-[#16283C] hover:bg-[#0E1620] dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] text-white font-bold rounded-xl text-xs px-5 py-3 transition shadow-sm whitespace-nowrap">
                    Open Deal Room
                </a>
            </div>
        </div>

    @elseif(in_array($status, ['sold', 'partially_sold', 'booked']) && $activePoolingJob)
        {{-- Pooling job awaiting farmer decision --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm mb-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Delivery Proposal</h4>
                    <p class="text-sm font-bold text-slate-800 dark:text-white mt-1">
                        Route proposal from <span class="text-[#16283C] dark:text-[#D7BC7A]">{{ $activePoolingJob->logisticsProfile->company_name ?? 'Logistics Partner' }}</span>
                    </p>
                    @if($activePoolingJob->hauling_rate_per_kg)
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 font-semibold">
                            Rate: ₱{{ number_format((float) $activePoolingJob->hauling_rate_per_kg, 2) }}/kg
                        </p>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <form action="{{ route('pooling.accept', $activePoolingJob->id) }}" method="POST">
                        @csrf
                        <button type="button"
                            onclick="swalConfirm(this.closest('form'), {title:'Accept Proposal?', text:'Book this delivery route for your crop?', icon:'question', confirmText:'Yes, accept', cancelText:'Cancel', confirmColor:'#16283C'})"
                            class="bg-brand hover:bg-brand-dark text-white dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] text-xs font-bold py-2.5 px-4 rounded-xl transition cursor-pointer">
                            Accept
                        </button>
                    </form>
                    <form action="{{ route('pooling.reject', $activePoolingJob->id) }}" method="POST">
                        @csrf
                        <button type="button"
                            onclick="swalConfirm(this.closest('form'), {title:'Reject Proposal?', text:'This delivery proposal will be turned down.', icon:'warning', confirmText:'Yes, reject', cancelText:'Cancel', confirmColor:'#ef4444'})"
                            class="bg-[var(--color-error-text)] hover:opacity-90 text-white text-xs font-bold py-2.5 px-4 rounded-xl transition cursor-pointer">
                            Reject
                        </button>
                    </form>
                </div>
            </div>
        </div>

    @elseif(in_array($status, ['assigned', 'in_progress']))
        {{-- In transit --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm mb-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Shipment</h4>
                    <p class="text-sm font-bold text-slate-800 dark:text-white mt-1">Your crop is {{ $status === 'in_progress' ? 'on the way' : 'assigned for pickup' }}.</p>
                    @if($activePoolingJob)
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 font-semibold">
                            Driver: {{ $activePoolingJob->driver->name ?? 'Assigned' }} · Truck: {{ $activePoolingJob->truck?->plate_number ?? '—' }}
                        </p>
                    @endif
                </div>
                <a href="{{ route('tracking.index') }}"
                    class="bg-[#16283C] hover:bg-[#0E1620] dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] text-white font-bold rounded-xl text-xs px-5 py-3 transition shadow-sm whitespace-nowrap">
                    Track Shipment
                </a>
            </div>
        </div>

    @elseif($status === 'completed')
        {{-- Completed --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm mb-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Completed</h4>
                    <p class="text-sm font-bold text-slate-800 dark:text-white mt-1">This delivery has been completed.</p>
                </div>
                <a href="{{ route('pooling.cost-ledger.farmer-index') }}"
                    class="bg-[#16283C] hover:bg-[#0E1620] dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] text-white font-bold rounded-xl text-xs px-5 py-3 transition shadow-sm whitespace-nowrap">
                    View Cost Ledger
                </a>
            </div>
        </div>

    @elseif($status === 'active' && !$activeNegotiation)
        {{-- Waiting for buyer --}}
        <div class="bg-slate-50 dark:bg-slate-900/40 border border-dashed border-slate-300 dark:border-slate-700/80 rounded-2xl p-8 text-center mb-6">
            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-3">
                <x-icon name="seedling" class="w-5 h-5 text-slate-300 dark:text-slate-600" />
            </div>
            <p class="text-sm font-bold text-slate-600 dark:text-slate-400 heading-font">Waiting for buyers</p>
            <p class="text-xs text-slate-500 dark:text-slate-500 mt-1 max-w-sm mx-auto">Your crop is listed on the board. Buyers will reach out to start a negotiation.</p>
        </div>
    @endif

    {{-- Notes --}}
    @if($harvest->notes)
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm mb-6">
            <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-600 uppercase tracking-wider mb-2">Notes</h4>
            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">{{ $harvest->notes }}</p>
        </div>
    @endif

    {{-- Activity History --}}
    @if($harvest->negotiations->isNotEmpty())
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
            <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-600 uppercase tracking-wider mb-4">Activity History</h4>
            <div class="space-y-3">
                @foreach($harvest->negotiations as $neg)
                    @php
                        $negStatus = $neg->status->value ?? (string) $neg->status;
                        $negStatusClass = match($negStatus) {
                            'OPEN' => 'text-gold-700 bg-gold/10 border-gold/10 dark:text-gold-light dark:bg-gold/20 dark:border-gold/20',
                            'AGREED' => 'text-[#16283C] bg-[#16283C]/10 border-[#16283C]/10 dark:text-[#D7BC7A] dark:bg-[#16283C]/20 dark:border-[#16283C]/20',
                            'COMPLETED' => 'text-[#0E1620] bg-[#0E1620]/10 border-[#0E1620]/10 dark:text-slate-200 dark:bg-slate-700 dark:border-slate-600',
                            'CANCELLED' => 'text-slate-500 bg-slate-100 border-slate-200 dark:text-slate-400 dark:bg-slate-800 dark:border-slate-700',
                            default => 'text-slate-500 bg-slate-500/10 border-slate-500/10 dark:text-slate-400',
                        };
                    @endphp
                    <a href="{{ route('farmer.deal-room', $neg->id) }}"
                        class="flex items-center justify-between gap-3 p-3 rounded-xl border border-slate-200/60 dark:border-slate-700/60 hover:border-brand/40 dark:hover:border-brand/30 transition group">
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-800 dark:text-white truncate">
                                {{ $neg->buyer->name ?? 'Buyer' }}
                                @if($neg->negotiated_price)
                                    <span class="text-[10px] font-mono text-slate-500 dark:text-slate-400">· ₱{{ number_format((float) $neg->negotiated_price, 2) }}/kg</span>
                                @endif
                            </p>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold mt-0.5">
                                {{ $neg->created_at->format('M d, Y') }}
                                @if($neg->last_activity_at) · Last activity {{ $neg->last_activity_at->diffForHumans() }}@endif
                            </p>
                        </div>
                        <span class="text-[10px] font-extrabold uppercase tracking-widest px-2 py-0.5 rounded border {{ $negStatusClass }} shrink-0">
                            {{ $negStatus }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

</div>
</x-layout>
