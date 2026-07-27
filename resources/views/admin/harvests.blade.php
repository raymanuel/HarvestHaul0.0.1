<x-layout title="Harvest Posts">
<div class="w-full max-w-7xl mx-auto">

    <!-- Nice Admin Page Header -->
    <header class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white heading-font tracking-tight">Harvest Posts</h1>
                <p class="text-sm text-slate-400 dark:text-slate-500 mt-1 font-semibold">All harvest posts across the platform</p>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-widest text-[#2E6336] dark:text-[#2E6336] bg-[#2E6336]/10 dark:bg-[#2E6336]/10 px-3 py-1.5 rounded-lg border border-[#2E6336]/10 dark:border-[#2E6336]/20 self-start">{{ $harvests->count() }} Posts</span>
        </div>
    </header>

    <!-- Nice Admin Card Table -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/40">
                        <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Farmer</th>
                        <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Crop</th>
                        <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Variety</th>
                        <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Quantity</th>
                        <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Harvest Date</th>
                        <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Status</th>
                        <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Posted</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/40">
                    @forelse($harvests as $harvest)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-[#2E6336]/15 to-[#2E6336]/10 dark:from-[#2E6336]/10 dark:to-[#2E6336]/5 border border-[#2E6336]/20 dark:border-[#2E6336]/15 flex items-center justify-center text-[10px] font-extrabold text-[#2E6336] dark:text-[#2E6336] uppercase">{{ substr($harvest->farmer->name ?? '—', 0, 2) }}</div>
                                <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ $harvest->farmer->name ?? '—' }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-400 text-xs font-semibold">
                            {{ $harvest->crop->name ?? $harvest->crop_type ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs font-medium">
                            {{ $harvest->cropVariety->name ?? $harvest->variety ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-bold text-slate-700 dark:text-slate-300 text-sm">{{ number_format($harvest->quantity_kg, 2) }}</span>
                            <span class="text-slate-400 dark:text-slate-500 text-[10px] font-bold ml-0.5">kg</span>
                        </td>
                        <td class="px-4 py-3 text-slate-400 dark:text-slate-500 text-xs font-semibold">
                            {{ $harvest->harvest_date ? $harvest->harvest_date->format('M d, Y') : '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $badge = match($harvest->status) {
                                    'active'    => ['bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 text-[#3A7D44] dark:text-[#3A7D44]', '●'],
                                    'completed' => ['bg-[#1F4D25]/10 dark:bg-[#1F4D25]/10 text-[#1F4D25] dark:text-[#1F4D25]', '●'],
                                    'cancelled' => ['bg-red-50 dark:bg-red-950/20 text-red-600 dark:text-red-400', '●'],
                                    default     => ['bg-slate-100 dark:bg-slate-900/50 text-slate-600 dark:text-slate-400', '●'],
                                };
                            @endphp
                            <span class="{{ $badge[0] }} text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide inline-flex items-center gap-1">
                                <span class="text-[6px]">{{ $badge[1] }}</span> {{ $harvest->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-400 dark:text-slate-500 text-xs font-semibold">
                            {{ $harvest->created_at->format('M d, Y') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-10 h-10 text-slate-200 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                <span class="text-slate-400 dark:text-slate-500 text-sm font-semibold">No harvest posts found</span>
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
