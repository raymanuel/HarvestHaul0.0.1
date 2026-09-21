<x-layout title="Buyer Dashboard — HarvestHaul">
    <x-page-header title="Buyer Workspace" :showDate="true" />

    @if($profile?->status === 'rejected')
        <x-status-banner variant="unverified" title="Account Not Approved"
            message="Your buyer account application was not approved. Contact platform support for details." />
    @elseif($profile?->status === 'suspended')
        <x-status-banner variant="unverified" title="Account Suspended"
            message="Your buyer account is suspended and cannot place orders. Contact platform support for details." />
    @elseif($profile?->status !== 'approved')
        <x-status-banner variant="unverified" title="Account Pending Approval"
            message="A platform admin is reviewing your business details. You can browse listings now, but placing orders opens once you are approved." />
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 mb-10">
        <x-stat-card badge="Orders" title="Open Orders" :value="$openOrders" unit="in progress" />
        <x-stat-card badge="Market" title="Available Listings" :value="$availableListings->count()" unit="shown below" />
        <x-stat-card badge="Account" title="Verification" :value="$profile?->is_verified ? 'Verified' : 'Pending'" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-card>
            <div class="flex items-center justify-between mb-1">
                <x-section-label title="Crop Listings Available" width="w-24" />
                <a href="{{ route('buyer.listings.index') }}" class="text-xs font-bold text-brand-700 dark:text-gold-light hover:underline">Browse all</a>
            </div>

            @if($availableListings->isEmpty())
                <x-empty-state type="first-use" title="No listings yet" description="When cooperatives list crops for sale, they appear here." />
            @else
                <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @foreach($availableListings as $listing)
                        <li class="py-3 flex items-center justify-between gap-3">
                            <a href="{{ route('buyer.listings.show', $listing) }}" class="hover:underline">
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-100">
                                    {{ $listing->crop?->name ?? 'Crop' }}@if($listing->cropGrade) · {{ $listing->cropGrade->name }}@endif
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    {{ $listing->cooperative?->name ?? 'Cooperative' }} · {{ number_format((float) $listing->remaining_kg, 2) }} kg available
                                </p>
                            </a>
                            <span class="text-sm font-extrabold text-brand-700 dark:text-gold-light">₱{{ number_format((float) $listing->selling_price_per_kg, 2) }}/kg</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <x-card>
            <div class="flex items-center justify-between mb-1">
                <x-section-label title="My Recent Orders" width="w-16" />
                <a href="{{ route('buyer.orders.index') }}" class="text-xs font-bold text-brand-700 dark:text-gold-light hover:underline">View all</a>
            </div>

            @if($recentOrders->isEmpty())
                <x-empty-state type="first-use" title="No orders yet" description="Orders you place with cooperatives appear here with their status." />
            @else
                <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @foreach($recentOrders as $order)
                        <li class="py-3 flex items-center justify-between gap-3">
                            <a href="{{ route('buyer.orders.show', $order) }}" class="hover:underline">
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $order->reference ?? 'Order #'.$order->id }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    {{ $order->cooperative?->name ?? 'Cooperative' }} · {{ number_format((float) $order->total_kg, 2) }} kg
                                </p>
                            </a>
                            <x-badge :status="$order->status" dot />
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</x-layout>
