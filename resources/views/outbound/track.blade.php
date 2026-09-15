<!DOCTYPE html>
<html lang="en" class="overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Live tracking for your HarvestHaul delivery. Follow your shipment in real time and confirm receipt on arrival.">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <meta name="theme-color" content="#16283C">
    <title>Track Your Delivery — HarvestHaul</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #F5F6F2 0%, #EFEADB 50%, #E7E1CF 100%);
        }
    </style>
</head>
<body class="antialiased text-slate-800">

    @php
        $job = $order->poolingJob;
        $statusLabel = match ($order->status) {
            'confirmed'             => 'Confirmed',
            'in_transit'            => 'In Transit',
            'awaiting_confirmation' => 'Awaiting Confirmation',
            'completed'             => 'Completed',
            default                 => ucfirst($order->status),
        };
        $statusColors = match ($order->status) {
            'completed'             => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'awaiting_confirmation' => 'bg-amber-100 text-amber-800 border-amber-200',
            'in_transit'            => 'bg-orange-100 text-orange-800 border-orange-200',
            default                 => 'bg-sky-100 text-sky-800 border-sky-200',
        };
        $jobCoords = null;
        if ($job) {
            $jobCoords = [
                'start' => [(float) $job->start_latitude, (float) $job->start_longitude],
                'end'   => [(float) $job->end_latitude, (float) $job->end_longitude],
            ];
        }
        $customerCoords = [(float) ($order->customerCard->latitude ?? 0), (float) ($order->customerCard->longitude ?? 0)];
    @endphp

    <main class="w-full max-w-5xl mx-auto px-4 py-8">

        <header class="mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-500">HarvestHaul · Delivery</p>
                    <h1 class="text-2xl font-bold text-[#16283C] tracking-tight heading-font mt-1">
                        {{ $order->customerCard->name }}
                    </h1>
                    @if ($job?->driver)
                        <p class="text-xs text-slate-600 mt-1">
                            Truck {{ $job->truck->plate_number ?? '—' }} · Driver {{ $job->driver->name }}
                        </p>
                    @endif
                </div>
                <span class="text-xs font-extrabold uppercase tracking-wider px-3 py-1.5 rounded-md border self-start {{ $statusColors }}">
                    {{ $statusLabel }}
                </span>
            </div>
        </header>

        @if (session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        @if ($order->status === 'completed')
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 flex items-start gap-3">
                <span class="text-emerald-600 text-lg leading-none mt-0.5">✓</span>
                <div>
                    <p class="text-sm font-bold text-emerald-800 heading-font">Order complete</p>
                    <p class="text-xs text-emerald-700 mt-1">Your delivery has been confirmed. The truck is released for its next run.</p>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">

            <div class="md:col-span-5 space-y-4">
                <div class="bg-white border border-slate-200/70 rounded-2xl overflow-hidden shadow-sm">
                    <div class="px-5 py-3.5 border-b border-slate-100">
                        <h2 class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Delivery Items</h2>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($order->orderLines as $line)
                            <div class="px-5 py-3 flex items-center justify-between gap-3 text-sm">
                                <span class="font-semibold text-slate-700">{{ $line->crop_type }}</span>
                                <span class="text-slate-600">
                                    {{ number_format((float) $line->quantity_kg) }} kg
                                    <span class="text-slate-400">× ₱{{ number_format((float) $line->rate_per_kg, 2) }}</span>
                                </span>
                            </div>
                        @empty
                            <div class="px-5 py-4 text-xs italic text-slate-400">No items recorded in this delivery.</div>
                        @endforelse
                    </div>
                    <div class="px-5 py-3.5 border-t border-slate-100 flex items-center justify-between text-sm">
                        <span class="font-bold text-slate-700 heading-font">Total</span>
                        <span class="font-bold text-slate-800 font-mono">{{ number_format((float) $order->total_amount, 2) }} PHP</span>
                    </div>
                </div>

                @if ($order->status === 'awaiting_confirmation')
                    <div class="bg-white border border-amber-200 rounded-2xl p-5 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-800 heading-font">Arrived?</h3>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Confirm that you received the delivery to complete this order.
                        </p>
                        <form method="POST" action="{{ route('outbound.track.confirm', $order->tracking_token) }}" class="mt-4">
                            @csrf
                            <button type="submit" class="w-full rounded-xl bg-[#16283C] text-white text-sm font-bold py-3 px-4 hover:bg-[#0E1620] transition">
                                Confirm Received
                            </button>
                        </form>
                    </div>
                @elseif ($order->status !== 'completed')
                    <div class="bg-white border border-slate-200/70 rounded-2xl px-5 py-4 shadow-sm">
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Once the truck arrives, a <span class="font-semibold text-slate-700">Confirm Received</span> button will appear on this page to complete your order.
                        </p>
                    </div>
                @endif
            </div>

            <div class="md:col-span-7 space-y-4">
                <div class="bg-white border border-slate-200/70 rounded-2xl overflow-hidden shadow-sm flex flex-col">
                    <div id="liveMap" class="w-full h-[420px] z-0"></div>
                    <div class="bg-slate-50 px-5 py-3.5 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs">
                        <div class="flex items-center gap-4 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                            <span class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#16283C]"></span> Pickup
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Delivery
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#D7BC7A]"></span> Truck GPS
                            </span>
                        </div>
                        <div class="flex items-center gap-4 text-[10px]">
                            <span class="text-slate-500 font-bold">Speed: <span id="speed-display" class="text-slate-600 font-mono">—</span></span>
                            <span class="text-slate-500 font-bold">Last signal: <span id="last-signal" class="text-slate-600 font-mono">—</span></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const mapEl = document.getElementById('liveMap');
            if (!mapEl) return;

            const GENSAN_CENTER = [6.1164, 125.1716];
            const orderStatus = @json($order->status);
            const jobData = @json($jobCoords);
            const customerCoords = @json($customerCoords);
            const pingUrl = @json(route('outbound.track.ping', $order->tracking_token));

            const map = L.map('liveMap').setView(GENSAN_CENTER, 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: ' OpenStreetMap contributors' }).addTo(map);

            const pickupIcon = L.divIcon({
                html: '<div style="width: 20px; height: 20px; border-radius: 50%; background: #16283C; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3)"></div>',
                className: '', iconSize: [20, 20], iconAnchor: [10, 10]
            });
            const deliveryIcon = L.divIcon({
                html: '<div style="width: 20px; height: 20px; border-radius: 50%; background: #EF4444; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3)"></div>',
                className: '', iconSize: [20, 20], iconAnchor: [10, 10]
            });
            const truckIcon = L.divIcon({
                html: '<div style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; background: #D7BC7A; border: 3px solid white; box-shadow: 0 4px 10px rgba(13, 148, 136, 0.45);"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg></div>',
                className: '', iconSize: [32, 32], iconAnchor: [16, 16]
            });

            const markerPoints = [];
            const bounds = L.latLngBounds();
            if (jobData && jobData.start) {
                L.marker(jobData.start, { icon: pickupIcon }).bindPopup('<b>Pickup (Coop)</b>').addTo(map);
                markerPoints.push(jobData.start);
                bounds.extend(jobData.start);
            }
            if (customerCoords[0] && customerCoords[1]) {
                L.marker(customerCoords, { icon: deliveryIcon }).bindPopup('<b>Delivery</b>').addTo(map);
                markerPoints.push(customerCoords);
                bounds.extend(customerCoords);
            }
            if (bounds.isValid()) {
                map.fitBounds(bounds, { padding: [50, 50] });
            }

            let truckMarker = null;

            function updateTruckMarker(lat, lng) {
                if (truckMarker) {
                    truckMarker.setLatLng([lat, lng]);
                } else {
                    truckMarker = L.marker([lat, lng], { icon: truckIcon })
                        .bindPopup('<b>Live Truck</b><br>Coordinates: ' + lat + ', ' + lng)
                        .addTo(map);
                }
                const currentBounds = map.getBounds();
                if (!currentBounds.contains([lat, lng])) {
                    const newBounds = L.latLngBounds(markerPoints);
                    newBounds.extend([lat, lng]);
                    map.fitBounds(newBounds, { padding: [50, 50] });
                }
            }

            function pollPing() {
                fetch(pingUrl)
                    .then(res => res.json())
                    .then(res => {
                        if (res.status === 'success' && res.data) {
                            updateTruckMarker(res.data.latitude, res.data.longitude);
                            const speedEl = document.getElementById('speed-display');
                            if (speedEl) {
                                speedEl.textContent = res.data.speed_kmh > 0 ? res.data.speed_kmh.toFixed(1) + ' km/h' : 'Stopped';
                            }
                            const signalEl = document.getElementById('last-signal');
                            if (signalEl && res.data.posted_at) {
                                signalEl.textContent = new Date(res.data.posted_at).toLocaleTimeString();
                            }
                        }
                    })
                    .catch(err => console.error('Error polling GPS', err));
            }

            pollPing();
            if (orderStatus !== 'completed') {
                setInterval(pollPing, 10000);
            }
        });
    </script>

</body>
</html>