<x-layout>
@php
    $user = Auth::user();
    $isBuyer = false;
    $status = $negotiation->status->value ?? (string) $negotiation->status;
    $buyer = $negotiation->buyer;
    $buyerName = $buyer?->name ?? 'Buyer';
    $cropName = $negotiation->harvest?->crop?->name ?? $negotiation->harvest?->crop_type ?? 'Crop';
    $variety = $negotiation->harvest?->cropVariety?->name ?? $negotiation->harvest?->variety ?? null;
    $qty = $negotiation->negotiated_volume ?? $negotiation->harvest?->quantity_kg;
    $lastProposal = $negotiation->messages
        ->filter(fn($m) => Str::startsWith($m->message_text, '[System Offer]'))
        ->sortByDesc('id')
        ->first();
    $viewerProposedLast = $lastProposal && $lastProposal->sender_id === $user->id;
@endphp

<div class="w-full max-w-7xl mx-auto pb-12">

    <!-- Back + header -->
    <div class="mb-6">
        <a href="{{ route('farmer.negotiations') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-[#16283C] dark:text-[#D7BC7A] hover:underline mb-2">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to My Deals
        </a>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white heading-font">
                    {{ $cropName }}@if($variety)<span class="text-sm font-semibold text-slate-500 dark:text-slate-400"> · {{ $variety }}</span>@endif
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-semibold">Negotiating with <span class="text-slate-700 dark:text-slate-200 font-bold">{{ $buyerName }}</span> · Deal #{{ $negotiation->id }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if($status === 'AGREED')
                    <span class="text-[10px] font-extrabold uppercase tracking-widest px-2.5 py-1 rounded border text-gold-700 bg-gold/10 border-gold/10 dark:text-gold-light dark:bg-gold/20 dark:border-gold/20">{{ $status }}</span>
                @else
                    <span class="text-[10px] font-extrabold uppercase tracking-widest px-2.5 py-1 rounded border bg-slate-200/70 dark:bg-slate-700 border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300">{{ $status }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Main column: chat console -->
        <div class="lg:col-span-2 flex flex-col gap-5">

            <!-- Chat console -->
            <div id="chat-console" class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl overflow-hidden shadow-sm flex flex-col">
                <!-- Console header -->
                <div class="px-5 py-3.5 bg-slate-50/50 dark:bg-slate-900/40 border-b border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-gold/15 dark:bg-gold-light/15 border border-gold/20 dark:border-gold-light/20 flex items-center justify-center text-[11px] font-bold text-[#16283C] dark:text-[#D7BC7A]">{{ mb_substr($buyerName, 0, 1) }}</div>
                        <div>
                            <p class="text-xs font-bold text-slate-800 dark:text-white">{{ $buyerName }}</p>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ $buyer?->role === 'logistics_partner' ? 'Logistics Partner' : 'Buyer' }}</p>
                        </div>
                    </div>
                    <span id="conn-status" class="hidden items-center gap-1.5 text-[10px] font-bold font-mono text-amber-600 dark:text-amber-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Reconnecting
                    </span>
                </div>

                <!-- Messages -->
                <div id="chat-messages-container" class="flex-1 overflow-y-auto max-h-[460px] min-h-[320px] px-5 py-4 space-y-2 bg-slate-50/30 dark:bg-slate-900/20">
                    @forelse($negotiation->messages->sortBy('id') as $message)
                        @if(Str::startsWith($message->message_text, '[System'))
                            <div class="flex justify-center my-3">
                                <div class="px-4 py-2 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] rounded-2xl max-w-md text-center">
                                    <p class="text-[11px] font-bold text-[var(--color-warning-text)] leading-relaxed italic">{{ $message->message_text }}</p>
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 block font-mono">{{ $message->created_at?->diffForHumans() }}</span>
                                </div>
                            </div>
                        @elseif($message->sender_id === $user->id)
                            <div class="flex justify-end items-end my-2">
                                <div class="max-w-[70%] flex flex-col items-end">
                                    <div class="px-4 py-3 rounded-2xl text-xs leading-relaxed shadow-sm font-medium bg-[#16283C] dark:bg-[#D7BC7A] dark:text-[#17202B] text-white rounded-br-none">{{ $message->message_text }}</div>
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 px-1 font-mono">{{ $message->created_at?->diffForHumans() }}</span>
                                </div>
                            </div>
                        @else
                            <div class="flex justify-start items-end my-2">
                                <div class="max-w-[70%] flex flex-col items-start">
                                    <span class="text-[10px] text-slate-400 dark:text-slate-500 mb-1 px-1 font-semibold">{{ $message->sender?->name ?? $buyerName }}</span>
                                    <div class="px-4 py-3 rounded-2xl text-xs leading-relaxed shadow-sm font-medium bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-bl-none border border-slate-200/40 dark:border-slate-700/60">{{ $message->message_text }}</div>
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 px-1 font-mono">{{ $message->created_at?->diffForHumans() }}</span>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="h-full flex flex-col items-center justify-center py-12 text-center">
                            <p class="text-xs font-bold text-slate-500 dark:text-slate-400">No messages yet</p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Chat with {{ $buyerName }} to settle the price, amount, and hauling rate.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Composer -->
                <form id="message-form" onsubmit="return sendMessage(event)" class="px-5 py-3.5 border-t border-slate-200/60 dark:border-slate-700/60 bg-white dark:bg-slate-800 flex items-end gap-2">
                    @csrf
                    <textarea id="message-input" rows="1" maxlength="1000" placeholder="Type your message…"
                        class="flex-1 px-4 py-3 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 dark:focus:ring-gold/10 focus:border-[#16283C]/40 dark:focus:border-gold/30 resize-none"></textarea>
                    <button type="submit" class="px-6 py-3 rounded-xl text-xs font-bold bg-[#16283C] hover:bg-[#0E1620] dark:bg-[#D7BC7A] dark:text-[#17202B] dark:hover:bg-[#BFA05A] text-white shadow-sm transition cursor-pointer">Send</button>
                </form>
            </div>

            @if($status === 'COMPLETED')
                <div class="p-4 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] rounded-2xl">
                    <p class="text-[11px] font-bold text-[var(--color-warning-text)] leading-relaxed">This deal is completed. New messages are closed.</p>
                </div>
            @endif
        </div>

        <!-- Side column: Deal Overview + Propose + Haul -->
        <aside class="lg:col-span-1 flex flex-col gap-4">

            <!-- Propose + Agree actions (open only) -->
            @if($status === 'OPEN')
                <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl p-4 shadow-sm">
                    <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-3">Transact</h4>

                    <form id="propose-terms-form" class="space-y-3" onsubmit="return proposeTerms(event)">
                        @csrf
                        <div>
                            <label for="negotiated_price" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Unit Price (₱/kg)</label>
                            <input type="number" step="0.01" min="0.01" name="negotiated_price" id="negotiated_price" required value="{{ $negotiation->negotiated_price ?? '' }}" placeholder="₱/kg"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 dark:focus:ring-gold/10 focus:border-[#16283C]/40 dark:focus:border-gold/30">
                        </div>
                        <div>
                            <label for="negotiated_volume" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Amount (kg)</label>
                            <input type="number" step="0.01" min="0.01" @if($negotiation->harvest) max="{{ $negotiation->harvest->quantity_kg }}" @endif name="negotiated_volume" id="negotiated_volume" required value="{{ $negotiation->negotiated_volume ?? '' }}" placeholder="kg"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 dark:focus:ring-gold/10 focus:border-[#16283C]/40 dark:focus:border-gold/30">
                            @if($negotiation->harvest)
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">Max: {{ number_format($negotiation->harvest->quantity_kg) }} kg (your posted harvest)</p>
                            @endif
                        </div>
                        <div>
                            <label for="term_hauling_rate" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Hauling Rate (₱/kg) <span class="normal-case font-semibold text-slate-400">— optional</span></label>
                            <input type="number" step="0.01" min="0" name="hauling_rate_per_kg" id="term_hauling_rate" value="{{ $negotiation->hauling_rate_per_kg ?? '' }}" placeholder="e.g. 5.00"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 dark:focus:ring-gold/10 focus:border-[#16283C]/40 dark:focus:border-gold/30">
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">A per-kg transport rate proposed with the price, so the grand total includes hauling.</p>
                        </div>
                        <button type="submit" id="propose-btn" class="w-full py-2.5 bg-[#16283C] hover:bg-[#0E1620] dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] text-white font-bold rounded-xl text-xs transition duration-200 shadow-sm cursor-pointer">Propose These Terms</button>
                    </form>

                    @if($status === 'AGREED')
                        <button type="button" disabled class="w-full py-3 bg-slate-300 dark:bg-slate-600 text-white font-bold rounded-xl text-xs transition duration-200 cursor-not-allowed opacity-70">Agreed</button>
                    @elseif($viewerProposedLast)
                        <div id="agree-waiting" class="mt-3 p-4 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] rounded-xl text-center">
                            <p class="text-[11px] font-bold text-[var(--color-warning-text)] leading-relaxed">Waiting for {{ $buyerName }} to agree to these terms...</p>
                        </div>
                    @elseif($lastProposal)
                        <form id="agree-terms-form" class="mt-3" onsubmit="return agreeTerms(event)">
                            @csrf
                            <button type="submit" id="agree-btn" class="w-full py-3 bg-[#16283C] hover:bg-[#0E1620] dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] text-white font-bold rounded-xl text-xs transition duration-200 shadow-sm cursor-pointer">Agree to These Terms</button>
                        </form>
                    @endif
                </div>
            @endif

            <!-- Deal Overview -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl p-4 shadow-sm">
                <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-3">Deal Overview</h4>
                <dl class="space-y-2.5 text-[11px]">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400 font-semibold">Status</dt>
                        <dd id="deal-status-badge" class="font-mono font-bold text-slate-800 dark:text-white">{{ 'Deal Status: ' . $status }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400 font-semibold">Unit Price</dt>
                        <dd id="proposed-price" class="font-mono font-bold text-slate-800 dark:text-white">₱{{ number_format((float) ($negotiation->negotiated_price ?? 0), 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400 font-semibold">Amount</dt>
                        <dd id="proposed-volume" class="font-mono font-bold text-slate-800 dark:text-white">{{ $qty ? number_format((float) $qty) . ' kg' : '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400 font-semibold">Crop total</dt>
                        <dd id="deal-total" class="font-mono font-bold text-slate-800 dark:text-white">—</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400 font-semibold">Hauling rate</dt>
                        <dd id="proposed-haul-rate" class="font-mono font-bold text-slate-800 dark:text-white">@if($negotiation->hauling_rate_per_kg)₱{{ number_format((float) $negotiation->hauling_rate_per_kg, 2) }}@else—@endif</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-slate-200/60 dark:border-slate-700/60 pt-2.5">
                        <dt class="text-slate-600 dark:text-slate-300 font-bold">Total (incl. haul)</dt>
                        <dd id="grand-total" class="font-mono font-extrabold text-[#16283C] dark:text-[#D7BC7A]">—</dd>
                    </div>
                </dl>
                @php
                    $destination = $negotiation->destination_address ?: ($negotiation->harvest?->destination?->name ?: ($negotiation->harvest?->destination?->address ?: null));
                @endphp
                @if($destination)
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-3 font-semibold"><span class="font-bold text-slate-600 dark:text-slate-300">Drop-off:</span> {{ $destination }}</p>
                @endif
            </div>

            <!-- Haul -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl p-4 shadow-sm">
                <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-3">Haul</h4>
                @if($haulRequest)
                    <p class="text-xs text-slate-600 dark:text-slate-300">
                        Haul request <span class="font-bold uppercase text-[10px]">{{ $haulRequest->status }}</span>
                        @if($haulIntents->isEmpty())
                            — waiting for logistics offers.
                        @endif
                    </p>
                    @foreach($haulIntents as $intent)
                        <a href="{{ route('haul-negotiations.room', $intent->id) }}"
                            class="mt-2.5 flex items-center justify-between gap-2 p-3 rounded-xl border border-slate-200/60 dark:border-slate-700/60 hover:border-gold/40 dark:hover:border-gold-light/30 transition group">
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-800 dark:text-white truncate">{{ $intent->logisticsProfile?->user?->name ?? 'Logistics Partner' }}</p>
                                @if($intent->hauling_rate_php_per_kg)
                                    <p class="text-[10px] font-mono text-slate-500 dark:text-slate-400">Agreed ₱{{ number_format((float) $intent->hauling_rate_php_per_kg, 2) }}/kg</p>
                                @elseif($intent->offer_rate_php_per_kg)
                                    <p class="text-[10px] font-mono text-slate-500 dark:text-slate-400">Offer ₱{{ number_format((float) $intent->offer_rate_php_per_kg, 2) }}/kg</p>
                                @endif
                            </div>
                            <span class="text-[10px] font-extrabold uppercase tracking-widest px-2 py-0.5 rounded border bg-slate-100 dark:bg-slate-700 border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 capitalize">{{ $intent->status }}</span>
                        </a>
                    @endforeach
                    <a href="{{ route('farmer.haul-requests') }}" class="mt-3 inline-flex items-center gap-1 text-[10px] font-bold text-[#16283C] dark:text-[#D7BC7A] hover:underline">Manage haul requests →</a>
                @elseif($status === 'AGREED')
                    <p class="text-xs text-slate-600 dark:text-slate-300">Deal agreed — set up the trucking to move your crop.</p>
                    <a href="{{ route('harvests.index') }}" class="mt-3 inline-flex items-center gap-1 text-[10px] font-bold text-[#16283C] dark:text-[#D7BC7A] hover:underline">Post a haul request →</a>
                @else
                    <p class="text-xs text-slate-500 dark:text-slate-400">Hauling is arranged after the deal is agreed.</p>
                @endif
            </div>
        </aside>
    </div>
</div>

<script>
    var negotiationId = {{ $negotiation->id }};
    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var userId = {{ Auth::id() }};
    var counterpartName = @json($buyerName);
    var pollFailures = 0;

    var lastMsgId = {{ $negotiation->messages->max('id') ?? 'null' }};
    var lastProposalSenderId = {{ $lastProposal && $lastProposal->sender_id ? $lastProposal->sender_id : 'null' }};

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
                '<span class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 block font-mono">' + timeAgo(msg.created_at) + '</span>' +
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
            '<div class="px-4 py-3 rounded-2xl text-xs leading-relaxed shadow-sm font-medium ' + bubble + '">' + escapeHtml(msg.message_text) + '</div>' +
            '<span class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 px-1 font-mono">' + timeAgo(msg.created_at) + '</span>' +
            '</div></div>';
    }

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
        updateAgreeVisibility(data.status);
        if (data.status) {
            var statusEl = document.getElementById('deal-status-badge');
            if (statusEl) statusEl.textContent = 'Deal Status: ' + data.status;
        }
    }

    function updateAgreeVisibility(status) {
        var st = status || 'OPEN';
        var proposeForm = document.getElementById('propose-terms-form');
        var existingForm = document.getElementById('agree-terms-form');
        var waitingEl = document.getElementById('agree-waiting');

        if (st === 'AGREED' || st === 'COMPLETED') {
            if (existingForm) existingForm.remove();
            if (waitingEl) waitingEl.remove();
            return;
        }
        var viewerProposedLast = (lastProposalSenderId !== null && lastProposalSenderId === userId);
        var hasTerms = document.getElementById('proposed-price') && lastProposalSenderId !== null;

        if (viewerProposedLast) {
            if (existingForm) existingForm.remove();
            if (!waitingEl && proposeForm) {
                proposeForm.insertAdjacentHTML('afterend',
                    '<div id="agree-waiting" class="mt-3 p-4 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] rounded-xl text-center">' +
                    '<p class="text-[11px] font-bold text-[var(--color-warning-text)] leading-relaxed">Waiting for the other party to agree to these terms...</p></div>');
            }
        } else {
            if (waitingEl) waitingEl.remove();
            if (!existingForm && proposeForm && hasTerms) {
                proposeForm.insertAdjacentHTML('afterend',
                    '<form id="agree-terms-form" class="mt-3" onsubmit="return agreeTerms(event)">' +
                    '<input type="hidden" name="_token" value="' + csrfToken + '">' +
                    '<button type="submit" id="agree-btn" class="w-full py-3 bg-[#16283C] hover:bg-[#0E1620] dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] text-white font-bold rounded-xl text-xs transition duration-200 shadow-sm cursor-pointer">Agree to These Terms</button></form>');
            }
        }
    }

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

    function setConnection(ok) {
        pollFailures = ok ? 0 : pollFailures + 1;
        var el = document.getElementById('conn-status');
        if (!el) return;
        var degraded = !ok && pollFailures >= 2;
        el.classList.toggle('hidden', !degraded);
        el.classList.toggle('flex', degraded);
    }

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

    (function pollLoop() {
        setTimeout(function () {
            refreshChat().then(pollLoop).catch(pollLoop);
        }, 3000);
    })();

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
        });
        return false;
    }

    function proposeTerms(e) {
        e.preventDefault();
        var form = document.getElementById('propose-terms-form');
        swalConfirm(function () {
            var data = new FormData(form);
            fetch('{{ route("negotiations.propose", $negotiation->id) }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: data
            }).then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            }).then(function (data) {
                if (data.message) appendMessage(data.message);
                updateDealUI(data);
            }).catch(function (err) { console.error('proposeTerms:', err); });
        }, {
            title: 'Propose These Terms?',
            text: 'Send this offer to the buyer?',
            icon: 'question',
            confirmText: 'Yes, send',
            cancelText: 'Cancel',
            confirmColor: '#16283C'
        });
        return false;
    }

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
        }).catch(function (err) { console.error('agreeTerms:', err); });
    }

    function agreeTerms(e) {
        e.preventDefault();
        var priceEl = document.getElementById('proposed-price');
        var volEl = document.getElementById('proposed-volume');
        var haulEl = document.getElementById('proposed-haul-rate');
        var price = priceEl ? parseFloat(priceEl.textContent.replace(/[₱,\s]/g, '')) : NaN;
        var volume = volEl ? parseFloat(volEl.textContent.replace(/[kg,\s]/g, '')) : NaN;
        var haul = haulEl ? parseFloat(haulEl.textContent.replace(/[₱,\s]/g, '')) : NaN;
        var summary = 'This will lock the currently proposed terms with ' + counterpartName + '.';
        if (price > 0 && volume > 0) {
            summary = '₱' + price.toFixed(2) + '/kg × ' + volume.toLocaleString() + ' kg = ₱' + (price * volume).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            if (!isNaN(haul)) {
                summary += '\n+ hauling ₱' + haul.toFixed(2) + '/kg → grand total ₱' + ((price + haul) * volume).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            summary += '\nCounterpart: ' + counterpartName;
        }
        swalConfirm(doAgree, {
            title: 'Agree to These Terms?',
            text: summary,
            icon: 'question',
            confirmText: 'Yes, agree',
            cancelText: 'Not yet',
            confirmColor: '#16283C'
        });
        return false;
    }

    document.addEventListener('DOMContentLoaded', function () {
        scrollChatBottom();
        updateDealTotal();
    });
</script>
</x-layout>