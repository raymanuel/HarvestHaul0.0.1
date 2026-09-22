<x-layout title="Admin Dashboard — HarvestHaul">
    <x-page-header title="Platform Overview" :showDate="true" />

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 mb-10">
        <x-stat-card
            badge="Review"
            title="Cooperatives Waiting"
            :value="$pendingCooperatives"
            unit="applications"
            :href="route('admin.cooperatives.index')"
            link-text="Review applications"
        />
        <x-stat-card
            badge="Review"
            title="Buyers Waiting"
            :value="$pendingBuyers"
            unit="accounts"
            :href="route('admin.buyers.index')"
            link-text="Verify accounts"
        />
        <x-stat-card
            title="Approved Cooperatives"
            :value="$approvedCooperatives"
            unit="operating"
            :href="route('admin.cooperatives.index', ['status' => 'approved'])"
            link-text="View cooperatives"
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <x-card class="lg:col-span-2">
            <x-section-label title="Needs Your Attention" width="w-24" />

            @if($attention->isEmpty())
                <x-empty-state type="cleared" title="Nothing waiting" description="Every application and buyer account has been reviewed." />
            @else
                <ul class="space-y-3">
                    @foreach($attention as $item)
                        <li>
                            <a href="{{ $item['href'] }}" class="flex items-center justify-between gap-4 p-4 rounded-xl border border-slate-200/70 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-700/20 hover:border-brand-700/40 transition">
                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $item['label'] }}</span>
                                <span class="shrink-0 text-xs font-extrabold text-brand-700 dark:text-gold-light">{{ $item['count'] }} waiting</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <x-card>
            <x-section-label title="Latest Applications" width="w-16" />

            @if($recentCooperatives->isEmpty())
                <x-empty-state type="first-use" title="No applications yet" description="Cooperative sign-ups will appear here for review." />
            @else
                <ul class="space-y-3">
                    @foreach($recentCooperatives as $cooperative)
                        <li>
                            <a href="{{ route('admin.cooperatives.show', $cooperative) }}" class="block p-4 rounded-xl border border-slate-200/70 dark:border-slate-700 hover:border-brand-700/40 transition">
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $cooperative->name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $cooperative->city ?: $cooperative->municipality }}, {{ $cooperative->province }}</p>
                                <div class="mt-2"><x-badge :status="$cooperative->status" dot /></div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</x-layout>
