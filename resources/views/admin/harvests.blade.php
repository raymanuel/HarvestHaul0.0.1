<x-layout title="Harvest Listings">
<div class="w-full max-w-7xl mx-auto">

    <!-- Nice Admin Page Header -->
    <header class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white heading-font tracking-tight">Harvest Listings</h1>
                <p class="text-sm text-slate-400 dark:text-slate-500 mt-1 font-semibold">All harvest listings posted across the platform</p>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-widest text-teal-700 dark:text-teal-400 bg-teal-50 dark:bg-teal-950/20 px-3 py-1.5 rounded-lg border border-teal-500/10 dark:border-teal-500/20 self-start">{{ $harvests->count() }} Listings</span>
        </div>
    </header>

    <!-- Nice Admin Card Table -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left" style="min-width:800px;">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/40">
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Farmer</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Crop</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Variety</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Quantity</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Harvest Date</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Posted</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/40">
                    @forelse($harvests as $harvest)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-teal-100 to-teal-50 dark:from-teal-950/20 dark:to-teal-900/20 border border-teal-200/50 dark:border-teal-800/30 flex items-center justify-center text-[10px] font-extrabold text-teal-700 dark:text-teal-400 uppercase">{{ substr($harvest->farmer->name ?? '—', 0, 2) }}</div>
                                <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ $harvest->farmer->name ?? '—' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-600 dark:text-slate-400 text-xs font-semibold">
                            {{ $harvest->crop->name ?? $harvest->crop_type ?? '—' }}
                        </td>
                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400 text-xs font-medium">
                            {{ $harvest->cropVariety->name ?? $harvest->variety ?? '—' }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-bold text-slate-700 dark:text-slate-300 text-sm">{{ number_format($harvest->quantity_kg, 2) }}</span>
                            <span class="text-slate-400 dark:text-slate-500 text-[10px] font-bold ml-0.5">kg</span>
                        </td>
                        <td class="px-6 py-4 text-slate-400 dark:text-slate-500 text-xs font-semibold">
                            {{ $harvest->harvest_date ? $harvest->harvest_date->format('M d, Y') : '—' }}
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $badge = match($harvest->status) {
                                    'active'    => ['bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400', '●'],
                                    'pending'   => ['bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400', '●'],
                                    'completed' => ['bg-sky-50 dark:bg-sky-950/20 text-sky-700 dark:text-sky-400', '●'],
                                    'cancelled' => ['bg-red-50 dark:bg-red-950/20 text-red-600 dark:text-red-400', '●'],
                                    default     => ['bg-slate-100 dark:bg-slate-900/50 text-slate-600 dark:text-slate-400', '●'],
                                };
                            @endphp
                            <span class="{{ $badge[0] }} text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide inline-flex items-center gap-1">
                                <span class="text-[6px]">{{ $badge[1] }}</span> {{ $harvest->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-400 dark:text-slate-500 text-xs font-semibold">
                            {{ $harvest->created_at->format('M d, Y') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-10 h-10 text-slate-200 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                <span class="text-slate-400 dark:text-slate-500 text-sm font-semibold">No harvest listings found</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-layout>
