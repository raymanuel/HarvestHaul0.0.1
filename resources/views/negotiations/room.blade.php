<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">

    <!-- Leaflet Assets -->
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>

    @php
        $user = Auth::user();
        $role = $user->role;
        $isBuyer = ($role === 'buyer') || ($role === 'logistics_partner' && $user->logisticsProfile && $user->logisticsProfile->isCooperative());
        $themeColor = $isBuyer ? 'gold' : 'brand';

        $lastProposal = $negotiation->messages
            ->filter(fn($m) => Str::startsWith($m->message_text, '[System Offer]'))
            ->sortByDesc('id')
            ->first();
        $viewerProposedLast = $lastProposal && $lastProposal->sender_id === Auth::id();
        
        $accentText = $isBuyer ? 'text-gold-700 dark:text-gold-light' : 'text-[#16283C] dark:text-[#D7BC7A]';
        $accentBg = 'bg-[#16283C] hover:bg-[#0E1620] text-white dark:bg-[#D7BC7A] dark:text-[#17202B] dark:hover:bg-[#BFA05A]';
        $accentBorder = $isBuyer ? 'border-gold/20' : 'border-[#16283C]/20';
        $accentBadge = $isBuyer ? 'bg-gold/10 dark:bg-gold-light/15' : 'bg-[#16283C]/10';
        $shadowColor = $isBuyer ? 'shadow-gold/10' : 'shadow-[#16283C]/10';

        $cropTotal = ($negotiation->negotiated_price && $negotiation->negotiated_volume)
            ? ((float) $negotiation->negotiated_price * (float) $negotiation->negotiated_volume)
            : null;
        $grandTotal = ($cropTotal !== null)
            ? $cropTotal + ((float) ($negotiation->hauling_rate_per_kg ?? 0) * (float) $negotiation->negotiated_volume)
            : null;
    @endphp

    <div class="relative z-10">
        <!-- Page Header -->
        <header class="mb-6 pt-8">
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ $isBuyer ? route('buyer.negotiations') : route('farmer.negotiations') }}" class="text-xs font-bold {{ $accentText }} hover:underline flex items-center gap-1">
                    ← Back to My Deals
                </a>
                <span class="text-xs font-bold {{ $accentText }} opacity-60">· Deal #{{ $negotiation->id }}</span>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight heading-font">
                        Crop Deal Chat
                    </h1>
                </div>
                <div>
                    <span id="deal-status-badge" class="text-[10px] font-extrabold uppercase tracking-widest px-3 py-1.5 rounded-md border
@if($negotiation->status->value === 'OPEN') text-gold-700 bg-gold/10 dark:bg-gold-light/15 border-gold/10 dark:border-gold-light/20
@elseif($negotiation->status->value === 'AGREED') text-[#16283C] dark:text-[#D7BC7A] bg-[#16283C]/10 dark:bg-slate-800 border-[#16283C]/10 dark:border-slate-700
@elseif($negotiation->status->value === 'COMPLETED') text-[#0E1620] dark:text-[#E9EEF4] bg-[#0E1620]/10 dark:bg-slate-800 border-[#0E1620]/10 dark:border-slate-700
                        @else text-slate-500 bg-slate-500/10 border-slate-500/10 @endif shadow-sm">
                        Deal Status: {{ $negotiation->status }}
                    </span>
                </div>
            </div>
        </header>

        <x-flash-success />

        <!-- Main Workspace -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Chat + Propose Terms row, Finalize Panel below -->
            <div class="lg:col-span-3 space-y-8">

            <div class="flex flex-col lg:flex-row bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-3xl overflow-hidden shadow-sm h-[min(940px,96vh)]">

            <div class="relative flex flex-col flex-1 min-w-0 min-h-0">
            <div class="flex flex-col flex-1 min-h-0">
                
                <!-- Chat Header -->
                <div class="px-6 py-4 bg-slate-50/50 dark:bg-slate-900/40 border-b border-slate-150 dark:border-slate-700/60 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Live Chat Console</h3>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span id="conn-status" class="hidden items-center gap-1.5 text-[10px] font-bold font-mono text-[var(--color-warning-text)] mr-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-[var(--color-warning-text)] animate-pulse"></span> Reconnecting
                        </span>
                        <button type="button" id="toggle-product-info" title="Product Overview" aria-label="Product Overview"
                            class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-700/60 transition cursor-pointer">
                            <x-icon name="package" size="w-4 h-4" />
                        </button>
                        <button type="button" id="toggle-counterparty-info" title="Counterparty Details" aria-label="Counterparty Details"
                            class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-700/60 transition cursor-pointer">
                            <x-icon name="users" size="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Chat Messages Scroll Area -->
                <div class="flex-1 p-4 overflow-y-auto space-y-3" id="chat-messages-container">
                    @foreach($negotiation->messages as $msg)
                        @php
                            $isSystem = Str::startsWith($msg->message_text, '[System');
                            $isMine = ($msg->sender_id === Auth::id());
                        @endphp

                        @if($isSystem)
                            <!-- System notification style -->
                            <div class="flex justify-center my-3">
                                <div class="px-4 py-2 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] rounded-2xl max-w-md text-center">
                                    <p class="text-[11px] font-bold text-[var(--color-warning-text)] leading-relaxed italic">
                                        {{ $msg->message_text }}
                                    </p>
                                    <span class="text-[9px] text-slate-500 dark:text-slate-400 mt-1 block font-mono">{{ $msg->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        @else
                            <!-- Chat message style -->
                            <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[70%] flex flex-col {{ $isMine ? 'items-end' : 'items-start' }}">
                                    <!-- Sender Name Tag -->
                                    <span class="text-[10px] text-slate-400 dark:text-slate-500 mb-1 px-1 font-semibold">
                                        {{ $msg->sender->name }}
                                    </span>
                                    <!-- Bubble -->
                                    <div class="px-3 py-2 rounded-2xl text-[11px] leading-relaxed shadow-sm font-medium
                                        @if($isMine)
                                            bg-[#16283C] dark:bg-[#D7BC7A] dark:text-[#17202B] text-white rounded-br-none
                                        @else
                                            bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-bl-none border border-slate-200/40 dark:border-slate-700/60
                                        @endif">
                                        {{ $msg->message_text }}
                                    </div>
                                    <span class="text-[9px] text-slate-500 dark:text-slate-400 mt-1 px-1 font-mono">
                                        {{ $msg->created_at->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                <!-- Chat Message Input Area -->
                <div class="p-4 border-t border-slate-150 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/30 shrink-0">
                    @if($negotiation->status->value === 'COMPLETED')
                        <div class="text-center p-4 bg-[#0E1620]/10 border border-[#0E1620]/20 rounded-xl">
                            <p class="text-[#0E1620] dark:text-[#E9EEF4] text-xs font-bold leading-none mb-3"><x-icon name="check" class="w-4 h-4" /> B2B deal finalized and closed. Chat room is locked to read-only.</p>
                            <div class="flex flex-wrap gap-2 justify-center">
                                @if(auth()->user()->role === 'logistics_partner')
                                    <a href="{{ route('route.optimization') }}"
                                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#16283C] hover:bg-[#0E1620] text-white dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] font-bold rounded-xl text-xs transition">
                                         <x-icon name="map" class="w-4 h-4" /> Go to Route Planning
                                    </a>
                                @endif
                                <a href="{{ $isBuyer ? route('buyer.negotiations') : route('farmer.negotiations') }}"
                                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs transition">
                                    ← Back to Deals
                                </a>
                            </div>
                        </div>
                    @else
                        <form id="send-message-form" class="flex gap-2" onsubmit="return sendMessage(event)">
                            @csrf
                            <input type="text" id="message-input" name="message_text" placeholder="Type message..." required autocomplete="off"
                                class="flex-1 px-4 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-{{ $themeColor }}-500/10 focus:border-{{ $themeColor }}-500 dark:text-white transition">
                            <button type="submit" class="px-5 py-3 {{ $accentBg }} text-white font-bold rounded-xl text-xs transition duration-200 shadow-sm {{ $shadowColor }} cursor-pointer">
                                Send
                            </button>
                        </form>
                    @endif
                </div>

            </div>

            <!-- Product Overview Popover -->
            <div id="popover-product-info" class="hidden absolute left-0 right-auto lg:left-auto lg:right-4 top-16 z-30 w-72 lg:w-80 bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl p-5 shadow-xl">
                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white heading-font mb-4 uppercase tracking-wider">Product Overview</h3>
                <div class="space-y-3 text-xs">
                    <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700/40">
                        <span class="text-slate-500 dark:text-slate-400">Crop Type:</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ $negotiation->harvest->crop->name ?? $negotiation->harvest->crop_type }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700/40">
                        <span class="text-slate-500 dark:text-slate-400">Variety:</span>
                        <span class="font-semibold text-slate-700 dark:text-slate-400">{{ $negotiation->harvest->cropVariety->name ?? $negotiation->harvest->variety ?? 'Standard' }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700/40">
                        <span class="text-slate-500 dark:text-slate-400">Original Volume:</span>
                        <span class="font-bold font-mono text-slate-700 dark:text-slate-400">{{ number_format($negotiation->harvest->quantity_kg) }} kg</span>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-slate-500 dark:text-slate-400">Pickup Location:</span>
                        <span class="font-semibold text-slate-700 dark:text-slate-400 text-right max-w-[150px] truncate" title="{{ $negotiation->harvest->farmer->farmerProfile->farm_location ?? 'Farmer' }}">
                            {{ $negotiation->harvest->farmer->farmerProfile->farm_location ?? 'Farmer farm' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Counterparty Details Popover -->
            <div id="popover-counterparty-info" class="hidden absolute left-0 right-auto lg:left-auto lg:right-14 top-16 z-30 w-72 lg:w-80 bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl p-5 shadow-xl">
                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white heading-font mb-4 uppercase tracking-wider">
                    @if($role === 'farmer')
                        Buyer Details
                    @else
                        Farmer Details
                    @endif
                </h3>

                @if($role === 'farmer')
                    {{-- Show the buyer/coop they're negotiating with --}}
                    @php
                        $counterparty = $negotiation->buyer;
                        $cpProfile = $counterparty->buyerProfile;
                        $cpLogistics = $counterparty->logisticsProfile;
                        $isCoopBuyer = $counterparty->role === 'logistics_partner' && $cpLogistics && $cpLogistics->isCooperative();
                    @endphp
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700/40">
                            <span class="text-slate-500 dark:text-slate-400">Name:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ $counterparty->name }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700/40">
                            <span class="text-slate-500 dark:text-slate-400">Type:</span>
                            @if($isCoopBuyer)
                                <span class="font-bold text-gold-700 dark:text-gold-light bg-gold/10 dark:bg-gold-light/15 px-2 py-0.5 rounded-md">Cooperative</span>
                            @elseif($counterparty->role === 'logistics_partner')
                                <span class="font-bold text-[var(--color-warning-text)] bg-[var(--color-warning-bg)] px-2 py-0.5 rounded-md">Logistics Company</span>
                            @else
                                <span class="font-bold text-[#16283C] dark:text-[#D7BC7A] bg-[#16283C]/10 px-2 py-0.5 rounded-md">Buyer</span>
                            @endif
                        </div>
                        @if($isCoopBuyer && $cpLogistics)
                            <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700/40">
                                <span class="text-slate-500 dark:text-slate-400">Cooperative:</span>
                                <span class="font-semibold text-slate-700 dark:text-slate-400">{{ $cpLogistics->company_name ?? '—' }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center py-2">
                            <span class="text-slate-500 dark:text-slate-400">Contact:</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-400">{{ $counterparty->phone ?? $cpProfile->phone ?? '—' }}</span>
                        </div>
                    </div>
                @else
                    {{-- Show the farmer they're negotiating with --}}
                    @php
                        $farmer = $negotiation->farmer;
                        $fp = $farmer->farmerProfile;
                    @endphp
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700/40">
                            <span class="text-slate-500 dark:text-slate-400">Name:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ $farmer->name }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700/40">
                            <span class="text-slate-500 dark:text-slate-400">Affiliation:</span>
                            @if($fp && $fp->affiliation_type === 'cooperative')
                                <span class="font-bold text-gold-700 dark:text-gold-light bg-gold/10 dark:bg-gold-light/15 px-2 py-0.5 rounded-md">Cooperative Member</span>
                            @else
                                <span class="font-bold text-[#16283C] dark:text-[#D7BC7A] bg-[#16283C]/10 px-2 py-0.5 rounded-md">Independent</span>
                            @endif
                        </div>
                        @if($fp && $fp->affiliation_type === 'cooperative' && $fp->cooperative)
                            <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700/40">
                                <span class="text-slate-500 dark:text-slate-400">Cooperative:</span>
                                <span class="font-semibold text-slate-700 dark:text-slate-400">{{ $fp->cooperative->company_name ?? '—' }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700/40">
                            <span class="text-slate-500 dark:text-slate-400">Farm Location:</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-400 text-right max-w-[150px] truncate" title="{{ $fp->farm_location ?? '—' }}">
                                {{ $fp->farm_location ?? '—' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-slate-500 dark:text-slate-400">Contact:</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-400">{{ $farmer->phone ?? $fp->phone ?? '—' }}</span>
                        </div>
                    </div>
                @endif
            </div>

            </div>

            <!-- Proposed Terms Panel -->
            <aside class="w-full lg:w-[320px] shrink-0 min-h-0 max-h-[45%] lg:max-h-none p-6 overflow-y-auto border-t lg:border-t-0 lg:border-l border-slate-200/60 dark:border-slate-700/60">
                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white heading-font mb-4 uppercase tracking-wider">Proposed Terms</h3>

                <div class="mb-6 divide-y divide-slate-100 dark:divide-slate-700/40">
                    <div class="flex justify-between items-center gap-3 py-2.5">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Price</span>
                        <span class="text-right">
                            <span id="proposed-price" class="text-sm font-bold text-slate-800 dark:text-white font-mono">{{ $negotiation->negotiated_price ? '₱'.number_format($negotiation->negotiated_price, 2) : '—' }}</span> <span class="text-[10px] font-semibold text-slate-400">/ kg</span>
                        </span>
                    </div>
                    <div class="flex justify-between items-center gap-3 py-2.5">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Volume</span>
                        <span id="proposed-volume" class="text-sm font-bold text-slate-800 dark:text-white font-mono text-right">{{ $negotiation->negotiated_volume ? number_format($negotiation->negotiated_volume).' kg' : '—' }}</span>
                    </div>
                    <div class="flex justify-between items-center gap-3 py-2.5">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Hauling Rate</span>
                        <span class="text-right">
                            <span id="proposed-haul-rate" class="text-sm font-bold text-slate-800 dark:text-white font-mono">{{ $negotiation->hauling_rate_per_kg ? '₱'.number_format((float) $negotiation->hauling_rate_per_kg, 2) : '—' }}</span> <span class="text-[10px] font-semibold text-slate-400">/ kg</span>
                        </span>
                    </div>
                    <div class="flex justify-between items-center gap-3 py-2.5">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Crop Total</span>
                        <span id="deal-total" class="text-sm font-black {{ $accentText }} font-mono text-right">{{ $cropTotal !== null ? '₱'.number_format($cropTotal, 2) : '—' }}</span>
                    </div>
                    <div class="flex justify-between items-center gap-3 py-2.5">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Grand Total</span>
                        <span id="grand-total" class="text-sm font-black {{ $accentText }} font-mono text-right">{{ $grandTotal !== null ? '₱'.number_format($grandTotal, 2) : '—' }}</span>
                    </div>
                </div>

                @if($negotiation->status->value !== 'COMPLETED')
                    <!-- Propose Terms Action Form -->
                    <form id="propose-terms-form" class="space-y-4 mb-4" onsubmit="return proposeTerms(event)">
                        @csrf
                        <h4 class="text-xs font-bold text-slate-700 dark:text-slate-400 uppercase tracking-wider">Update Proposed Terms</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="negotiated_price" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Unit Price (₱/kg)</label>
                                <input type="number" step="0.01" min="0.01" name="negotiated_price" id="negotiated_price" required value="{{ $negotiation->negotiated_price ?? '' }}" placeholder="₱/kg"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-{{ $themeColor }}-500/10 focus:border-{{ $themeColor }}-500 transition">
                            </div>
                            <div>
                                <label for="negotiated_volume" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Volume (kg)</label>
                                <input type="number" step="0.01" min="0.01" max="{{ $negotiation->harvest->quantity_kg }}" name="negotiated_volume" id="negotiated_volume" required value="{{ $negotiation->negotiated_volume ?? '' }}" placeholder="kg"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-{{ $themeColor }}-500/10 focus:border-{{ $themeColor }}-500 transition">
                                <p class="text-[9px] text-slate-500 dark:text-slate-400 mt-1">Max: {{ number_format($negotiation->harvest->quantity_kg) }} kg (farmer's posted harvest)</p>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="term_hauling_rate" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Hauling Rate (₱/kg) <span class="normal-case font-semibold text-slate-400">— optional</span></label>
                                <input type="number" step="0.01" min="0" name="hauling_rate_per_kg" id="term_hauling_rate" value="{{ $negotiation->hauling_rate_per_kg ?? '' }}" placeholder="e.g. 5.00"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-{{ $themeColor }}-500/10 focus:border-{{ $themeColor }}-500 transition">
                                <p class="text-[9px] text-slate-500 dark:text-slate-400 mt-1">A per-kg transport rate proposed with the price, so the grand total includes hauling.</p>
                                @if(!empty($rateReference))
                                    <p class="text-[9px] text-slate-500 dark:text-slate-400 mt-0.5">Fair road-cost reference: <span class="font-bold">~₱{{ number_format($rateReference, 2) }}/kg</span> — straight-line farm→market estimate; hilly routes cost more.</p>
                                @endif
                            </div>
                        </div>
                        <button type="submit" id="propose-btn" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs transition duration-200 cursor-pointer">
                            Propose New Terms
                        </button>
                    </form>

                    @if($negotiation->negotiated_price)
                        @if($negotiation->status->value === 'AGREED')
                            <!-- Agreed (disabled) -->
                            <div class="mb-4">
                                <button type="button" id="agree-btn" disabled class="w-full py-3 bg-slate-300 dark:bg-slate-600 text-white font-bold rounded-xl text-xs transition duration-200 cursor-not-allowed opacity-70">
                                    Agreed
                                </button>
                            </div>
                        @elseif($viewerProposedLast)
                            <!-- Waiting for the other party to agree -->
                            <div id="agree-waiting" class="mb-4 p-4 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] rounded-xl text-center">
                                <p class="text-[11px] font-bold text-[var(--color-warning-text)] leading-relaxed">Waiting for the other party to agree to these terms...</p>
                            </div>
                        @else
                            <!-- Agree Button -->
                            <form id="agree-terms-form" class="mb-4" onsubmit="return agreeTerms(event)">
                                @csrf
                                <button type="submit" id="agree-btn" class="w-full py-3 {{ $accentBg }} text-white font-bold rounded-xl text-xs transition duration-200 shadow-sm {{ $shadowColor }} cursor-pointer">
                                    Agree to These Terms
                                </button>
                            </form>
                        @endif
                    @endif
                @endif
            </aside>

            </div>

            <!-- Finalize & Drop-off Panel (Buyer Side, revealed on AGREED) — directly below chat console -->
            @if($isBuyer)
                <div id="finalize-panel" class="{{ $negotiation->status->value === 'AGREED' ? '' : 'hidden' }}">
                @php
                    $viewingCoop = ($role === 'logistics_partner') ? $negotiation->buyer->logisticsProfile : null;
                    $viewerIsCoop = $viewingCoop && $viewingCoop->isCooperative();
                    $hasFixedPoint = $viewerIsCoop && !is_null($viewingCoop->latitude) && !is_null($viewingCoop->longitude);
                    $fixedAddress = $viewerIsCoop
                        ? ($viewingCoop->office_address ?: ($viewingCoop->company_name . ' Drop-off Point'))
                        : '';
                @endphp

                <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm">
                    <h3 class="text-sm font-extrabold text-slate-800 dark:text-white heading-font mb-2 uppercase tracking-wider text-gold-700 dark:text-gold-light">Finalize & Submit Drop-off</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-4 leading-relaxed font-semibold">Terms are agreed. Choose the drop-off point below to lock the transaction deal.</p>

                    <form action="{{ route('negotiations.finalize', $negotiation->id) }}" method="POST" class="space-y-4" id="finalize-form">
                        @csrf

                        @if($viewerIsCoop)
                            {{-- Drop-off point choice --}}
                            <div class="space-y-2">
                                <label class="flex items-start gap-3 p-3.5 rounded-xl border transition {{ $hasFixedPoint ? 'border-gold/30 dark:border-gold-light/30 hover:bg-gold/5 dark:hover:bg-gold-light/10 cursor-pointer' : 'border-slate-200 dark:border-slate-700 opacity-60 cursor-not-allowed' }}" id="choice-fixed-wrap">
                                    <input type="radio" name="dropoff_choice" id="choice-fixed" value="fixed" class="mt-0.5 accent-[#16283C]" {{ !$hasFixedPoint ? 'disabled' : '' }} {{ $hasFixedPoint ? 'checked' : '' }}>
                                    <span>
                                        <span class="block text-xs font-bold text-slate-800 dark:text-white">Fixed Cooperative Drop-off</span>
                                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 mt-0.5" id="fixed-desc">
                                            @if($hasFixedPoint)
                                                {{ $fixedAddress }}  {{ number_format($viewingCoop->latitude, 5) }}, {{ number_format($viewingCoop->longitude, 5) }}
                                            @else
                                                No saved location yet
                                            @endif
                                        </span>
                                    </span>
                                </label>
                                <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/40 cursor-pointer transition" id="choice-custom-wrap">
                                    <input type="radio" name="dropoff_choice" id="choice-custom" value="custom" class="mt-0.5 accent-[#16283C]" {{ $hasFixedPoint ? '' : 'checked' }}>
                                    <span>
                                        <span class="block text-xs font-bold text-slate-800 dark:text-white">Custom Destination</span>
                                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Type an address and pin the exact spot on the map.</span>
                                    </span>
                                </label>
                                <div id="use-location-wrap" class="pl-6">
                                    <button type="button" id="use-my-location"
                                        class="inline-flex items-center gap-1.5 text-[10px] font-bold text-[#16283C] dark:text-[#D7BC7A] bg-[#16283C]/10 dark:bg-slate-800 hover:bg-[#16283C]/20 dark:hover:bg-slate-700 border border-[#16283C]/20 dark:border-slate-700 px-3 py-1.5 rounded-xl transition cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        Use my current location
                                    </button>
                                    <p id="use-location-hint" class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">Detect your coords and auto-fill the drop-off address.</p>
                                </div>
                            </div>

                            @unless($hasFixedPoint)
                                <div class="p-4 bg-[var(--color-warning-bg)] dark:bg-[var(--color-warning-bg-dark)] border border-[var(--color-warning-border)] dark:border-[var(--color-warning-border-dark)] rounded-2xl">
                                    <p class="text-xs font-semibold text-[var(--color-warning-text)] dark:text-[var(--color-warning-text-dark)] leading-relaxed">
                                        Your cooperative has no saved drop-off location. Set it once in
                                        <a href="{{ route('profile.show') }}" class="underline font-bold">Profile Settings</a>
                                        to unlock the fixed drop-off option — or pin a custom destination below.
                                    </p>
                                </div>
                            @endunless
                        @endif

                        <div id="custom-address-wrap">
                            <label for="destination_address" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Drop-off Street Address</label>
                            <input type="text" name="destination_address" id="destination_address" required placeholder="e.g. Dadiangas Wholesale Market Hub"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-gold/10 focus:border-gold transition">
                        </div>

                        <div id="custom-map-wrap">
                            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Pin Drop-off Location on Map</label>
                            <div id="dropoff-map" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden" style="height: 220px;"></div>
                            <div class="flex items-center justify-between mt-1.5">
                                <p id="dropoff-feedback" class="text-[10px] text-slate-500 italic">Click map to place a pin marker.</p>
                            </div>
                        </div>

                        @if($viewerIsCoop)
                            <div class="p-4 bg-slate-50 dark:bg-slate-900/50 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl space-y-3">
                                <div class="flex items-center justify-between">
                                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Estimated Haul Distance</p>
                                    <p class="text-sm font-black text-slate-800 dark:text-white font-mono">
                                        @if($haulDistanceKm)
                                            {{ number_format($haulDistanceKm, 2) }} km
                                        @else
                                            —
                                        @endif
                                    </p>
                                </div>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">Straight-line farm-to-drop-off estimate. Use this to agree the hauling rate in chat with the farmer.</p>
                                <div>
                                    <label for="hauling_rate_per_kg" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Agreed Hauling Rate (₱/kg)</label>
                                    <input type="number" step="0.01" min="0" name="hauling_rate_per_kg" id="hauling_rate_per_kg" required placeholder="e.g. 5.00" value="{{ $negotiation->hauling_rate_per_kg }}"
                                        class="w-full px-3 py-2 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-gold/10 focus:border-gold transition">
                                    <p class="text-[9px] text-slate-500 dark:text-slate-400 mt-1">Auto-filled from the hauling rate agreed in chat, in ₱ per kilogram. You can still adjust it before closing — saved with this deal and used for this farmer's route cost share.</p>
                                    @if(!empty($rateReference))
                                        <p class="text-[9px] text-slate-500 dark:text-slate-400 mt-0.5">Fair road-cost reference: <span class="font-bold">~₱{{ number_format($rateReference, 2) }}/kg</span> — straight-line farm→market estimate; hilly routes cost more.</p>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Hidden coordinate values -->
                        <input type="hidden" name="destination_latitude" id="destination_latitude">
                        <input type="hidden" name="destination_longitude" id="destination_longitude">

                        <button type="submit" class="w-full py-3 bg-[#16283C] hover:bg-[#0E1620] dark:bg-[#D7BC7A] dark:text-[#17202B] dark:hover:bg-[#BFA05A] text-white font-bold rounded-xl text-xs transition duration-200 shadow-md shadow-gold/10 cursor-pointer">
                            Close Deal & Confirm Drop-off
                        </button>
                    </form>
                </div>

                <script>
                    (function () {
                        var fixedLat = {{ $hasFixedPoint ? json_encode((float) $viewingCoop->latitude) : 'null' }};
                        var fixedLng = {{ $hasFixedPoint ? json_encode((float) $viewingCoop->longitude) : 'null' }};
                        var fixedAddr = @json($viewerIsCoop ? $fixedAddress : '');
                        var viewerIsCoop = {{ $viewerIsCoop ? 'true' : 'false' }};

                        var addrInput = document.getElementById('destination_address');
                        var latInput = document.getElementById('destination_latitude');
                        var lngInput = document.getElementById('destination_longitude');
                        var mapWrap = document.getElementById('custom-map-wrap');

                        var map = null;
                        var marker = null;

                        function ensureMap() {
                            if (map) {
                                setTimeout(function () { map.invalidateSize(); }, 100);
                                return;
                            }
                            map = L.map('dropoff-map').setView([6.1164, 125.1716], 11);
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
                            map.on('click', function (e) {
                                if (marker) map.removeLayer(marker);
                                marker = L.marker(e.latlng).addTo(map);
                                latInput.value = e.latlng.lat;
                                lngInput.value = e.latlng.lng;
                                document.getElementById('dropoff-feedback').textContent =
                                    'Selected: ' + e.latlng.lat.toFixed(5) + ', ' + e.latlng.lng.toFixed(5);
                            });
                            setTimeout(function () { map.invalidateSize(); }, 200);
                        }

                        function applyFixed() {
                            addrInput.value = fixedAddr;
                            addrInput.readOnly = true;
                            latInput.value = fixedLat;
                            lngInput.value = fixedLng;
                            mapWrap.classList.add('hidden');
                            useLocationWrap.classList.add('hidden');
                        }

                        var useLocationWrap = document.getElementById('use-location-wrap');
                        var useLocationHint = document.getElementById('use-location-hint');

                        function useCurrentLocation() {
                            var feedback = document.getElementById('dropoff-feedback');
                            if (!navigator.geolocation) {
                                feedback.textContent = 'Geolocation is not supported by this browser.';
                                useLocationHint.textContent = 'Geolocation is not supported here; type the address and pin the map instead.';
                                return;
                            }
                            feedback.textContent = 'Locating...';
                            useLocationHint.textContent = 'Fetching your current location...';
                            navigator.geolocation.getCurrentPosition(function (pos) {
                                var lat = pos.coords.latitude;
                                var lng = pos.coords.longitude;
                                ensureMap();
                                map.setView([lat, lng], 15);
                                if (marker) map.removeLayer(marker);
                                marker = L.marker([lat, lng]).addTo(map);
                                latInput.value = lat;
                                lngInput.value = lng;
                                feedback.textContent = 'Selected: ' + lat.toFixed(5) + ', ' + lng.toFixed(5);
                                useLocationHint.textContent = 'Resolving your address...';
                                fetch('https://nominatim.openstreetmap.org/reverse?lat=' + lat + '&lon=' + lng + '&format=json')
                                    .then(function (r) { return r.json(); })
                                    .then(function (data) {
                                        var addr = (data && data.display_name) ? data.display_name : '';
                                        if (addr) {
                                            addrInput.value = addr;
                                            addrInput.readOnly = false;
                                            useLocationHint.textContent = 'Address auto-filled. Review below.';
                                        } else {
                                            useLocationHint.textContent = 'Could not resolve an address - type it below before submitting.';
                                        }
                                    })
                                    .catch(function () {
                                        useLocationHint.textContent = 'Could not resolve an address - type it below before submitting.';
                                    });
                            }, function () {
                                feedback.textContent = 'Could not get your location. Click the map to pin instead.';
                                useLocationHint.textContent = 'Location denied - type the address and pin the map instead.';
                            }, { enableHighAccuracy: true, timeout: 10000 });
                        }

                        document.getElementById('use-my-location').addEventListener('click', function (e) {
                            e.preventDefault();
                            useCurrentLocation();
                        });

                        function applyCustom() {
                            addrInput.readOnly = false;
                            addrInput.value = '';
                            latInput.value = '';
                            lngInput.value = '';
                            document.getElementById('dropoff-feedback').textContent = 'Click map to place a pin marker.';
                            mapWrap.classList.remove('hidden');
                            useLocationWrap.classList.remove('hidden');
                            useLocationHint.textContent = 'Detect your coords and auto-fill the drop-off address.';
                            ensureMap();
                        }

                        if (!viewerIsCoop) {
                            ensureMap();
                            return;
                        }

                        var fixedRadio = document.getElementById('choice-fixed');
                        var customRadio = document.getElementById('choice-custom');

                        function syncMode() {
                            if (fixedRadio.checked) {
                                applyFixed();
                            } else {
                                applyCustom();
                            }
                        }

                        fixedRadio.addEventListener('change', syncMode);
                        customRadio.addEventListener('change', syncMode);
                        syncMode();
                    })();
                </script>

            </div>
            </div>
            @endif

        </div>
    </div>

</div>

<script>
    var negotiationId = {{ $negotiation->id }};
    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var userId = {{ Auth::id() }};
    var isBuyer = {{ $isBuyer ? 'true' : 'false' }};
    var counterpartName = @json($isBuyer ? $negotiation->farmer->name : $negotiation->buyer->name);
    var pollFailures = 0;

    // Highest message ID already rendered by Blade — poll skips these
    var lastMsgId = {{ $negotiation->messages->max('id') ?? 'null' }};

    // Sender id of the last proposed-terms ([System Offer]) message — used to gate
    // the Agree button so the party who proposed last cannot agree to their own terms.
    var lastProposalSenderId = {{ $lastProposal && $lastProposal->sender_id ? $lastProposal->sender_id : 'null' }};

    // ── Helpers ──
    function scrollChatBottom() {
        var c = document.getElementById('chat-messages-container');
        if (c) c.scrollTop = c.scrollHeight;
    }

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(str));
        return d.innerHTML;
    }

    function timeAgo(ts) {
        var diff = Date.now() - new Date(ts).getTime();
        var s = Math.floor(diff / 1000);
        if (s < 60) return s + 's ago';
        var m = Math.floor(s / 60);
        if (m < 60) return m + 'm ago';
        var h = Math.floor(m / 60);
        if (h < 24) return h + 'h ago';
        return Math.floor(h / 24) + 'd ago';
    }

    function renderMessage(msg) {
        var isSystem = msg.message_text.indexOf('[System') === 0;

        if (isSystem) {
            return '<div class="flex justify-center my-3">' +
                '<div class="px-4 py-2 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] rounded-2xl max-w-md text-center">' +
                '<p class="text-[11px] font-bold text-[var(--color-warning-text)] leading-relaxed italic">' + escapeHtml(msg.message_text) + '</p>' +
                '<span class="text-[9px] text-slate-500 dark:text-slate-400 mt-1 block font-mono">' + timeAgo(msg.created_at) + '</span>' +
                '</div></div>';
        }

        var isMine = msg.sender_id === userId;
        var align = isMine ? 'justify-end items-end' : 'justify-start items-start';
        var bubble = isMine
            ? 'bg-[#16283C] dark:bg-[#D7BC7A] dark:text-[#17202B] text-white rounded-br-none'
            : 'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-bl-none border border-slate-200/40 dark:border-slate-700/60';
        var name = (msg.sender && msg.sender.name) ? msg.sender.name : 'Unknown';

        return '<div class="flex ' + align + ' my-2">' +
            '<div class="max-w-[70%] flex flex-col ' + align + '">' +
            '<span class="text-[10px] text-slate-400 dark:text-slate-500 mb-1 px-1 font-semibold">' + escapeHtml(name) + '</span>' +
            '<div class="px-3 py-2 rounded-2xl text-[11px] leading-relaxed shadow-sm font-medium ' + bubble + '">' + escapeHtml(msg.message_text) + '</div>' +
            '<span class="text-[9px] text-slate-500 dark:text-slate-400 mt-1 px-1 font-mono">' + timeAgo(msg.created_at) + '</span>' +
            '</div></div>';
    }

    // Keep the propose button in sync with who proposed last so the two sides
    // alternate turns: after YOU offer, you must wait for the other party to
    // respond (counter-offer or agree) before proposing again.
    function updateProposeButton(status) {
        var st = status || 'OPEN';
        var btn = document.getElementById('propose-btn');
        if (!btn) return;
        var hint = document.getElementById('propose-waiting-hint');

        if (st === 'AGREED' || st === 'COMPLETED') {
            btn.disabled = true;
            btn.classList.add('cursor-not-allowed', 'opacity-50');
            if (hint) hint.remove();
            return;
        }

        var viewerProposedLast = (lastProposalSenderId !== null && lastProposalSenderId === userId);
        btn.disabled = viewerProposedLast;
        btn.classList.toggle('cursor-not-allowed', viewerProposedLast);
        btn.classList.toggle('opacity-50', viewerProposedLast);

        if (viewerProposedLast) {
            if (!hint && btn.closest('form')) {
                var el = document.createElement('p');
                el.id = 'propose-waiting-hint';
                el.className = 'text-[10px] font-bold text-[var(--color-warning-text)] mt-3 text-center';
                el.textContent = 'Waiting for the other party to respond to your offer...';
                btn.closest('form').insertAdjacentElement('afterend', el);
            }
        } else if (hint) {
            hint.remove();
        }
    }

    // ── Helper: update price/volume/status UI ──
    function updateDealTotal() {
        var totalEl = document.getElementById('deal-total');
        var grandEl = document.getElementById('grand-total');
        if (!totalEl && !grandEl) return;
        var priceEl = document.getElementById('proposed-price');
        var volEl = document.getElementById('proposed-volume');
        var haulEl = document.getElementById('proposed-haul-rate');
        var price = priceEl ? parseFloat(priceEl.textContent.replace(/[₱,\s]/g, '')) : NaN;
        var volume = volEl ? parseFloat(volEl.textContent.replace(/[kg,\s]/g, '')) : NaN;
        var haul = haulEl ? parseFloat(haulEl.textContent.replace(/[₱,\s]/g, '')) : NaN;
        if (totalEl) {
            totalEl.textContent = (price > 0 && volume > 0)
                ? '₱' + (price * volume).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                : '—';
        }
        if (grandEl) {
            grandEl.textContent = (price > 0 && volume > 0)
                ? '₱' + ((price + (isNaN(haul) ? 0 : haul)) * volume).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                : '—';
        }
    }

    // Fill the Finalize panel's "Agreed Hauling Rate" input with the rate
    // proposed and agreed in chat (buyer keeps it editable at close).
    function syncAgreedRateToFinalize(rate) {
        var input = document.getElementById('hauling_rate_per_kg');
        if (input && rate !== undefined && rate !== null && parseFloat(rate) > 0) {
            input.value = parseFloat(rate).toFixed(2);
        }
    }

    function updateDealUI(data) {
        if (data.negotiated_price !== undefined && data.negotiated_price !== null) {
            var priceEl = document.querySelector('#proposed-price');
            var volEl = document.querySelector('#proposed-volume');
            var haulEl = document.querySelector('#proposed-haul-rate');
            if (priceEl) priceEl.textContent = '₱' + parseFloat(data.negotiated_price).toFixed(2);
            if (volEl) volEl.textContent = parseFloat(data.negotiated_volume).toLocaleString() + ' kg';
            if (haulEl && data.hauling_rate_per_kg !== undefined) {
                var h = data.hauling_rate_per_kg;
                haulEl.textContent = (h !== null && parseFloat(h) > 0) ? '₱' + parseFloat(h).toFixed(2) : '—';
            }
            updateDealTotal();
        }
        if (data.hauling_rate_per_kg !== undefined) {
            syncAgreedRateToFinalize(data.hauling_rate_per_kg);
        }
        updateAgreeVisibility(data.status);
        if (data.status) {
            var statusEl = document.querySelector('#deal-status-badge');
            if (statusEl && statusEl.textContent.indexOf(data.status) === -1) {
                statusEl.textContent = 'Deal Status: ' + data.status;
            }
        }
        if (data.status === 'AGREED' && isBuyer) {
            var btn = document.getElementById('agree-btn');
            if (btn) btn.textContent = 'Agreed';
            // Reveal the Finalize panel live (no reload) and focus it.
            var panel = document.getElementById('finalize-panel');
            if (panel && panel.classList.contains('hidden')) {
                // Ensure the agreed rate is present before the panel is seen.
                syncAgreedRateToFinalize(data.hauling_rate_per_kg);
                panel.classList.remove('hidden');
                setTimeout(function () {
                    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 150);
            }
        }
    }

    // Keep the Agree button state consistent with who proposed the latest terms.
    // Open + viewer proposed last  -> muted "waiting" hint (no button)
    // Open + viewer did NOT propose -> enabled "Agree to These Terms" button
    // AGREED/COMPLETED             -> disabled "Agreed" button
    function updateAgreeVisibility(status) {
        var st = status || 'OPEN';
        updateProposeButton(st);
        var proposeForm = document.getElementById('propose-terms-form');
        var existingForm = document.getElementById('agree-terms-form');
        var waitingEl = document.getElementById('agree-waiting');

        if (st === 'AGREED' || st === 'COMPLETED') {
            if (existingForm) existingForm.remove();
            if (waitingEl) waitingEl.remove();
            if (!document.getElementById('agree-btn') && proposeForm) {
                proposeForm.insertAdjacentHTML('afterend',
                    '<div class="mb-4"><button type="button" id="agree-btn" disabled class="w-full py-3 bg-slate-300 dark:bg-slate-600 text-white font-bold rounded-xl text-xs transition duration-200 cursor-not-allowed opacity-70">Agreed</button></div>');
            }
            return;
        }

        var viewerProposedLast = (lastProposalSenderId !== null && lastProposalSenderId === userId);
        var hasTerms = document.getElementById('proposed-price') && lastProposalSenderId !== null;

        if (viewerProposedLast) {
            // Remove enabled button, keep/show the waiting hint
            if (existingForm) existingForm.remove();
            if (!waitingEl && proposeForm) {
                proposeForm.insertAdjacentHTML('afterend',
                    '<div id="agree-waiting" class="mb-4 p-4 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] rounded-xl text-center">' +
                    '<p class="text-[11px] font-bold text-[var(--color-warning-text)] leading-relaxed">Waiting for the other party to agree to these terms...</p></div>');
            }
        } else {
            // Remove waiting hint, show/keep the enabled button (only once terms exist)
            if (waitingEl) waitingEl.remove();
            if (!existingForm && proposeForm && hasTerms) {
                proposeForm.insertAdjacentHTML('afterend',
                    '<form id="agree-terms-form" class="mb-4" onsubmit="return agreeTerms(event)">' +
                    '<input type="hidden" name="_token" value="' + csrfToken + '">' +
                    '<button type="submit" id="agree-btn" class="w-full py-3 {{ $accentBg }} text-white font-bold rounded-xl text-xs transition duration-200 shadow-sm {{ $shadowColor }} cursor-pointer">Agree to These Terms</button></form>');
            }
        }
    }

    // ── Helper: append a single message to the chat ──
    function appendMessage(msg) {
        if (msg.id <= lastMsgId) return;
        var container = document.getElementById('chat-messages-container');
        container.insertAdjacentHTML('beforeend', renderMessage(msg));
        lastMsgId = msg.id;
        if (msg.message_text.indexOf('[System Offer]') === 0) {
            lastProposalSenderId = msg.sender_id;
            updateAgreeVisibility();
        }
        scrollChatBottom();
    }

    // ── Connection indicator (poll health) ──
    function setConnection(ok) {
        pollFailures = ok ? 0 : pollFailures + 1;
        var el = document.getElementById('conn-status');
        if (!el) return;
        var degraded = !ok && pollFailures >= 2;
        el.classList.toggle('hidden', !degraded);
        el.classList.toggle('flex', degraded);
    }

    // ── Single poll cycle: fetch only NEW messages since lastMsgId ──
    function refreshChat() {
        var url = '{{ route("negotiations.messages", $negotiation->id) }}?since_id=' + lastMsgId;
        return fetch(url, {
            headers: { 'Accept': 'application/json' }
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        }).then(function (data) {
            setConnection(true);
            data.messages.forEach(function (msg) {
                if (msg.id > lastMsgId) {
                    appendMessage(msg);
                }
            });
            updateDealUI(data);
        }).catch(function (err) {
            setConnection(false);
            console.error('refreshChat:', err);
        });
    }

    // ── Poll loop every 1.5s ──
    (function pollLoop() {
        setTimeout(function () {
            refreshChat().then(pollLoop).catch(pollLoop);
        }, 1500);
    })();

    // ── Send Message (AJAX — input only cleared after the server confirms) ──
    function sendMessage(e) {
        e.preventDefault();
        var input = document.getElementById('message-input');
        var text = input.value.trim();
        if (!text) return false;

        fetch('{{ route("negotiations.message", $negotiation->id) }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ message_text: text })
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        }).then(function (data) {
            if (data.message) appendMessage(data.message);
            input.value = '';
            setConnection(true);
        }).catch(function (err) {
            console.error('sendMessage:', err);
            input.value = text;
            input.focus();
            setConnection(false);
            swalToast('error', 'Could not send your message. Please try again.');
        });
        return false;
    }

    // ── Propose Terms (AJAX, direct append + price update) ──

    // Optimistic echo: render the offer bubble the instant the user confirms so
    // the wait for the server round-trip is invisible. Replaced by the real
    // message on success, removed on failure.
    function buildOfferPreview() {
        var price = document.getElementById('negotiated_price').value;
        var vol = document.getElementById('negotiated_volume').value;
        var haul = document.getElementById('term_hauling_rate').value;
        var txt = '[System Offer] Proposes terms: \u20B1' + parseFloat(price).toFixed(2) + '/kg for ' + Number(vol).toLocaleString() + ' kg.';
        if (haul && parseFloat(haul) > 0) txt += ' Hauling rate: \u20B1' + parseFloat(haul).toFixed(2) + '/kg.';
        return txt;
    }

    function showOptimisticOffer() {
        var container = document.getElementById('chat-messages-container');
        var wrap = document.createElement('div');
        wrap.className = 'proposal-optimistic';
        wrap.style.opacity = '0.6';
        wrap.innerHTML =
            '<div class="flex justify-center my-3">' +
            '<div class="px-4 py-2 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] rounded-2xl max-w-md text-center">' +
            '<p class="text-[11px] font-bold text-[var(--color-warning-text)] leading-relaxed italic">' + escapeHtml(buildOfferPreview()) + '</p>' +
            '<span class="text-[9px] text-slate-500 dark:text-slate-400 mt-1 block font-mono">Sending...</span>' +
            '</div></div>';
        container.appendChild(wrap);
        scrollChatBottom();
        return wrap;
    }

    function removeOptimisticOffer(wrap) {
        if (wrap && wrap.parentNode) wrap.parentNode.removeChild(wrap);
    }

    function proposeTerms(e) {
        e.preventDefault();
        var form = document.getElementById('propose-terms-form');
        swalConfirm(function () {
            var btn = document.getElementById('propose-btn');
            if (!btn || btn.disabled) return;
            btn.disabled = true;
            var originalText = btn.textContent;
            btn.textContent = 'Sending...';
            var optimistic = showOptimisticOffer();
            var data = new FormData(form);
            fetch('{{ route("negotiations.propose", $negotiation->id) }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: data
            }).then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            }).then(function (data) {
                removeOptimisticOffer(optimistic);
                if (data.message) appendMessage(data.message);
                updateDealUI(data);
                btn.textContent = originalText;
                updateProposeButton();
            }).catch(function (err) {
                console.error('proposeTerms:', err);
                removeOptimisticOffer(optimistic);
                btn.disabled = false;
                btn.textContent = originalText;
                updateProposeButton();
                swalToast('error', 'Could not send your offer. Please try again.');
            });
        }, {
            title: 'Propose These Terms?',
            text: 'Send this offer to the other party?',
            icon: 'question',
            confirmText: 'Yes, send',
            cancelText: 'Cancel',
            confirmColor: '{{ Auth::user()->role === 'buyer' ? '#BFA05A' : '#16283C' }}'
        });
        return false;
    }

    // ── Agree Terms (confirm sheet → AJAX) ──
    function doAgree() {
        fetch('{{ route("negotiations.agree", $negotiation->id) }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        }).then(function (data) {
            if (data.message) appendMessage(data.message);
            updateDealUI(data);
        }).catch(function (err) { console.error('agreeTerms:', err); swalToast('error', 'Could not agree right now. Please try again.'); });
    }

    function agreeTerms(e) {
        e.preventDefault();
        var priceEl = document.getElementById('proposed-price');
        var volEl = document.getElementById('proposed-volume');
        var haulEl = document.getElementById('proposed-haul-rate');
        var price = priceEl ? parseFloat(priceEl.textContent.replace(/[₱,\s]/g, '')) : NaN;
        var volume = volEl ? parseFloat(volEl.textContent.replace(/[kg,\s]/g, '')) : NaN;
        var haul = haulEl ? parseFloat(haulEl.textContent.replace(/[₱,\s]/g, '')) : NaN;
        var summary;
        if (price > 0 && volume > 0) {
            summary = '₱' + price.toFixed(2) + '/kg × ' + volume.toLocaleString() + ' kg = ₱' + (price * volume).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            if (!isNaN(haul)) {
                summary += '\n+ hauling ₱' + haul.toFixed(2) + '/kg → grand total ₱' + ((price + haul) * volume).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            summary += '\nCounterpart: ' + counterpartName;
        } else {
            summary = 'This will lock the currently proposed terms with ' + counterpartName + '.';
        }
        swalConfirm(doAgree, {
            title: 'Agree to These Terms?',
            text: summary,
            icon: 'question',
            confirmText: 'Yes, agree',
            cancelText: 'Not yet',
            confirmColor: isBuyer ? '#BFA05A' : '#16283C'
        });
        return false;
    }

    // ── Info Popovers (hover on desktop, click toggle for touch) ──
    function setupPopover(btnId, panelId) {
        var btn = document.getElementById(btnId);
        var panel = document.getElementById(panelId);
        if (!btn || !panel) return;
        var pinned = false;
        var timer = null;

        function show() { if (timer) { clearTimeout(timer); timer = null; } panel.classList.remove('hidden'); }
        function scheduleHide() {
            if (timer) clearTimeout(timer);
            timer = setTimeout(function () { if (!pinned) panel.classList.add('hidden'); }, 150);
        }

        btn.addEventListener('mouseenter', show);
        btn.addEventListener('mouseleave', scheduleHide);
        panel.addEventListener('mouseenter', show);
        panel.addEventListener('mouseleave', scheduleHide);
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            pinned = !pinned;
            if (pinned) { show(); } else { panel.classList.add('hidden'); }
        });
        document.addEventListener('click', function (e) {
            if (pinned && !panel.contains(e.target) && !btn.contains(e.target)) {
                pinned = false;
                panel.classList.add('hidden');
            }
        });
    }
    setupPopover('toggle-product-info', 'popover-product-info');
    setupPopover('toggle-counterparty-info', 'popover-counterparty-info');

    // Auto-scroll on load
    document.addEventListener('DOMContentLoaded', function () {
        scrollChatBottom();
        updateProposeButton();
    });
</script>

@php
    $hasLocation = false;
    if ($isBuyer && Auth::user()->buyerProfile) {
        $hasLocation = !is_null(Auth::user()->buyerProfile->latitude);
    } elseif (Auth::user()->logisticsProfile) {
        $hasLocation = !is_null(Auth::user()->logisticsProfile->latitude);
    }
@endphp

<x-location-picker-modal />

<script>
(function () {
    var hasLocation = {{ $hasLocation ? 'true' : 'false' }};
    var finalizeForm = document.querySelector('form[action*="finalize"]');
    if (!finalizeForm) return;
    var harvestKg = {{ (float) ($negotiation->harvest->quantity_kg ?? 0) }};

    function doSubmit() {
        if (hasLocation) {
            finalizeForm.submit();
            return;
        }
        window.__locationPicker.open('Deal', function(data) {
            document.querySelector('[name="destination_latitude"]').value = data.lat;
            document.querySelector('[name="destination_longitude"]').value = data.lng;
            document.querySelector('[name="destination_address"]').value = data.address;
            if (data.savePermanently) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'popup_save_permanently';
                input.value = '1';
                finalizeForm.appendChild(input);
            }
            finalizeForm.submit();
        });
    }

    finalizeForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var rate = parseFloat((document.getElementById('hauling_rate_per_kg') || {}).value);
        if (isNaN(rate) || rate <= 0) {
            swalConfirm(doSubmit, {
                title: 'Close Deal and Confirm Drop-off?',
                text: 'This locks this deal with the agreed drop-off details. Make sure the hauling rate is fair to the farmer before confirming.',
                icon: 'question',
                confirmText: 'Yes, close deal',
                cancelText: 'Not yet',
                confirmColor: {{ $isBuyer ? "'#BFA05A'" : "'#16283C'" }}
            });
            return;
        }
        var total = rate * harvestKg;
        swalConfirm(doSubmit, {
            title: 'Close Deal and Lock Rate?',
            text: '₱' + rate.toFixed(2) + '/kg × ' + harvestKg.toLocaleString() + ' kg = ₱' + total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '. This locks this deal for the farmer\u2019s route cost share.',
            icon: 'question',
            confirmText: 'Yes, close deal',
            cancelText: 'Not yet',
            confirmColor: {{ $isBuyer ? "'#BFA05A'" : "'#16283C'" }}
        });
    });
})();
</script>

</x-layout>
