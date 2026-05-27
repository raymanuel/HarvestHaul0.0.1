<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="theme-color" content="#16a34a" />
    <title>HarvestHaul — Driver</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen">

    {{-- Top Bar --}}
    <header class="bg-green-600 text-white px-4 pt-safe pb-4 sticky top-0 z-10">
        <div class="flex items-center justify-between max-w-lg mx-auto">
            <div>
                <p class="text-xs text-green-200 font-medium uppercase tracking-wider">HarvestHaul</p>
                <h1 class="text-lg font-bold leading-tight">{{ Auth::user()->name }}</h1>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-green-100 text-xs border border-green-400 rounded-lg px-3 py-1.5 hover:bg-green-700 transition">
                    Logout
                </button>
            </form>
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 py-5">

        {{-- Summary Strip --}}
        <div class="grid grid-cols-2 gap-3 mb-6">
            <div class="bg-white rounded-2xl border border-gray-200 p-4">
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Active Jobs</p>
                <p class="text-3xl font-black text-green-600">{{ $jobs->count() }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 p-4">
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Completed</p>
                <p class="text-3xl font-black text-gray-700">{{ $completedJobs }}</p>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-xl px-4 py-3">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
                {{ session('error') }}
            </div>
        @endif

        {{-- Section Label --}}
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Your Assignments</p>

        {{-- Job Cards --}}
        @forelse($jobs as $job)
            <div class="bg-white rounded-2xl border border-gray-200 mb-4 overflow-hidden">

                {{-- Card Header --}}
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <div>
                        <p class="text-sm font-bold text-gray-800">Job #{{ $job->id }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ $job->farm_count }} {{ Str::plural('stop', $job->farm_count) }}
                            &middot; {{ number_format($job->total_kg, 1) }} kg
                        </p>
                    </div>
                    @php
                        $badge = match($job->status) {
                            'confirmed'   => ['bg-amber-100 text-amber-700',  'Ready'],
                            'in_progress' => ['bg-blue-100 text-blue-700',    'In Progress'],
                            default       => ['bg-gray-100 text-gray-500',    ucfirst($job->status)],
                        };
                    @endphp
                    <span class="text-xs font-semibold px-3 py-1 rounded-full {{ $badge[0] }}">
                        {{ $badge[1] }}
                    </span>
                </div>

                {{-- Truck Row --}}
                <div class="px-4 py-3 text-sm text-gray-600 border-b border-gray-100">
                    <span class="font-medium text-gray-700">Truck:</span>
                    {{ $job->truck->plate_number ?? '—' }}
                    @if($job->truck->vehicle_type ?? false)
                        &middot; {{ $job->truck->vehicle_type }}
                    @endif
                </div>

                {{-- Notes Preview --}}
                @if($job->notes)
                    <div class="px-4 py-2.5 text-xs text-amber-700 bg-amber-50 border-b border-amber-100 italic">
                        "{{ Str::limit($job->notes, 90) }}"
                    </div>
                @endif

                {{-- Action Buttons --}}
                <div class="flex gap-2 px-4 py-3">
                    <a href="{{ route('driver.jobs.show', $job) }}"
                       class="flex-1 text-center text-sm font-semibold text-white bg-green-600 active:bg-green-800 rounded-xl py-3 transition">
                        View Details
                    </a>
                    @if($job->status === 'confirmed')
                        <form method="POST" action="{{ route('driver.jobs.status', $job) }}" class="flex-1">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="w-full text-sm font-semibold text-white bg-blue-600 active:bg-blue-800 rounded-xl py-3 transition">
                                Start Job
                            </button>
                        </form>
                    @endif
                </div>

            </div>
        @empty
            <div class="text-center py-20 text-gray-400">
                <p class="text-4xl mb-3">🚛</p>
                <p class="text-base font-semibold text-gray-500">No active jobs yet.</p>
                <p class="text-sm mt-1">Your coordinator will assign a route when one is ready.</p>
            </div>
        @endforelse

    </main>

</body>
</html>
