<x-driver-layout title="Job #{{ $job->id }} — HarvestHaul" themeColor="#059669">

    <!-- Top Header Panel -->
    <header class="bg-gradient-to-tr from-[#3A7D44] to-[#2E6336] text-white px-5 pt-6 pb-5 sticky top-0 z-20 shadow-md">
        <div class="flex items-center gap-4 max-w-lg mx-auto">
            <a href="{{ route('driver.dashboard') }}" class="text-white bg-white/10 hover:bg-white/20 p-2 rounded-xl border border-white/10 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <p class="text-[10px] text-[#3A7D44]/60 font-bold uppercase tracking-widest">Cargo Details</p>
                <h1 class="text-xl font-bold leading-tight heading-font mt-0.5">Job #{{ $job->id }}</h1>
            </div>
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 py-6 space-y-5">

        <!-- Flash Messages -->
        <x-flash-success />
        <x-flash-error />

        <!-- Job Summary Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-sm">
            <div class="flex items-start justify-between px-5 py-5 border-b border-slate-100 bg-slate-50/30">
                <div>
                    <p class="text-sm font-black text-slate-800 heading-font">
                        {{ $job->farm_count }} {{ Str::plural('stop', $job->farm_count) }}
                        &middot; {{ number_format($job->total_kg, 1) }} kg payload
                    </p>
                    <p class="text-[11px] text-slate-400 font-semibold mt-1">{{ number_format($job->load_percentage, 1) }}% truck capacity utilized</p>
                </div>
                @php
                    $badge = match($job->status) {
                        'confirmed'   => ['bg-amber-50 text-amber-700 border-amber-200/50',  'Ready'],
                        'in_progress' => ['bg-[#1F4D25]/10 text-[#1F4D25] border-[#1F4D25]/20',    'In Transit'],
                        'completed'   => ['bg-[#3A7D44]/10 text-[#3A7D44] border-[#3A7D44]/20',  'Completed'],
                        default       => ['bg-slate-50 text-slate-500 border-slate-200/50',    ucfirst($job->status)],
                    };
                @endphp
                <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-md border {{ $badge[0] }}">
                    {{ $badge[1] }}
                </span>
            </div>

            <div class="px-5 py-4 text-xs text-slate-500 flex items-center gap-1.5 font-semibold">
                <span class="text-slate-400">Assigned Fleet:</span>
                <span class="text-slate-700 font-bold bg-slate-100 px-2 py-0.5 rounded-md">{{ $job->truck->plate_number ?? '—' }}</span>
                @if($job->truck->vehicle_type ?? false)
                    <span class="text-slate-300">&middot;</span>
                    <span class="text-slate-600">{{ $job->truck->vehicle_type }}</span>
                @endif
            </div>
        </div>

        <!-- Coordinator Instructions -->
        @if($job->notes)
            <div class="bg-amber-50/60 border border-amber-200/80 rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] font-bold text-amber-700 uppercase tracking-widest mb-2 flex items-center gap-1"><x-icon name="document" class="w-3 h-3" /> Dispatch Instructions</p>
                <p class="text-xs text-amber-800 leading-relaxed font-semibold">{{ $job->notes }}</p>
            </div>
        @endif

        <!-- Quick Fuel Log Form -->
        @if($job->status === 'in_progress')
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
                <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-1">
                    <x-icon name="fuel" class="w-3 h-3" /> Log Fuel Purchase
                </h3>
                <form method="POST" action="{{ route('driver.jobs.fuel-log', $job) }}" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Liters</label>
                            <input type="number" step="0.01" name="fuel_liters" required placeholder="0.00"
                                class="w-full border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs text-slate-700 font-bold focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/10 focus:border-[#3A7D44]">
                        </div>
                        <div>
                            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Cost (₱)</label>
                            <input type="number" step="0.01" name="cost" required placeholder="0.00"
                                class="w-full border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs text-slate-700 font-bold focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/10 focus:border-[#3A7D44]">
                        </div>
                        <div>
                            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Odometer (km)</label>
                            <input type="number" step="0.1" name="odometer_reading" required placeholder="0.0"
                                class="w-full border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs text-slate-700 font-bold focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/10 focus:border-[#3A7D44]">
                        </div>
                    </div>
                    <button type="submit" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow-sm transition">
                        Save Refuel Log
                    </button>
                </form>
            </div>
        @endif

        <!-- Status Action Button -->
        @if(in_array($job->status, ['confirmed', 'in_progress']))
            @php
                $allStopsDelivered = true;
                foreach($job->harvests as $h) {
                    if($h->pivot->status !== 'delivered') {
                        $allStopsDelivered = false;
                        break;
                    }
                }
            @endphp
            <form method="POST" action="{{ route('driver.jobs.status', $job) }}">
                @csrf @method('PATCH')
                @if($job->status === 'confirmed')
                    <button type="submit" class="w-full py-4 bg-gradient-to-tr from-[#1F4D25] to-[#2E6336] hover:shadow-[#1F4D25]/10 rounded-2xl text-xs font-bold text-white transition-all shadow-md active:scale-[0.98]">
                        Start Job — Mark In Transit
                    </button>
                @else
                    @if($allStopsDelivered)
                        <button type="submit" class="w-full py-4 bg-gradient-to-tr from-[#3A7D44] to-[#2E6336] hover:shadow-[#3A7D44]/10 rounded-2xl text-xs font-bold text-white transition-all shadow-md active:scale-[0.98]">
                            Finalize Job — Mark Completed
                        </button>
                    @else
                        <button type="button" disabled class="w-full py-4 bg-slate-200 text-slate-400 rounded-2xl text-xs font-bold cursor-not-allowed">
                            Complete Stop Deliveries to Finalize Job
                        </button>
                    @endif
                @endif
            </form>
        @endif

        <!-- Pickup Sequence -->
        <div>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3 px-1">Pickup Sequence</p>

            @foreach($job->harvests as $index => $harvest)
                <div class="bg-white rounded-2xl border border-slate-200/80 mb-4 overflow-hidden shadow-sm hover:shadow-md transition-all duration-200">

                    <!-- Stop Header -->
                    <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100 bg-slate-50/20">
                        <div class="w-7 h-7 rounded-xl bg-gradient-to-tr from-[#3A7D44] to-[#2E6336] text-white text-xs font-black flex items-center justify-center flex-shrink-0 heading-font">
                            {{ $index + 1 }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-800 truncate heading-font">
                                {{ $harvest->farmer->name ?? 'Unknown Farmer' }}
                            </p>
                            <p class="text-[11px] text-slate-400 font-semibold mt-0.5">
                                <span class="text-slate-400"><x-icon name="pin" class="w-3 h-3 inline" /> {{ $harvest->farmer->farmerProfile->barangay ?? 'No barangay on record' }}</span>
                              </p>
                          </div>
                      </div>

                    <!-- Stop Details -->
                    <div class="px-5 py-4 space-y-2 text-xs text-slate-500 font-semibold">
                        <div class="flex justify-between items-center py-1 border-b border-slate-50">
                            <span class="text-slate-400">Crop Type</span>
                            <span class="text-slate-800 font-bold">
                                {{ $harvest->crop->name ?? $harvest->crop_type ?? '—' }}
                                @if($harvest->variety)
                                    &middot; {{ $harvest->variety }}
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-slate-50">
                            <span class="text-slate-400"><x-icon name="gauge" class="w-3 h-3 inline" /> Est. Weight</span>
                            <span class="text-slate-800 font-bold bg-slate-100 px-2 py-0.5 rounded-md">{{ number_format($harvest->quantity_kg, 1) }} kg</span>
                        </div>
                        @if($harvest->latitude && $harvest->longitude)
                            <div class="flex justify-between items-center py-1 border-b border-slate-50">
                                <span class="text-slate-400"><x-icon name="map" class="w-3 h-3 inline" /> Coordinates</span>
                                <span class="font-mono text-[10px] text-slate-400 bg-slate-50 px-2 py-0.5 rounded border border-slate-100">{{ $harvest->latitude }}, {{ $harvest->longitude }}</span>
                            </div>
                        @endif
                        @if($harvest->destination_label !== '—')
                            <div class="flex justify-between items-start py-1">
                                <span class="text-slate-400">Drop-off Terminal</span>
                                <span class="text-slate-700 font-bold max-w-[180px] text-right truncate">{{ $harvest->destination_label }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Stop Status & Actions -->
                    <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/10">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[10px] text-slate-400 uppercase tracking-widest font-bold">Stop Status</span>
                            <span class="text-xs font-bold uppercase
                                @if($harvest->pivot->status === 'assigned') text-slate-500
                                @elseif($harvest->pivot->status === 'arrived') text-amber-600
                                @elseif($harvest->pivot->status === 'loaded') text-[#1F4D25]
                                @elseif($harvest->pivot->status === 'delivered') text-[#3A7D44]
                                @endif">
                                {{ strtoupper($harvest->pivot->status ?? 'assigned') }}
                            </span>
                        </div>

                        @if($job->status === 'in_progress')
                            @if($harvest->pivot->status === 'assigned')
                                <form method="POST" action="{{ route('driver.jobs.stop.status', [$job, $harvest->id]) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="arrived">
                                    <button type="submit" class="w-full py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl shadow-sm transition cursor-pointer">
                                        <x-icon name="pin" class="w-4 h-4 inline" /> Mark Arrived at Pick-up
                                    </button>
                                </form>
                            @elseif($harvest->pivot->status === 'arrived')
                                <form method="POST" action="{{ route('driver.jobs.stop.status', [$job, $harvest->id]) }}" class="space-y-3">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="loaded">
                                    
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Loaded Weight (kg)</label>
                                            <input type="number" step="0.01" min="0.01" name="loaded_quantity_kg" required value="{{ $harvest->quantity_kg }}"
                                                class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-700 font-bold focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/10 focus:border-[#3A7D44]">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Loaded Vol (m³)</label>
                                            <input type="number" step="0.01" min="0.01" name="loaded_volume_cubic_meters" required value="1.5"
                                                class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-700 font-bold focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/10 focus:border-[#3A7D44]">
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="w-full py-2.5 bg-[#1F4D25]/100 hover:bg-[#1F4D25] text-white text-xs font-bold rounded-xl shadow-sm transition cursor-pointer">
                                        <x-icon name="gauge" class="w-4 h-4 inline" /> Confirm Cargo & Mark Loaded
                                    </button>
                                </form>
                            @elseif($harvest->pivot->status === 'loaded')
                                <form method="POST" action="{{ route('driver.jobs.stop.status', [$job, $harvest->id]) }}" enctype="multipart/form-data" class="space-y-3">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="delivered">
                                    
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Take/Upload Photo of Delivered Goods</label>
                                        <input type="file" name="delivery_receipt" required accept="image/*" capture="environment"
                                            class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-[#3A7D44]/10 file:text-[#3A7D44] hover:file:bg-[#3A7D44]/15 transition cursor-pointer">
                                    </div>

                                    <button type="submit" class="w-full py-2.5 bg-[#3A7D44] hover:bg-[#2E6336] text-white text-xs font-bold rounded-xl shadow-sm transition cursor-pointer">
                                        <x-icon name="camera" class="w-4 h-4 inline" /> Mark Delivered & Upload Photo of Goods
                                    </button>
                                </form>
                            @elseif($harvest->pivot->status === 'delivered')
                                <div class="space-y-2 mt-2">
                                    @if($harvest->pivot->loaded_quantity_kg)
                                        <div class="flex justify-between items-center text-xs">
                                            <span class="text-slate-400"><x-icon name="gauge" class="w-3 h-3 inline" /> Actual Weight Loaded:</span>
                                            <span class="text-slate-800 font-bold font-mono">{{ number_format($harvest->pivot->loaded_quantity_kg, 1) }} kg</span>
                                        </div>
                                    @endif
                                    @if($harvest->pivot->delivery_receipt_path)
                                        <div class="text-[10px] text-slate-400 font-bold flex items-center gap-1">
                                            <x-icon name="paperclip" class="w-3 h-3 inline" /> Goods Photo Uploaded: 
                                            <a href="{{ Storage::url($harvest->pivot->delivery_receipt_path) }}" target="_blank" class="text-[#3A7D44] hover:underline">View Photo</a>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @else
                            <!-- Not in progress yet -->
                            <p class="text-[10px] text-slate-400 italic">Start the job to unlock stop status controls.</p>
                        @endif
                    </div>

                </div>
            @endforeach
        </div>

    </main>

    {{-- ── OFFLINE TELEMETRY SYNC ── --}}
    {{-- Status Banner (hidden by default, shown when needed) --}}
    <div id="offline-banner" class="hidden fixed bottom-5 left-4 right-4 max-w-sm mx-auto z-50">
        <div id="offline-badge"
             class="hidden bg-rose-600 text-white text-xs font-bold px-4 py-3 rounded-2xl shadow-xl flex items-center gap-2.5 border border-rose-500">
            <span class="w-2 h-2 rounded-full bg-white animate-pulse shrink-0"></span>
            <span>Offline — GPS pings queued locally</span>
        </div>
        <div id="syncing-badge"
             class="hidden bg-amber-500 text-white text-xs font-bold px-4 py-3 rounded-2xl shadow-xl flex items-center gap-2.5 border border-amber-400">
            <span class="w-2 h-2 rounded-full bg-white animate-pulse shrink-0"></span>
            <span id="syncing-text">Syncing queued pings…</span>
        </div>
        <div id="synced-badge"
             class="hidden bg-[#3A7D44] text-white text-xs font-bold px-4 py-3 rounded-2xl shadow-xl flex items-center gap-2.5 border border-[#3A7D44]">
            <span class="text-[#3A7D44]"><svg class="w-4 h-4 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></span>
            <span id="synced-text">All pings synced successfully</span>
        </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        const jobStatus  = '{{ $job->status }}';
        const jobId      = {{ $job->id }};
        const csrfToken  = document.querySelector('meta[name="csrf-token"]').content;
        const trackingUrl = '{{ route("driver.tracking.store") }}';

        let sw = null;
        let routePoints  = @json($job->route_geometry ?? []);
        let currentIndex = 0;

        // ─────────────────────────────────────────
        // UI helpers
        // ─────────────────────────────────────────
        const banner = document.getElementById('offline-banner');
        const offlineBadge  = document.getElementById('offline-badge');
        const syncingBadge  = document.getElementById('syncing-badge');
        const syncedBadge   = document.getElementById('synced-badge');
        const syncingText   = document.getElementById('syncing-text');
        const syncedText    = document.getElementById('synced-text');

        function showBadge(type, extra) {
            banner.classList.remove('hidden');
            [offlineBadge, syncingBadge, syncedBadge].forEach(b => b.classList.add('hidden'));

            if (type === 'offline')  offlineBadge.classList.remove('hidden');
            if (type === 'syncing') {
                syncingText.textContent = extra || 'Syncing queued pings…';
                syncingBadge.classList.remove('hidden');
            }
            if (type === 'synced') {
                syncedText.textContent = extra || 'All pings synced';
                syncedBadge.classList.remove('hidden');
                setTimeout(() => banner.classList.add('hidden'), 4000);
            }
        }

        function hideBanner() {
            banner.classList.add('hidden');
        }

        // ─────────────────────────────────────────
        // Service Worker registration
        // ─────────────────────────────────────────
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').then(reg => {
                sw = reg;
                console.log('[HH] Service Worker registered:', reg.scope);
            });

            // Listen for SW messages
            navigator.serviceWorker.addEventListener('message', event => {
                const { type, count, synced, remaining } = event.data;

                if (type === 'telemetry-queued') {
                    showBadge('offline');
                }
                if (type === 'sync-started') {
                    showBadge('syncing', `Syncing ${count} queued ping${count > 1 ? 's' : ''}…`);
                }
                if (type === 'sync-complete') {
                    if (synced > 0) {
                        showBadge('synced', `${synced} ping${synced > 1 ? 's' : ''} synced`);
                    } else {
                        hideBanner();
                    }
                }
            });
        }

        // ─────────────────────────────────────────
        // Online / offline event handlers
        // ─────────────────────────────────────────
        window.addEventListener('offline', () => showBadge('offline'));
        window.addEventListener('online',  () => {
            hideBanner();
            // Trigger SW to flush queued pings
            if (navigator.serviceWorker?.controller) {
                navigator.serviceWorker.controller.postMessage({
                    type: 'flush-offline-queue',
                    csrfToken,
                });
            }
        });

        // ─────────────────────────────────────────
        // Wake Lock API Integration
        // ─────────────────────────────────────────
        let wakeLock = null;

        async function requestWakeLock() {
            try {
                if ('wakeLock' in navigator) {
                    wakeLock = await navigator.wakeLock.request('screen');
                    console.log('[HH] Wake Lock is active');
                    wakeLock.addEventListener('release', () => {
                        console.log('[HH] Wake Lock was released');
                    });
                }
            } catch (err) {
                console.warn(`[HH] Wake Lock error: ${err.name}, ${err.message}`);
            }
        }

        // ─────────────────────────────────────────
        // GPS telemetry loop (only when in_progress)
        // Uses real device GPS via watchPosition()
        // Sends position every 30 seconds to server
        // ─────────────────────────────────────────
        if (jobStatus === 'in_progress') {
            requestWakeLock();

            // Re-acquire lock when page is visible again
            document.addEventListener('visibilitychange', async () => {
                if (document.visibilityState === 'visible') {
                    await requestWakeLock();
                }
            });

            let lastSentAt = 0;
            const SEND_INTERVAL_MS = 30000; // 30 seconds per spec

            async function sendPosition(latitude, longitude) {
                const now = Date.now();
                if (now - lastSentAt < SEND_INTERVAL_MS) return; // Throttle
                lastSentAt = now;

                const payload = {
                    pooling_job_id: jobId,
                    latitude:  latitude,
                    longitude: longitude,
                    posted_at: new Date().toISOString(),
                };

                try {
                    const res = await fetch(trackingUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });

                    if (res.ok || res.status === 202) {
                        const data = await res.json().catch(() => ({}));
                        if (data.status === 'queued') {
                            showBadge('offline');
                        } else if (!navigator.onLine) {
                            showBadge('offline');
                        }
                    }
                } catch (err) {
                    showBadge('offline');
                    console.warn('[HH] Fetch error (likely offline):', err.message);
                }
            }

            // Real GPS tracking via Geolocation API
            if ('geolocation' in navigator) {
                const geoOptions = {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 10000,
                };

                const watchId = navigator.geolocation.watchPosition(
                    (position) => {
                        const { latitude, longitude } = position.coords;
                        console.log(`[HH] GPS: ${latitude}, ${longitude} (accuracy: ${position.coords.accuracy}m)`);
                        sendPosition(latitude, longitude);
                    },
                    (error) => {
                        console.warn(`[HH] Geolocation error: ${error.code} — ${error.message}`);
                        // Fallback: if GPS fails, try simulated route points
                        if (routePoints.length > 0 && currentIndex < routePoints.length) {
                            const point = routePoints[currentIndex];
                            sendPosition(point[1], point[0]);
                            currentIndex++;
                        }
                    },
                    geoOptions
                );

                // Also send position on a 30-second interval as backup
                // (watchPosition may not fire frequently enough on all devices)
                setInterval(() => {
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            sendPosition(position.coords.latitude, position.coords.longitude);
                        },
                        () => {}, // Silently ignore errors on interval
                        { enableHighAccuracy: true, timeout: 10000, maximumAge: 25000 }
                    );
                }, SEND_INTERVAL_MS);
            } else {
                // Fallback for devices without Geolocation API: use simulated route
                console.warn('[HH] Geolocation API not available. Using simulated route.');
                setInterval(() => {
                    if (currentIndex >= routePoints.length) return;
                    const point = routePoints[currentIndex];
                    sendPosition(point[1], point[0]);
                    currentIndex++;
                }, SEND_INTERVAL_MS);
            }
        }
    })();
    </script>
    @endpush

</x-driver-layout>
