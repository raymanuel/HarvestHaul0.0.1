<x-layout title="Platform Reports — HarvestHaul">
    <x-page-header title="Platform Reports" :showDate="true" />

    <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 max-w-2xl">
        Aggregate counts across every cooperative on the platform. For a single cooperative's detailed procurement, sales, payout, or trip records, open that cooperative's own Reports from its admin account.
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 mb-8">
        <x-stat-card
            title="Cooperatives"
            :value="$totalCooperatives"
            unit="registered"
            :href="route('admin.cooperatives.index')"
            link-text="View cooperatives"
        />
        <x-stat-card
            title="Approved Cooperatives"
            :value="$approvedCooperatives"
            unit="operating"
        />
        <x-stat-card
            title="Registered Farmers"
            :value="$totalFarmers"
            unit="accounts"
        />
        <x-stat-card
            title="Active Buyers"
            :value="$activeBuyers"
            unit="verified"
            :href="route('admin.buyers.index')"
            link-text="View buyers"
        />
        <x-stat-card
            title="Completed Orders"
            :value="$completedOrders"
            unit="buyer orders"
        />
        <x-stat-card
            title="Completed Trips"
            :value="$completedTrips"
            unit="pickups + deliveries"
        />
    </div>

    <x-card class="max-w-sm">
        <x-section-label title="Transaction Volume" width="w-40" />
        <p class="text-2xl font-bold text-slate-800 dark:text-slate-100 mt-2">₱{{ number_format($transactionVolume, 2) }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Total value of completed buyer orders, platform-wide.</p>
    </x-card>
</x-layout>
