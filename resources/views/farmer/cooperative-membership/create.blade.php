<x-layout title="Join a Cooperative — HarvestHaul">
    <x-page-header title="Join a Cooperative" :showDate="true" />

    <div class="max-w-3xl">
        @if($cooperatives->isEmpty())
            <x-card>
                <x-empty-state type="first-use" title="No cooperatives available yet" description="Check back once a cooperative has been approved on the platform." />
            </x-card>
        @else
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                Farmers sell their harvests through their cooperative. Pick the cooperative you belong to, and its admin will review your request.
            </p>

            <form method="POST" action="{{ route('farmer.join-cooperative.store') }}" class="space-y-6">
                @csrf

                <div>
                    <span class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Cooperative <span class="text-[var(--color-error-text)]">*</span></span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($cooperatives as $cooperative)
                            <label class="relative block cursor-pointer">
                                <input type="radio" name="cooperative_id" value="{{ $cooperative->id }}"
                                    class="peer sr-only" required
                                    @checked(old('cooperative_id') == $cooperative->id)>
                                <div class="h-full bg-white dark:bg-slate-800 border-2 border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm transition
                                    peer-checked:border-brand-700 dark:peer-checked:border-[#D7BC7A] peer-checked:bg-brand-50/50 dark:peer-checked:bg-[#D7BC7A]/10
                                    peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500/40">
                                    <div class="flex items-start gap-3 mb-4">
                                        <div class="w-10 h-10 rounded-2xl bg-brand-700/10 border border-brand-700/15 flex items-center justify-center text-brand-700 dark:text-[#D7BC7A] shrink-0">
                                            <x-icon name="building" class="w-5 h-5" />
                                        </div>
                                        <div class="min-w-0">
                                            <h3 class="text-sm font-bold text-slate-800 dark:text-white heading-font truncate">{{ $cooperative->name }}</h3>
                                            @if($cooperative->city)
                                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $cooperative->city }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-600 uppercase tracking-wider">Members</h4>
                                            <p class="text-sm font-bold text-slate-800 dark:text-white mt-0.5">{{ $cooperative->member_farmers_count }}</p>
                                        </div>
                                        <div>
                                            <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-600 uppercase tracking-wider">Distance</h4>
                                            <p class="text-sm font-bold text-slate-800 dark:text-white mt-0.5">
                                                {{ $cooperative->distance_km !== null ? number_format($cooperative->distance_km, 1).' km' : '—' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('cooperative_id')<p class="text-xs text-[var(--color-error-text)] mt-2">{{ $message }}</p>@enderror
                </div>

                <x-card>
                    <div class="space-y-4">
                        <x-input name="phone" label="Contact Number (optional)" :value="old('phone')" />
                        <x-input name="farm_location" label="Farm Location (optional)" :value="old('farm_location')" />

                        @if($lat && $lng)
                            <p class="text-xs text-slate-500 dark:text-slate-400 -mt-1">Pre-filled from your farm location. Drag the pin if this pickup spot is different.</p>
                        @endif
                        <x-location-picker latField="latitude" lngField="longitude" label="Pickup Location" :lat="$lat" :lng="$lng" />

                        <div class="flex justify-end pt-2">
                            <x-button variant="primary" size="sm">Send Membership Request</x-button>
                        </div>
                    </div>
                </x-card>
            </form>
        @endif
    </div>
</x-layout>
