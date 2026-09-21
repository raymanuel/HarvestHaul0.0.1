<x-layout title="Facility Receiving — HarvestHaul">
    <x-page-header title="Facility Receiving" :showDate="true" />

    <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 max-w-2xl">
        Once a truck returns, re-verify each pickup's weight against what actually arrived. A mismatch gets flagged for review rather than silently accepted.
    </p>

    <x-card class="mb-6">
        <x-section-label title="Awaiting Verification" width="w-20" />

        @if($pending->isEmpty())
            <x-empty-state type="cleared" title="Nothing to verify" description="Receiving records from completed trips will appear here until facility-verified." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Farmer</th>
                            <th class="px-4 py-3">Crop</th>
                            <th class="px-4 py-3">Pickup Weight</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($pending as $record)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">{{ $record->farmer?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $record->crop?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($record->actual_weight_kg, 2) }} kg</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('coop.facility-receiving.show', $record) }}" class="text-xs font-bold text-brand-700 dark:text-gold-light hover:underline">Verify</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <x-card class="mb-6">
        <x-section-label title="Flagged Variances" width="w-20" />

        @if($flagged->isEmpty())
            <x-empty-state type="cleared" title="No open variances" description="Verified records with a weight mismatch appear here until resolved." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Farmer</th>
                            <th class="px-4 py-3">Crop</th>
                            <th class="px-4 py-3">Pickup</th>
                            <th class="px-4 py-3">Facility</th>
                            <th class="px-4 py-3">Variance</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($flagged as $record)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">{{ $record->farmer?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $record->crop?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($record->actual_weight_kg, 2) }} kg</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($record->facility_received_weight_kg, 2) }} kg</td>
                                <td class="px-4 py-3"><x-badge status="rejected" :label="($record->variance_kg > 0 ? '+' : '').number_format($record->variance_kg, 2).' kg'" dot /></td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('coop.facility-receiving.show', $record) }}" class="text-xs font-bold text-brand-700 dark:text-gold-light hover:underline">Review</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <x-card>
        <x-section-label title="Recently Verified" width="w-24" />

        @if($verified->isEmpty())
            <x-empty-state type="first-use" title="Nothing verified yet" description="Verified records will appear here." />
        @else
            <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
                @foreach($verified as $record)
                    <li class="py-3 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $record->farmer?->name ?? '—' }} — {{ $record->crop?->name ?? '—' }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Verified {{ $record->facility_verified_at?->format('M d, Y g:i A') }}</p>
                        </div>
                        <x-badge :status="$record->variance_status === 'resolved' ? 'completed' : 'active'" :label="$record->variance_status === 'resolved' ? 'Resolved' : 'Matched'" dot />
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>
</x-layout>
