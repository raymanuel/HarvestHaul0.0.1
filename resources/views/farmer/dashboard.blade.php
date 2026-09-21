<x-layout title="Farmer Dashboard — HarvestHaul">
    <x-page-header title="My Farm" :showDate="true" />

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 mb-10">
        <x-stat-card badge="Requests" title="Open Haul Requests" :value="$openHaulRequests" unit="waiting" />
        <x-stat-card badge="Payout" title="Confirmed Earnings" value="₱{{ number_format($totalPayout, 2) }}" />
        <x-stat-card badge="Paid" title="Received So Far" value="₱{{ number_format($totalPaid, 2) }}" unit="₱{{ number_format($totalBalance, 2) }} balance" />
        <x-stat-card badge="Records" title="Confirmed Receiving" :value="$confirmedRecords->count()" unit="shown below" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-card>
            <x-section-label title="My Haul Requests" width="w-24" />

            @if($haulRequests->isEmpty())
                <x-empty-state type="first-use" title="No haul requests yet" description="When you file a haul request, it appears here with its status." />
            @else
                <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @foreach($haulRequests as $request)
                        <li class="py-3 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-100">
                                    {{ $request->crop?->name ?? 'Crop' }}@if($request->cropVariety) · {{ $request->cropVariety->name }}@endif
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    {{ $request->estimated_sacks }} sacks · {{ number_format((float) $request->estimated_weight_kg, 2) }} kg
                                    @if($request->preferred_pickup_date) · prefer {{ $request->preferred_pickup_date->format('M d') }}@endif
                                </p>
                            </div>
                            <x-badge :status="$request->status" dot />
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <x-card>
            <x-section-label title="Confirmed Receiving" width="w-24" />

            @if($confirmedRecords->isEmpty())
                <x-empty-state type="first-use" title="No confirmed records yet" description="After your crop is received and confirmed, the weight and payment show here." />
            @else
                <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @foreach($confirmedRecords as $record)
                        <li class="py-3 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-100">
                                    {{ $record->crop?->name ?? 'Crop' }}@if($record->cropGrade) · {{ $record->cropGrade->name }}@endif
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    {{ number_format((float) $record->actual_weight_kg, 2) }} kg
                                    · ₱{{ number_format((float) $record->buying_price_per_kg, 2) }}/kg
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="block text-sm font-extrabold text-brand-700 dark:text-gold-light">₱{{ number_format((float) $record->total_amount, 2) }}</span>
                                @php $ps = $record->paymentStatus(); @endphp
                                <x-badge :status="['pending' => 'pending', 'partial' => 'ready', 'paid' => 'active'][$ps]" :label="['pending' => 'Payment Pending', 'partial' => 'Partially Paid', 'paid' => 'Paid'][$ps]" dot />
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</x-layout>
