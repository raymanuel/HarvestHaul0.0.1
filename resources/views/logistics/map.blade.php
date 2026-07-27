<x-layout>
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex justify-between items-end">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">Logistics Routing</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">General Santos City — K-Means Cluster Map</p>
            </div>
            <button class="bg-slate-900 dark:bg-slate-700 text-white px-4 py-2 text-sm font-medium rounded hover:bg-slate-800 dark:hover:bg-slate-600 transition-colors">
                Run Clustering
            </button>
        </div>

        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded shadow-sm overflow-hidden">
            <div id="routingMap" class="w-full h-[400px] sm:h-[650px] z-10"></div>
        </div>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // General Santos City exact coordinates
            const genSanLat = 6.1135;
            const genSanLng = 125.1716;

            // Initialize the map, zoom controls disabled initially for a cleaner look
            const map = L.map('routingMap', {
                zoomControl: false
            }).setView([genSanLat, genSanLng], 12);

            // Re-add zoom control to the bottom right (keeps top-left minimalist)
            L.control.zoom({ position: 'bottomright' }).addTo(map);

            // CartoDB Positron tiles for a cold, high-contrast, minimalist aesthetic
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
                subdomains: 'abcd',
                maxZoom: 20
            }).addTo(map);

            // TODO: Inject backend K-Means JSON data here to plot markers
        });
    </script>
</x-layout>
