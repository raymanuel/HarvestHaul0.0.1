<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="theme-color" content="#059669" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Job #{{ $job->id }} — HarvestHaul</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #F8FAFC;
        }
        .heading-font {
            font-family: 'Outfit', sans-serif;
        }
    </style>
</head>
<body class="text-slate-800 antialiased min-h-screen">

    <!-- Top Header Panel -->
    <header class="bg-gradient-to-tr from-emerald-600 to-teal-500 text-white px-5 pt-6 pb-5 sticky top-0 z-20 shadow-md">
        <div class="flex items-center gap-4 max-w-lg mx-auto">
            <a href="{{ route('driver.dashboard') }}" class="text-white bg-white/10 hover:bg-white/20 p-2 rounded-xl border border-white/10 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <p class="text-[10px] text-emerald-100 font-bold uppercase tracking-widest">Cargo Details</p>
                <h1 class="text-xl font-bold leading-tight heading-font mt-0.5">Job #{{ $job->id }}</h1>
            </div>
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 py-6 space-y-5">

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200/60 text-emerald-800 text-xs font-bold heading-font rounded-xl px-4 py-3">
                ✅ {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-rose-50 border border-rose-200/60 text-rose-800 text-xs font-bold heading-font rounded-xl px-4 py-3">
                ⚠️ {{ session('error') }}
            </div>
        @endif

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
                        'in_progress' => ['bg-sky-50 text-sky-700 border-sky-200/50',    'In Transit'],
                        'completed'   => ['bg-emerald-50 text-emerald-700 border-emerald-200/50',  'Completed'],
                        default       => ['bg-slate-50 text-slate-500 border-slate-200/50',    ucfirst($job->status)],
                    };
                @endphp
                <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-md border {{ $badge[0] }}">
                    {{ $badge[1] }}
                </span>
            </div>

            <div class="px-5 py-4 text-xs text-slate-500 flex items-center gap-1.5 font-semibold">
                <span class="text-slate-400">🚛 Assigned Fleet:</span>
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
                <p class="text-[10px] font-bold text-amber-700 uppercase tracking-widest mb-2 flex items-center gap-1"><span>📝</span> Dispatch Instructions</p>
                <p class="text-xs text-amber-800 leading-relaxed font-semibold">{{ $job->notes }}</p>
            </div>
        @endif

        <!-- Status Action Button -->
        @if(in_array($job->status, ['confirmed', 'in_progress']))
            <form method="POST" action="{{ route('driver.jobs.status', $job) }}">
                @csrf @method('PATCH')
                <button type="submit" @class([
                    'w-full py-4 rounded-2xl text-xs font-bold text-white transition-all shadow-md active:scale-[0.98]',
                    'bg-gradient-to-tr from-sky-600 to-blue-500 hover:shadow-sky-600/10' => $job->status === 'confirmed',
                    'bg-gradient-to-tr from-emerald-600 to-teal-500 hover:shadow-emerald-600/10' => $job->status === 'in_progress',
                ])>
                    {{ $job->status === 'confirmed' ? '🚛 Start Job — Mark In Transit' : '✅ Complete Job — Mark Delivered' }}
                </button>
            </form>
        @endif

        <!-- Pickup Sequence -->
        <div>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3 px-1">Pickup Sequence</p>

            @foreach($job->harvests as $index => $harvest)
                <div class="bg-white rounded-2xl border border-slate-200/80 mb-4 overflow-hidden shadow-sm hover:shadow-md transition-all duration-200">

                    <!-- Stop Header -->
                    <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100 bg-slate-50/20">
                        <div class="w-7 h-7 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white text-xs font-black flex items-center justify-center flex-shrink-0 heading-font">
                            {{ $index + 1 }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-800 truncate heading-font">
                                {{ $harvest->farmer->name ?? 'Unknown Farmer' }}
                            </p>
                            <p class="text-[11px] text-slate-400 font-semibold mt-0.5">
                                📍 {{ $harvest->farmer->farmerProfile->barangay ?? 'No barangay on record' }}
                              </p>
                          </div>
                      </div>

                    <!-- Stop Details -->
                    <div class="px-5 py-4 space-y-2 text-xs text-slate-500 font-semibold">
                        <div class="flex justify-between items-center py-1 border-b border-slate-50">
                            <span class="text-slate-400">🌾 Crop Type</span>
                            <span class="text-slate-800 font-bold">
                                {{ $harvest->crop->name ?? $harvest->crop_type ?? '—' }}
                                @if($harvest->variety)
                                    &middot; {{ $harvest->variety }}
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-slate-50">
                            <span class="text-slate-400">⚖️ Weight</span>
                            <span class="text-slate-800 font-bold bg-slate-100 px-2 py-0.5 rounded-md">{{ number_format($harvest->quantity_kg, 1) }} kg</span>
                        </div>
                        @if($harvest->latitude && $harvest->longitude)
                            <div class="flex justify-between items-center py-1 border-b border-slate-50">
                                <span class="text-slate-400">🗺️ Coordinates</span>
                                <span class="font-mono text-[10px] text-slate-400 bg-slate-50 px-2 py-0.5 rounded border border-slate-100">{{ $harvest->latitude }}, {{ $harvest->longitude }}</span>
                            </div>
                        @endif
                        @if($harvest->destination_label !== '—')
                            <div class="flex justify-between items-start py-1">
                                <span class="text-slate-400">📦 Drop-off Terminal</span>
                                <span class="text-slate-700 font-bold max-w-[180px] text-right truncate">{{ $harvest->destination_label }}</span>
                            </div>
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
             class="hidden bg-emerald-600 text-white text-xs font-bold px-4 py-3 rounded-2xl shadow-xl flex items-center gap-2.5 border border-emerald-500">
            <span>✅</span>
            <span id="synced-text">All pings synced successfully</span>
        </div>
    </div>

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
                        showBadge('synced', `${synced} ping${synced > 1 ? 's' : ''} synced ✓`);
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
        // GPS telemetry loop (only when in_progress)
        // ─────────────────────────────────────────
        if (jobStatus === 'in_progress') {
            setInterval(async () => {
                if (currentIndex >= routePoints.length) {
                    console.log('[HH] Driver reached destination.');
                    return;
                }

                const point   = routePoints[currentIndex];
                const payload = {
                    pooling_job_id: jobId,
                    latitude:  point[1], // OSRM GeoJSON is [lng, lat]
                    longitude: point[0],
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
                        currentIndex++;
                        const data = await res.json().catch(() => ({}));
                        if (data.status === 'queued') {
                            showBadge('offline');
                        } else {
                            // Successful direct sync
                            if (!navigator.onLine) showBadge('offline');
                        }
                    }
                } catch (err) {
                    // Fetch itself threw — SW intercept should have handled it
                    showBadge('offline');
                    console.warn('[HH] Fetch error (likely offline):', err.message);
                }
            }, 3000);
        }
    })();
    </script>
</body>
</html>
