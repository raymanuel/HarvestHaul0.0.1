<x-layout>
    <div class="w-full max-w-7xl mx-auto pb-12">

        {{-- Header --}}
        <header class="mb-8 pt-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">
                        Live Shipment Tracking
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">
                        Real-time GPS tracking and route monitoring of active cargo shipments
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#3A7D44] dark:text-[#3A7D44] bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 px-3 py-1.5 rounded-lg border border-[#3A7D44]/10 dark:border-[#3A7D44]/20">
                        GPS Active
                    </span>
                </div>
            </div>
        </header>

        @if($activeJobs->isEmpty())
            <div class="bg-white dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-16 text-center shadow-sm">
                <h3 class="text-base font-bold text-slate-800 dark:text-white heading-font">No Active Shipments</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 max-w-md mx-auto leading-relaxed">
                    There are no shipments currently in transit. Once a logistics partner dispatches an assigned pooling job, real-time GPS telemetry will display here.
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                {{-- Left Panel: Shipment List --}}
                <div class="lg:col-span-4 space-y-4 max-h-[700px] overflow-y-auto pr-2">
                    <h2 class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-2">Active Shipments</h2>
                    
                    @foreach($activeJobs as $job)
                        <button type="button" 
                                onclick="selectJob({{ json_encode([
                                    'id' => $job->id,
                                    'status' => $job->status,
                                    'truck' => $job->truck->plate_number ?? '—',
                                    'driver' => $job->driver->name ?? '—',
                                    'stops' => $job->harvests->map(function($h) {
                                        return [
                                            'crop' => $h->crop->name ?? $h->crop_type ?? '—',
                                            'qty' => $h->quantity_kg,
                                            'farmer' => $h->farmer->name ?? '—',
                                            'farmer_lat' => (float)($h->farmer->farmerProfile->latitude ?? 0),
                                            'farmer_lng' => (float)($h->farmer->farmerProfile->longitude ?? 0),
                                            'dest_label' => $h->destination_label ?? '—',
                                            'dest_lat' => (float)($h->destination_latitude ?? 0),
                                            'dest_lng' => (float)($h->destination_longitude ?? 0),
                                        ];
                                    })
                                ]) }})"
                                id="btn-job-{{ $job->id }}"
                                class="w-full text-left bg-white dark:bg-slate-805 border-2 border-slate-200/60 dark:border-slate-700/60 hover:border-[#3A7D44]/40 dark:hover:border-[#3A7D44]/40 rounded-2xl p-5 transition-all shadow-sm focus:outline-none flex flex-col justify-between gap-4 group">
                            
                            <div class="flex items-start justify-between w-full">
                                <div>
                                    <p class="text-sm font-bold text-slate-800 dark:text-white heading-font">
                                        Route #{{ $job->id }}
                                    </p>
                                    <p class="text-[10px] font-semibold text-slate-400 mt-0.5">
                                        🚛 {{ $job->truck->plate_number ?? '—' }} · Driver: {{ $job->driver->name ?? '—' }}
                                    </p>
                                </div>
                                <span class="text-[9px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded {{ $job->status === 'in_progress' ? 'bg-[#1F4D25]/10 dark:bg-[#1F4D25]/10 text-[#1F4D25] dark:text-[#1F4D25] border border-[#1F4D25]/20' : 'bg-amber-50 dark:bg-amber-950/30 text-amber-700 dark:text-amber-400 border border-amber-200/30' }}">
                                    {{ $job->status === 'in_progress' ? 'In Transit' : 'Awaiting confirmation' }}
                                </span>
                            </div>

                            <div class="space-y-1.5 w-full pt-3 border-t border-slate-100 dark:border-slate-700/50">
                                @foreach($job->harvests as $h)
                                    <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                                        <span>🌾 {{ $h->crop->name ?? $h->crop_type }}</span>
                                        <span class="font-bold text-slate-700 dark:text-slate-300 font-mono">{{ number_format($h->quantity_kg) }} kg</span>
                                    </div>
                                @endforeach
                            </div>

                            @if($job->latestTracking)
                                <div class="text-[10px] text-slate-400 dark:text-slate-500 font-medium flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#3A7D44]/100 animate-pulse"></span>
                                    Last signal: {{ $job->latestTracking->posted_at->diffForHumans() }}
                                </div>
                            @else
                                <div class="text-[10px] text-slate-400 italic">No GPS signals logged yet</div>
                            @endif
                        </button>
                    @endforeach
                </div>
                
                {{-- Right Panel: Leaflet Map --}}
                <div class="lg:col-span-8 space-y-4">
                    <div class="bg-white dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 rounded-3xl overflow-hidden shadow-sm flex flex-col">
                        <div id="liveMap" class="w-full h-[550px] z-0"></div>
                        <div class="bg-slate-50 dark:bg-slate-900/30 px-6 py-4 border-t border-slate-100 dark:border-slate-700/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs">
                            <div class="flex items-center gap-4 text-slate-500 dark:text-slate-450 font-bold uppercase tracking-wider text-[10px]">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#3A7D44]/100"></span> Pickup (Farmer)
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Drop-off (Buyer)
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#1F4D25]/100"></span> Truck GPS
                                </span>
                            </div>
                            <div id="map-status" class="text-slate-400 font-medium">Select a shipment to begin tracking</div>
                            <div class="flex items-center gap-4 text-[10px]">
                                <span class="text-slate-500 font-bold">Speed: <span id="speed-display" class="text-slate-600">—</span></span>
                                <span class="text-slate-500 font-bold">ETA: <span id="eta-display" class="text-slate-600">—</span></span>
                                <span class="text-slate-500 font-bold">Dist: <span id="distance-display" class="text-slate-600">—</span></span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        @endif

    </div>

    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush

    @push('scripts')
    <script>
        let map;
        let activeMarkers = [];
        let activePolyline;
        let pollingInterval;
        let selectedJobId = null;
        let truckMarker = null;

        const GENSAN_CENTER = [6.1164, 125.1716];

        document.addEventListener('DOMContentLoaded', function () {
            if (document.getElementById('liveMap')) {
                map = L.map('liveMap').setView(GENSAN_CENTER, 12);

                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                    attribution: '© OpenStreetMap contributors © CARTO',
                    subdomains: 'abcd',
                    maxZoom: 19,
                }).addTo(map);

                // Select first job automatically if available
                @if($activeJobs->isNotEmpty())
                    const firstBtn = document.querySelector('[id^="btn-job-"]');
                    if (firstBtn) {
                        firstBtn.click();
                    }
                @endif
            }
        });

        function selectJob(job) {
            // Highlight button
            document.querySelectorAll('[id^="btn-job-"]').forEach(btn => {
                btn.classList.remove('border-[#3A7D44]', 'ring-2', 'ring-[#3A7D44]/20');
            });
            const selectedBtn = document.getElementById(`btn-job-${job.id}`);
            if (selectedBtn) {
                selectedBtn.classList.add('border-[#3A7D44]', 'ring-2', 'ring-[#3A7D44]/20');
            }

            selectedJobId = job.id;
            document.getElementById('map-status').innerHTML = `Tracking Route #${job.id} · Driver: ${job.driver}`;

            // Clean map
            activeMarkers.forEach(m => map.removeLayer(m));
            activeMarkers = [];
            if (activePolyline) {
                map.removeLayer(activePolyline);
            }
            if (truckMarker) {
                map.removeLayer(truckMarker);
                truckMarker = null;
            }

            // Stop old polling
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }

            const bounds = L.latLngBounds();

            // Custom markers
            const farmerIcon = L.divIcon({
                html: `<div style="width: 20px; height: 20px; border-radius: 50%; background: #3A7D44; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3)"></div>`,
                className: '', iconSize: [20, 20], iconAnchor: [10, 10]
            });
            const buyerIcon = L.divIcon({
                html: `<div style="width: 20px; height: 20px; border-radius: 50%; background: #EF4444; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3)"></div>`,
                className: '', iconSize: [20, 20], iconAnchor: [10, 10]
            });

            const pathCoords = [];

            // Add stops markers
            job.stops.forEach(stop => {
                if (stop.farmer_lat && stop.farmer_lng) {
                    const fMarker = L.marker([stop.farmer_lat, stop.farmer_lng], { icon: farmerIcon })
                        .bindPopup(`<b>Farmer:</b> ${stop.farmer}<br><b>Cargo:</b> ${stop.crop} (${stop.qty} kg)`)
                        .addTo(map);
                    activeMarkers.push(fMarker);
                    bounds.extend([stop.farmer_lat, stop.farmer_lng]);
                    pathCoords.push([stop.farmer_lat, stop.farmer_lng]);
                }

                if (stop.dest_lat && stop.dest_lng) {
                    const bMarker = L.marker([stop.dest_lat, stop.dest_lng], { icon: buyerIcon })
                        .bindPopup(`<b>Destination:</b> ${stop.dest_label}<br><b>Buyer Target Drop-off</b>`)
                        .addTo(map);
                    activeMarkers.push(bMarker);
                    bounds.extend([stop.dest_lat, stop.dest_lng]);
                    pathCoords.push([stop.dest_lat, stop.dest_lng]);
                }
            });

            // Draw sequence path
            if (pathCoords.length > 1) {
                activePolyline = L.polyline(pathCoords, {
                    color: '#64748B',
                    weight: 3,
                    dashArray: '5, 8',
                    opacity: 0.6
                }).addTo(map);
            }

            // Fit bounds
            if (bounds.isValid()) {
                map.fitBounds(bounds, { padding: [50, 50] });
            }

            // Load latest location immediately and poll
            pollLatestGPS(job.id);
            pollingInterval = setInterval(() => pollLatestGPS(job.id), 10000);

            // Connect to WebSocket real-time channel
            connectWebSocket();
        }

        let socket = null;
        function connectWebSocket() {
            if (socket) {
                try { socket.close(); } catch (e) {}
            }

            const wsProtocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const wsHost = wsProtocol + '//' + window.location.hostname + ':8080';
            
            try {
                socket = new WebSocket(wsHost);

                socket.onopen = function () {
                    console.log('Real-time telemetry WebSocket streaming active.');
                    const statusEl = document.getElementById('map-status');
                    if (statusEl) {
                        statusEl.innerHTML = `<span class="text-[#3A7D44] dark:text-[#3A7D44] font-bold">● Live Connection Active</span> · Tracking Route #${selectedJobId}`;
                    }
                };

                socket.onmessage = function (event) {
                    try {
                        const payload = JSON.parse(event.data);
                        if (payload && payload.pooling_job_id === selectedJobId) {
                            console.log('Real-time telemetry WebSocket frame received:', payload);
                            updateTruckMarker(payload.latitude, payload.longitude);
                        }
                    } catch (e) {
                        console.error('WebSocket frame parsing error', e);
                    }
                };

                socket.onerror = function (err) {
                    console.warn('WebSocket connection error. Polling fallback active.', err);
                };

                socket.onclose = function () {
                    console.log('WebSocket stream connection closed. Polling remains active.');
                };
            } catch (e) {
                console.warn('Failed to initiate WebSocket connection:', e);
            }
        }

        function updateTruckMarker(lat, lng) {
            const truckIcon = L.divIcon({
                html: `<div style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; background: #0EA5E9; border: 3px solid white; box-shadow: 0 4px 10px rgba(14, 165, 233, 0.45); font-size: 16px; color: white">🚚</div>`,
                className: '', iconSize: [32, 32], iconAnchor: [16, 16]
            });

            if (truckMarker) {
                truckMarker.setLatLng([lat, lng]);
            } else {
                truckMarker = L.marker([lat, lng], { icon: truckIcon })
                    .bindPopup(`<b>Live Dispatch Truck</b><br>Coordinates: ${lat}, ${lng}`)
                    .addTo(map);
            }

            // Adjust map bounds to include truck
            const currentBounds = map.getBounds();
            if (!currentBounds.contains([lat, lng])) {
                const newBounds = L.latLngBounds(activeMarkers.map(m => m.getLatLng()));
                newBounds.extend([lat, lng]);
                map.fitBounds(newBounds, { padding: [50, 50] });
            }
        }

        function pollLatestGPS(jobId) {
            if (selectedJobId !== jobId) return;

            fetch(`/tracking/${jobId}/latest`)
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success' && res.data) {
                        updateTruckMarker(res.data.latitude, res.data.longitude);
                        updateSpeedDisplay(res.data.speed_kmh);
                    }
                })
                .catch(err => console.error("Error polling GPS", err));

            // Poll ETA
            fetch(`/tracking/${jobId}/eta`)
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success' && res.data) {
                        updateETADisplay(res.data);
                    }
                })
                .catch(err => console.error("Error polling ETA", err));
        }

        function updateSpeedDisplay(speedKmh) {
            const speedEl = document.getElementById('speed-display');
            if (speedEl) {
                speedEl.textContent = speedKmh > 0 ? `${speedKmh.toFixed(1)} km/h` : 'Stopped';
                speedEl.className = speedKmh > 0
                    ? 'text-[#3A7D44] dark:text-[#3A7D44] font-bold'
                    : 'text-amber-600 dark:text-amber-400 font-bold';
            }
        }

        function updateETADisplay(eta) {
            const etaEl = document.getElementById('eta-display');
            const distEl = document.getElementById('distance-display');
            if (etaEl) {
                etaEl.textContent = eta.eta_formatted;
            }
            if (distEl) {
                distEl.textContent = `${eta.remaining_distance_km} km`;
            }
        }
    </script>
    @endpush
</x-layout>
