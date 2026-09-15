<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">

    <div class="relative z-10">
        <!-- Page Header -->
        <header class="mb-8 pt-8">
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('buyer.crop-board') }}" class="text-xs font-bold text-[#16283C] dark:text-[#D7BC7A] hover:underline flex items-center gap-1">
                    ← Back to Crop Board
                </a>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">My Deals</h1>
                </div>
            </div>
        </header>

        <!-- Deals List -->
        @if($negotiations->isEmpty())
            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl p-12 text-center">
                <h3 class="text-lg font-bold text-slate-800 dark:text-white heading-font">No Deals Yet</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto">You have not started any crop purchase deals yet. Head to the Crop Board to find fresh harvests.</p>
                <div class="mt-6">
                    <a href="{{ route('buyer.crop-board') }}" class="inline-flex items-center justify-center px-5 py-3 bg-[#16283C] hover:bg-[#0E1620] text-white dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] font-bold rounded-xl text-xs transition duration-200 shadow-sm cursor-pointer">
                        Browse Crop Board
                    </a>
                </div>
            </div>
        @else
            <div class="space-y-3">
                @foreach($negotiations as $negotiation)
                    @php
                        $statusClass = match($negotiation->status->value) {
                            'OPEN' => 'text-gold-700 bg-gold/10 border-gold/10 dark:text-gold-light dark:bg-gold/20 dark:border-gold/20',
                            'AGREED' => 'text-[#16283C] bg-[#16283C]/10 border-[#16283C]/10 dark:text-[#D7BC7A] dark:bg-[#D7BC7A]/10 dark:border-[#D7BC7A]/20',
                            'COMPLETED' => 'text-[#0E1620] bg-[#0E1620]/10 border-[#0E1620]/10 dark:text-slate-200 dark:bg-slate-700 dark:border-slate-600',
                            default => 'text-slate-500 bg-slate-500/10 border-slate-500/10 dark:text-slate-400',
                        };
                        $cropName = $negotiation->harvest->crop->name ?? $negotiation->harvest->crop_type ?? 'Unknown Crop';
                        $variety = $negotiation->harvest->cropVariety->name ?? $negotiation->harvest->variety ?? 'Standard';
                    @endphp
                    <x-deal-card
                        type="crop"
                        :title="$cropName"
                        counterpart="Farmer: {{ $negotiation->farmer->name ?? 'Farmer' }} · Product #{{ $negotiation->harvest_id }} · {{ $variety }}"
                        :status="strtoupper($negotiation->status->value)"
                        :statusClass="$statusClass"
                        :url="route('negotiations.room', $negotiation->id)"
                        :volume="$negotiation->negotiated_volume ? number_format($negotiation->negotiated_volume) . ' kg' : null"
                        :price="$negotiation->negotiated_price ? '₱' . number_format($negotiation->negotiated_price, 2) . '/kg' : null"
                        :activity="$negotiation->last_activity_at?->diffForHumans()"
                    />
                @endforeach
            </div>

            <!-- Pagination Links -->
            @if($negotiations->hasPages())
                <div class="mt-6">
                    {{ $negotiations->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
</x-layout>