<x-layout title="Join a Cooperative — HarvestHaul">
    <x-page-header title="Join a Cooperative" :showDate="true" />

    <div class="max-w-xl">
        <x-card>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                Farmers sell their harvests through their cooperative. Pick the cooperative you belong to, and its admin will review your request.
            </p>

            @if($cooperatives->isEmpty())
                <x-empty-state type="first-use" title="No cooperatives available yet" description="Check back once a cooperative has been approved on the platform." />
            @else
                <form method="POST" action="{{ route('farmer.join-cooperative.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="cooperative_id" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Cooperative <span class="text-[var(--color-error-text)]">*</span></label>
                        <select name="cooperative_id" id="cooperative_id" required class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white">
                            <option value="">Select cooperative…</option>
                            @foreach($cooperatives as $cooperative)
                                <option value="{{ $cooperative->id }}" @selected(old('cooperative_id') == $cooperative->id)>
                                    {{ $cooperative->name }}@if($cooperative->city) — {{ $cooperative->city }}@endif
                                </option>
                            @endforeach
                        </select>
                        @error('cooperative_id')<p class="text-xs text-[var(--color-error-text)] mt-1">{{ $message }}</p>@enderror
                    </div>

                    <x-input name="phone" label="Contact Number (optional)" :value="old('phone')" />
                    <x-input name="farm_location" label="Farm Location (optional)" :value="old('farm_location')" />

                    <x-location-picker latField="latitude" lngField="longitude" label="Pickup Location" />

                    <div class="flex justify-end pt-2">
                        <x-button variant="primary" size="sm">Send Membership Request</x-button>
                    </div>
                </form>
            @endif
        </x-card>
    </div>
</x-layout>
