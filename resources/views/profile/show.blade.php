<x-layout title="Profile Settings — HarvestHaul">
    <x-page-header title="Profile Settings" :showDate="true" />

    @if(session('profile_complete'))
        <x-status-banner variant="missing-location" title="Finish Your Profile"
            message="Add your farm location below so your cooperative can plan pickups." />
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-card>
            <x-section-label title="My Details" width="w-16" />
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4" id="my-details-form">
                @csrf
                @method('PUT')
                <x-input name="name" label="Full Name" :value="$user->name" required />
                <x-input name="email" label="Email" type="email" :value="$user->email" required :error="$errors->first('email')" />
                <x-input name="phone" label="Phone" :value="$user->phone" />
                <input type="hidden" name="password" id="my-details-password">
                <x-button variant="primary" size="sm" full>Save Details</x-button>
            </form>
        </x-card>

        <x-card>
            <x-section-label title="Change Password" width="w-16" />
            <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <x-input name="current_password" label="Current Password" type="password" show-toggle required autocomplete="current-password" />
                <x-input name="password" label="New Password" type="password" show-toggle required autocomplete="new-password" />
                <x-input name="password_confirmation" label="Confirm New Password" type="password" show-toggle required autocomplete="new-password" />
                <x-button variant="primary" size="sm" full>Update Password</x-button>
            </form>
        </x-card>

        <x-modal id="email-password-modal" title="Confirm Your Password">
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                You're changing your email. Enter your password to continue — you'll need to verify the new address with a fresh code before you can use the rest of the app again.
            </p>
            <x-input name="email_password_confirm" label="Current Password" type="password" show-toggle autocomplete="current-password" :error="$errors->first('password')" />
            <x-slot:footer>
                <x-button type="button" variant="secondary" size="sm" onclick="closeModal('email-password-modal')">Cancel</x-button>
                <x-button type="button" variant="primary" size="sm" id="email-password-confirm-btn">Confirm</x-button>
            </x-slot:footer>
        </x-modal>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var form = document.getElementById('my-details-form');
                var emailInput = document.getElementById('email');
                var passwordField = document.getElementById('my-details-password');
                var modalInput = document.getElementById('email_password_confirm');
                var originalEmail = emailInput.value.trim().toLowerCase();
                var passwordConfirmed = false;

                form.addEventListener('submit', function (e) {
                    var changed = emailInput.value.trim().toLowerCase() !== originalEmail;
                    if (changed && !passwordConfirmed) {
                        e.preventDefault();
                        openModal('email-password-modal');
                    }
                });

                document.getElementById('email-password-confirm-btn').addEventListener('click', function () {
                    if (!modalInput.value) {
                        modalInput.focus();
                        return;
                    }
                    passwordField.value = modalInput.value;
                    passwordConfirmed = true;
                    closeModal('email-password-modal');
                    form.requestSubmit();
                });

                @if($errors->has('password'))
                    openModal('email-password-modal');
                @endif
            });
        </script>

        @if($user->isFarmer() || $user->isCoopAdmin() || $user->isBuyer())
            <x-card class="lg:col-span-2">
                <x-section-label title="My Location" width="w-16" />

                @php
                    $lat = match(true) {
                        $user->isFarmer() => $user->farmerProfile?->latitude,
                        $user->isCoopAdmin() => $user->cooperative?->latitude,
                        $user->isBuyer() => $user->buyerProfile?->latitude,
                    };
                    $lng = match(true) {
                        $user->isFarmer() => $user->farmerProfile?->longitude,
                        $user->isCoopAdmin() => $user->cooperative?->longitude,
                        $user->isBuyer() => $user->buyerProfile?->longitude,
                    };
                    $label = match(true) {
                        $user->isFarmer() => $user->farmerProfile?->farm_location,
                        $user->isCoopAdmin() => $user->cooperative?->fullAddress(),
                        $user->isBuyer() => $user->buyerProfile?->location_label,
                    };
                    $hasExistingLocation = $lat && $lng;
                @endphp

                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                    {{ match(true) {
                        $user->isFarmer() => 'Your farm location lets your cooperative plan the best pickup route.',
                        $user->isCoopAdmin() => 'Your cooperative location is used as the pickup and storage point for hauling.',
                        $user->isBuyer() => 'Your delivery location is where cooperatives will route your orders.',
                    } }}
                    @if($lat && $lng)
                        <span class="block mt-1 font-semibold text-slate-600 dark:text-slate-300">Current: {{ $label ?: 'Saved' }} ({{ number_format((float) $lat, 5) }}, {{ number_format((float) $lng, 5) }})</span>
                    @else
                        <span class="block mt-1 font-semibold text-[var(--color-warning-text)]">No location saved yet.</span>
                    @endif
                </p>

                <form method="POST" action="{{ route('profile.save-location') }}" class="space-y-4">
                    @csrf

                    <x-location-picker label="Pin Your Location" :lat="$lat" :lng="$lng" :show-coordinate-inputs="true" />

                    @if($user->isFarmer())
                        <x-input name="location_label" label="Farm Location Label" :value="$user->farmerProfile?->farm_location" placeholder="e.g. Purok 3, Brgy. San Isidro" />
                    @elseif($user->isBuyer())
                        <x-input name="location_label" label="Delivery Address Label" :value="$user->buyerProfile?->location_label" placeholder="e.g. Km 5 Diversion Rd, Brgy. Bula" />
                    @elseif($user->isCoopAdmin())
                        <p class="text-xs text-slate-400 dark:text-slate-500">
                            This pin is for routing only — it doesn't change your cooperative's registered address.
                        </p>
                    @endif

                    @if($hasExistingLocation)
                        <x-input name="password" label="Confirm Password to Change Location" type="password" show-toggle required autocomplete="current-password" :error="$errors->first('password')" />
                    @endif

                    <x-button variant="primary" size="sm">Save Location</x-button>
                </form>
            </x-card>
        @endif
    </div>
</x-layout>
