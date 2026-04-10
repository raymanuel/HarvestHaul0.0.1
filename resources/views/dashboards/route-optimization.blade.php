<x-layout>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>

    <div class="w-full pb-12">
        <header class="pt-8 mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Route Optimization Engine</h1>
            <p class="text-gray-500 text-lg">Plan your pickup routes and discover farms nearby.</p>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div id="routing-map" class="w-full rounded-xl border-2 border-gray-200 relative z-10 shadow-inner" style="height: 600px;"></div>
                <p class="text-sm text-gray-500 mt-2">💡 <b> Click the map to set a Start point, then click again for the End point. </b> </p>
            </div>

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
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
        // 1. Initialize Map (Centered on GenSan)
        const map = L.map('routing-map').setView([6.1164, 125.1716], 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

        const farms = @json($farmersData);
        let farmMarkers = [];
        let startMarker = null;
        let endMarker = null;
        let routePolyline = null;
        let currentRouteGeoJSON = null; // NEW: Store the route globally

        const defaultIcon = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png', iconSize: [25, 41], iconAnchor: [12, 41] });
        const highlightIcon = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png', iconSize: [25, 41], iconAnchor: [12, 41] });

        farms.forEach((farm) => {
            if(farm.farmer_profile && farm.farmer_profile.latitude) {
                const marker = L.marker([farm.farmer_profile.latitude, farm.farmer_profile.longitude], {icon: defaultIcon}).addTo(map);
                const harvestList = farm.harvests.length
                    ? farm.harvests.map(h => `<li>🌾 ${h.crop} — ${h.quantity} kg</li>`).join('')
                    : '<li class="text-gray-400">No active listings</li>';

                marker.bindPopup(`
                    <b>${farm.name}</b>
                    <br><span class="text-gray-500">📍 ${farm.farmer_profile.farm_location}</span>
                    <hr style="margin:6px 0">
                    <b style="font-size:11px">ACTIVE HARVESTS</b>
                    <ul style="margin:4px 0 0;padding-left:14px;font-size:12px">${harvestList}</ul>
                `);
                farmMarkers.push({ marker: marker, data: farm });
            }
        });

        map.on('click', function(e) {
            if (!startMarker) {
                startMarker = L.marker(e.latlng).addTo(map).bindPopup("<b>Start:</b> Logistics Hub").openPopup();
            } else if (!endMarker) {
                endMarker = L.marker(e.latlng).addTo(map).bindPopup("<b>End:</b> Drop-off Point").openPopup();
                generateRoute(startMarker.getLatLng(), endMarker.getLatLng());
            }
        });

        async function generateRoute(start, end) {
            const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${start.lng},${start.lat};${end.lng},${end.lat}?overview=full&geometries=geojson`;

            try {
                const response = await fetch(osrmUrl);
                const data = await response.json();

                if (data.routes && data.routes.length > 0) {
                    currentRouteGeoJSON = data.routes[0].geometry; // Store the route

                    routePolyline = L.geoJSON(currentRouteGeoJSON, { style: { color: '#3b82f6', weight: 5 } }).addTo(map);

                    findFarmsAlongRoute(); // Call without passing args, uses global state
                    document.getElementById('reset-map').classList.remove('hidden');
                }
            } catch (error) {
                console.error("Routing Error:", error);
                alert("Failed to connect to routing engine.");
            }
        }

        // NEW: Dynamic Proximity Math
        function findFarmsAlongRoute() {
            if (!currentRouteGeoJSON) return; // Don't run if no route exists yet

            // Read the current value from the dropdown
            const currentRadius = parseFloat(document.getElementById('radius-select').value);

            // Update the UI text dynamically
            document.getElementById('radius-description').innerText = `Farms within ${currentRadius}km of the selected route will appear here.`;

            const queueContainer = document.getElementById('pickup-queue');
            queueContainer.innerHTML = '';

            const routeLine = turf.lineString(currentRouteGeoJSON.coordinates);
            let farmsFound = 0;

            farmMarkers.forEach(item => {
                const farmPt = turf.point([item.data.farmer_profile.longitude, item.data.farmer_profile.latitude]);
                const distance = turf.pointToLineDistance(farmPt, routeLine, { units: 'kilometers' });

                // Compare against the dynamic radius
                if (distance <= currentRadius) {
                    farmsFound++;
                    item.marker.setIcon(highlightIcon);

                    queueContainer.innerHTML += `
                        <div class="bg-white p-3 rounded shadow-sm border-l-4 border-green-500">
                            <strong class="text-green-700">${item.data.name}</strong>
                            <p class="text-xs text-gray-600 mt-1">📍 ${item.data.farmer_profile.farm_location}</p>
                            <p class="text-xs text-gray-500 mt-1">🚗 ${distance.toFixed(2)} km off-route</p>
                        </div>
                    `;
                } else {
                    item.marker.setIcon(defaultIcon);
                }
            });

            if (farmsFound === 0) {
                queueContainer.innerHTML = `<div class="text-center text-gray-400 mt-10">No farms detected within ${currentRadius}km of this route.</div>`;
            }
        }

        // NEW: Listen for changes on the dropdown
        document.getElementById('radius-select').addEventListener('change', function() {
            findFarmsAlongRoute(); // Recalculate instantly when user changes the dropdown
        });

        document.getElementById('reset-map').addEventListener('click', function() {
            if (startMarker) map.removeLayer(startMarker);
            if (endMarker) map.removeLayer(endMarker);
            if (routePolyline) map.removeLayer(routePolyline);

            startMarker = null; endMarker = null; currentRouteGeoJSON = null;
            farmMarkers.forEach(item => item.marker.setIcon(defaultIcon));

            document.getElementById('pickup-queue').innerHTML = '<div class="text-center text-gray-400 mt-10 italic">Awaiting route generation...</div>';
            this.classList.add('hidden');
        });
    });
    </script>
</x-layout>
