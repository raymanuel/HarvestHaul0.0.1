<x-layout title="Harvest Posts">
<div class="w-full max-w-7xl mx-auto">

    <header class="pt-8 mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white heading-font tracking-tight">Harvest Posts</h1>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-widest text-[#0E1620] dark:text-[#E9EEF4] bg-[#0E1620]/10 dark:bg-[#0E1620]/10 px-3 py-1.5 rounded-md border border-[#0E1620]/10 dark:border-[#0E1620]/20 self-start">{{ $harvests->count() }} Posts</span>
        </div>
    </header>

    <x-data-table empty-message="No harvest posts found">
        <x-slot:header>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Farmer</th>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Crop</th>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Variety</th>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Quantity</th>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Harvest Date</th>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Status</th>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Posted</th>
        </x-slot:header>

        @forelse($harvests as $harvest)
        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
            <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-md bg-[#0E1620]/10 dark:bg-[#0E1620]/10 border border-[#0E1620]/20 dark:border-[#0E1620]/15 flex items-center justify-center text-[10px] font-extrabold text-[#0E1620] dark:text-[#E9EEF4] uppercase">{{ substr($harvest->farmer->name ?? '—', 0, 2) }}</div>
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
                <span class="text-slate-500 dark:text-slate-400 text-[10px] font-bold ml-0.5">kg</span>
            </td>
            <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs font-semibold">
                {{ $harvest->harvest_date ? $harvest->harvest_date->format('M d, Y') : '—' }}
            </td>
            <td class="px-4 py-3">
                <x-badge :status="$harvest->status" :label="$harvest->status" dot />
            </td>
            <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs font-semibold">
                {{ $harvest->created_at->format('M d, Y') }}
            </td>
        </tr>
        @empty
        @endforelse
    </x-data-table>
</div>
</x-layout>
