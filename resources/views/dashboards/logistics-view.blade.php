<x-layout>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <div class="w-full pb-12">
        <header class="pt-8 mb-4">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome, {{ Auth::user()->name }}</h1>
            <p class="text-gray-500 text-lg">Manage your fleet, drivers, and logistical operations from here.</p>
        </header>

        <div class="report-grid">
            <div class="report-widget">
                <span class="text-4xl mb-3 block">🚛</span>
                <h3 class="font-bold text-gray-400 text-sm uppercase tracking-wider">Active Fleet</h3>
                <p class="text-3xl font-black text-gray-900 mt-2">0 <span class="text-sm font-medium text-gray-400">Vehicles</span></p>
                <div class="mt-6 pt-4 border-t border-gray-100">
                    <a href="#" class="text-blue-600 font-bold text-sm hover:underline">Manage vehicles →</a>
                </div>
            </div>

            <div class="report-widget">
                <span class="text-4xl mb-3 block">👤</span>
                <h3 class="font-bold text-gray-400 text-sm uppercase tracking-wider">Managed Drivers</h3>
                <p class="text-3xl font-black text-gray-900 mt-2">0 <span class="text-sm font-medium text-gray-400">Staff</span></p>
                <div class="mt-6 pt-4 border-t border-gray-100">
                    <a href="#" class="text-blue-600 font-bold text-sm hover:underline">Assign tasks →</a>
                </div>
            </div>

            <div class="report-widget">
                <span class="text-4xl mb-3 block">📊</span>
                <h3 class="font-bold text-gray-400 text-sm uppercase tracking-wider">Total Revenue</h3>
                <p class="text-3xl font-black text-gray-900 mt-2">₱0.00</p>
                <div class="mt-6 pt-4 border-t border-gray-100">
                    <a href="#" class="text-blue-600 font-bold text-sm hover:underline">Revenue logs →</a>
                </div>
            </div>
        </div>

        <div class="mt-12">
            <h2 class="text-xl font-bold text-gray-800 mb-6">Partner Actions</h2>
            <div class="flex flex-wrap gap-4">
                <button class="bg-blue-600 text-white px-8 py-4 rounded-xl font-semibold hover:bg-blue-700 transition shadow-md">
                    Add New Vehicle
                </button>
                <button class="bg-slate-800 text-white px-8 py-4 rounded-xl font-semibold hover:bg-black transition shadow-md">
                    Register Driver
                </button>
                <button class="bg-white text-slate-700 px-8 py-4 rounded-xl font-semibold hover:bg-slate-50 transition shadow-sm border border-slate-200">
                    Optimize Route Stops
                </button>
            </div>
        </div>

        <div class="mt-14 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Route Planning & Pickup Locations</h2>
                <p class="text-gray-500 mt-1">Interactive map showing verified farms ready for harvest collection.</p>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div id="routing-map" class="w-full rounded-xl border-2 border-gray-200 relative z-10 shadow-inner" style="height: 550px;"></div>
        <p class="text-sm text-gray-500 mt-2">💡 <b>How to test:</b> Click anywhere on the map to set a Start point, then click again to set an End point.</p>
    </div>

    <div class="bg-slate-50 rounded-xl border border-gray-200 p-5 flex flex-col h-[550px]">
        <h3 class="text-lg font-bold text-gray-800 mb-2">📍 Route Pickups</h3>
<p class="text-xs text-gray-500 mb-4">Farms within 3km of the selected route will appear here.</p>

        <div id="pickup-queue" class="flex-1 overflow-y-auto space-y-3">
            <div class="text-center text-gray-400 mt-10 italic">
                Awaiting route generation...
            </div>
        </div>

        <button id="reset-map" class="w-full mt-4 bg-red-50 text-red-600 font-bold py-2 rounded-lg border border-red-200 hover:bg-red-100 transition hidden">
            Clear Route & Restart
        </button>
    </div>
</div>
        </div>

    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Initialize Map
        const map = L.map('routing-map').setView([6.1164, 125.1716], 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

        // 2. State Variables for Routing
        const farms = @json($farmers ?? []);
        let farmMarkers = []; // Keep track of markers to change their colors later
        let startMarker = null;
        let endMarker = null;
        let routePolyline = null;

        // 3. Custom Icons for visual feedback
        const defaultIcon = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png', iconSize: [25, 41], iconAnchor: [12, 41] });
        const highlightIcon = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png', iconSize: [25, 41], iconAnchor: [12, 41] });

        // Plot initial farms
        farms.forEach((farm, index) => {
            if(farm.farmer_profile && farm.farmer_profile.latitude) {
                const marker = L.marker([farm.farmer_profile.latitude, farm.farmer_profile.longitude], {icon: defaultIcon}).addTo(map);
                marker.bindPopup(`<b>${farm.name}</b><br>${farm.farmer_profile.farm_location}`);

                // Store marker reference and original data together
                farmMarkers.push({ marker: marker, data: farm });
            }
        });

        // 4. Map Click Logic (Defense Demo feature!)
        map.on('click', function(e) {
            if (!startMarker) {
                // First click: Set Start
                startMarker = L.marker(e.latlng).addTo(map).bindPopup("<b>Start:</b> Logistics Hub").openPopup();
            } else if (!endMarker) {
                // Second click: Set End & Generate Route
                endMarker = L.marker(e.latlng).addTo(map).bindPopup("<b>End:</b> Drop-off Point").openPopup();
                generateRoute(startMarker.getLatLng(), endMarker.getLatLng());
            }
        });

        // 5. Fetch Route from OSRM
        async function generateRoute(start, end) {
            // OSRM requires Coordinates in [Longitude, Latitude] format
            const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${start.lng},${start.lat};${end.lng},${end.lat}?overview=full&geometries=geojson`;

            try {
                const response = await fetch(osrmUrl);
                const data = await response.json();

                if (data.routes && data.routes.length > 0) {
                    const routeGeoJSON = data.routes[0].geometry;

                    // Draw the line on the map
                    routePolyline = L.geoJSON(routeGeoJSON, { style: { color: '#3b82f6', weight: 5 } }).addTo(map);

                    // Trigger Objective #2: Identify nearby farms
                    findFarmsAlongRoute(routeGeoJSON);

                    // Show the reset button
                    document.getElementById('reset-map').classList.remove('hidden');
                }
            } catch (error) {
                console.error("Routing Error:", error);
                alert("Failed to connect to routing engine.");
            }
        }

        // 6. Proximity Math (Objective #2)
        function findFarmsAlongRoute(routeGeoJSON) {
            const queueContainer = document.getElementById('pickup-queue');
            queueContainer.innerHTML = ''; // Clear previous

            // Convert the route into a Turf.js readable line
            const routeLine = turf.lineString(routeGeoJSON.coordinates);
            let farmsFound = 0;

            farmMarkers.forEach(item => {
                // Turf requires [lng, lat]
                const farmPt = turf.point([item.data.farmer_profile.longitude, item.data.farmer_profile.latitude]);

                // Calculate distance in kilometers
                const distance = turf.pointToLineDistance(farmPt, routeLine, { units: 'kilometers' });

                // If within 2km buffer, flag it!
                if (distance <= 2) {
                    farmsFound++;
                    item.marker.setIcon(highlightIcon); // Turn marker green

                    // Add to Sidebar
                    queueContainer.innerHTML += `
                        <div class="bg-white p-3 rounded shadow-sm border-l-4 border-green-500">
                            <strong class="text-green-700">${item.data.name}</strong>
                            <p class="text-xs text-gray-600 mt-1">📍 ${item.data.farmer_profile.farm_location}</p>
                            <p class="text-xs text-gray-500 mt-1">🚗 ${distance.toFixed(2)} km off-route</p>
                        </div>
                    `;
                } else {
                    item.marker.setIcon(defaultIcon); // Reset to blue if too far
                }
            });

            if (farmsFound === 0) {
                queueContainer.innerHTML = '<div class="text-center text-gray-400 mt-10">No farms detected within 3km of this route.</div>';
            }
        }

        // 7. Reset Map functionality
        document.getElementById('reset-map').addEventListener('click', function() {
            if (startMarker) map.removeLayer(startMarker);
            if (endMarker) map.removeLayer(endMarker);
            if (routePolyline) map.removeLayer(routePolyline);

            startMarker = null; endMarker = null;
            farmMarkers.forEach(item => item.marker.setIcon(defaultIcon));

            document.getElementById('pickup-queue').innerHTML = '<div class="text-center text-gray-400 mt-10 italic">Awaiting route generation...</div>';
            this.classList.add('hidden');
        });
    });
</script>
</x-layout>
