<x-layout>
    <div class="w-full max-w-7xl mx-auto">

        <header class="pt-8 mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">Haul Requests</h1>
            </div>
        </header>

        <x-flash-success />
        <x-flash-error />

        @if(Auth::user()->farmerProfile?->affiliation_type !== 'cooperative')
            <div class="flex gap-1 mb-8 bg-slate-100 dark:bg-slate-800/60 rounded-xl p-1 w-fit border border-slate-200/70 dark:border-slate-700/80">
                <a href="{{ route('farmer.proposals') }}"
                   class="px-5 py-2.5 rounded-xl text-sm font-bold transition {{ request()->routeIs('farmer.proposals') ? 'bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}">
                    Route Offers
                </a>
                <a href="{{ route('farmer.haul-requests') }}"
                   class="px-5 py-2.5 rounded-xl text-sm font-bold transition {{ request()->routeIs('farmer.haul-requests') ? 'bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}">
                    Haul Requests
                </a>
            </div>
        @endif

        @if($haulRequests->isEmpty())
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-12 text-center shadow-sm">
                <p class="text-slate-500 dark:text-slate-400 text-sm font-semibold">No haul requests yet.</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">Mark a harvest as sold, then request haul from My Active Harvests page.</p>
            </div>
        @else
            <div class="space-y-6">
                @foreach($haulRequests as $haulRequest)
                    <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Harvest</span>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-slate-200 mt-0.5">
                                    {{ $haulRequest->harvest?->crop?->name ?? '—' }}
                                    <span class="text-sm font-normal text-slate-500">({{ $haulRequest->harvest?->cropVariety?->name ?? 'Standard' }})</span>
                                </h3>
                            </div>
                            <span class="text-[10px] font-bold uppercase tracking-wide px-3 py-1 rounded-md border
                                @if($haulRequest->status === 'open') text-[var(--color-warning-text)] bg-[var(--color-warning-bg)] border-[var(--color-warning-border)]
                                @elseif($haulRequest->status === 'booked') text-purple-700 bg-purple-50 dark:bg-purple-950/20 border-purple-500/10
                                @elseif($haulRequest->status === 'fulfilled') text-[#16283C] bg-[#16283C]/10 dark:bg-[#16283C]/10 border-[#16283C]/20
                                @else text-slate-500 bg-slate-50 dark:bg-slate-900/40 border-slate-300/50 @endif">
                                {{ $haulRequest->status }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4 text-sm">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Quantity</span>
                                <p class="font-semibold text-slate-700 dark:text-slate-300 mt-0.5">{{ number_format($haulRequest->harvest?->quantity_kg ?? 0) }} kg</p>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pickup Date</span>
                                <p class="font-semibold text-slate-700 dark:text-slate-300 mt-0.5">{{ $haulRequest->pickup_date ? $haulRequest->pickup_date->format('M d, Y') : 'Not set' }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Destination</span>
                                <p class="font-semibold text-slate-700 dark:text-slate-300 mt-0.5 truncate">{{ $haulRequest->harvest?->destination_label ?? '—' }}</p>
                            </div>
                        </div>

                        @if($haulRequest->notes)
                            <div class="mb-4 p-3 bg-slate-50 dark:bg-slate-900/40 rounded-xl border border-slate-100 dark:border-slate-800/60">
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $haulRequest->notes }}</p>
                            </div>
                        @endif

                        @if($haulRequest->intents->isNotEmpty() && $haulRequest->status === 'open')
                            <div class="border-t border-slate-100 dark:border-slate-700/60 pt-4 mt-4">
                                <h4 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">Logistics Partners Expressing Intent</h4>
                                <div class="space-y-3">
                                    @foreach($haulRequest->intents as $intent)
                                            <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-900/40 rounded-xl border border-slate-100 dark:border-slate-800/60
                                            @if($intent->status === 'declined') opacity-50 @endif">
                                            <div class="flex-1">
                                                <p class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                                    {{ $intent->logisticsProfile->company_name ?? 'Unknown Company' }}
                                                </p>
                                                @if($intent->notes)
                                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $intent->notes }}</p>
                                                @endif
                                                @if($intent->suggested_date)
                                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Suggested pickup: {{ $intent->suggested_date->format('M d, Y') }}</p>
                                                @endif
                                                @if($intent->offer_rate_php_per_kg || $intent->counter_rate_php_per_kg || $intent->hauling_rate_php_per_kg)
                                                    <p class="text-xs font-semibold text-[#16283C] dark:text-[#D7BC7A] mt-0.5">
                                                        @if($intent->hauling_rate_php_per_kg)
                                                            Agreed rate: ₱{{ number_format((float) $intent->hauling_rate_php_per_kg, 2) }}/kg
                                                        @else
                                                            Offer: ₱{{ number_format((float) $intent->offer_rate_php_per_kg, 2) }}/kg
                                                            @if($intent->counter_rate_php_per_kg)
                                                                 Your counter: ₱{{ number_format((float) $intent->counter_rate_php_per_kg, 2) }}/kg
                                                            @endif
                                                        @endif
                                                    </p>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-2 ml-4">
                                                @if(!in_array($intent->status, ['accepted', 'declined']))
                                                    <a href="{{ route('haul-negotiations.room', $intent->id) }}"
                                                       class="bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-bold px-4 py-2 rounded-xl transition cursor-pointer">
                                                        Chat &amp; Negotiate
                                                    </a>
                                                @endif
                                                @if($intent->status === 'pending')
                                                    <form action="{{ route('haul-intents.accept', $intent->id) }}" method="POST">
                                                        @csrf
                                                        <button type="button" onclick="swalConfirm(this.closest('form'), {title:'Accept Haul Request?', text:'Book this haul with this logistics partner?', icon:'question', confirmText:'Yes, accept', cancelText:'Cancel', confirmColor:'#16283C'})" class="bg-[#16283C] hover:bg-[#0E1620] text-white dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] text-xs font-bold px-4 py-2 rounded-xl transition cursor-pointer">
                                                            Accept
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('haul-intents.decline', $intent->id) }}" method="POST">
                                                        @csrf
                                                        <button type="button" onclick="swalConfirm(this.closest('form'), {title:'Decline Haul Request?', text:'This haul request will be turned down.', icon:'warning', confirmText:'Yes, decline', cancelText:'Cancel', confirmColor:'#ef4444'})" class="bg-[var(--color-error-text)] hover:opacity-90 text-white text-xs font-bold px-4 py-2 rounded-xl transition cursor-pointer">
                                                            Decline
                                                        </button>
                                                    </form>
                                                @elseif($intent->status === 'accepted')
                                                    <span class="text-xs font-bold text-[#16283C] dark:text-[#D7BC7A] bg-[#16283C]/10 dark:bg-[#16283C]/10 px-3 py-1.5 rounded-md border border-[#16283C]/10 dark:border-[#16283C]/20">Accepted</span>
                                                @elseif($intent->status === 'agreed')
                                                    <span class="text-xs font-bold text-[var(--color-warning-text)] bg-[var(--color-warning-bg)] px-3 py-1.5 rounded-md border border-[var(--color-warning-border)]">Agreed — Confirm to book</span>
                                                @elseif($intent->status === 'declined')
                                                    <span class="text-xs font-bold text-[var(--color-error-text)] bg-[var(--color-error-bg)] px-3 py-1.5 rounded-md border border-[var(--color-error-border)]">Declined</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @elseif($haulRequest->status === 'open')
                            <div class="border-t border-slate-100 dark:border-slate-700/60 pt-4 mt-4">
                                <p class="text-xs text-slate-500 dark:text-slate-400 italic">No logistics partner has expressed intent yet.</p>
                            </div>
                        @elseif($haulRequest->status === 'booked')
                            @php $accepted = $haulRequest->acceptedIntent; @endphp
                            <div class="border-t border-slate-100 dark:border-slate-700/60 pt-4 mt-4">
                                <div class="p-3 bg-purple-50 dark:bg-purple-950/20 rounded-xl border border-purple-200/50 dark:border-purple-900/30">
                                    <p class="text-sm font-bold text-purple-800 dark:text-purple-300">
                                        Booked with {{ $accepted?->logisticsProfile?->company_name ?? 'a logistics partner' }}
                                    </p>
                                    <p class="text-xs text-purple-600 dark:text-purple-400 mt-1">Harvest status updated to Booked.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
