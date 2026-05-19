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
                ⚙️ Generate Pooling Plan
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

        {{-- ─── Pooling Plan Panel (hidden until plan is generated) ─── --}}
        <div id="plan-panel" class="hidden mt-8 bg-white border border-gray-200 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800">🧮 Pooling Plan Preview</h2>
                <span id="plan-status-badge" class="text-xs font-bold px-3 py-1 rounded-full bg-yellow-100 text-yellow-700">Unconfirmed</span>
            </div>

            {{-- Summary row --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
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
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Truck</p>
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
                            <th class="py-2">Load (kg)</th>
                        </tr>
                    </thead>
                    <tbody id="plan-table-body">
                        <tr><td colspan="5" class="py-4 text-center text-gray-400">No plan generated yet.</td></tr>
                    </tbody>
                </table>
            </div>

            {{-- Notes + Confirm --}}
            <div class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[220px]">
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-1">Notes (optional)</label>
                    <input id="plan-notes" type="text" maxlength="500"
                           placeholder="e.g. deliver before noon"
                           class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                </div>
                <button id="btn-confirm-plan"
                        class="bg-green-700 text-white font-bold px-6 py-2 rounded-lg text-sm hover:bg-green-800 transition disabled:opacity-40 disabled:cursor-not-allowed">
                    ✅ Confirm & Save Job
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
            let lastNearbyFarms     = [];  // farms visible in current radius
            let currentPlan         = null; // last plan returned from server

            const defaultIcon   = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',  iconSize: [25, 41], iconAnchor: [12, 41] });
            const highlightIcon = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png', iconSize: [25, 41], iconAnchor: [12, 41] });

            // ─── Truck selector ───────────────────────────────────────────
            const truckSelect   = document.getElementById('truck-select');
            const truckInfo     = document.getElementById('truck-info');
            const btnGenerate   = document.getElementById('btn-generate-plan');

            truckSelect.addEventListener('change', function () {
                const opt = this.options[this.selectedIndex];
                if (!this.value) {
                    truckInfo.classList.add('hidden');
                    btnGenerate.disabled = true;
                    return;
                }
                document.getElementById('truck-info-driver').textContent   = '🚗 ' + opt.dataset.driver;
                document.getElementById('truck-info-capacity').textContent = '⚖️ ' + Number(opt.dataset.capacity).toLocaleString() + ' kg capacity';
                truckInfo.classList.remove('hidden');
                // Enable generate only when a route has also been drawn
                btnGenerate.disabled = !(baseRouteGeoJSON && startMarker && endMarker);
            });

            // ─── Map click: set start → end → generate base route ─────────
            map.on('click', function (e) {
                if (!startMarker) {
                    startMarker = L.marker(e.latlng).addTo(map).bindPopup('<b>Start:</b> Logistics Hub').openPopup();
                } else if (!endMarker) {
                    endMarker = L.marker(e.latlng).addTo(map).bindPopup('<b>End:</b> Drop-off Point').openPopup();
                    generateBaseRoute(startMarker.getLatLng(), endMarker.getLatLng());
                }
            });

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

                    // Enable Generate Plan button if a truck is selected
                    if (truckSelect.value) btnGenerate.disabled = false;
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

            // Plot all farm markers on load
            farms.forEach((farm) => {
                if (farm.farmer_profile && farm.farmer_profile.latitude) {
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
                }
            });

            // ─── Radius detection ─────────────────────────────────────────
            function findFarmsAlongRoute() {
                if (!baseRouteGeoJSON) return [];
                const currentRadius = parseFloat(document.getElementById('radius-select').value);
                document.getElementById('radius-description').innerText =
                    `Farms within ${currentRadius}km of the selected route will appear here.`;

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

            // ─── Pickup queue sidebar ─────────────────────────────────────
            function renderPickupQueue(nearbyFarms) {
                const currentRadius  = parseFloat(document.getElementById('radius-select').value);
                const queueContainer = document.getElementById('pickup-queue');
                queueContainer.innerHTML = '';

                if (nearbyFarms.length === 0) {
                    queueContainer.innerHTML = `<div class="text-center text-gray-400 mt-10">No farms detected within ${currentRadius}km of this route.</div>`;
                    return;
                }

                nearbyFarms.forEach(item => {
                    const totalKg = item.data.harvests.reduce((sum, h) => sum + parseFloat(h.quantity || 0), 0);
                    queueContainer.innerHTML += `
                        <div class="bg-white p-3 rounded shadow-sm border-l-4 border-green-500">
                            <strong class="text-green-700">${item.data.name}</strong>
                            <p class="text-xs text-gray-600 mt-1">📍 ${item.data.farmer_profile.farm_location}</p>
                            <p class="text-xs text-gray-500 mt-1">🚗 ${item.distance.toFixed(2)} km off-route</p>
                            <p class="text-xs text-gray-500">⚖️ ${totalKg.toLocaleString()} kg total</p>
                        </div>
                    `;
                });
            }

            // ─── Generate Pooling Plan ────────────────────────────────────
            btnGenerate.addEventListener('click', async function () {
                if (!truckSelect.value || !startMarker || !endMarker || lastNearbyFarms.length === 0) {
                    alert('Please set a route and select a truck first.');
                    return;
                }

                // Collect all harvest IDs from nearby farms
                const harvestIds = lastNearbyFarms.flatMap(f => f.data.harvests.map(h => h.id));

                if (!harvestIds.length) {
                    alert('No harvest IDs found for nearby farms.');
                    return;
                }

                btnGenerate.disabled = true;
                btnGenerate.textContent = '⏳ Generating...';

                try {
                    const res = await fetch('{{ route("pooling.plan") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
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

                    if (!res.ok) {
                        alert(plan.error ?? 'Failed to generate plan.');
                        return;
                    }

                    currentPlan = plan;
                    renderPlanPanel(plan);
                } catch (err) {
                    console.error('Plan error:', err);
                    alert('An error occurred while generating the plan.');
                } finally {
                    btnGenerate.disabled = false;
                    btnGenerate.textContent = '⚙️ Generate Pooling Plan';
                }
            });

            // ─── Render plan panel ────────────────────────────────────────
            function renderPlanPanel(plan) {
                const panel = document.getElementById('plan-panel');
                panel.classList.remove('hidden');
                panel.scrollIntoView({ behavior: 'smooth', block: 'start' });

                document.getElementById('plan-farm-count').textContent = plan.farm_count ?? '—';
                document.getElementById('plan-total-kg').textContent   = (plan.total_kg ?? 0).toLocaleString() + ' kg';
                document.getElementById('plan-load-pct').textContent   = (plan.load_percentage ?? 0).toFixed(1) + '%';
                document.getElementById('plan-truck-label').textContent = truckSelect.options[truckSelect.selectedIndex].text;
                document.getElementById('plan-status-badge').textContent = 'Unconfirmed';
                document.getElementById('plan-status-badge').className   = 'text-xs font-bold px-3 py-1 rounded-full bg-yellow-100 text-yellow-700';
                document.getElementById('confirm-feedback').classList.add('hidden');

                const tbody = document.getElementById('plan-table-body');
                tbody.innerHTML = '';

                if (!plan.selected_harvests || plan.selected_harvests.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="py-4 text-center text-gray-400">No farms could fit within truck capacity.</td></tr>';
                    return;
                }

                plan.selected_harvests.forEach((h, i) => {
                    tbody.innerHTML += `
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-2 pr-4 text-gray-500 font-mono">#${i + 1}</td>
                            <td class="py-2 pr-4 font-semibold text-gray-800">${h.farm_name ?? '—'}</td>
                            <td class="py-2 pr-4 text-gray-500 text-xs">${h.farm_location ?? '—'}</td>
                            <td class="py-2 pr-4 text-gray-600 text-xs">${h.crop ?? '—'}</td>
                            <td class="py-2 font-bold text-green-700">${Number(h.quantity_kg).toLocaleString()}</td>
                        </tr>
                    `;
                });
            }

            // ─── Confirm Plan ─────────────────────────────────────────────
            document.getElementById('btn-confirm-plan').addEventListener('click', async function () {
                if (!currentPlan || !truckSelect.value || !startMarker || !endMarker) return;

                this.disabled = true;
                this.textContent = '⏳ Saving...';

                const harvestIds = lastNearbyFarms.flatMap(f => f.data.harvests.map(h => h.id));

                try {
                    const res = await fetch('{{ route("pooling.confirm") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            truck_id:    parseInt(truckSelect.value),
                            harvest_ids: harvestIds,
                            total_kg:    currentPlan.total_kg,
                            start_lat:   startMarker.getLatLng().lat,
                            start_lng:   startMarker.getLatLng().lng,
                            end_lat:     endMarker.getLatLng().lat,
                            end_lng:     endMarker.getLatLng().lng,
                            radius_km:   parseFloat(document.getElementById('radius-select').value),
                            notes:       document.getElementById('plan-notes').value,
                        }),
                    });

                    const result = await res.json();
                    const feedback = document.getElementById('confirm-feedback');

                    if (res.ok && result.success) {
                        document.getElementById('plan-status-badge').textContent = 'Confirmed';
                        document.getElementById('plan-status-badge').className   = 'text-xs font-bold px-3 py-1 rounded-full bg-green-100 text-green-700';
                        feedback.className   = 'mt-4 p-3 rounded-lg text-sm font-medium bg-green-50 text-green-800 border border-green-200';
                        feedback.textContent = '✅ ' + result.message + ' Job #' + result.pooling_job_id;
                        feedback.classList.remove('hidden');
                        this.disabled    = true;
                        this.textContent = '✅ Confirmed';
                    } else {
                        feedback.className   = 'mt-4 p-3 rounded-lg text-sm font-medium bg-red-50 text-red-800 border border-red-200';
                        feedback.textContent = '❌ ' + (result.error ?? 'Something went wrong.');
                        feedback.classList.remove('hidden');
                        this.disabled    = false;
                        this.textContent = '✅ Confirm & Save Job';
                    }
                } catch (err) {
                    console.error('Confirm error:', err);
                    this.disabled    = false;
                    this.textContent = '✅ Confirm & Save Job';
                }
            });

            // ─── Radius dropdown ──────────────────────────────────────────
            document.getElementById('radius-select').addEventListener('change', function () {
                if (!baseRouteGeoJSON || !startMarker || !endMarker) return;

                const nearbyFarms = findFarmsAlongRoute();
                lastNearbyFarms   = nearbyFarms;
                renderPickupQueue(nearbyFarms);

                if (nearbyFarms.length > 0) {
                    generateDetourRoute(startMarker.getLatLng(), nearbyFarms, endMarker.getLatLng());
                } else {
                    drawRoute(baseRouteGeoJSON);
                }

                // Disable generate if no farms in new radius
                btnGenerate.disabled = !(truckSelect.value && nearbyFarms.length > 0);
            });

            // ─── Reset ────────────────────────────────────────────────────
            document.getElementById('reset-map').addEventListener('click', function () {
                if (startMarker)   map.removeLayer(startMarker);
                if (endMarker)     map.removeLayer(endMarker);
                if (routePolyline) map.removeLayer(routePolyline);

                startMarker = null; endMarker = null;
                currentRouteGeoJSON = null; baseRouteGeoJSON = null;
                lastNearbyFarms = []; currentPlan = null;

                farmMarkers.forEach(item => item.marker.setIcon(defaultIcon));

                document.getElementById('pickup-queue').innerHTML       = '<div class="text-center text-gray-400 mt-10 italic">Awaiting route generation...</div>';
                document.getElementById('radius-description').innerText = 'Farms within 5km of the selected route will appear here.';
                document.getElementById('plan-panel').classList.add('hidden');
                btnGenerate.disabled = true;
                this.classList.add('hidden');
            });


            // ─── Real-Time Tracking Coordinator Engine ────────────────────
            const activeJobs = @json($activeJobIds);
            const activeTruckMarkers = {};

            const truckIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-orange.png', // Distinct fleet icon
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
            });

            if (activeJobs.length > 0) {
                pollTruckLocations();
                setInterval(pollTruckLocations, 10000); // 10-second polling cycle
            }

            async function pollTruckLocations() {
                for (const jobId of activeJobs) {
                    try {
                        let url = '{{ route("tracking.latest", "JOB_ID_PLACEHOLDER") }}'.replace('JOB_ID_PLACEHOLDER', jobId);

                        const res = await fetch(url, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const response = await res.json();

                        if (response.status === 'success' && response.data) {
                            const lat = response.data.latitude;
                            const lng = response.data.longitude;
                            const postedAt = new Date(response.data.posted_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second:'2-digit' });

                            if (activeTruckMarkers[jobId]) {
                                // Update existing fleet marker dynamically
                                activeTruckMarkers[jobId].setLatLng([lat, lng]);
                                activeTruckMarkers[jobId].getPopup().setContent(`<b>🚛 Active Fleet (Job #${jobId})</b><br><span style="font-size:11px;color:gray;">Last GPS Sync: ${postedAt}</span>`);
                            } else {
                                // Plot new fleet marker
                                const marker = L.marker([lat, lng], { icon: truckIcon })
                                    .addTo(map)
                                    .bindPopup(`<b>🚛 Active Fleet (Job #${jobId})</b><br><span style="font-size:11px;color:gray;">Last GPS Sync: ${postedAt}</span>`);
                                activeTruckMarkers[jobId] = marker;
                            }
                        }
                    } catch (error) {
                        console.warn(`Polling failed for Job ${jobId}:`, error);
                    }
                }
            }
        });
    </script>
</x-layout>
