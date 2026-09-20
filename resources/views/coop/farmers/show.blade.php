<x-layout :title="$user->name . ' — Farmer Details'">
    <x-page-header variant="back-link" :title="$user->name" :back-href="route('coop.farmers.index')" back-label="← Back to Farmers">
        <a href="{{ route('coop.farmers.edit', $user) }}" class="px-4 py-2 rounded-xl text-xs font-bold border-2 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:border-brand-700/40 transition">Edit Farmer</a>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card>
                <x-section-label title="Farmer Haul History" width="w-24" />
                @if($haulRequests->isEmpty())
                    <x-empty-state type="haul" title="No pickup requests yet" description="This farmer hasn't submitted a haul request." />
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                    <th class="px-4 py-3">Crop</th>
                                    <th class="px-4 py-3">Est. Weight</th>
                                    <th class="px-4 py-3">Pickup Date</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach($haulRequests as $request)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-800 dark:text-slate-200">{{ $request->crop?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($request->estimated_weight_kg, 2) }} kg</td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $request->preferred_pickup_date?->format('M d, Y') ?? '—' }}</td>
                                        <td class="px-4 py-3"><x-badge :status="$request->status" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>

            <x-card>
                <x-section-label title="Farmer Procurement History" width="w-24" />
                @if($receivingRecords->isEmpty())
                    <x-empty-state type="procurement" title="No confirmed procurement yet" description="Confirmed receiving records for this farmer will appear here." />
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                    <th class="px-4 py-3">Crop</th>
                                    <th class="px-4 py-3">Grade</th>
                                    <th class="px-4 py-3">Actual Weight</th>
                                    <th class="px-4 py-3">Price/kg</th>
                                    <th class="px-4 py-3">Total</th>
                                    <th class="px-4 py-3">Confirmed</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach($receivingRecords as $record)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-800 dark:text-slate-200">{{ $record->crop?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $record->cropGrade?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($record->actual_weight_kg, 2) }} kg</td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">₱{{ number_format($record->buying_price_per_kg, 2) }}</td>
                                        <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">₱{{ number_format($record->total_amount, 2) }}</td>
                                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $record->confirmed_at?->format('M d, Y') ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card>
                <x-section-label title="Farmer Details" width="w-16" />
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Email</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $user->email }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Phone</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $user->phone ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Farm / Pickup Location</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $farmer->farm_location ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Coordinates</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $farmer->latitude && $farmer->longitude ? $farmer->latitude.', '.$farmer->longitude : '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Member Since</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $farmer->membership_decided_at?->format('M d, Y') ?? '—' }}</dd></div>
                </dl>
            </x-card>
        </div>
    </div>
</x-layout>
