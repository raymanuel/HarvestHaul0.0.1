<x-layout :title="'Trip #'.$haulJob->id.' Receiving — Field'">
    <x-page-header variant="back-link" :title="'Trip #'.$haulJob->id" :back-href="route('field.receiving.index')" back-label="← Back to Receiving Queue">
        <x-badge :status="$haulJob->status" dot />
    </x-page-header>

    <x-card>
        <x-section-label title="Stops" width="w-16" />

        @if($haulJob->stops->isEmpty())
            <x-empty-state type="first-use" title="No stops on this trip" />
        @else
            <div class="space-y-4">
                @foreach($haulJob->stops as $stop)
                    @php($record = $stop->receivingRecords->first())
                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                        <div class="flex items-center justify-between flex-wrap gap-2 mb-2">
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-100">
                                #{{ $stop->sequence_no }} — {{ $stop->haulRequest?->farmer?->name ?? 'Farmer' }}
                            </p>
                            <x-badge :status="$stop->status" />
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ $stop->haulRequest?->crop?->name ?? 'Crop' }}
                            @if($stop->haulRequest?->estimated_weight_kg) · Estimated {{ number_format((float) $stop->haulRequest->estimated_weight_kg, 2) }} kg @endif
                        </p>

                        @if($record)
                            <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                <div><dt class="font-bold uppercase tracking-wider text-slate-400">Actual Weight</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ number_format((float) $record->actual_weight_kg, 2) }} kg</dd></div>
                                <div><dt class="font-bold uppercase tracking-wider text-slate-400">Grade</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $record->cropGrade?->name ?? '—' }}</dd></div>
                                <div><dt class="font-bold uppercase tracking-wider text-slate-400">Price/kg</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $record->buying_price_per_kg !== null ? '₱'.number_format((float) $record->buying_price_per_kg, 2) : 'Not set' }}</dd></div>
                                <div><dt class="font-bold uppercase tracking-wider text-slate-400">Status</dt><dd class="mt-0.5"><x-badge :status="$record->status === \App\Models\ReceivingRecord::STATUS_CONFIRMED ? 'confirmed' : 'pending'" /></dd></div>
                            </div>
                        @elseif($stop->status === \App\Models\HaulJobStop::STATUS_PICKED_UP)
                            <a href="{{ route('field.receiving.create', [$haulJob, $stop]) }}" class="inline-flex items-center mt-3 px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-900 text-white dark:bg-white dark:text-slate-900 hover:opacity-90">Record Receiving</a>
                        @else
                            <p class="text-xs text-slate-400 mt-3">Not yet picked up.</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>
</x-layout>
