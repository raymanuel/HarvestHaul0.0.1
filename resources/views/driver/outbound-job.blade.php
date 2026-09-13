<x-driver-layout title="Job #{{ $job->id }} — HarvestHaul" themeColor="#16283C">

    <header class="bg-[#16283C] text-white px-5 pt-6 pb-5 sticky top-0 z-20 shadow-md">
        <div class="flex items-center gap-4 max-w-lg mx-auto">
            <a href="{{ route('driver.dashboard') }}" class="text-white bg-white/10 hover:bg-white/20 p-2 rounded-xl border border-white/10 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <p class="text-[10px] text-[#16283C]/60 font-bold uppercase tracking-widest">Outbound Delivery</p>
                <h1 class="text-xl font-bold leading-tight heading-font mt-0.5">Job #{{ $job->id }}</h1>
            </div>
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 py-6 space-y-5">

        <x-flash-success />
        <x-flash-error />

        {{-- Job Summary Card --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-sm">
            <div class="flex items-start justify-between px-5 py-5 border-b border-slate-100 bg-slate-50/30">
                <div>
                    <p class="text-sm font-black text-slate-800 heading-font">Outbound Delivery &middot; {{ number_format($job->total_kg, 1) }} kg</p>
                    <p class="text-[11px] text-slate-400 font-semibold mt-1">{{ number_format($job->load_percentage, 1) }}% truck capacity utilized</p>
                </div>
                @php
                    $badge = match($job->status->value) {
                        'confirmed'   => ['bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border-[var(--color-warning-border)]', 'Ready'],
                        'in_progress' => ['bg-[#0E1620]/10 text-[#0E1620] border-[#0E1620]/20', 'In Transit'],
                        default       => ['bg-slate-50 text-slate-500 border-slate-200/50', $job->status->label()],
                    };
                @endphp
                <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-md border {{ $badge[0] }}">
                    {{ $badge[1] }}
                </span>
            </div>
            <div class="px-5 py-4 text-xs text-slate-500 flex items-center gap-1.5 font-semibold">
                <span class="text-slate-400">Assigned Vehicle:</span>
                <span class="text-slate-700 font-bold bg-slate-100 px-2 py-0.5 rounded-md">{{ $job->truck->plate_number ?? '—' }}</span>
            </div>
            @if($job->delivery_deadline)
                <div class="px-5 py-3 border-t border-slate-100 bg-[var(--color-warning-bg)]/50">
                    <p class="text-[10px] font-bold text-[var(--color-warning-text)] uppercase tracking-widest flex items-center gap-1">
                        <x-icon name="clock" class="w-3 h-3" /> Delivery Deadline: {{ \Carbon\Carbon::parse($job->delivery_deadline)->format('g:i A') }}
                    </p>
                </div>
            @endif
        </div>

        {{-- Customer Stop Card --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-sm">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100 bg-slate-50/20">
                <div class="w-7 h-7 rounded-xl bg-rose-500 text-white text-xs font-black flex items-center justify-center flex-shrink-0 heading-font">
                    <x-icon name="pin" class="w-4 h-4" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-slate-800 truncate heading-font">{{ $order->customerCard->name ?? 'Customer' }}</p>
                    <p class="text-[11px] text-slate-400 font-semibold mt-0.5">{{ $order->customerCard->address ?? '' }}</p>
                </div>
            </div>

            <div class="px-5 py-4 space-y-2 text-xs text-slate-500 font-semibold">
                @if($order->customerCard->latitude && $order->customerCard->longitude)
                    <div class="flex justify-between items-center py-1 border-b border-slate-50">
                        <span class="text-slate-400"><x-icon name="map" class="w-3 h-3 inline" /> Drop Coordinates</span>
                        <span class="font-mono text-[10px] text-slate-400 bg-slate-50 px-2 py-0.5 rounded border border-slate-100">{{ $order->customerCard->latitude }}, {{ $order->customerCard->longitude }}</span>
                    </div>
                @endif
                @if($order->customerCard->contact)
                    <div class="flex justify-between items-center py-1 border-b border-slate-50">
                        <span class="text-slate-400"><x-icon name="document" class="w-3 h-3 inline" /> Contact</span>
                        <span class="text-slate-800 font-bold">{{ $order->customerCard->contact }}</span>
                    </div>
                @endif
            </div>

            {{-- Manifest Lines --}}
            <div class="px-5 pb-4">
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2">Manifest</p>
                <div class="bg-slate-50 rounded-xl overflow-hidden">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-slate-200/60">
                                <th class="px-3 py-2 text-left text-[9px] font-bold text-slate-400 uppercase tracking-widest">Crop</th>
                                <th class="px-3 py-2 text-right text-[9px] font-bold text-slate-400 uppercase tracking-widest">Kg</th>
                                <th class="px-3 py-2 text-right text-[9px] font-bold text-slate-400 uppercase tracking-widest">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->orderLines as $line)
                                <tr class="border-b border-slate-100 last:border-0">
                                    <td class="px-3 py-2 font-bold text-slate-800">{{ $line->crop_type }}</td>
                                    <td class="px-3 py-2 text-right text-slate-600 font-bold">{{ number_format((float) $line->quantity_kg, 1) }}</td>
                                    <td class="px-3 py-2 text-right text-slate-700 font-bold">₱{{ number_format((float) $line->subtotal, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Route Map --}}
        @if($job->start_latitude && $job->start_longitude && $order->customerCard->latitude && $order->customerCard->longitude)
            <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-sm">
                <div id="routeMap" class="w-full h-48"></div>
            </div>
        @endif

        {{-- Coordinator Instructions --}}
        @if($job->notes)
            <div class="bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] font-bold text-[var(--color-warning-text)] uppercase tracking-widest mb-2 flex items-center gap-1"><x-icon name="document" class="w-3 h-3" /> Dispatch Instructions</p>
                <p class="text-xs text-[var(--color-warning-text)] leading-relaxed font-semibold">{{ $job->notes }}</p>
            </div>
        @endif

        {{-- Quick Fuel Log Form --}}
        @if($job->status->value === 'in_progress')
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
                <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-1">
                    <x-icon name="fuel" class="w-3 h-3" /> Log Fuel Purchase
                </h3>
                <form method="POST" action="{{ route('driver.jobs.fuel-log', $job) }}" class="space-y-3" onsubmit="event.preventDefault(); swalConfirm(this, {title:'Save Refuel Log?', text:'Log this fuel purchase for your trip?', icon:'question', confirmText:'Yes, save', cancelText:'Cancel', confirmColor:'#16283C'});">
                    @csrf
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label for="fuel_liters" class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Liters</label>
                            <input type="number" step="0.01" name="fuel_liters" id="fuel_liters" required placeholder="0.00"
                                class="w-full border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs text-slate-700 font-bold focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C]">
                        </div>
                        <div>
                            <label for="cost" class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Cost (₱)</label>
                            <input type="number" step="0.01" name="cost" id="cost" required placeholder="0.00"
                                class="w-full border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs text-slate-700 font-bold focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C]">
                        </div>
                        <div>
                            <label for="odometer_reading" class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Odometer (km)</label>
                            <input type="number" step="0.1" name="odometer_reading" id="odometer_reading" required placeholder="0.0"
                                class="w-full border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs text-slate-700 font-bold focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C]">
                        </div>
                    </div>
                    <button type="submit" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow-sm transition">
                        Save Refuel Log
                    </button>
                </form>
            </div>
        @endif

        {{-- Accept Job --}}
        @if($job->status->value === 'confirmed' && !$job->accepted_at)
            <form method="POST" action="{{ route('driver.jobs.accept', $job) }}" onsubmit="event.preventDefault(); swalConfirm(this, {title:'Accept Job?', text:'Accept this outbound delivery and begin your trip?', icon:'question', confirmText:'Yes, accept', cancelText:'Cancel', confirmColor:'#16283C'});">
                @csrf
                <button type="submit" class="w-full py-4 bg-[#16283C] hover:bg-[#0E1620] text-white text-xs font-bold rounded-2xl shadow-sm transition active:scale-[0.98]">
                    Accept Outbound Delivery
                </button>
            </form>
            <p class="text-[10px] text-slate-400 text-center -mt-2 font-semibold">Accept before starting the trip.</p>
        @endif

        {{-- Mark Delivered Button (only during in_progress and not yet delivered) --}}
        @if($job->status->value === 'in_progress' && !$order->delivered_at)
            <form method="POST" action="{{ route('driver.jobs.outbound-delivered', $job) }}" onsubmit="event.preventDefault(); swalConfirm(this, {title:'Mark Delivered?', text:'Confirm you have delivered the goods to the customer?', icon:'success', confirmText:'Yes, delivered', cancelText:'Cancel', confirmColor:'#16283C'});">
                @csrf
                <button type="submit" class="w-full py-4 bg-[#16283C] hover:bg-[#0E1620] text-white text-xs font-bold rounded-2xl shadow-sm transition active:scale-[0.98]">
                    <x-icon name="pin" class="w-4 h-4 inline" /> Mark Delivered at Customer Location
                </button>
            </form>
        @endif

        {{-- Status Action (Start / Finalize) --}}
        @if(in_array($job->status->value, ['confirmed', 'in_progress']))
            <form method="POST" action="{{ route('driver.jobs.status', $job) }}" onsubmit="event.preventDefault(); swalConfirm(this, {{ $job->status->value === 'confirmed' ? "{title:'Start Trip?', text:'Begin the outbound delivery?', icon:'question', confirmText:'Yes, start', cancelText:'Cancel', confirmColor:'#16283C'}" : ($order->delivered_at ? "{title:'Finalize Job?', text:'Mark this job as completed?', icon:'success', confirmText:'Yes, complete', cancelText:'Cancel', confirmColor:'#16283C'}" : 'false') }});">
                @csrf @method('PATCH')
                @if($job->status->value === 'confirmed')
                    <button type="submit" class="w-full py-4 bg-[#0E1620]/10 hover:bg-[#0E1620]/20 text-[#0E1620] text-xs font-bold rounded-2xl transition active:scale-[0.98] {{ !$job->accepted_at ? 'opacity-50 cursor-not-allowed' : '' }}" {{ !$job->accepted_at ? 'disabled' : '' }}>
                        Start Job — Mark In Transit
                    </button>
                @elseif($order->delivered_at)
                    <div class="mb-3">
                        <label for="end_odometer_reading" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">End Odometer (km)</label>
                        <input type="number" step="0.01" min="0.01" name="end_odometer_reading" id="end_odometer_reading" required placeholder="0.00"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-xs text-slate-700 font-bold focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C]">
                    </div>
                    <button type="submit" class="w-full py-4 bg-[#16283C] hover:bg-[#0E1620] text-white text-xs font-bold rounded-2xl transition active:scale-[0.98]">
                        Finalize Job — Mark Completed
                    </button>
                @else
                    <button type="button" disabled class="w-full py-4 bg-slate-200 text-slate-400 rounded-2xl text-xs font-bold cursor-not-allowed">
                        Mark Delivered to Finalize Job
                    </button>
                @endif
            </form>
        @endif

    </main>

    @push('scripts')
    <script>
    (function () {
        const jobStatus  = '{{ $job->status->value }}';
        const jobId      = {{ $job->id }};
        const csrfToken  = document.querySelector('meta[name="csrf-token"]').content;
        const trackingUrl = '{{ route("driver.tracking.store") }}';

        if (jobStatus === 'in_progress') {
            let lastSentAt = 0;
            const SEND_INTERVAL_MS = 30000;

            async function sendPosition(latitude, longitude) {
                const now = Date.now();
                if (now - lastSentAt < SEND_INTERVAL_MS) return;
                lastSentAt = now;
                try {
                    await fetch(trackingUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: JSON.stringify({ pooling_job_id: jobId, latitude, longitude, posted_at: new Date().toISOString() }),
                    });
                } catch (err) { /* offline */ }
            }

            if ('geolocation' in navigator) {
                navigator.geolocation.watchPosition(
                    (pos) => sendPosition(pos.coords.latitude, pos.coords.longitude),
                    () => {},
                    { enableHighAccuracy: true, timeout: 15000, maximumAge: 10000 }
                );
                setInterval(() => {
                    navigator.geolocation.getCurrentPosition(
                        (pos) => sendPosition(pos.coords.latitude, pos.coords.longitude),
                        () => {},
                        { enableHighAccuracy: true, timeout: 10000, maximumAge: 25000 }
                    );
                }, SEND_INTERVAL_MS);
            }
        }

        const mapEl = document.getElementById('routeMap');
        if (mapEl && typeof L !== 'undefined') {
            const startLat = {{ $job->start_latitude ?? 6.1164 }};
            const startLng = {{ $job->start_longitude ?? 125.1716 }};
            const endLat   = {{ $order->customerCard->latitude ?? 6.1164 }};
            const endLng   = {{ $order->customerCard->longitude ?? 125.1716 }};

            const map = L.map('routeMap').setView([startLat, startLng], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: ' OpenStreetMap contributors' }).addTo(map);

            const pickupIcon = L.divIcon({
                html: '<div style="width:16px;height:16px;border-radius:50%;background:#16283C;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.3)"></div>',
                className: '', iconSize: [16, 16], iconAnchor: [8, 8]
            });
            const deliveryIcon = L.divIcon({
                html: '<div style="width:16px;height:16px;border-radius:50%;background:#EF4444;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.3)"></div>',
                className: '', iconSize: [16, 16], iconAnchor: [8, 8]
            });

            const bounds = L.latLngBounds();
            L.marker([startLat, startLng], { icon: pickupIcon }).bindPopup('<b>Start (Coop)</b>').addTo(map);
            bounds.extend([startLat, startLng]);
            L.marker([endLat, endLng], { icon: deliveryIcon }).bindPopup('<b>{{ $order->customerCard->name ?? "Delivery" }}</b>').addTo(map);
            bounds.extend([endLat, endLng]);
            map.fitBounds(bounds, { padding: [50, 50] });
        }
    })();
    </script>
    @endpush

</x-driver-layout>
