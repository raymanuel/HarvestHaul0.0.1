<x-layout title="Manage Fleet Vehicles">

    <div class="w-full max-w-4xl mx-auto pb-12">

        <header class="pt-8 mb-6 border-b border-slate-200/80 dark:border-slate-700/80 pb-5">
            <a href="{{ route('dashboard') }}" class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 mb-4 inline-block font-semibold transition">
                ← Back to Dashboard
            </a>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-sky-750 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/20 px-3 py-1.5 rounded-lg border border-sky-500/10 dark:border-sky-500/20 inline-block mb-2">Fleet Management</span>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">Fleet Vehicles</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium">Add, assign, and oversee vehicle configurations within your transport network.</p>
                </div>
                <div>
                    <a href="{{ route('logistics.vehicles.create') }}" 
                        class="inline-flex items-center gap-2 bg-gradient-to-tr from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white text-sm font-bold px-5 py-3 rounded-xl shadow-md shadow-emerald-600/15 dark:shadow-emerald-900/30 hover:shadow-lg transition duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Fleet Vehicle
                    </a>
                </div>
            </div>
        </header>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-6 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 rounded-xl px-5 py-4 text-sm font-medium flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- Vehicles List --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6">
            <h2 class="text-base font-bold text-slate-800 dark:text-slate-200 mb-5 heading-font">Registered Vehicles</h2>

            @if($vehicles->isEmpty())
                <div class="bg-slate-50 dark:bg-slate-900/40 border border-dashed border-slate-300 dark:border-slate-700/80 rounded-xl p-12 text-center">
                    <p class="text-4xl mb-4">🚛</p>
                    <p class="text-slate-800 dark:text-slate-200 font-bold text-base mb-1 heading-font">No Vehicles Registered</p>
                    <p class="text-slate-400 dark:text-slate-500 font-medium text-xs max-w-sm mx-auto">
                        Register a new truck, wing van, or utility vehicle to calculate optimized multi-party cargo routes.
                    </p>
                    <a href="{{ route('logistics.vehicles.create') }}" class="mt-5 inline-block text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline transition">
                        Add first vehicle <span>→</span>
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-700/60">
                                <th class="pb-3 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Vehicle Details</th>
                                <th class="pb-3 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Plate Number</th>
                                <th class="pb-3 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Capacity</th>
                                <th class="pb-3 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Assigned Driver</th>
                                <th class="pb-3 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            @foreach($vehicles as $vehicle)
                                @php
                                    $statusColor = match($vehicle->status) {
                                        'available' => 'text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 border-emerald-500/10 dark:border-emerald-500/20',
                                        'in_transit' => 'text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/20 border-blue-500/10 dark:border-blue-500/20',
                                        'maintenance' => 'text-red-750 dark:text-red-400 bg-red-50 dark:bg-red-950/20 border-red-500/10 dark:border-red-500/20',
                                        default => 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700',
                                    };
                                @endphp
                                <tr>
                                    <td class="py-4 pr-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-950/20 border border-sky-100/50 dark:border-sky-800/30 flex items-center justify-center text-sky-600 dark:text-sky-400 font-extrabold uppercase text-sm select-none">
                                                🚛
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-800 dark:text-slate-200 text-sm mb-0.5">{{ $vehicle->truck_name }}</p>
                                                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-900/60 px-2 py-0.5 rounded border border-slate-200/50 dark:border-slate-700 inline-block font-mono">
                                                    {{ $vehicle->vehicle_type ?: 'Standard Truck' }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-3 font-mono text-sm font-semibold text-slate-700 dark:text-slate-300">
                                        {{ $vehicle->plate_number }}
                                    </td>
                                    <td class="py-4 px-3">
                                        <div class="text-xs text-slate-700 dark:text-slate-300 font-medium">
                                            <span class="font-bold text-sm">{{ number_format($vehicle->capacity_kg) }}</span> kg
                                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Max Weight Capacity</p>
                                        </div>
                                    </td>
                                    <td class="py-4 px-3">
                                        @if($vehicle->driver)
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-lg bg-violet-50 dark:bg-violet-950/30 border border-violet-100/30 dark:border-violet-800/30 flex items-center justify-center text-violet-600 dark:text-violet-400 font-bold uppercase text-[10px] select-none shrink-0">
                                                    {{ substr($vehicle->driver->name, 0, 2) }}
                                                </div>
                                                <span class="text-xs font-bold text-slate-800 dark:text-slate-250">{{ $vehicle->driver->name }}</span>
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-405 dark:text-slate-500 italic">No driver assigned</span>
                                        @endif
                                    </td>
                                    <td class="py-4 pl-3">
                                        <span class="text-[10px] font-bold uppercase tracking-wider {{ $statusColor }} border px-2.5 py-1 rounded-lg">
                                            {{ str_replace('_', ' ', $vehicle->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>

</x-layout>
