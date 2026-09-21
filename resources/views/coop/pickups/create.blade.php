<x-layout title="Plan a Pickup Trip — Cooperative">
    <x-page-header variant="back-link" title="Plan a Pickup Trip" :back-href="route('coop.pickups.index')" back-label="← Back to Trips" />

    <form method="GET" action="{{ route('coop.pickups.create') }}" class="mb-6 max-w-xs">
        <x-input name="date" label="Pickup Date" type="date" :value="$date" onchange="this.form.submit()" />
    </form>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-card>
                <x-section-label title="Approved Requests for {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}" width="w-24" />

                @if($plan['requests']->isEmpty())
                    <x-empty-state type="first-use" title="No approved requests for this date" description="Approve pending pickup requests for this date first, or pick another date." />
                @else
                    <form method="POST" action="{{ route('coop.pickups.store') }}">
                        @csrf
                        <input type="hidden" name="date" value="{{ $date }}">

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        <th class="px-4 py-3">Select</th>
                                        <th class="px-4 py-3">Farmer</th>
                                        <th class="px-4 py-3">Est. Weight</th>
                                        <th class="px-4 py-3">Window</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @foreach($plan['requests'] as $i => $req)
                                        <tr>
                                            <td class="px-4 py-3">
                                                <input type="checkbox" name="requests[]" value="{{ $req->id }}" checked>
                                                <input type="hidden" name="sequences[{{ $i + 1 }}]" value="{{ $req->id }}">
                                            </td>
                                            <td class="px-4 py-3 text-slate-800 dark:text-slate-200">{{ $req->farmer?->name ?? 'Farmer' }}</td>
                                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format((float) $req->estimated_weight_kg, 2) }} kg</td>
                                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                                {{ $req->pickup_window_start?->format('g:i A') }} – {{ $req->pickup_window_end?->format('g:i A') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-5">
                            <x-select name="truck_id" label="Truck" :required="true" :placeholder="'Select a truck'"
                                :options="$plan['trucks']->mapWithKeys(fn ($t) => [$t->id => $t->truck_name.' ('.$t->plate_number.', '.number_format((float) $t->capacity_kg).' kg)'])->all()" />
                            <x-select name="delivery_personnel_id" label="Delivery Personnel" :required="true" :placeholder="'Select a driver'"
                                :options="$drivers->mapWithKeys(fn ($d) => [$d->id => $d->name])->all()" />
                        </div>

                        <div class="flex justify-end gap-3 mt-6">
                            <x-button variant="primary" size="sm">Create Pickup Trip</x-button>
                        </div>
                    </form>
                @endif
            </x-card>
        </div>

        <div>
            <x-card>
                <x-section-label title="Plan Summary" width="w-16" />
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Routing Source</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ ucfirst($plan['matrix']) }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Advisory Distance</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $plan['distance_km'] }} km</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Advisory Travel Time</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $plan['proposed']['travel_time'] ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Time Windows</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ ($plan['proposed']['windows_ok'] ?? true) ? 'All satisfied' : 'Conflict detected' }}</dd></div>
                    @foreach($plan['capacity'] as $cap)
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $cap['truck'] }}</dt>
                            <dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $cap['load_kg'] }} / {{ $cap['cap_kg'] }} kg ({{ $cap['pct'] }}%)</dd>
                        </div>
                    @endforeach
                </dl>
                <p class="text-xs text-slate-400 mt-4">Distance and travel time are advisory only — they're never billed to farmers.</p>
            </x-card>
        </div>
    </div>
</x-layout>
