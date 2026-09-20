<x-layout :title="'Edit ' . $user->name . ' — Cooperative'">
    <x-page-header variant="back-link" :title="'Edit ' . $user->name" :back-href="route('coop.farmers.show', $user)" back-label="← Back to Farmer Details" />

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('coop.farmers.update', $user) }}">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <x-input name="name" label="Full Name" required :value="$user->name" />
                <x-input name="email" label="Email" type="email" required :value="$user->email" />
                <x-input name="phone" label="Phone" :value="$user->phone" />
                <x-input name="farm_location" label="Farm / Pickup Location" :value="$farmer->farm_location" />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input name="latitude" label="Latitude" type="number" step="any" :value="$farmer->latitude" />
                    <x-input name="longitude" label="Longitude" type="number" step="any" :value="$farmer->longitude" />
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <a href="{{ route('coop.farmers.show', $user) }}" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-800">Cancel</a>
                <x-button variant="primary" size="sm">Save Changes</x-button>
            </div>
        </form>
    </x-card>
</x-layout>
