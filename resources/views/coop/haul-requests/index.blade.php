<x-layout title="Haul Requests — Cooperative">
    <x-page-header title="Pickup Requests" :showDate="true">
    </x-page-header>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
        <x-stat-card badge="Waiting" title="Pending Requests" :value="$counts['pending']" unit="to review" />
        <x-stat-card badge="Scheduled" title="Scheduled Pickups" :value="$counts['scheduled']" unit="in trips" />
        <x-stat-card badge="Done" title="Completed" :value="$counts['completed']" unit="pickups" />
    </div>

    @if($upcomingPickup)
        <x-status-banner type="info">
            Next scheduled pickup date in your cooperative: <strong>{{ \Carbon\Carbon::parse($upcomingPickup)->format('M d, Y') }}</strong>.
        </x-status-banner>
    @endif

    <x-card>
        <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
            <x-section-label title="Requests" />
            <div class="flex items-center gap-2 flex-wrap">
                @php
                    $tabs = [
                        '' => 'All',
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'scheduled' => 'Scheduled',
                        'completed' => 'Completed',
                        'rejected' => 'Declined',
                        'cancelled' => 'Cancelled',
                    ];
                @endphp
                @foreach($tabs as $value => $label)
                    <a href="{{ route('coop.haul-requests.index', $value ? ['status' => $value] : []) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-bold transition
                              {{ ($status ?? '') === $value ? 'bg-brand-700 text-white dark:bg-[#D7BC7A] dark:text-[#17202B]' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        @if($requests->isEmpty())
            <x-empty-state type="first-use" title="No requests here"
                description="Pickup requests from your farmers show up here. New requests appear as pending for you to schedule." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm min-w-[720px]">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Farmer</th>
                            <th class="px-4 py-3">Crop</th>
                            <th class="px-4 py-3">Load</th>
                            <th class="px-4 py-3 hidden lg:table-cell">Pickup</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Review</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($requests as $request)
                            <tr class="align-top">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900 dark:text-white">{{ $request->farmer?->name ?? 'Farmer' }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $request->farmer?->farmerProfile?->farm_location ?? 'No farm location' }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                    {{ $request->crop?->name ?? 'Crop' }}@if($request->cropVariety) · {{ $request->cropVariety->name }}@endif
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                    @if($request->estimated_sacks){{ $request->estimated_sacks }} sacks · @endif{{ number_format((float) $request->estimated_weight_kg, 2) }} kg
                                </td>
                                <td class="px-4 py-3 hidden lg:table-cell text-slate-600 dark:text-slate-300">
                                    {{ $request->preferred_pickup_date?->format('M d') }}
                                    @if($request->pickup_window_start)
                                        {{ $request->pickup_window_start->format('g:i A') }}–{{ $request->pickup_window_end->format('g:i A') }}
                                    @endif
                                </td>
                                <td class="px-4 py-3"><x-badge :status="$request->status" dot /></td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('coop.haul-requests.show', $request) }}"
                                       class="text-xs font-bold text-brand-700 dark:text-gold-light hover:underline">Review →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-layout>