<x-layout title="Procurement — HarvestHaul">
    <x-page-header title="Procurement" :showDate="true" />

    <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 max-w-2xl">
        Receiving records move from awaiting price, to priced and awaiting confirmation, to confirmed. Confirming locks the payout and notifies the farmer.
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
        <x-card>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Awaiting Price</p>
            <p class="text-2xl font-extrabold text-slate-800 dark:text-white">{{ $awaitingPrice->count() }}</p>
        </x-card>
        <x-card>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Total Confirmed</p>
            <p class="text-2xl font-extrabold text-slate-800 dark:text-white">₱{{ number_format($totalConfirmed, 2) }}</p>
        </x-card>
    </div>

    <x-card class="mb-6">
        <x-section-label title="Awaiting Price" width="w-16" />

        @if($awaitingPrice->isEmpty())
            <x-empty-state type="cleared" title="Nothing awaiting price" description="Receiving records without a buying price yet will appear here." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Farmer</th>
                            <th class="px-4 py-3">Crop</th>
                            <th class="px-4 py-3">Weight</th>
                            <th class="px-4 py-3">Recorded</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($awaitingPrice as $record)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">{{ $record->farmer?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $record->crop?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($record->actual_weight_kg, 2) }} kg</td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $record->created_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('coop.procurement.show', $record) }}" class="text-xs font-bold text-brand-700 dark:text-gold-light hover:underline">Set Price</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <x-card class="mb-6">
        <x-section-label title="Awaiting Confirmation" width="w-20" />

        @if($awaitingConfirmation->isEmpty())
            <x-empty-state type="cleared" title="Nothing awaiting confirmation" description="Priced records ready to confirm will appear here." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Farmer</th>
                            <th class="px-4 py-3">Crop</th>
                            <th class="px-4 py-3">Weight</th>
                            <th class="px-4 py-3">Price</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($awaitingConfirmation as $record)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">{{ $record->farmer?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $record->crop?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($record->actual_weight_kg, 2) }} kg</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">₱{{ number_format($record->buying_price_per_kg, 2) }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">₱{{ number_format($record->total_amount, 2) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('coop.procurement.show', $record) }}" class="text-xs font-bold text-brand-700 dark:text-gold-light hover:underline">Review</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <x-card>
        <x-section-label title="Recently Confirmed" width="w-24" />

        @if($recent->isEmpty())
            <x-empty-state type="first-use" title="No confirmed procurement yet" description="Confirmed records will appear here." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Farmer</th>
                            <th class="px-4 py-3">Crop</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Confirmed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($recent as $record)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">
                                    <a href="{{ route('coop.procurement.show', $record) }}" class="hover:underline">{{ $record->farmer?->name ?? '—' }}</a>
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $record->crop?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">₱{{ number_format($record->total_amount, 2) }}</td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $record->confirmed_at?->format('M d, Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-layout>
