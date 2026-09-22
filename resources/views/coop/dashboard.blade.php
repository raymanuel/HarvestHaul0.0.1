<x-layout title="Cooperative Dashboard — HarvestHaul">
    <x-page-header :title="$cooperative->name" :showDate="true" />

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-10">
        <x-stat-card badge="People" title="Members Waiting" :value="$pendingMembers" unit="requests" />
        <x-stat-card badge="Pickups" title="Haul Requests" :value="$pendingHaulRequests" unit="to schedule" />
        <x-stat-card badge="Pickups" title="Scheduled Jobs" :value="$scheduledJobs" unit="upcoming" />
        <x-stat-card badge="Orders" title="Buyer Orders" :value="$pendingOrders" unit="to decide" />
    </div>

    <x-card>
        <x-section-label title="Needs Your Attention" width="w-24" />

        @if($attention->isEmpty())
            <x-empty-state type="cleared" title="All caught up" description="No member requests, haul requests, or buyer orders are waiting on you." />
        @else
            <ul class="space-y-3">
                @foreach($attention as $item)
                    <li class="flex items-center justify-between gap-4 p-4 rounded-xl border border-slate-200/70 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-700/20">
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $item['label'] }}</span>
                        <span class="shrink-0 text-xs font-extrabold text-brand-700 dark:text-gold-light">{{ $item['count'] }} waiting</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>
</x-layout>
