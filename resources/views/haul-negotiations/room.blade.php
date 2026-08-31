<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">

    @php
        $user = Auth::user();
        $isFarmer = ($user->role === 'farmer');
        $accentBg = 'bg-[#16283C] hover:bg-[#0E1620] dark:bg-[#16283C] dark:hover:bg-[#16283C]';
        $accentText = 'text-[#16283C] dark:text-[#D7BC7A]';
        $accentBorder = 'border-[#16283C]/20';
        $accentBadge = 'bg-[#16283C]/10';
        $shadowColor = 'shadow-[#16283C]/10';
        $backRoute = $isFarmer ? route('farmer.haul-requests') : route('logistics.haul-negotiations');
        $harvest = $haulIntent->haulRequest->harvest;
        $logistics = $haulIntent->logisticsProfile;
        $farmer = $haulIntent->haulRequest->farmer;
        $status = $haulIntent->status;
    @endphp

    <div class="relative z-10">
        <header class="mb-6 pt-8">
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ $backRoute }}" class="text-xs font-bold {{ $accentText }} hover:underline flex items-center gap-1">
                    ← Back to {{ $isFarmer ? 'Haul Requests' : 'Haul Negotiations' }}
                </a>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest {{ $accentText }} {{ $accentBadge }} px-3 py-1 rounded-full border {{ $accentBorder }}">Haul Thread #{{ $haulIntent->id }}</span>
                    <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight heading-font mt-3">
                        Haul Negotiation Chat
                    </h1>
                </div>
                <div>
                    <span id="deal-status-badge" class="text-[10px] font-extrabold uppercase tracking-widest px-3 py-1.5 rounded-full border shadow-sm
                        @if($status === 'pending') text-[var(--color-warning-text)] bg-[var(--color-warning-bg)] border-[var(--color-warning-border)]
                        @elseif($status === 'agreed') text-[#16283C] bg-[#16283C]/10 border-[#16283C]/10
                        @elseif($status === 'accepted') text-purple-700 bg-purple-50 dark:bg-purple-950/20 border-purple-500/10
                        @else text-slate-500 bg-slate-500/10 border-slate-500/10 @endif">
                        Status: {{ $status }}
                    </span>
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 flex flex-col bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl overflow-hidden shadow-sm h-[600px]">
                <div class="px-6 py-4 bg-slate-50/50 dark:bg-slate-900/40 border-b border-slate-150 dark:border-slate-700/60 flex items-center justify-between shrink-0">
                    <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Live Chat Console</h3>
                    <span class="text-[10px] font-bold font-mono text-slate-500 dark:text-slate-400">Secure Direct Message Tunnel</span>
                </div>

                <div class="flex-1 p-6 overflow-y-auto space-y-4" id="chat-messages-container">
                    @foreach($haulIntent->messages as $msg)
                        @php
                            $isSystem = Str::startsWith($msg->message_text, '[System');
                            $isMine = ($msg->sender_id === Auth::id());
                        @endphp
                        @if($isSystem)
                            <div class="flex justify-center my-3">
                                <div class="px-4 py-2 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] rounded-2xl max-w-md text-center">
                                    <p class="text-[11px] font-bold text-[var(--color-warning-text)] leading-relaxed italic">{{ $msg->message_text }}</p>
                                    <span class="text-[9px] text-slate-500 dark:text-slate-400 mt-1 block font-mono">{{ $msg->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        @else
                            <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[70%] flex flex-col {{ $isMine ? 'items-end' : 'items-start' }}">
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 mb-1 px-1 font-semibold">{{ $msg->sender->name }}</span>
                                    <div class="px-4 py-3 rounded-2xl text-xs leading-relaxed shadow-sm font-medium
                                        @if($isMine) bg-[#16283C] dark:bg-[#16283C] text-white rounded-br-none
                                        @else bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-bl-none border border-slate-200/40 dark:border-slate-700/60 @endif">
                                        {{ $msg->message_text }}
                                    </div>
                                    <span class="text-[9px] text-slate-500 dark:text-slate-400 mt-1 px-1 font-mono">{{ $msg->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="p-4 border-t border-slate-150 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/30 shrink-0">
                    @if(in_array($status, ['accepted', 'declined']))
                        <div class="text-center p-4 bg-purple-50 dark:bg-purple-950/20 border border-purple-200/50 dark:border-purple-900/30 rounded-xl">
                            <p class="text-purple-800 dark:text-purple-300 text-xs font-bold leading-none">
                                {{ $status === 'accepted' ? 'Haul booking confirmed. This thread is read-only.' : 'This intent was declined and the thread is closed.' }}
                            </p>
                        </div>
                    @else
                        <form id="send-message-form" class="flex gap-2" onsubmit="return sendMessage(event)">
                            @csrf
                            <input type="text" id="message-input" name="message_text" placeholder="Type message..." required autocomplete="off"
                                class="flex-1 px-4 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] dark:text-white transition">
                            <button type="submit" class="px-5 py-3 {{ $accentBg }} text-white font-bold rounded-xl text-xs transition duration-200 shadow-sm {{ $shadowColor }} cursor-pointer">Send</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="space-y-6">

                <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm">
                    <h3 class="text-sm font-extrabold text-slate-800 dark:text-white heading-font mb-4 uppercase tracking-wider">Load Overview</h3>
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700/40">
                            <span class="text-slate-500 dark:text-slate-400">Crop:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ $harvest?->crop?->name ?? $harvest?->crop_type }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700/40">
                            <span class="text-slate-500 dark:text-slate-400">Volume:</span>
                            <span class="font-bold font-mono text-slate-700 dark:text-slate-300">{{ number_format($haulIntent->haulRequest->harvest?->quantity_kg ?? 0) }} kg</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700/40">
                            <span class="text-slate-500 dark:text-slate-400">Pickup Date:</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $haulIntent->haulRequest->pickup_date?->format('M d, Y') ?? 'Not set' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-slate-500 dark:text-slate-400">Suggested:</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $haulIntent->suggested_date?->format('M d, Y') ?? 'Not set' }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm">
                    <h3 class="text-sm font-extrabold text-slate-800 dark:text-white heading-font mb-4 uppercase tracking-wider">Proposed Hauling Rate</h3>
                    <div class="bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 p-4 rounded-2xl mb-4 text-center">
                        <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Current Rate</p>
                        <p class="text-2xl font-black text-slate-800 dark:text-white font-mono mt-1">
                            <span id="current-rate">{{ $haulIntent->hauling_rate_php_per_kg ? '₱'.number_format((float) $haulIntent->hauling_rate_php_per_kg, 2).' /kg' : '—' }}</span>
                        </p>
                        <p class="text-[10px] text-slate-400 mt-1">Logistics offer: <span id="offer-rate">{{ $haulIntent->offer_rate_php_per_kg ? '₱'.number_format((float) $haulIntent->offer_rate_php_per_kg, 2) : '—' }}</span>
                             Farmer counter: <span id="counter-rate">{{ $haulIntent->counter_rate_php_per_kg ? '₱'.number_format((float) $haulIntent->counter_rate_php_per_kg, 2) : '—' }}</span>
                        </p>
                    </div>

                    @if(!in_array($status, ['accepted', 'declined']))
                        @if(!$isFarmer)
                            <form id="propose-rate-form" class="space-y-3 mb-4" onsubmit="return proposeRate(event)">
                                @csrf
                                <label for="offer_rate_php_per_kg" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Offer Rate (₱/kg)</label>
                                <input type="number" step="0.01" min="0.5" max="1000" name="offer_rate_php_per_kg" id="offer_rate_php_per_kg" required value="{{ $haulIntent->offer_rate_php_per_kg ?? '' }}" placeholder="₱/kg"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
                                <button type="submit" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs transition duration-200 cursor-pointer">Propose Rate</button>
                            </form>
                        @else
                            <form id="counter-rate-form" class="space-y-3 mb-4" onsubmit="return counterRate(event)">
                                @csrf
                                <label for="counter_rate_php_per_kg" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Counter Rate (₱/kg)</label>
                                <input type="number" step="0.01" min="0.5" max="1000" name="counter_rate_php_per_kg" id="counter_rate_php_per_kg" required value="{{ $haulIntent->counter_rate_php_per_kg ?? '' }}" placeholder="₱/kg"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
                                <button type="submit" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs transition duration-200 cursor-pointer">Counter Rate</button>
                            </form>
                        @endif

                        @if($haulIntent->currentRate())
                            <form id="agree-terms-form" onsubmit="return agreeTerms(event)">
                                @csrf
                                <button type="submit" id="agree-btn" class="w-full py-3 {{ $accentBg }} text-white font-bold rounded-xl text-xs transition duration-200 shadow-sm {{ $shadowColor }} cursor-pointer">
                                    {{ $isFarmer ? 'Agree & Book This Rate' : 'Agree to This Rate' }}
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var intentId = {{ $haulIntent->id }};
    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var userId = {{ Auth::id() }};
    var isFarmer = {{ $isFarmer ? 'true' : 'false' }};
    var lastMsgId = {{ $haulIntent->messages->max('id') ?? 'null' }};

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
            ? 'bg-[#16283C] dark:bg-[#16283C] text-white rounded-br-none'
            : 'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-bl-none border border-slate-200/40 dark:border-slate-700/60';
        var name = (msg.sender && msg.sender.name) ? msg.sender.name : 'Unknown';
        return '<div class="flex ' + align + ' my-2">' +
            '<div class="max-w-[70%] flex flex-col ' + align + '">' +
            '<span class="text-[10px] text-slate-500 dark:text-slate-400 mb-1 px-1 font-semibold">' + escapeHtml(name) + '</span>' +
            '<div class="px-4 py-3 rounded-2xl text-xs leading-relaxed shadow-sm font-medium ' + bubble + '">' + escapeHtml(msg.message_text) + '</div>' +
            '<span class="text-[9px] text-slate-500 dark:text-slate-400 mt-1 px-1 font-mono">' + timeAgo(msg.created_at) + '</span>' +
            '</div></div>';
    }

    function updateUI(data) {
        var cur = document.getElementById('current-rate');
        var off = document.getElementById('offer-rate');
        var cnt = document.getElementById('counter-rate');
        var rate = data.hauling_rate || data.counter_rate || data.offer_rate;
        if (rate !== undefined && rate !== null && cur && rate) {
            cur.textContent = '₱' + parseFloat(rate).toFixed(2) + ' /kg';
        }
        if (data.offer_rate && off) off.textContent = '₱' + parseFloat(data.offer_rate).toFixed(2);
        if (data.counter_rate && cnt) cnt.textContent = '₱' + parseFloat(data.counter_rate).toFixed(2);
        if (data.status) {
            var statusEl = document.getElementById('deal-status-badge');
            if (statusEl && statusEl.textContent.indexOf(data.status) === -1) {
                statusEl.textContent = 'Status: ' + data.status;
            }
        }
        // Auto-reveal Agree button once any rate exists (no reload needed)
        if (rate && data.status !== 'accepted' && data.status !== 'declined'
            && !document.getElementById('agree-terms-form')) {
            var rateForm = document.getElementById('counter-rate-form') || document.getElementById('propose-rate-form');
            if (rateForm) {
                rateForm.insertAdjacentHTML('afterend',
                    '<form id="agree-terms-form" onsubmit="return agreeTerms(event)">' +
                    '<input type="hidden" name="_token" value="' + csrfToken + '">' +
                    '<button type="submit" id="agree-btn" class="w-full py-3 bg-[#16283C] hover:bg-[#0E1620] dark:bg-[#16283C] dark:hover:bg-[#0E1620] text-white font-bold rounded-xl text-xs transition duration-200 shadow-sm cursor-pointer">' +
                    (isFarmer ? 'Agree & Book This Rate' : 'Agree to This Rate') +
                    '</button></form>');
            }
        }
    }

    function appendMessage(msg) {
        if (msg.id <= lastMsgId) return;
        var container = document.getElementById('chat-messages-container');
        container.insertAdjacentHTML('beforeend', renderMessage(msg));
        lastMsgId = msg.id;
        scrollChatBottom();
    }

    function refreshChat() {
        var url = '/haul-negotiations/' + intentId + '/messages?since_id=' + lastMsgId;
        return fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(function (data) {
                (data.messages || []).forEach(function (msg) { if (msg.id > lastMsgId) appendMessage(msg); });
                updateUI(data);
            })
            .catch(function (err) { console.error('refreshChat:', err); });
    }

    (function pollLoop() {
        setTimeout(function () { refreshChat().then(pollLoop).catch(pollLoop); }, 3000);
    })();

    function sendMessage(e) {
        e.preventDefault();
        var input = document.getElementById('message-input');
        var text = input.value.trim();
        if (!text) return false;
        input.value = '';
        input.focus();
        fetch('/haul-negotiations/' + intentId + '/message', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ message_text: text })
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        }).then(function (data) {
            if (data.message) appendMessage(data.message);
        }).catch(function (err) { console.error('sendMessage:', err); });
        return false;
    }

    function proposeRate(e) {
        e.preventDefault();
        var form = document.getElementById('propose-rate-form');
        var data = new FormData(form);
        fetch('/haul-negotiations/' + intentId + '/propose-rate', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: data
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        }).then(function (data) {
            if (data.message) appendMessage(data.message);
            updateUI(data);
        }).catch(function (err) { console.error('proposeRate:', err); });
        return false;
    }

    function counterRate(e) {
        e.preventDefault();
        var form = document.getElementById('counter-rate-form');
        var data = new FormData(form);
        fetch('/haul-negotiations/' + intentId + '/counter-rate', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: data
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        }).then(function (data) {
            if (data.message) appendMessage(data.message);
            updateUI(data);
        }).catch(function (err) { console.error('counterRate:', err); });
        return false;
    }

    function agreeTerms(e) {
        e.preventDefault();
        var rate = {{ $haulIntent->hauling_rate_php_per_kg ? (float) $haulIntent->hauling_rate_php_per_kg : 'null' }};
        var volume = {{ (float) ($haulIntent->haulRequest->harvest?->quantity_kg ?? 0) }};
        var text = rate !== null && volume > 0
            ? 'Book this haul at ₱' + rate.toFixed(2) + '/kg × ' + volume.toLocaleString() + ' kg = ₱' + (rate * volume).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '?'
            : 'Book this haul at the agreed rate?';
        swalConfirm(function () {
            fetch('/haul-negotiations/' + intentId + '/agree', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            }).then(function (data) {
                if (data.message) appendMessage(data.message);
                updateUI(data);
            }).catch(function (err) { console.error('agreeTerms:', err); });
        }, {
            title: 'Agree & Book This Rate?',
            text: text,
            icon: 'question',
            confirmText: 'Yes, book this haul',
            cancelText: 'Not yet',
            confirmColor: isFarmer ? '#16283C' : '#BFA05A'
        });
        return false;
    }

    document.addEventListener('DOMContentLoaded', scrollChatBottom);
</script>
</x-layout>
