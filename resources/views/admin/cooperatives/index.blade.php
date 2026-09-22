<x-layout title="Cooperatives — HarvestHaul">
    <x-page-header title="Cooperatives" :showDate="true" />

    @php
        $tabs = [
            'pending' => 'Pending',
            'under_review' => 'Under Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'suspended' => 'Suspended',
            'all' => 'All',
        ];
    @endphp

    <div class="flex flex-wrap gap-2 mb-6">
        @foreach($tabs as $key => $label)
            <a href="{{ route('admin.cooperatives.index', ['status' => $key]) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold border transition {{ $status === $key ? 'bg-brand-700 text-white border-brand-700' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-brand-700/40' }}">
                {{ $label }}
                @if(isset($counts[$key]))
                    <span class="ml-1 opacity-80">{{ $counts[$key] }}</span>
                @elseif($key === 'all')
                    <span class="ml-1 opacity-80">{{ $counts->sum() }}</span>
                @endif
            </a>
        @endforeach
    </div>

    @if($cooperatives->isEmpty())
        <x-empty-state type="cleared" title="Nothing in this list" description="No cooperatives match this status right now." />
    @else
        <div class="space-y-3">
            @foreach($cooperatives as $cooperative)
                <a href="{{ route('admin.cooperatives.show', $cooperative) }}" class="block bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm hover:border-brand-700/40 transition">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <p class="text-base font-bold text-slate-800 dark:text-white heading-font">{{ $cooperative->name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ $cooperative->fullAddress() ?: 'No address on file' }}
                            </p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                                Submitted {{ $cooperative->created_at->format('M d, Y') }} · {{ $cooperative->members_count }} member{{ $cooperative->members_count === 1 ? '' : 's' }}
                            </p>
                        </div>
                        <div class="shrink-0">
                            <x-badge :status="$cooperative->status" dot />
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $cooperatives->links() }}
        </div>
    @endif
</x-layout>
