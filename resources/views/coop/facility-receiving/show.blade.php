<x-layout title="Facility Receiving — HarvestHaul">
    <x-page-header title="Facility Receiving" :showDate="true" />

    <div class="max-w-2xl">
        <x-card class="mb-6">
            <x-section-label title="Pickup Record" width="w-20" />
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Farmer</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $receivingRecord->farmer?->name ?? '—' }}</dd></div>
                <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Crop</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $receivingRecord->crop?->name ?? '—' }}</dd></div>
                <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Grade</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $receivingRecord->cropGrade?->name ?? '—' }}</dd></div>
                <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Pickup Weight</dt><dd class="mt-0.5 font-bold text-slate-800 dark:text-slate-200">{{ number_format($receivingRecord->actual_weight_kg, 2) }} kg</dd></div>
            </dl>
        </x-card>

        @if($receivingRecord->facility_verified_at === null)
            <x-card>
                <x-section-label title="Verify Facility Weight" width="w-24" />
                <form method="POST" action="{{ route('coop.facility-receiving.verify', $receivingRecord) }}" class="space-y-4">
                    @csrf
                    <x-input name="facility_received_weight_kg" type="number" step="0.01" label="Facility-Received Weight (kg)" required placeholder="e.g. {{ $receivingRecord->actual_weight_kg }}" />
                    <div>
                        <label for="variance_notes" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Notes (required only if weight differs)</label>
                        <textarea name="variance_notes" id="variance_notes" rows="3" maxlength="1000" class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white" placeholder="e.g. moisture loss during transit"></textarea>
                    </div>
                    <div class="flex justify-end">
                        <x-button variant="primary" size="sm">Verify</x-button>
                    </div>
                </form>
            </x-card>
        @else
            <x-card>
                <div class="flex items-center justify-between mb-4">
                    <x-section-label title="Verified" width="w-20" />
                    @if($receivingRecord->variance_status === 'flagged')
                        <x-badge status="rejected" label="Variance Flagged" dot />
                    @elseif($receivingRecord->variance_status === 'resolved')
                        <x-badge status="completed" label="Resolved" dot />
                    @else
                        <x-badge status="active" label="Matched" dot />
                    @endif
                </div>
                <dl class="grid grid-cols-2 gap-4 text-sm mb-4">
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Facility Weight</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ number_format($receivingRecord->facility_received_weight_kg, 2) }} kg</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Variance</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ number_format($receivingRecord->variance_kg, 2) }} kg</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Verified By</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $receivingRecord->facilityVerifier?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Verified At</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $receivingRecord->facility_verified_at->format('M d, Y g:i A') }}</dd></div>
                </dl>
                @if($receivingRecord->variance_notes)
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-700/60 mb-4">
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Notes</dt>
                        <dd class="text-sm text-slate-600 dark:text-slate-300">{{ $receivingRecord->variance_notes }}</dd>
                    </div>
                @endif

                @if($receivingRecord->variance_status === 'flagged')
                    <form method="POST" action="{{ route('coop.facility-receiving.resolve', $receivingRecord) }}" class="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                        @csrf
                        <div>
                            <label for="resolve_notes" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Resolution <span class="text-[var(--color-error-text)]">*</span></label>
                            <textarea name="variance_notes" id="resolve_notes" rows="3" maxlength="1000" required class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white" placeholder="How was this variance explained/resolved?"></textarea>
                        </div>
                        <div class="flex justify-end">
                            <x-button variant="primary" size="sm">Mark Resolved</x-button>
                        </div>
                    </form>
                @endif
            </x-card>
        @endif
    </div>
</x-layout>
