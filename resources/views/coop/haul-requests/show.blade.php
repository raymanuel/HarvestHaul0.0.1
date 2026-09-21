<x-layout title="Haul Request — Cooperative">
    <x-page-header
        variant="back-link"
        title="Pickup Request"
        :backHref="route('coop.haul-requests.index')"
        backLabel="← Back to Haul Requests"
    />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <x-card class="lg:col-span-2">
            <x-section-label title="Request Details" />
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Farmer</dt>
                    <dd class="mt-1 font-medium text-slate-900 dark:text-white">{{ $haulRequest->farmer?->name ?? 'Farmer' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Crop</dt>
                    <dd class="mt-1 font-medium text-slate-900 dark:text-white">
                        {{ $haulRequest->crop?->name ?? 'Crop' }}@if($haulRequest->cropVariety) · {{ $haulRequest->cropVariety->name }}@endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Packaging</dt>
                    <dd class="mt-1 font-medium text-slate-900 dark:text-white">{{ $haulRequest->packagingType?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Estimated Load</dt>
                    <dd class="mt-1 font-medium text-slate-900 dark:text-white">
                        @if($haulRequest->estimated_sacks){{ $haulRequest->estimated_sacks }} sacks · @endif{{ number_format((float) $haulRequest->estimated_weight_kg, 2) }} kg
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Pickup Location</dt>
                    <dd class="mt-1 font-medium text-slate-900 dark:text-white">{{ $haulRequest->pickup_location ?? '—' }}</dd>
                </div>
            </dl>

            @if($haulRequest->notes)
                <div class="mt-5">
                    <dt class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Notes</dt>
                    <dd class="mt-1 text-sm text-slate-700 dark:text-slate-300">{{ $haulRequest->notes }}</dd>
                </div>
            @endif
        </x-card>

        <x-card>
            <x-section-label title="Scheduling" />
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Preferred pickup</dt>
                    <dd class="mt-1 font-medium text-slate-900 dark:text-white">
                        {{ $haulRequest->preferred_pickup_date?->format('M d, Y') ?? 'TBD' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Time window</dt>
                    <dd class="mt-1 font-medium text-slate-900 dark:text-white">
                        {{ $haulRequest->pickup_window_start?->format('g:i A') }} – {{ $haulRequest->pickup_window_end?->format('g:i A') }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Current Status</dt>
                    <dd class="mt-1"><x-badge :status="$haulRequest->status" dot /></dd>
                </div>
            </dl>

            @if($haulRequest->haulJob)
                <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                    <dt class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Assigned Trip</dt>
                    <dd class="mt-1">
                        <a href="{{ route('coop.pickups.show', $haulRequest->haulJob) }}" class="text-sm font-bold text-brand-700 dark:text-gold-light hover:underline">Trip #{{ $haulRequest->haulJob->id }} →</a>
                    </dd>
                </div>
            @endif

            @if($haulRequest->status === \App\Models\HaulRequest::STATUS_PENDING)
                <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700/60 space-y-2">
                    <form method="POST" action="{{ route('coop.haul-requests.approve', $haulRequest) }}">
                        @csrf
                        <x-button variant="primary" full>Approve Request</x-button>
                    </form>

                    <x-modal triggerLabel="Decline Request">
                        <form method="POST" action="{{ route('coop.haul-requests.reject', $haulRequest) }}">
                            @csrf
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Decline this pickup request. This notifies the farmer.</p>
                            <div class="mb-3">
                                <label for="reason" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1.5">Reason</label>
                                <textarea name="reason" id="reason" rows="3" required
                                    class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white"></textarea>
                            </div>
                            <div class="flex justify-end gap-3 mt-4">
                                <x-button tag="a" variant="ghost" onclick="closeModal('reason-modal')">Cancel</x-button>
                                <button type="submit"
                                    class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-[var(--color-error-text)] hover:opacity-90"
                                    onclick="return false">
                                    Decline Request
                                </button>
                            </div>
                        </form>
                    </x-modal>
                </div>
            @endif
        </x-card>
    </div>
</x-layout>