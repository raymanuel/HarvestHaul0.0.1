<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="theme-color" content="#16a34a" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Job #{{ $job->id }} — HarvestHaul</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen">

    {{-- Top Bar --}}
    <header class="bg-green-600 text-white px-4 pt-safe pb-4 sticky top-0 z-10">
        <div class="flex items-center gap-3 max-w-lg mx-auto">
            <a href="{{ route('driver.dashboard') }}" class="text-green-100 hover:text-white transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <p class="text-xs text-green-200 font-medium uppercase tracking-wider">Job Details</p>
                <h1 class="text-lg font-bold leading-tight">Job #{{ $job->id }}</h1>
            </div>
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 py-5 space-y-4">

        {{-- Flash --}}
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-xl px-4 py-3">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
                {{ session('error') }}
            </div>
        @endif

        {{-- Job Summary Card --}}
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="flex items-start justify-between px-4 py-4 border-b border-gray-100">
                <div>
                    <p class="text-sm font-bold text-gray-800">
                        {{ $job->farm_count }} {{ Str::plural('stop', $job->farm_count) }}
                        &middot; {{ number_format($job->total_kg, 1) }} kg
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ number_format($job->load_percentage, 1) }}% truck load</p>
                </div>
                @php
                    $badge = match($job->status) {
                        'confirmed'   => ['bg-amber-100 text-amber-700',  'Ready'],
                        'in_progress' => ['bg-blue-100 text-blue-700',    'In Progress'],
                        'completed'   => ['bg-green-100 text-green-700',  'Completed'],
                        default       => ['bg-gray-100 text-gray-500',    ucfirst($job->status)],
                    };
                @endphp
                <span class="text-xs font-semibold px-3 py-1 rounded-full {{ $badge[0] }}">
                    {{ $badge[1] }}
                </span>
            </div>

            <div class="px-4 py-3 text-sm text-gray-600">
                <span class="font-medium text-gray-700">Truck:</span>
                {{ $job->truck->plate_number ?? '—' }}
                @if($job->truck->vehicle_type ?? false)
                    &middot; {{ $job->truck->vehicle_type }}
                @endif
            </div>
        </div>

        {{-- Coordinator Instructions --}}
        @if($job->notes)
            <div class="bg-amber-50 border border-amber-200 rounded-2xl px-4 py-4">
                <p class="text-xs font-bold text-amber-600 uppercase tracking-wider mb-1.5">Coordinator Instructions</p>
                <p class="text-sm text-amber-800 leading-relaxed">{{ $job->notes }}</p>
            </div>
        @endif

        {{-- Status Action Button --}}
        @if(in_array($job->status, ['confirmed', 'in_progress']))
            <form method="POST" action="{{ route('driver.jobs.status', $job) }}">
                @csrf @method('PATCH')
                <button type="submit" @class([
                    'w-full py-4 rounded-2xl text-sm font-bold text-white transition active:scale-95',
                    'bg-blue-600 active:bg-blue-800'   => $job->status === 'confirmed',
                    'bg-green-600 active:bg-green-800' => $job->status === 'in_progress',
                ])>
                    {{ $job->status === 'confirmed' ? '🚛 Start Job — Mark In Transit' : '✅ Complete Job — Mark Delivered' }}
                </button>
            </form>
        @endif

        {{-- Pickup Sequence --}}
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Pickup Sequence</p>

            @foreach($job->harvests as $index => $harvest)
                <div class="bg-white rounded-2xl border border-gray-200 mb-3 overflow-hidden">

                    {{-- Stop Header --}}
                    <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100">
                        <div class="w-8 h-8 rounded-full bg-green-600 text-white text-sm font-bold flex items-center justify-center flex-shrink-0">
                            {{ $index + 1 }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-gray-800 truncate">
                                {{ $harvest->farmer->name ?? 'Unknown Farmer' }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ $harvest->farmer->farmerProfile->barangay ?? 'No barangay on record' }}
                            </p>
                        </div>
                    </div>

                    {{-- Stop Details --}}
                    <div class="px-4 py-3 space-y-1.5 text-sm text-gray-600">
                        <div>
                            <span class="font-medium text-gray-700">Crop:</span>
                            {{ $harvest->crop->name ?? $harvest->crop_type ?? '—' }}
                            @if($harvest->variety)
                                &middot; {{ $harvest->variety }}
                            @endif
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Quantity:</span>
                            {{ number_format($harvest->quantity_kg, 1) }} kg
                        </div>
                        @if($harvest->latitude && $harvest->longitude)
                            <div>
                                <span class="font-medium text-gray-700">Coordinates:</span>
                                <span class="font-mono text-xs text-gray-400">
                                    {{ $harvest->latitude }}, {{ $harvest->longitude }}
                                </span>
                            </div>
                        @endif
                        @if($harvest->destination_label !== '—')
                            <div>
                                <span class="font-medium text-gray-700">Drop-off:</span>
                                {{ $harvest->destination_label }}
                            </div>
                        @endif
                    </div>

                </div>
            @endforeach
        </div>

    </main>

    {{-- HTML5 Geolocation Tracking Engine --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const jobStatus = '{{ $job->status }}';
            const jobId = {{ $job->id }};
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            if (jobStatus === 'in_progress') {
                // Execute immediately on load, then loop every 15 seconds
                postLocation();
                setInterval(postLocation, 15000);
            }

            function postLocation() {
                if (!navigator.geolocation) {
                    console.error("Geolocation is not supported by this browser.");
                    return;
                }

                navigator.geolocation.getCurrentPosition((position) => {
                    const payload = {
                        pooling_job_id: jobId,
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        posted_at: new Date().toISOString()
                    };

                    fetch('{{ route("driver.tracking.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(res => res.json())
                    .then(data => console.log('Location Synced:', data))
                    .catch(err => console.error('Sync Failed:', err));

                }, (error) => {
                    console.warn('GPS Error:', error.message);
                }, {
                    enableHighAccuracy: true,
                    maximumAge: 10000, // Do not accept cached locations older than 10s
                    timeout: 5000      // Drop connection if GPS lock takes too long
                });
            }
        });
    </script>
</body>
</html>
