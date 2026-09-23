<x-layout title="Settings — Cooperative">
    <x-page-header title="Cooperative Settings" :showDate="true" />

    <x-card class="max-w-2xl">
        <x-section-label title="Pickup Consolidation" />
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
            Farmers only share a truck when their pickups are close enough together — not just when the combined weight fits. This sets how close counts as "close enough" for your cooperative.
        </p>

        <form method="POST" action="{{ route('coop.settings.update') }}">
            @csrf
            @method('PUT')

            <x-input
                name="max_cluster_radius_km"
                label="Pickup Consolidation Radius (km)"
                type="number"
                step="any"
                :value="old('max_cluster_radius_km', $cooperative->max_cluster_radius_km)"
                placeholder="Platform default (20 km)"
            />
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1.5">
                Leave blank to use the platform default. Farmers farther apart than this won't be grouped onto the same truck, even if there's room by weight.
            </p>

            <div class="flex justify-end pt-6">
                <x-button variant="primary" size="sm">Save Settings</x-button>
            </div>
        </form>
    </x-card>
</x-layout>
