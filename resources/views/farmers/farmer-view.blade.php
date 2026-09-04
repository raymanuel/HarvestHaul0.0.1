<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">
    <h1 class="sr-only">Farmer Dashboard</h1>

    <div class="relative z-10">
        @if (!Auth::user()->farmerProfile?->is_verified)
            <x-status-banner message="Your account is being reviewed. You'll have full access once approved." />
        @endif

        @if(Auth::user()->farmerProfile?->is_verified && is_null(Auth::user()->farmerProfile?->latitude))
            <x-status-banner variant="missing-location" title="Set Your Farm Location">
                Set your farm coordinates in <a href="{{ route('profile.show') }}" class="underline font-bold">Profile Settings</a> to access full features.
            </x-status-banner>
        @endif

        <x-flash-success />
        <x-flash-error />

        <x-welcome-bar :name="Auth::user()->name">
            @if($unreadMessagesCount > 0)
                <p class="text-xs font-bold text-amber-600 dark:text-amber-400 mt-1">{{ $unreadMessagesCount }} unread message{{ $unreadMessagesCount > 1 ? 's' : '' }} from buyers</p>
            @endif
        </x-welcome-bar>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <x-stat-card
                title="Revenue This Month"
                value="{{ number_format($monthlyRevenue, 2) }}"
                unit="PHP"
                href="{{ route('farmer.reports.sales') }}"
                linkText="View Sales Report"
            />

            <x-stat-card
                title="Active Harvests"
                :value="$activeHarvestsCount"
                unit="posts"
                href="{{ route('harvests.index') }}"
                linkText="Manage Harvests"
            />

            <x-stat-card
                title="Pending Proposals"
                :value="$pendingProposalsCount"
                unit="to review"
                href="{{ route('farmer.proposals') }}"
                linkText="Review Proposals"
            />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
            <x-weather-card class="lg:col-span-1" :weather="$weatherData" />
            <x-market-prices-card class="lg:col-span-2" />
        </div>
</div>
</div>
</x-layout>
