<x-layout title="Record Receiving — Field">
    <x-page-header variant="back-link" title="Record Receiving" :back-href="route('field.receiving.show', $haulJob)" back-label="← Back to Trip" />

    <x-card class="max-w-2xl">
        <x-section-label title="Stop #{{ $stop->sequence_no }} — {{ $stop->haulRequest?->farmer?->name ?? 'Farmer' }}" width="w-40" />
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-5">
            {{ $stop->haulRequest?->crop?->name ?? 'Crop' }}
            @if($stop->haulRequest?->estimated_sacks) · Estimated {{ $stop->haulRequest->estimated_sacks }} sacks @endif
            @if($stop->haulRequest?->estimated_weight_kg) / {{ number_format((float) $stop->haulRequest->estimated_weight_kg, 2) }} kg @endif
        </p>

        <form method="POST" action="{{ route('field.receiving.store', [$haulJob, $stop]) }}" class="space-y-4">
            @csrf

            @if($varieties->isNotEmpty())
                <div>
                    <label for="crop_variety_id" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Variety (optional)</label>
                    <x-select name="crop_variety_id" :options="$varieties->pluck('name', 'id')->all()" placeholder="No specific variety" />
                </div>
            @endif

            <div>
                <label for="crop_grade_id" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Grade <span class="text-red-500">*</span></label>
                <x-select name="crop_grade_id" :required="true" :options="$grades->pluck('name', 'id')->all()" placeholder="Select a grade" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-input name="actual_sacks" type="number" min="1" label="Actual Sacks" required :value="$stop->haulRequest?->estimated_sacks" />
                <x-input name="actual_weight_kg" type="number" step="0.01" min="0.01" label="Actual Weight (kg)" required :value="$stop->haulRequest?->estimated_weight_kg" />
            </div>

            <x-input name="buying_price_per_kg" type="number" step="0.01" min="0" label="Buying Price / kg (optional)" placeholder="Leave blank if the coop will set it later" />

            <div>
                <label for="remarks" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Remarks</label>
                <textarea name="remarks" id="remarks" rows="3" maxlength="1000" class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white placeholder:text-slate-400"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('field.receiving.show', $haulJob) }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Cancel</a>
                <x-button variant="primary">Record Receiving</x-button>
            </div>
        </form>
    </x-card>
</x-layout>
