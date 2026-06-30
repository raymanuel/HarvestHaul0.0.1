<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">

    <!-- Leaflet Assets -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    @php
        $role = Auth::user()->role;
        $isBuyer = ($role === 'buyer');
        $themeColor = $isBuyer ? 'violet' : 'emerald';
        
        $accentText = $isBuyer ? 'text-violet-600 dark:text-violet-400' : 'text-emerald-600 dark:text-emerald-400';
        $accentBg = $isBuyer ? 'bg-violet-600 hover:bg-violet-700 dark:bg-violet-500 dark:hover:bg-violet-600' : 'bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600';
        $accentBorder = $isBuyer ? 'border-violet-500/20' : 'border-emerald-500/20';
        $accentBadge = $isBuyer ? 'bg-violet-500/10' : 'bg-emerald-500/10';
        $shadowColor = $isBuyer ? 'shadow-violet-500/10' : 'shadow-emerald-500/10';
    @endphp

    <!-- Ambient glow decoration -->
    <div class="absolute top-0 right-1/4 w-96 h-96 rounded-full bg-{{ $themeColor }}-500/5 blur-[120px] pointer-events-none z-0"></div>

    <div class="relative z-10">
        <!-- Page Header -->
        <header class="mb-6 pt-6">
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ $isBuyer ? route('buyer.negotiations') : route('farmer.negotiations') }}" class="text-xs font-bold {{ $accentText }} hover:underline flex items-center gap-1">
                    ← Back to Negotiations List
                </a>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest {{ $accentText }} {{ $accentBadge }} px-3 py-1 rounded-full border {{ $accentBorder }}">B2B Room #{{ $negotiation->id }}</span>
                    <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight heading-font mt-3">
                        Crop Negotiation Chat
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Direct negotiation room between Farmer <strong>{{ $negotiation->farmer->name }}</strong> and Buyer <strong>{{ $negotiation->buyer->name }}</strong>.</p>
                </div>
                <div>
                    <span class="text-[10px] font-extrabold uppercase tracking-widest px-3 py-1.5 rounded-full border
                        @if($negotiation->status === 'OPEN') text-violet-700 bg-violet-500/10 border-violet-500/10
                        @elseif($negotiation->status === 'AGREED') text-emerald-700 bg-emerald-500/10 border-emerald-500/10
                        @elseif($negotiation->status === 'COMPLETED') text-sky-700 bg-sky-500/10 border-sky-500/10
                        @else text-slate-500 bg-slate-500/10 border-slate-500/10 @endif shadow-sm">
                        Deal Status: {{ $negotiation->status }}
                    </span>
                </div>
            </div>
        </header>

        <!-- Main Workspace: 2 Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- COLUMN 1 & 2: Chat Window -->
            <div class="lg:col-span-2 flex flex-col bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl overflow-hidden shadow-sm h-[600px]">
                
                <!-- Chat Header -->
                <div class="px-6 py-4 bg-slate-50/50 dark:bg-slate-900/40 border-b border-slate-150 dark:border-slate-700/60 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></div>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Live Chat Console</h3>
                    </div>
                    <span class="text-[10px] font-bold font-mono text-slate-400 dark:text-slate-500">Secure Direct Message Tunnel</span>
                </div>

                <!-- Chat Messages Scroll Area -->
                <div class="flex-1 p-6 overflow-y-auto space-y-4" id="chat-messages-container">
                    @foreach($negotiation->messages as $msg)
                        @php
                            $isSystem = Str::startsWith($msg->message_text, '[System');
                            $isMine = ($msg->sender_id === Auth::id());
                        @endphp

                        @if($isSystem)
                            <!-- System notification style -->
                            <div class="flex justify-center my-3">
                                <div class="px-4 py-2 bg-amber-500/10 dark:bg-amber-400/5 border border-amber-500/20 dark:border-amber-400/10 rounded-2xl max-w-md text-center">
                                    <p class="text-[11px] font-bold text-amber-800 dark:text-amber-400 leading-relaxed italic">
                                        {{ $msg->message_text }}
                                    </p>
                                    <span class="text-[9px] text-slate-400 dark:text-slate-500 mt-1 block font-mono">{{ $msg->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        @else
                            <!-- Chat message style -->
                            <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[70%] flex flex-col {{ $isMine ? 'items-end' : 'items-start' }}">
                                    <!-- Sender Name Tag -->
                                    <span class="text-[10px] text-slate-400 dark:text-slate-505 mb-1 px-1 font-semibold">
                                        {{ $msg->sender->name }}
                                    </span>
                                    <!-- Bubble -->
                                    <div class="px-4 py-3 rounded-2xl text-xs leading-relaxed shadow-sm font-medium
                                        @if($isMine)
                                            {{ $isBuyer ? 'bg-violet-600 text-white rounded-br-none' : 'bg-emerald-600 text-white rounded-br-none' }}
                                        @else
                                            bg-slate-100 dark:bg-slate-750 text-slate-800 dark:text-slate-200 rounded-bl-none border border-slate-200/40 dark:border-slate-700/60
                                        @endif">
                                        {{ $msg->message_text }}
                                    </div>
                                    <!-- Timestamp -->
                                    <span class="text-[9px] text-slate-400 dark:text-slate-500 mt-1 px-1 font-mono">
                                        {{ $msg->created_at->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                <!-- Chat Message Input Area -->
                <div class="p-4 border-t border-slate-150 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/30 shrink-0">
                    @if($negotiation->status === 'COMPLETED')
                        <div class="text-center p-3 bg-sky-500/10 border border-sky-500/20 text-sky-700 dark:text-sky-405 rounded-xl text-xs font-bold leading-none">
                            🔒 B2B deal finalized and closed. Chat room is locked to read-only.
                        </div>
                    @else
                        <form action="{{ route('negotiations.message', $negotiation->id) }}" method="POST" class="flex gap-2">
                            @csrf
                            <input type="text" name="message_text" placeholder="Type message..." required autocomplete="off"
                                class="flex-1 px-4 py-3 bg-white dark:bg-slate-750 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-{{ $themeColor }}-500/10 focus:border-{{ $themeColor }}-500 dark:text-white transition">
                            <button type="submit" class="px-5 py-3 {{ $accentBg }} text-white font-bold rounded-xl text-xs transition duration-200 shadow-sm {{ $shadowColor }} cursor-pointer">
                                Send
                            </button>
                        </form>
                    @endif
                </div>

            </div>

            <!-- COLUMN 3: Offer Panel & Drop-off Config -->
            <div class="space-y-6">

                <!-- Lot Overview Card -->
                <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm">
                    <h3 class="text-sm font-extrabold text-slate-800 dark:text-white heading-font mb-4 uppercase tracking-wider">Lot Overview</h3>
                    
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700/40">
                            <span class="text-slate-400 dark:text-slate-500">Crop Type:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ $negotiation->harvest->crop->name ?? $negotiation->harvest->crop_type }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700/40">
                            <span class="text-slate-400 dark:text-slate-500">Variety:</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-350">{{ $negotiation->harvest->cropVariety->name ?? $negotiation->harvest->variety ?? 'Standard' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700/40">
                            <span class="text-slate-400 dark:text-slate-500">Original Volume:</span>
                            <span class="font-bold font-mono text-slate-700 dark:text-slate-350">{{ number_format($negotiation->harvest->quantity_kg) }} kg</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-slate-400 dark:text-slate-500">Pickup Location:</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-350 text-right max-w-[150px] truncate" title="{{ $negotiation->harvest->farmer->farmerProfile->farm_location ?? 'Farmer' }}">
                                {{ $negotiation->harvest->farmer->farmerProfile->farm_location ?? 'Farmer farm' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Proposed Terms Panel -->
                <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm">
                    <h3 class="text-sm font-extrabold text-slate-800 dark:text-white heading-font mb-4 uppercase tracking-wider">Proposed Terms</h3>

                    <div class="space-y-4 mb-6">
                        <div class="bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 p-4 rounded-2xl flex justify-between items-center">
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Proposed Price</p>
                                <p class="text-2xl font-black text-slate-800 dark:text-white font-mono mt-1">
                                    {{ $negotiation->negotiated_price ? '₱'.number_format($negotiation->negotiated_price, 2) : '—' }} <span class="text-[10px] font-semibold text-slate-400">/ kg</span>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Proposed Volume</p>
                                <p class="text-lg font-extrabold text-slate-700 dark:text-slate-300 font-mono mt-1">
                                    {{ $negotiation->negotiated_volume ? number_format($negotiation->negotiated_volume).' kg' : '—' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    @if($negotiation->status !== 'COMPLETED')
                        <!-- Propose Terms Action Form -->
                        <form action="{{ route('negotiations.propose', $negotiation->id) }}" method="POST" class="space-y-4 mb-4">
                            @csrf
                            <h4 class="text-xs font-bold text-slate-650 dark:text-slate-350 uppercase tracking-wider">Update Proposed Terms</h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Unit Price (₱/kg)</label>
                                    <input type="number" step="0.01" min="0.01" name="negotiated_price" required value="{{ $negotiation->negotiated_price ?? '' }}" placeholder="₱/kg"
                                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-750 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-{{ $themeColor }}-500/10 focus:border-{{ $themeColor }}-500 transition">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Volume (kg)</label>
                                    <input type="number" step="0.01" min="0.01" name="negotiated_volume" required value="{{ $negotiation->negotiated_volume ?? '' }}" placeholder="kg"
                                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-750 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-{{ $themeColor }}-500/10 focus:border-{{ $themeColor }}-500 transition">
                                </div>
                            </div>
                            <button type="submit" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-750 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs transition duration-200 cursor-pointer">
                                Propose New Terms
                            </button>
                        </form>

                        @if($negotiation->negotiated_price)
                            <!-- Agree Button -->
                            <form action="{{ route('negotiations.agree', $negotiation->id) }}" method="POST" class="mb-4">
                                @csrf
                                <button type="submit" class="w-full py-3 {{ $accentBg }} text-white font-bold rounded-xl text-xs transition duration-200 shadow-sm {{ $shadowColor }} cursor-pointer">
                                    {{ $negotiation->status === 'AGREED' ? '✓ You Already Agreed' : 'Agree to These Terms' }}
                                </button>
                            </form>
                        @endif
                    @endif
                </div>

                <!-- Finalize Deal Drop-off Panel (Buyer Only when status is AGREED) -->
                @if($isBuyer && $negotiation->status === 'AGREED')
                    <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm">
                        <h3 class="text-sm font-extrabold text-slate-850 dark:text-white heading-font mb-2 uppercase tracking-wider text-violet-650 dark:text-violet-400">Finalize & Submit Drop-off</h3>
                        <p class="text-[11px] text-slate-505 dark:text-slate-400 mb-4 leading-relaxed font-semibold">Terms are agreed. Submit your custom delivery drop-off location coordinates below to lock the transaction deal.</p>

                        <form action="{{ route('negotiations.finalize', $negotiation->id) }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Drop-off Street Address</label>
                                <input type="text" name="destination_address" id="destination_address" required placeholder="e.g. Dadiangas Wholesale Market Hub"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-750 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500/10 focus:border-violet-500 transition">
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Pin Drop-off Location on Map</label>
                                <div id="dropoff-map" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden" style="height: 220px;"></div>
                                <p id="dropoff-feedback" class="text-[10px] text-slate-500 mt-1.5 italic">Click map to place a pin marker.</p>
                            </div>

                            <!-- Hidden coordinate values -->
                            <input type="hidden" name="destination_latitude" id="destination_latitude">
                            <input type="hidden" name="destination_longitude" id="destination_longitude">

                            <button type="submit" class="w-full py-3 bg-gradient-to-r from-violet-650 to-indigo-600 hover:brightness-105 text-white font-bold rounded-xl text-xs transition duration-200 shadow-md shadow-violet-600/10 cursor-pointer">
                                Close Deal & Create Haul Request
                            </button>
                        </form>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            // General Santos City center defaults
                            let map = L.map('dropoff-map').setView([6.1164, 125.1716], 11);
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

                            let marker = null;

                            map.on('click', function (e) {
                                if (marker) map.removeLayer(marker);
                                marker = L.marker(e.latlng).addTo(map);

                                document.getElementById('destination_latitude').value = e.latlng.lat;
                                document.getElementById('destination_longitude').value = e.latlng.lng;
                                document.getElementById('dropoff-feedback').textContent = `📍 Selected: ${e.latlng.lat.toFixed(5)}, ${e.latlng.lng.toFixed(5)}`;
                            });

                            setTimeout(() => map.invalidateSize(), 200);
                        });
                    </script>
                @endif

            </div>

        </div>
    </div>

</div>

<!-- Auto scroll chat messages window to bottom on load -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chatContainer = document.getElementById('chat-messages-container');
        if (chatContainer) {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    });
</script>
</x-layout>
