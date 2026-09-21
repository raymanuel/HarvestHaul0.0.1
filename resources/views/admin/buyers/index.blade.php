<x-layout title="Buyers — HarvestHaul">
    <x-page-header title="Buyers" :showDate="true" />

    <div class="flex flex-wrap gap-2 mb-6">
        @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'suspended' => 'Suspended', 'all' => 'All'] as $key => $label)
            <a href="{{ route('admin.buyers.index', ['status' => $key]) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold border transition {{ $status === $key ? 'bg-brand-700 text-white border-brand-700' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-brand-700/40' }}">
                {{ $label }} ({{ $counts[$key] ?? ($key === 'all' ? $counts->sum() : 0) }})
            </a>
        @endforeach
    </div>

    @if($buyers->isEmpty())
        <x-empty-state type="cleared" title="No buyers in this list" description="New buyer accounts will appear here for verification." />
    @else
        <div class="space-y-3">
            @foreach($buyers as $buyer)
                @php
                    $bStatus = $buyer->buyerProfile?->status ?? 'pending';
                    $badge = ['pending' => 'pending', 'approved' => 'active', 'rejected' => 'rejected', 'suspended' => 'inactive'][$bStatus] ?? 'default';
                @endphp
                <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <p class="text-base font-bold text-slate-800 dark:text-white heading-font">{{ $buyer->buyerProfile?->business_name ?? $buyer->name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ $buyer->buyerProfile?->contact_person ?? $buyer->name }} · {{ $buyer->email }}
                            </p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                                {{ $buyer->buyerProfile?->business_address ?: 'No business address on file' }}
                                @if($buyer->buyerProfile?->phone) · {{ $buyer->buyerProfile->phone }} @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <x-badge :status="$badge" :label="ucfirst($bStatus)" dot />

                            @if(in_array($bStatus, ['pending', 'rejected']))
                                <form method="POST" action="{{ route('admin.buyers.approve', $buyer) }}">
                                    @csrf
                                    <x-button variant="primary" size="sm">Approve</x-button>
                                </form>
                            @endif

                            @if($bStatus === 'pending')
                                <x-modal triggerLabel="Reject" triggerClass="px-3.5 py-2 rounded-xl text-[11px] font-bold bg-[var(--color-error-bg)] text-[var(--color-error-text)] border border-[var(--color-error-border)] hover:opacity-90 transition">
                                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Reject {{ $buyer->name }}</h2>
                                    <form method="POST" action="{{ route('admin.buyers.reject', $buyer) }}" class="space-y-4">
                                        @csrf
                                        <textarea name="rejection_reason" rows="3" required maxlength="2000" placeholder="Reason for rejection" class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white"></textarea>
                                        <div class="flex justify-end gap-3">
                                            <button type="button" data-modal-close class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Cancel</button>
                                            <button class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-[var(--color-error-text)] hover:opacity-90">Reject</button>
                                        </div>
                                    </form>
                                </x-modal>
                            @endif

                            @if($bStatus === 'approved')
                                <form method="POST" action="{{ route('admin.buyers.suspend', $buyer) }}" onsubmit="return confirm('Suspend {{ $buyer->name }}? They will not be able to place orders until reactivated.');">
                                    @csrf
                                    <button class="px-3.5 py-2 rounded-xl text-[11px] font-bold bg-[var(--color-error-bg)] text-[var(--color-error-text)] border border-[var(--color-error-border)] hover:opacity-90 transition">Suspend</button>
                                </form>
                            @endif

                            @if($bStatus === 'suspended')
                                <form method="POST" action="{{ route('admin.buyers.reactivate', $buyer) }}">
                                    @csrf
                                    <x-button variant="primary" size="sm">Reactivate</x-button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">{{ $buyers->links() }}</div>
    @endif
</x-layout>
