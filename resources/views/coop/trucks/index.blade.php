<x-layout title="Truck Fleet — Cooperative">
    <x-page-header title="Truck Fleet" :showDate="true" />

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-10">
        <x-stat-card
            badge="Fleet"
            title="Trucks Registered"
            :value="$totals['count']"
            unit="vehicles"
        />
        <x-stat-card
            badge="Capacity"
            title="Combined Capacity"
            :value="$totals['capacity']"
            unit="kg"
        />
        <x-stat-card
            badge="Status"
            title="Available Now"
            :value="$totals['available']"
            unit="ready"
        />
    </div>

    @if($trucks->isEmpty())
        <x-card>
            <x-section-label title="Your Fleet" />
            <x-empty-state
                type="fleet"
                title="No trucks yet"
                description="Register your cooperative's trucks so pickup consolidation knows the fleet's real hauling capacity."
            />
        </x-card>

        <x-card class="mt-8">
            <x-section-label title="Add Your First Truck" />
            @include('coop.trucks._form', ['truck' => null, 'route' => route('coop.trucks.store')])
        </x-card>
    @else
        <x-card>
            <x-section-label title="Your Fleet" />

            @include('coop.trucks._form', ['truck' => null, 'route' => route('coop.trucks.store')])

            <div class="overflow-x-auto mt-6">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Truck</th>
                            <th class="px-4 py-3 hidden md:table-cell">Type</th>
                            <th class="px-4 py-3 hidden lg:table-cell">Capacity</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($trucks as $truck)
                            <tr class="align-top">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900 dark:text-white">{{ $truck->truck_name }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $truck->plate_number }}</div>
                                    <div class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ $truck->driver?->name ?? 'No driver assigned' }}</div>
                                </td>
                                <td class="px-4 py-3 hidden md:table-cell text-slate-600 dark:text-slate-300">{{ $truck->vehicle_type }}</td>
                                <td class="px-4 py-3 hidden lg:table-cell text-slate-600 dark:text-slate-300">{{ number_format($truck->capacity_kg, 0) }} kg</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $truck->status === 'available' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : ($truck->status === 'in_use' ? 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300' : ($truck->status === 'inactive' ? 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300')) }}">
                                        {{ str_replace('_', ' ', ucfirst($truck->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2 flex-wrap">
                                        <form method="POST" action="{{ route('coop.trucks.status', [$truck, 'available']) }}">
                                            @csrf
                                            <button class="px-3 py-1.5 rounded-lg text-xs font-bold text-emerald-700 dark:text-emerald-300 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition">Available</button>
                                        </form>
                                        <form method="POST" action="{{ route('coop.trucks.status', [$truck, 'in_use']) }}">
                                            @csrf
                                            <button class="px-3 py-1.5 rounded-lg text-xs font-bold text-sky-700 dark:text-sky-300 hover:bg-sky-50 dark:hover:bg-sky-500/10 transition">In Use</button>
                                        </form>
                                        <form method="POST" action="{{ route('coop.trucks.status', [$truck, 'maintenance']) }}">
                                            @csrf
                                            <button class="px-3 py-1.5 rounded-lg text-xs font-bold text-amber-700 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-500/10 transition">Maintenance</button>
                                        </form>
                                        <form method="POST" action="{{ route('coop.trucks.status', [$truck, 'inactive']) }}">
                                            @csrf
                                            <button class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition">Retire</button>
                                        </form>

                                        <x-modal triggerLabel="Edit">
                                            @include('coop.trucks._form', ['truck' => $truck, 'route' => route('coop.trucks.update', $truck)])
                                        </x-modal>

                                        <form method="POST" action="{{ route('coop.trucks.destroy', $truck) }}" onsubmit="return confirm('Remove this truck from the fleet?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="px-3 py-1.5 rounded-lg text-xs font-bold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition">Remove</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
</x-layout>
