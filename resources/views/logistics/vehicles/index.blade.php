<x-layout title="Vehicles">

    <div class="w-full max-w-4xl mx-auto pb-12">

        <header class="pt-8 mb-6 border-b border-slate-200/80 dark:border-slate-700/80 pb-5">
            <a href="{{ route('dashboard') }}" class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 mb-4 inline-block font-semibold transition">
                ← Back to Dashboard
            </a>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-[#0E1620] dark:text-[#E9EEF4] bg-[#0E1620]/10 dark:bg-[#0E1620]/10 px-3 py-1.5 rounded-md border border-[#0E1620]/10 dark:border-[#0E1620]/20 inline-block mb-2">Transport Management</span>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">Vehicles</h1>
                </div>
                <div>
                    <x-button tag="a" :href="route('logistics.vehicles.create')" size="lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Vehicle
                    </x-button>
                </div>
            </div>
        </header>

        @php
            $fleetSetupTabs = [
                ['label' => 'Drivers', 'url' => route('logistics.drivers.index'), 'active' => false],
                ['label' => 'Vehicles', 'url' => route('logistics.vehicles.index'), 'active' => true],
            ];
        @endphp
        <x-nav-tabs :tabs="$fleetSetupTabs" />

        {{-- Flash Messages --}}
        <x-flash-success />

        {{-- Vehicles List --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6">
            <h2 class="text-base font-bold text-slate-800 dark:text-slate-200 mb-5 heading-font">Registered Vehicles</h2>

            @if($vehicles->isEmpty())
                <div class="bg-slate-50 dark:bg-slate-900/40 border border-dashed border-slate-300 dark:border-slate-700/80 rounded-xl p-12 text-center">
                    <p class="text-4xl mb-4 font-bold text-slate-300 dark:text-slate-600">—</p>
                    <p class="text-slate-800 dark:text-slate-200 font-bold text-base mb-1 heading-font">No Vehicles Registered</p>
                    <p class="text-slate-500 dark:text-slate-400 font-medium text-xs max-w-sm mx-auto">
                        Register a new truck, wing van, or utility vehicle to calculate optimized multi-party cargo routes.
                    </p>
                    <a href="{{ route('logistics.vehicles.create') }}" class="mt-5 inline-block text-xs font-bold text-[#16283C] dark:text-[#D7BC7A] hover:underline transition">
                        Add first vehicle <span>→</span>
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-700/60">
                                <th class="pb-3 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Vehicle Details</th>
                                <th class="pb-3 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Plate Number</th>
                                <th class="pb-3 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Capacity</th>
                                <th class="pb-3 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Assigned Driver</th>
                                <th class="pb-3 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            @foreach($vehicles as $vehicle)
                                @php
                                    $statusColor = match($vehicle->status) {
                                        'available' => 'text-[#16283C] dark:text-[#D7BC7A] bg-[#16283C]/10 dark:bg-[#16283C]/10 border-[#16283C]/10 dark:border-[#16283C]/20',
                                        'in_transit' => 'text-[var(--color-info-text)] bg-[var(--color-info-bg)] border-[var(--color-info-border)]',
                                        'maintenance' => 'text-[var(--color-error-text)] bg-[var(--color-error-bg)] border-[var(--color-error-border)]',
                                        default => 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700',
                                    };
                                @endphp
                                <tr>
                                    <td class="py-4 pr-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-[#0E1620]/10 dark:bg-[#0E1620]/10 border border-[#0E1620]/15 dark:border-[#0E1620]/15 flex items-center justify-center text-[#0E1620] dark:text-[#E9EEF4] font-extrabold uppercase text-sm select-none">
                                                 {{ substr($vehicle->truck_name, 0, 1) }}
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-800 dark:text-slate-200 text-sm mb-0.5">{{ $vehicle->truck_name }}</p>
                                                <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/60 px-2 py-0.5 rounded border border-slate-200/50 dark:border-slate-700 inline-block font-mono">
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
                                            @if($vehicle->capacity_volume_cubic_m)
                                                <span class="text-slate-400">&middot;</span>
                                                <span class="font-bold text-sm">{{ number_format($vehicle->capacity_volume_cubic_m, 1) }}</span> m³
                                            @endif
                                            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Max Weight Capacity</p>
                                        </div>
                                    </td>
                                    <td class="py-4 px-3">
                                        @if($vehicle->driver)
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-md bg-harvest/10 dark:bg-harvest/20 border border-harvest/20 dark:border-harvest/20 flex items-center justify-center text-harvest-dark dark:text-harvest-light font-bold uppercase text-[10px] select-none shrink-0">
                                                    {{ substr($vehicle->driver->name, 0, 2) }}
                                                </div>
                                                <span class="text-xs font-bold text-slate-800 dark:text-slate-250">{{ $vehicle->driver->name }}</span>
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-500 dark:text-slate-400 italic">No driver assigned</span>
                                        @endif
                                    </td>
                                    <td class="py-4 pl-3">
                                        <span class="text-[10px] font-bold uppercase tracking-wider {{ $statusColor }} border px-2.5 py-1 rounded-md">
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
