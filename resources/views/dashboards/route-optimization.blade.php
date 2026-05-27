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
        <header class="pt-8 mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Route Optimization Engine</h1>
            <p class="text-gray-500 text-lg">Plan your pickup routes and discover farms nearby.</p>
        </header>

        {{-- ─── Truck Selector + Generate Plan Bar ─── --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 mb-6 flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-1">Select Truck</label>
                <select id="truck-select" class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    <option value="">— Choose a truck —</option>
                    @forelse($trucks as $truck)
                        <option value="{{ $truck['id'] }}"
                                data-capacity="{{ $truck['capacity_kg'] }}"
                                data-driver="{{ $truck['driver'] }}">
                            {{ $truck['label'] }} ({{ number_format($truck['capacity_kg']) }} kg)
                        </option>
                    @empty
                        <option disabled>No available trucks</option>
                    @endforelse
                </select>
            </div>

            <div id="truck-info" class="hidden text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-lg px-4 py-2">
                <span id="truck-info-driver"></span> &bull;
                <span id="truck-info-capacity"></span>
            </div>

            <button id="btn-generate-plan"
                    disabled
                    class="bg-green-600 text-white font-bold px-5 py-2 rounded-lg text-sm hover:bg-green-700 transition disabled:opacity-40 disabled:cursor-not-allowed">
                ⚙️ Generate Route Plan
            </button>
        </div>

        {{-- ─── Main Grid: Map + Sidebar ─── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Map --}}
            <div class="lg:col-span-2">
                <div id="routing-map" class="w-full rounded-xl border-2 border-gray-200 relative z-10 shadow-inner" style="height: 600px;"></div>
                <p class="text-sm text-gray-500 mt-2">💡 <b>Click the map to set a Start point, then click again for the End point.</b></p>
            </div>

            {{-- Sidebar --}}
            <div class="bg-slate-50 rounded-xl border border-gray-200 p-5 flex flex-col h-[600px]">
                <h3 class="text-lg font-bold text-gray-800 mb-2">📍 Route Pickups</h3>

                <div class="mb-4 bg-white p-3 rounded-lg border border-gray-200 shadow-sm">
                    <label for="radius-select" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1">Search Radius</label>
                    <select id="radius-select" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm cursor-pointer">
                        <option value="1">Within 1 km</option>
                        <option value="3">Within 3 km</option>
                        <option value="5" selected>Within 5 km</option>
                        <option value="10">Within 10 km</option>
                        <option value="20">Within 20 km</option>
                    </select>
                </div>

                <p id="radius-description" class="text-xs text-gray-500 mb-4">Farms within 5km of the selected route will appear here.</p>

                <div id="pickup-queue" class="flex-1 overflow-y-auto space-y-3 pr-2">
                    <div class="text-center text-gray-400 mt-10 italic">Awaiting route generation...</div>
                </div>

                <button id="reset-map" class="w-full mt-4 bg-red-50 text-red-600 font-bold py-2 rounded-lg border border-red-200 hover:bg-red-100 transition hidden">
                    Clear Route & Restart
                </button>
            </div>
        </div>

        {{-- ─── Pooling Plan Panel ─── --}}
        <div id="plan-panel" class="hidden mt-8 bg-white border border-gray-200 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800">🧮 Pooling Plan Preview</h2>
                <span id="plan-status-badge" class="text-xs font-bold px-3 py-1 rounded-full bg-yellow-100 text-yellow-700">Unconfirmed</span>
            </div>

            {{-- Summary row --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                <div class="bg-gray-50 rounded-lg p-3 border border-gray-100 text-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Farms Selected</p>
                    <p id="plan-farm-count" class="text-2xl font-bold text-gray-900 mt-1">—</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 border border-gray-100 text-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Total Load</p>
                    <p id="plan-total-kg" class="text-2xl font-bold text-green-700 mt-1">—</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 border border-gray-100 text-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Capacity Used</p>
                    <p id="plan-load-pct" class="text-2xl font-bold text-blue-700 mt-1">—</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 border border-gray-100 text-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Est. Distance</p>
                    <p id="plan-distance" class="text-2xl font-bold text-slate-700 mt-1">—</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 border border-gray-100 text-center ring-2 ring-green-600/10 bg-green-50/30">
                    <p class="text-xs text-green-700 font-bold uppercase tracking-wide">Suggested Price</p>
                    <p id="plan-price-ref" class="text-2xl font-black text-green-700 mt-1">—</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 border border-gray-100 text-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Assigned Truck</p>
                    <p id="plan-truck-label" class="text-sm font-bold text-gray-700 mt-1 truncate">—</p>
                </div>
            </div>

            {{-- Farm pickup order table --}}
            <div class="overflow-x-auto mb-6">
                <table class="w-full text-sm text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wide">
                            <th class="py-2 pr-4">Order</th>
                            <th class="py-2 pr-4">Farm</th>
                            <th class="py-2 pr-4">Location</th>
                            <th class="py-2 pr-4">Crop(s)</th>
                            <th class="py-2 pr-4">Load (kg)</th>
                            <th class="py-2 text-right">Cost Share</th>
                        </tr>
                    </thead>
                    <tbody id="plan-table-body">
                        <tr><td colspan="6" class="py-4 text-center text-gray-400">No plan generated yet.</td></tr>
                    </tbody>
                </table>
            </div>

            {{-- Notes + Proposal Submission Trigger --}}
            <div class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[220px]">
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-1">Notes (optional)</label>
                    <input id="plan-notes" type="text" maxlength="500"
                           placeholder="e.g. deliver before noon"
                           class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                </div>
                <button id="btn-confirm-plan"
                        class="bg-green-700 text-white font-bold px-6 py-2 rounded-lg text-sm hover:bg-green-800 transition disabled:opacity-40 disabled:cursor-not-allowed">
                    📩 Create Delivery Proposal
                </button>
            </div>

            {{-- Confirm feedback --}}
            <div id="confirm-feedback" class="hidden mt-4 p-3 rounded-lg text-sm font-medium"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const map = L.map('routing-map').setView([6.1164, 125.1716], 11);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

            const farms = @json($farmersData);

            let baseRouteGeoJSON    = null;
            let farmMarkers         = [];
            let startMarker         = null;
            let endMarker           = null;
            let routePolyline       = null;
            let currentRouteGeoJSON = null;
            let lastNearbyFarms     = [];
            let currentPlan         = null;

            const defaultIcon   = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',  iconSize: [25, 41], iconAnchor: [12, 41] });
            const highlightIcon = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png', iconSize: [25, 41], iconAnchor: [12, 41] });

            // NEW: High contrast red marker mapping for B2B wholesalers/markets
            const destinationMarkerIcon = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png', iconSize: [25, 41], iconAnchor: [12, 41] });

            const truckSelect   = document.getElementById('truck-select');
            const truckInfo     = document.getElementById('truck-info');
            const btnGenerate   = document.getElementById('btn-generate-plan');

            // ─── Truck Selector & Driver Validation Guard ───────────────────────
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
                    document.getElementById('truck-info-capacity').textContent = 'Cannot route without fleet operator.';
                    truckInfo.className = 'text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-4 py-2 font-medium';
                    truckInfo.classList.remove('hidden');
                    btnGenerate.disabled = true;
                } else {
                    document.getElementById('truck-info-driver').textContent   = '🚗 Driver: ' + driverValue;
                    document.getElementById('truck-info-capacity').textContent = '⚖️ ' + Number(opt.dataset.capacity).toLocaleString() + ' kg max capacity';
                    truckInfo.className = 'text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-lg px-4 py-2';
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
                    startMarker = L.marker(e.latlng).addTo(map).bindPopup('<b>Start:</b> Logistics Hub').openPopup();
                } else if (!endMarker) {
                    endMarker = L.marker(e.latlng).addTo(map).bindPopup('<b>End:</b> Drop-off Point').openPopup();
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
                        ? `<div style="margin-top:8px;padding-top:8px;border-top:1px solid #e5e7eb;">
                            <b style="font-size:11px">DESTINATION</b>
                            <p style="margin:4px 0 0;font-size:12px;">📦 ${farm.destination.name}</p>
                            <p style="margin:2px 0 0;font-size:11px;color:#6b7280;">${farm.destination.address}</p>
                           </div>`
                        : farm.destination_address
                            ? `<div style="margin-top:8px;padding-top:8px;border-top:1px solid #e5e7eb;">
                                <b style="font-size:11px">DESTINATION</b>
                                <p style="margin:4px 0 0;font-size:12px;">📍 ${farm.destination_address}</p>
                               </div>`
                            : `<div style="margin-top:8px;padding-top:8px;border-top:1px solid #e5e7eb;">
                                <p style="font-size:11px;color:#9ca3af;">No destination set.</p>
                               </div>`;

                    const hasDestination = farm.destination_latitude && farm.destination_longitude;
                    const plotButtonHtml = hasDestination
                        ? `<button onclick="plotFarmRoute(${farm.farmer_profile.latitude},${farm.farmer_profile.longitude},${farm.destination_latitude},${farm.destination_longitude})"
                            style="margin-top:10px;width:100%;background:#2D8A37;color:white;border:none;border-radius:6px;padding:6px 0;font-size:12px;font-weight:700;cursor:pointer;">
                            🗺️ Plot Route to Destination
                           </button>`
                        : `<button disabled style="margin-top:10px;width:100%;background:#e5e7eb;color:#9ca3af;border:none;border-radius:6px;padding:6px 0;font-size:12px;font-weight:700;cursor:not-allowed;">
                            No destination set
                           </button>`;

                    marker.bindPopup(`
                        <div style="min-width:200px;">
                            <b>${farm.name}</b>
                            <br><span style="color:#6b7280;font-size:12px;">📍 ${farm.farmer_profile.farm_location}</span>
                            <hr style="margin:6px 0">
                            <b style="font-size:11px">ACTIVE HARVESTS</b>
                            <ul style="margin:4px 0 0;padding-left:14px;font-size:12px">${harvestList}</ul>
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
                         .bindPopup(`<b>📦 Dynamic Drop-off Terminal</b><br><span style="font-size:12px;color:gray;">Linked Cargo: ${farm.name} Wholesaler</span>`);
                    }
                }
            });

            function findFarmsAlongRoute() {
                if (!baseRouteGeoJSON) return [];
                const currentRadius = parseFloat(document.getElementById('radius-select').value);
                const routeLine = turf.lineString(baseRouteGeoJSON.coordinates);
                const found = [];

                farmMarkers.forEach(item => {
                    const farmPt  = turf.point([item.data.farmer_profile.longitude, item.data.farmer_profile.latitude]);
                    const distance = turf.pointToLineDistance(farmPt, routeLine, { units: 'kilometers' });

                    if (distance <= currentRadius) {
                        const snapped = turf.nearestPointOnLine(routeLine, farmPt);
                        found.push({ ...item, distance, routePosition: snapped.properties.location });
                        item.marker.setIcon(highlightIcon);
                    } else {
                        item.marker.setIcon(defaultIcon);
                    }
                });

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
                        ? 'bg-gray-100 p-3 rounded shadow-sm border-l-4 border-red-500 opacity-50 filter grayscale'
                        : 'bg-white p-3 rounded shadow-sm border-l-4 border-green-500';

                    const capacityBadge = exceedsCapacity
                        ? `<span class="inline-block mt-1 text-[10px] font-bold bg-red-100 text-red-700 px-2 py-0.5 rounded">⚠️ Exceeds Capacity</span>`
                        : '';

                    queueContainer.innerHTML += `
                        <div class="${cardClass}">
                            <strong class="${exceedsCapacity ? 'text-gray-500 line-through' : 'text-green-700'}">${item.data.name}</strong>
                            <p class="text-xs text-gray-600 mt-1">📍 ${item.data.farmer_profile.farm_location}</p>
                            <p class="text-xs text-gray-500 mt-1">🚗 ${item.distance.toFixed(2)} km off-route</p>
                            <p class="text-xs ${exceedsCapacity ? 'text-red-600 font-bold' : 'text-gray-500'}">⚖️ ${totalKg.toLocaleString()} kg total</p>
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
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-3 font-mono text-gray-500">#${i + 1}</td>
                            <td class="py-3 font-semibold text-gray-800">${h.farm_name ?? '—'}</td>
                            <td class="py-3 text-gray-600 text-xs">${h.farm_location ?? '—'}</td>
                            <td class="py-3 text-gray-600 text-xs">${h.crop ?? '—'}</td>
                            <td class="py-3 font-bold text-slate-700">${Number(h.quantity_kg).toLocaleString()} kg</td>
                            <td class="py-3 font-black text-green-700 text-right">
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
                        feedback.className = 'mt-4 p-3 rounded bg-green-50 text-green-800 border border-green-200';
                        feedback.textContent = '📩 Proposal pipeline open. Room linked to Job #' + result.pooling_job_id;
                        this.textContent = '📩 Proposal Sent';
                    } else {
                        feedback.className = 'mt-4 p-3 rounded bg-red-50 text-red-800 border border-red-200';
                        feedback.textContent = '❌ ' + result.error;
                        this.disabled = false; this.textContent = '📩 Create Delivery Proposal';
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
