<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">

    <div class="relative z-10">
        <!-- Page Header -->
        <header class="mb-8 pt-8">
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('dashboard') }}" class="text-xs font-bold text-[#16283C] dark:text-[#D7BC7A] hover:underline flex items-center gap-1">
                    ← Back to Dashboard
                </a>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight heading-font">My Deals</h1>
                </div>
            </div>
        </header>

        <!-- Deals List -->
        @if($negotiations->isEmpty() && $haulIntents->isEmpty())
            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-12 text-center">
                <h3 class="text-lg font-bold text-slate-800 dark:text-white heading-font">No Deals Yet</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto">You have no active crop or haul conversations at the moment. Buyers will reach out once they see your crop posts.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($negotiations as $negotiation)
                    @php
                        $statusClass = match($negotiation->status->value) {
                            'OPEN' => 'text-gold-700 bg-gold/10 border-gold/10 dark:text-gold-light dark:bg-gold/20 dark:border-gold/20',
                            'AGREED' => 'text-[#16283C] bg-[#16283C]/10 border-[#16283C]/10 dark:text-[#D7BC7A] dark:bg-[#16283C]/20 dark:border-[#16283C]/20',
                            'COMPLETED' => 'text-[#0E1620] bg-[#0E1620]/10 border-[#0E1620]/10 dark:text-slate-200 dark:bg-slate-700 dark:border-slate-600',
                            default => 'text-slate-500 bg-slate-500/10 border-slate-500/10 dark:text-slate-400',
                        };
                        $cropName = $negotiation->harvest->crop->name ?? $negotiation->harvest->crop_type ?? 'Unknown Crop';
                        $variety = $negotiation->harvest->cropVariety->name ?? $negotiation->harvest->variety ?? 'Standard';
                    @endphp
                    <x-deal-card
                        type="crop"
                        :title="$cropName"
                        counterpart="Buyer: {{ $negotiation->buyer->name ?? 'Buyer' }} · Lot #{{ $negotiation->harvest_id }} · {{ $variety }}"
                        :status="strtoupper($negotiation->status->value)"
                        :statusClass="$statusClass"
                        :url="route('farmer.deal-room', $negotiation->id)"
                        :volume="$negotiation->negotiated_volume ? number_format($negotiation->negotiated_volume) . ' kg' : null"
                        :price="$negotiation->negotiated_price ? '₱' . number_format($negotiation->negotiated_price, 2) . '/kg' : null"
                        :activity="$negotiation->last_activity_at?->diffForHumans()"
                    />
                @endforeach

                @foreach($haulIntents as $intent)
                    @php
                        $statusClass = match($intent->status) {
                            'pending' => 'text-amber-700 bg-amber-50 border-amber-500/10 dark:bg-amber-950/20 dark:text-amber-300',
                            'agreed' => 'text-[#16283C] bg-[#16283C]/10 border-[#16283C]/10 dark:text-[#D7BC7A] dark:bg-[#16283C]/20 dark:border-[#16283C]/20',
                            'accepted' => 'text-purple-700 bg-purple-50 border-purple-500/10 dark:bg-purple-950/20 dark:text-purple-300',
                            'declined' => 'text-red-600 bg-red-50 border-red-500/10 dark:bg-red-950/20 dark:text-red-300',
                            default => 'text-slate-500 bg-slate-500/10 border-slate-500/10 dark:text-slate-400',
                        };
                        $haulHarvest = $intent->haulRequest?->harvest;
                        $haulRate = $intent->hauling_rate_php_per_kg ? 'Agreed ₱' . number_format((float) $intent->hauling_rate_php_per_kg, 2) . '/kg' : ($intent->offer_rate_php_per_kg ? 'Offer ₱' . number_format((float) $intent->offer_rate_php_per_kg, 2) . '/kg' : null);
                    @endphp
                    <x-deal-card
                        type="haul"
                        :title="$haulHarvest?->crop?->name ?? $haulHarvest?->crop_type ?? 'Haul'"
                        counterpart="Logistics: {{ $intent->logisticsProfile?->user?->name ?? '—' }}"
                        :status="strtoupper($intent->status)"
                        :statusClass="$statusClass"
                        :url="route('haul-negotiations.room', $intent->id)"
                        :volume="$haulHarvest ? number_format($haulHarvest->quantity_kg) . ' kg' : null"
                        :rate="$haulRate"
                        :activity="$intent->updated_at?->diffForHumans()"
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