<x-layout>
@push('head')
    <style>
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }
    </style>
@endpush
<div class="w-full max-w-7xl mx-auto pb-12">
    <h1 class="sr-only">Buyer Dashboard</h1>

    <div class="relative z-10">
        <x-flash-success />
        <x-flash-error />

        <x-welcome-bar message="Welcome back, {{ Auth::user()->name }}." />

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <x-stat-card
                accent="harvest"
                title="Pending Confirmations"
                :value="$pendingConfirmations->count()"
                unit="deliveries"
                href="{{ route('buyer.negotiations') }}"
                linkText="View Deliveries"
            />

            <x-stat-card
                accent="brand"
                title="Open Negotiations"
                :value="$activeNegotiations->count()"
                unit="active deals"
                href="{{ route('buyer.negotiations') }}"
                linkText="View Negotiations"
            />

            <x-stat-card
                accent="brand"
                title="Available Crops"
                :value="$recentPosts->count()"
                unit="postings"
                href="{{ route('buyer.crop-board') }}"
                linkText="Browse Crops"
            />
        </div>

        <div class="mb-10">
            <x-market-prices-card :daPrices="$daPrices" :priceTrends="$priceTrends" :latestDate="$latestDaDate" :scraperStatus="$scraperStatus" />
        </div>

        @if($pendingConfirmations->isNotEmpty())
        <div class="mb-10">
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left" aria-label="Pending confirmations">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/30">
                                <th class="p-5 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Delivery</th>
                                <th class="p-5 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Truck</th>
                                <th class="p-5 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Driver</th>
                                <th class="p-5 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100/50 dark:divide-slate-700/30">
                            @foreach($pendingConfirmations as $job)
                            <tr class="group hover:bg-slate-50/30 dark:hover:bg-slate-900/20 transition duration-150">
                                <td class="p-5 whitespace-nowrap">
                                    <div class="font-bold text-slate-800 dark:text-slate-200 text-xs">Delivery #{{ $job->id }}</div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                                        {{ $job->harvests->count() }} stop(s) • {{ number_format($job->total_kg) }} kg
                                    </div>
                                </td>
                                <td class="p-5 text-xs font-bold text-slate-700 dark:text-slate-300">
                                    {{ $job->truck->truck_name ?? 'N/A' }}
                                </td>
                                <td class="p-5 text-xs font-bold text-slate-700 dark:text-slate-300">
                                    {{ $job->driver->name ?? 'N/A' }}
                                </td>
                                <td class="p-5 text-center">
                                    <form method="POST" action="{{ route('buyer.confirm-receipt', $job) }}">
                                        @csrf
                                        <button type="button"
                                            onclick="swalConfirm(this.closest('form'), {title:'Confirm Receipt?', text:'Mark delivery #{{ $job->id }} as received?', confirmText:'Yes, confirm', icon:'question', confirmColor:'#065F46'})"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-harvest hover:bg-harvest-dark text-white text-[10px] font-bold rounded-xl transition shadow-sm shadow-harvest/10 cursor-pointer">
                                            Confirm Receipt
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
</x-layout>
