<x-layout>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>

    @php
        // Fetch active pooling jobs for the authenticated logistics partner directly in the view
        $activeJobIds = \App\Models\PoolingJob::where('logistics_profile_id', auth()->user()->logisticsProfile->id)
            ->where('status', 'in_progress')
            ->pluck('id');
    @endphp

    <div class="w-full pb-12">
        <header class="pt-8 mb-6 border-b border-slate-200/80 dark:border-slate-700/80 pb-5">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 px-3 py-1.5 rounded-lg border border-emerald-500/10 dark:border-emerald-500/20 self-start">Logistics Engine</span>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white heading-font mt-2">Route Optimization Engine</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Plan consolidated multi-stop pickup routes and optimize empty fleet capacities.</p>
        </header>

        {{-- ─── Truck Selector + Generate Plan Bar ─── --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-5 mb-6 flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-bold text-slate-400 dark:text-slate-550 uppercase tracking-widest mb-2">Select Truck</label>
                <div class="relative">
                    <select id="truck-select" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition outline-none appearance-none cursor-pointer">
                        <option value="">— Choose a truck —</option>
                        @forelse($trucks as $truck)
                            <option value="{{ $truck['id'] }}"
                                    data-capacity="{{ $truck['capacity_kg'] }}"
                                    data-driver="{{ $truck['driver'] }}">
                                🚛 {{ $truck['label'] }} ({{ number_format($truck['capacity_kg']) }} kg)
                            </option>
                        @empty
                            <option disabled>No available trucks</option>
                        @endforelse
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                    </div>
                </div>
            </div>

            <div id="truck-info" class="hidden text-sm border rounded-xl px-5 py-3 transition-all duration-200">
                <span id="truck-info-driver"></span> &bull;
                <span id="truck-info-capacity"></span>
            </div>

            <button id="btn-generate-plan"
                    disabled
                    class="bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-bold px-6 py-3.5 rounded-xl text-sm hover:shadow-lg hover:shadow-emerald-600/10 hover:translate-y-[-1px] transition-all disabled:opacity-40 disabled:cursor-not-allowed disabled:transform-none disabled:shadow-none flex items-center gap-2">
                <span>⚙️</span> Generate Route Plan
            </button>
        </div>

        {{-- ─── Main Grid: Map + Sidebar ─── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Map --}}
            <div class="lg:col-span-2">
                <div id="routing-map" class="w-full rounded-2xl border border-slate-200 dark:border-slate-700 relative z-10 shadow-sm" style="height: 600px;"></div>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-3 flex items-center gap-1">
                    <span>💡</span> <b>Click map to plot custom Start (Logistics hub) and End (Drop-off Terminal) points.</b>
                </p>
            </div>

            {{-- Sidebar --}}
            <div class="bg-slate-50 dark:bg-slate-900/40 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 p-5 flex flex-col h-[600px]">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-250 heading-font mb-4 flex items-center gap-2">
                    <span class="text-emerald-600">📍</span> Route Pickups
                </h3>

                <div class="mb-4 bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200/60 dark:border-slate-700/60 shadow-sm">
                    <label for="radius-select" class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Search Radius</label>
                    <div class="relative">
                        <select id="radius-select" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl px-3 py-2.5 text-xs focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition outline-none appearance-none cursor-pointer">
                            <option value="1">Within 1 km</option>
                            <option value="3">Within 3 km</option>
                            <option value="5" selected>Within 5 km</option>
                            <option value="10">Within 10 km</option>
                            <option value="20">Within 20 km</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                            <svg class="fill-current h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                </div>

                <p id="radius-description" class="text-[11px] text-slate-450 dark:text-slate-500 mb-4 leading-relaxed">Farms within 5km buffer off the planned road segments will auto-detect.</p>

                <div id="pickup-queue" class="flex-1 overflow-y-auto space-y-3 pr-1 custom-scroll">
                    <div class="text-center text-slate-400 dark:text-slate-500 mt-10 italic text-xs">Awaiting route coordinates...</div>
                </div>

                <button id="reset-map" class="w-full mt-4 bg-red-55/10 dark:bg-red-950/20 text-red-600 dark:text-red-400 font-bold py-2.5 rounded-xl border border-red-200/50 dark:border-red-900/30 hover:bg-red-100 dark:hover:bg-red-950/40 transition text-xs hidden">
                    Clear Route & Restart
                </button>
            </div>
        </div>

        {{-- ─── Pooling Plan Panel ─── --}}
        <div id="plan-panel" class="hidden mt-8 bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 heading-font">🧮 Consolidated Pooling Plan</h2>
                <span id="plan-status-badge" class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-md bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 border border-amber-200/50 dark:border-amber-900/30">Unconfirmed</span>
            </div>

            {{-- Summary row --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-100 dark:border-slate-700/40 text-center hover:shadow-sm transition-shadow duration-200">
                    <p class="text-[10px] text-slate-400 dark:text-slate-550 uppercase tracking-widest font-bold">Farms Selected</p>
                    <p id="plan-farm-count" class="text-2xl font-black text-slate-800 dark:text-white mt-1">—</p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-100 dark:border-slate-700/40 text-center hover:shadow-sm transition-shadow duration-200">
                    <p class="text-[10px] text-slate-400 dark:text-slate-550 uppercase tracking-widest font-bold">Total Load</p>
                    <p id="plan-total-kg" class="text-2xl font-black text-emerald-600 dark:text-emerald-450 mt-1">—</p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-100 dark:border-slate-700/40 text-center hover:shadow-sm transition-shadow duration-200">
                    <p class="text-[10px] text-slate-400 dark:text-slate-550 uppercase tracking-widest font-bold">Capacity Used</p>
                    <p id="plan-load-pct" class="text-2xl font-black text-blue-600 dark:text-blue-450 mt-1">—</p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-100 dark:border-slate-700/40 text-center hover:shadow-sm transition-shadow duration-200">
                    <p class="text-[10px] text-slate-400 dark:text-slate-550 uppercase tracking-widest font-bold">Est. Distance</p>
                    <p id="plan-distance" class="text-2xl font-black text-slate-700 dark:text-slate-350 mt-1">—</p>
                </div>
                <div class="bg-emerald-50/50 dark:bg-emerald-950/20 rounded-xl p-4 border border-emerald-100 dark:border-emerald-900/30 text-center ring-2 ring-emerald-500/10 hover:shadow-sm transition-shadow duration-200">
                    <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold uppercase tracking-widest">Suggested Cost</p>
                    <p id="plan-price-ref" class="text-2xl font-black text-emerald-700 dark:text-emerald-300 mt-1">—</p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-100 dark:border-slate-700/40 text-center hover:shadow-sm transition-shadow duration-200">
                    <p class="text-[10px] text-slate-400 dark:text-slate-550 uppercase tracking-widest font-bold">Assigned Truck</p>
                    <p id="plan-truck-label" class="text-xs font-bold text-slate-655 dark:text-slate-350 mt-2.5 truncate">—</p>
                </div>
            </div>

            {{-- Farm pickup order table --}}
            <div class="overflow-x-auto border border-slate-200/60 dark:border-slate-700/60 rounded-2xl mb-6">
                <table class="w-full text-sm text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/40 text-[10px] text-slate-400 dark:text-slate-500 uppercase tracking-widest font-bold">
                            <th class="py-3.5 px-4">Order</th>
                            <th class="py-3 px-4">Farm</th>
                            <th class="py-3 px-4">Location</th>
                            <th class="py-3 px-4">Crop(s)</th>
                            <th class="py-3 px-4">Load</th>
                            <th class="py-3 px-4 text-right">Cost Share</th>
                        </tr>
                    </thead>
                    <tbody id="plan-table-body" class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <tr><td colspan="6" class="py-6 text-center text-slate-400 dark:text-slate-500 italic text-xs">No plan generated yet.</td></tr>
                    </tbody>
                </table>
            </div>

            {{-- Notes + Proposal Submission Trigger --}}
            <div class="flex flex-wrap items-end gap-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                <div class="flex-1 min-w-[220px]">
                    <label class="block text-xs font-bold text-slate-400 dark:text-slate-550 uppercase tracking-widest mb-2">Instructions / Notes (optional)</label>
                    <input id="plan-notes" type="text" maxlength="500"
                           placeholder="e.g., Deliver to port before 12:00 PM, secure tarpaulin"
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition outline-none">
                </div>
                <button id="btn-confirm-plan"
                        class="bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-bold px-6 py-3.5 rounded-xl text-sm hover:shadow-lg hover:shadow-emerald-600/10 hover:translate-y-[-1px] transition-all flex items-center gap-2">
                    <span>📩</span> Create Delivery Proposal
                </button>
            </div>

            {{-- Confirm feedback --}}
            <div id="confirm-feedback" class="hidden mt-4 p-4 rounded-xl text-sm font-bold animate-pulse"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize the Leaflet map centered on Southern Mindanao (GenSan coordinates)
            const map = L.map('routing-map').setView([6.1164, 125.1716], 11);
            
            // Add standard OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

            // Farmers coordinate data passed from the controller
            const farms = @json($farmersData);

            // Map and routing variables
            let baseRouteGeoJSON    = null;  // Original straight-line route path
            let farmMarkers         = [];    // Array holding all plotted farmer pins
            let startMarker         = null;  // Depot / start pin
            let endMarker           = null;  // Market / end destination pin
            let routePolyline       = null;  // Line drawn on Leaflet map representing active path
            let currentRouteGeoJSON = null;  // Current active route geometry (e.g. including detours)
            let lastNearbyFarms     = [];    // Farms matched inside the selected radius
            let currentPlan         = null;  // Final calculated cost and weight allocations

            // Colors for custom markers
            const defaultIcon   = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',  iconSize: [25, 41], iconAnchor: [12, 41] });
            const highlightIcon = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png', iconSize: [25, 41], iconAnchor: [12, 41] });

            // High contrast red marker mapping for drop-off terminals / buyers
            const destinationMarkerIcon = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png', iconSize: [25, 41], iconAnchor: [12, 41] });

            const truckSelect   = document.getElementById('truck-select');
            const truckInfo     = document.getElementById('truck-info');
            const btnGenerate   = document.getElementById('btn-generate-plan');

            // ─── Truck Selector & Driver Validation Guard ───────────────────────
            // Checks if the chosen truck has a valid driver assigned before allowing routing.
            // Displays feedback if a driver is required.
            truckSelect.addEventListener('change', function () {
                const opt = this.options[this.selectedIndex];
                if (!this.value) {
                    truckInfo.classList.add('hidden');
                    btnGenerate.disabled = true;
                    return;
                }

                const driverValue = opt.dataset.driver ? opt.dataset.driver.trim() : '';
                const isDriverAssigned = driverValue !== '' && driverValue !== 'No driver assigned';

                if (!isDriverAssigned) {
                    document.getElementById('truck-info-driver').textContent   = '⚠️ ' + driverValue;
                    document.getElementById('truck-info-capacity').textContent = 'Requires driver assignment.';
                    truckInfo.className = 'text-xs text-amber-705 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/20 border border-amber-250/50 dark:border-amber-900/30 rounded-xl px-4 py-3.5 font-bold flex items-center gap-2';
                    truckInfo.classList.remove('hidden');
                    btnGenerate.disabled = true;
                } else {
                    document.getElementById('truck-info-driver').innerHTML     = '👤 Driver: <b>' + driverValue + '</b>';
                    document.getElementById('truck-info-capacity').textContent = '⚖️ ' + Number(opt.dataset.capacity).toLocaleString() + ' kg limit';
                    truckInfo.className = 'text-xs text-slate-650 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3.5 font-semibold flex items-center gap-2';
                    truckInfo.classList.remove('hidden');

                    btnGenerate.disabled = !(baseRouteGeoJSON && startMarker && endMarker);
                }

                if (lastNearbyFarms.length > 0) {
                    renderPickupQueue(lastNearbyFarms);
                }
            });

            // ─── Map Click Handlers ───────────────────────────────────────
            map.on('click', function (e) {
                if (!startMarker) {
                    startMarker = L.marker(e.latlng).addTo(map).bindPopup('<b>Start Point:</b> Hub Depot').openPopup();
                } else if (!endMarker) {
                    endMarker = L.marker(e.latlng).addTo(map).bindPopup('<b>End Point:</b> Delivery Wholesaler').openPopup();
                    generateBaseRoute(startMarker.getLatLng(), endMarker.getLatLng());
                }
            });

            // ─── OSRM Base Route Calculations & Callback Protections ──────
            async function generateBaseRoute(start, end) {
                const osrmUrl = buildOsrmUrl([start, end]);
                try {
                    const res  = await fetch(osrmUrl);
                    const data = await res.json();
                    if (!data.routes?.length) return;

                    currentRouteGeoJSON = data.routes[0].geometry;
                    baseRouteGeoJSON    = currentRouteGeoJSON;

                    const nearbyFarms = findFarmsAlongRoute();
                    lastNearbyFarms   = nearbyFarms;

                    if (nearbyFarms.length > 0) {
                        await generateDetourRoute(start, nearbyFarms, end);
                    } else {
                        drawRoute(currentRouteGeoJSON);
                    }

                    renderPickupQueue(nearbyFarms);
                    document.getElementById('reset-map').classList.remove('hidden');

                    const selectedOpt = truckSelect.options[truckSelect.selectedIndex];
                    const validDriver = selectedOpt && selectedOpt.value &&
                                        selectedOpt.dataset.driver !== 'No driver assigned' &&
                                        selectedOpt.dataset.driver.trim() !== '';

                    btnGenerate.disabled = !validDriver;
                } catch (err) {
                    console.error('Routing Error:', err);
                    alert('Failed to connect to routing engine.');
                }
            }

            async function generateDetourRoute(start, nearbyFarms, end) {
                const waypoints = [start, ...nearbyFarms.map(f => ({
                    lat: f.data.farmer_profile.latitude,
                    lng: f.data.farmer_profile.longitude,
                })), end];
                const osrmUrl = buildOsrmUrl(waypoints);
                try {
                    const res  = await fetch(osrmUrl);
                    const data = await res.json();
                    if (!data.routes?.length) return;
                    currentRouteGeoJSON = data.routes[0].geometry;
                    drawRoute(currentRouteGeoJSON);
                } catch (err) {
                    console.error('Detour routing error:', err);
                }
            }

            function buildOsrmUrl(points) {
                const coords = points.map(p => `${p.lng},${p.lat}`).join(';');
                return `https://router.project-osrm.org/route/v1/driving/${coords}?overview=full&geometries=geojson`;
            }

            function drawRoute(geojson) {
                if (routePolyline) map.removeLayer(routePolyline);
                routePolyline = L.geoJSON(geojson, { style: { color: '#3b82f6', weight: 5 } }).addTo(map);
            }

            window.plotFarmRoute = function(farmLat, farmLng, destLat, destLng) {
                if (startMarker)   map.removeLayer(startMarker);
                if (endMarker)     map.removeLayer(endMarker);
                if (routePolyline) map.removeLayer(routePolyline);

                startMarker = null; endMarker = null;
                baseRouteGeoJSON = null; currentRouteGeoJSON = null;
                farmMarkers.forEach(item => item.marker.setIcon(defaultIcon));

                const startLatLng = L.latLng(farmLat, farmLng);
                const endLatLng   = L.latLng(destLat, destLng);

                startMarker = L.marker(startLatLng).addTo(map).bindPopup('<b>Start:</b> Farm Pickup').openPopup();
                endMarker   = L.marker(endLatLng).addTo(map).bindPopup('<b>End:</b> Delivery Destination').openPopup();

                map.fitBounds([startLatLng, endLatLng], { padding: [60, 60] });
                generateBaseRoute(startLatLng, endLatLng);
                document.getElementById('reset-map').classList.remove('hidden');
            }

            // ─── Plot all farm markers + Red Destinations on load ──────
            farms.forEach((farm) => {
                if (farm.farmer_profile && farm.farmer_profile.latitude) {
                    // Plot standard pickup pin
                    const marker = L.marker([farm.farmer_profile.latitude, farm.farmer_profile.longitude], { icon: defaultIcon }).addTo(map);

                    const harvestList = farm.harvests.length
                        ? farm.harvests.map(h => `<li>🌾 ${h.crop} — ${h.quantity} kg</li>`).join('')
                        : '<li class="text-gray-400">No active listings</li>';

                    const destinationHtml = farm.destination
                        ? `<div style="margin-top:8px;padding-top:8px;border-top:1px solid #e2e8f0;">
                            <b style="font-size:11px;color:#64748b;letter-spacing:0.05em;text-transform:uppercase;">Destination</b>
                            <p style="margin:4px 0 0;font-size:12px;font-weight:700;color:#1e293b;">📦 ${farm.destination.name}</p>
                            <p style="margin:2px 0 0;font-size:11px;color:#64748b;">${farm.destination.address}</p>
                           </div>`
                        : farm.destination_address
                            ? `<div style="margin-top:8px;padding-top:8px;border-top:1px solid #e2e8f0;">
                                <b style="font-size:11px;color:#64748b;letter-spacing:0.05em;text-transform:uppercase;">Destination</b>
                                <p style="margin:4px 0 0;font-size:12px;font-weight:700;color:#1e293b;">📍 ${farm.destination_address}</p>
                               </div>`
                            : `<div style="margin-top:8px;padding-top:8px;border-top:1px solid #e2e8f0;">
                                <p style="font-size:11px;color:#94a3b8;">No destination set.</p>
                               </div>`;

                    const hasDestination = farm.destination_latitude && farm.destination_longitude;
                    const plotButtonHtml = hasDestination
                        ? `<button onclick="plotFarmRoute(${farm.farmer_profile.latitude},${farm.farmer_profile.longitude},${farm.destination_latitude},${farm.destination_longitude})"
                            style="margin-top:10px;width:100%;background:linear-gradient(to top right, #059669, #14b8a6);color:white;border:none;border-radius:8px;padding:8px 0;font-size:12px;font-weight:700;cursor:pointer;box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                            🗺️ Plot Route
                           </button>`
                        : `<button disabled style="margin-top:10px;width:100%;background:#f1f5f9;color:#94a3b8;border:none;border-radius:8px;padding:8px 0;font-size:12px;font-weight:700;cursor:not-allowed;">
                            No destination set
                           </button>`;

                    marker.bindPopup(`
                        <div style="min-width:200px;font-family:'Plus Jakarta Sans',sans-serif;">
                            <b style="font-size:14px;color:#0f172a;">${farm.name}</b>
                            <br><span style="color:#64748b;font-size:12px;">📍 ${farm.farmer_profile.farm_location}</span>
                            <hr style="margin:8px 0;border:0;border-top:1px solid #f1f5f9;">
                            <b style="font-size:11px;color:#64748b;letter-spacing:0.05em;text-transform:uppercase;">Active Harvests</b>
                            <ul style="margin:4px 0 0;padding-left:14px;font-size:12px;color:#334155;list-style-type:square;">${harvestList}</ul>
                            ${destinationHtml}
                            ${plotButtonHtml}
                        </div>
                    `, { maxWidth: 260 });

                    farmMarkers.push({ marker, data: farm });

                    // Plot Corresponding Red Drop-off Terminal Marker dynamically
                    if (farm.destination_latitude && farm.destination_longitude) {
                        const marketLabel = farm.destination ? farm.destination.name : (farm.destination_address ?? 'B2B Wholesale Terminal');

                        L.marker([farm.destination_latitude, farm.destination_longitude], { icon: destinationMarkerIcon })
                         .addTo(map)
                         .bindPopup(`<div style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;"><b>📦 Drop-off Terminal</b><br><span style="color:gray;">Cargo Wholesaler: ${farm.name}</span></div>`);
                    }
                }
            });

            /**
             * Finds all active farm listings situated within a specific search buffer distance 
             * off the main straight line connecting Start and End points.
             * Uses TurfJS for precise geometric calculation.
             */
            function findFarmsAlongRoute() {
                if (!baseRouteGeoJSON) return [];
                const currentRadius = parseFloat(document.getElementById('radius-select').value);
                const routeLine = turf.lineString(baseRouteGeoJSON.coordinates);
                const found = [];

                farmMarkers.forEach(item => {
                    const farmPt  = turf.point([item.data.farmer_profile.longitude, item.data.farmer_profile.latitude]);
                    // Calculate geographic distance in km from farm coordinate to route polyline
                    const distance = turf.pointToLineDistance(farmPt, routeLine, { units: 'kilometers' });

                    if (distance <= currentRadius) {
                        // Snap farm onto closest point on path to understand relative sequence
                        const snapped = turf.nearestPointOnLine(routeLine, farmPt);
                        found.push({ ...item, distance, routePosition: snapped.properties.location });
                        item.marker.setIcon(highlightIcon); // Highlight on map
                    } else {
                        item.marker.setIcon(defaultIcon); // Reset default color
                    }
                });

                // Sort farmers sequentially from Start depot towards End terminal
                found.sort((a, b) => a.routePosition - b.routePosition);
                return found;
            }

            function renderPickupQueue(nearbyFarms) {
                const currentRadius  = parseFloat(document.getElementById('radius-select').value);
                const queueContainer = document.getElementById('pickup-queue');
                queueContainer.innerHTML = '';

                if (nearbyFarms.length === 0) {
                    queueContainer.innerHTML = `<div class="text-center text-gray-400 mt-10">No farms detected within ${currentRadius}km of this route.</div>`;
                    return;
                }

                const selectedOpt = truckSelect.options[truckSelect.selectedIndex];
                const truckCapacity = selectedOpt && selectedOpt.value ? parseFloat(selectedOpt.dataset.capacity) : Infinity;

                nearbyFarms.forEach(item => {
                    const totalKg = item.data.harvests.reduce((sum, h) => sum + parseFloat(h.quantity || 0), 0);
                    const exceedsCapacity = totalKg > truckCapacity;

                    const cardClass = exceedsCapacity
                        ? 'bg-slate-50 dark:bg-slate-905/40 p-4 rounded-xl border border-slate-205 dark:border-slate-800 border-l-4 border-l-rose-500 opacity-60 filter grayscale relative overflow-hidden'
                        : 'bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-100/70 dark:border-slate-700/80 border-l-4 border-l-emerald-500 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden';

                    const capacityBadge = exceedsCapacity
                        ? `<span class="inline-block mt-2 text-[9px] font-bold uppercase tracking-wider bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400 border border-rose-250/50 dark:border-rose-900/30 px-2 py-0.5 rounded-md">⚠️ Over Limit</span>`
                        : '';

                    queueContainer.innerHTML += `
                        <div class="${cardClass}">
                            <strong class="text-sm ${exceedsCapacity ? 'text-slate-450 dark:text-slate-550 line-through' : 'text-slate-800 dark:text-slate-200 heading-font'}">${item.data.name}</strong>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 flex items-center gap-1"><span>📍</span> ${item.data.farmer_profile.farm_location}</p>
                            <p class="text-xs text-slate-404 dark:text-slate-500 mt-1">🚗 ${item.distance.toFixed(2)} km off-route</p>
                            <p class="text-xs mt-1.5 ${exceedsCapacity ? 'text-rose-600 dark:text-rose-450 font-bold' : 'text-slate-650 dark:text-slate-350 font-semibold'}">⚖️ ${totalKg.toLocaleString()} kg payload</p>
                            ${capacityBadge}
                        </div>
                    `;
                });
            }

            btnGenerate.addEventListener('click', async function () {
                const harvestIds = lastNearbyFarms.flatMap(f => f.data.harvests.map(h => h.id));
                btnGenerate.disabled = true;
                btnGenerate.textContent = '⏳ Generating...';

                try {
                    const res = await fetch('{{ route("pooling.plan") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({
                            truck_id:    parseInt(truckSelect.value),
                            harvest_ids: harvestIds,
                            start_lat:   startMarker.getLatLng().lat,
                            start_lng:   startMarker.getLatLng().lng,
                            end_lat:     endMarker.getLatLng().lat,
                            end_lng:     endMarker.getLatLng().lng,
                            radius_km:   parseFloat(document.getElementById('radius-select').value),
                        }),
                    });

                    const plan = await res.json();
                    if (!res.ok) { alert(plan.error); return; }
                    currentPlan = plan;
                    renderPlanPanel(plan);
                } catch (err) {
                    console.error(err);
                } finally {
                    btnGenerate.disabled = false;
                    btnGenerate.textContent = '⚙️ Generate Route Plan';
                }
            });

            function renderPlanPanel(plan) {
                const panel = document.getElementById('plan-panel');
                panel.classList.remove('hidden');
                panel.scrollIntoView({ behavior: 'smooth', block: 'start' });

                document.getElementById('plan-farm-count').textContent = plan.farm_count ?? '—';
                document.getElementById('plan-total-kg').textContent   = (plan.total_kg ?? 0).toLocaleString() + ' kg';
                document.getElementById('plan-load-pct').textContent   = (plan.load_percentage ?? 0).toFixed(1) + '%';
                document.getElementById('plan-truck-label').textContent = truckSelect.options[truckSelect.selectedIndex].text;
                document.getElementById('plan-distance').textContent   = (plan.total_distance_km ?? 0).toFixed(2) + ' km';
                document.getElementById('plan-price-ref').textContent  = '₱' + Number(plan.price_reference ?? 0).toLocaleString(undefined, {minimumFractionDigits: 2});

                const tbody = document.getElementById('plan-table-body');
                tbody.innerHTML = '';

                plan.selected_harvests.forEach((h, i) => {
                    tbody.innerHTML += `
                        <tr class="border-b border-slate-100 dark:border-slate-700/40 hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition-colors">
                            <td class="py-3.5 px-4 font-mono text-xs text-slate-400 dark:text-slate-550">#${i + 1}</td>
                            <td class="py-3.5 px-4 font-bold text-slate-700 dark:text-slate-300">${h.farm_name ?? '—'}</td>
                            <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 text-xs">${h.farm_location ?? '—'}</td>
                            <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 text-xs font-semibold">${h.crop ?? '—'}</td>
                            <td class="py-3.5 px-4 font-bold text-slate-800 dark:text-slate-200">${Number(h.quantity_kg).toLocaleString()} kg</td>
                            <td class="py-3.5 px-4 font-extrabold text-emerald-600 dark:text-emerald-400 text-right">
                                ₱${Number(h.split_cost ?? 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                            </td>
                        </tr>
                    `;
                });
            }

            document.getElementById('btn-confirm-plan').addEventListener('click', async function () {
                this.disabled = true; this.textContent = '⏳ Creating Proposal...';
                const harvestIds = lastNearbyFarms.flatMap(f => f.data.harvests.map(h => h.id));

                try {
                    const res = await fetch('{{ route("pooling.confirm") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({
                            truck_id:       parseInt(truckSelect.value),
                            harvest_ids:    harvestIds,
                            total_kg:       currentPlan.total_kg,
                            start_lat:      startMarker.getLatLng().lat,
                            start_lng:      startMarker.getLatLng().lng,
                            end_lat:        endMarker.getLatLng().lat,
                            end_lng:        endMarker.getLatLng().lng,
                            radius_km:      parseFloat(document.getElementById('radius-select').value),
                            notes:          document.getElementById('plan-notes').value,
                            route_geometry: currentRouteGeoJSON ? currentRouteGeoJSON.coordinates : [],
                        }),
                    });

                    const result = await res.json();
                    const feedback = document.getElementById('confirm-feedback');
                    feedback.classList.remove('hidden');

                    if (res.ok && result.success) {
                        document.getElementById('plan-status-badge').textContent = 'Proposal Created';
                        feedback.className = 'mt-4 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-900/30 font-bold flex items-center gap-2';
                        feedback.innerHTML = '<span>📩</span> Proposal pipeline open. Room linked to Job #' + result.pooling_job_id;
                        this.innerHTML = '<span>📩</span> Proposal Sent';
                    } else {
                        feedback.className = 'mt-4 p-4 rounded-xl bg-rose-50 dark:bg-rose-950/20 text-rose-800 dark:text-rose-455 border border-rose-200/60 dark:border-rose-900/30 font-bold flex items-center gap-2';
                        feedback.innerHTML = '<span>❌</span> ' + result.error;
                        this.disabled = false; this.innerHTML = '<span>📩</span> Create Delivery Proposal';
                    }
                } catch (err) { console.error(err); }
            });

            document.getElementById('radius-select').addEventListener('change', function () {
                if (!baseRouteGeoJSON) return;
                const nearbyFarms = findFarmsAlongRoute();
                lastNearbyFarms = nearbyFarms;
                renderPickupQueue(nearbyFarms);
                if (nearbyFarms.length > 0) generateDetourRoute(startMarker.getLatLng(), nearbyFarms, endMarker.getLatLng());
                else drawRoute(baseRouteGeoJSON);
            });

            document.getElementById('reset-map').addEventListener('click', function () {
                if (startMarker) map.removeLayer(startMarker); if (endMarker) map.removeLayer(endMarker); if (routePolyline) map.removeLayer(routePolyline);
                startMarker = null; endMarker = null; currentRouteGeoJSON = null; baseRouteGeoJSON = null; lastNearbyFarms = [];
                farmMarkers.forEach(item => item.marker.setIcon(defaultIcon));
                document.getElementById('pickup-queue').innerHTML = '<div class="text-center text-gray-400 mt-10 italic">Awaiting route generation...</div>';
                document.getElementById('plan-panel').classList.add('hidden');
                btnGenerate.disabled = true; this.classList.add('hidden');
            });
        });
    </script>
</x-layout>
