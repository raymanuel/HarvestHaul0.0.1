<x-layout>
<div class="w-full">

    <header class="pt-8 mb-8">
        <a href="{{ route('dashboard') }}" class="text-sm text-slate-400 hover:text-slate-650 dark:hover:text-slate-350 mb-4 inline-block font-semibold transition">
            ← Back to Dashboard
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">My Posts</h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium">Manage your active crop posts. Active posts are visible on the logistics map.</p>
            </div>

            @if (Auth::user()->farmerProfile?->is_verified)
                <a href="{{ route('harvests.create') }}"
                    class="bg-[#3A7D44] text-white px-6 py-3 rounded-xl font-semibold hover:bg-[#2E6336] dark:bg-[#3A7D44] dark:hover:bg-[#2E6336] transition shadow-md text-sm self-start sm:self-center whitespace-nowrap">
                    + Post New Harvest
                </a>
            @else
                <div class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-5 py-3 self-start sm:self-center">
                    <span class="text-slate-400 dark:text-slate-500 text-sm font-semibold">+ Post New Harvest</span>
                    <span class="text-xs bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 border border-amber-200/50 dark:border-amber-900/30 font-bold px-2.5 py-1 rounded-lg uppercase tracking-wider">
                        Pending Approval
                    </span>
                </div>
            @endif
        </div>
    </header>

    {{-- PENDING VERIFICATION BANNER --}}
    @if (!Auth::user()->farmerProfile?->is_verified)
        <div class="mb-6 bg-amber-50 dark:bg-amber-950/20 border border-amber-250/20 dark:border-amber-900/30 text-amber-750 dark:text-amber-450 rounded-2xl px-5 py-4 flex gap-3.5 items-start shadow-sm">
            <span class="text-xl mt-0.5 select-none">⏳</span>
            <div>
                <p class="text-sm font-bold text-amber-850 dark:text-amber-300 heading-font">Account Pending Verification</p>
                <p class="text-xs text-amber-750 dark:text-amber-400 mt-1 leading-relaxed font-medium">
                    An administrator needs to approve your farmer account before you can post harvests.
                </p>
            </div>
        </div>
    @endif

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="mb-6 bg-green-50 dark:bg-green-950/20 border border-green-200/50 dark:border-green-800/30 text-green-700 dark:text-green-400 rounded-xl px-5 py-4 text-sm font-medium">
            ✅ {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 bg-red-50 dark:bg-red-950/20 border border-red-200/50 dark:border-red-800/30 text-red-700 dark:text-red-400 rounded-xl px-5 py-4 text-sm font-medium">
            ⚠️ {{ session('error') }}
        </div>
    @endif

    {{-- Posts Table --}}
    @if($harvests->isEmpty())
        <div class="bg-slate-50 dark:bg-slate-900/40 border border-dashed border-slate-300 dark:border-slate-700/80 rounded-xl p-16 text-center">
            <p class="text-4xl mb-4">🌾</p>
            <p class="text-slate-500 dark:text-slate-400 font-medium mb-4">You have no harvest posts yet.</p>
            @if (Auth::user()->farmerProfile?->is_verified)
                <a href="{{ route('harvests.create') }}"
                    class="bg-[#3A7D44] text-white px-6 py-3 rounded-xl font-semibold hover:bg-[#2E6336] transition shadow-md text-sm inline-block">
                    Post Your First Harvest
                </a>
            @endif
        </div>
    @else
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm overflow-hidden mb-10">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left" style="min-width: 640px;">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-slate-900/40 border-b border-slate-100 dark:border-slate-700/60">
                            <th class="px-6 py-4 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Crop</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Variety</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Est. Quantity</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Harvest Date</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Notes</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Destination</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Posted</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-700/40">
                        @foreach($harvests as $harvest)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                            <td class="px-6 py-4 font-bold text-slate-800 dark:text-slate-200">
                                {{ $harvest->crop->name ?? $harvest->crop_type ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-350 font-medium">
                                {{ $harvest->cropVariety->name ?? $harvest->variety ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-350 font-semibold">{{ number_format($harvest->quantity_kg, 2) }} kg
                                @if($harvest->remaining_quantity_kg && (float)$harvest->remaining_quantity_kg < (float)$harvest->quantity_kg)
                                    <span class="text-[9px] text-amber-600 dark:text-amber-400 block">({{ number_format($harvest->remaining_quantity_kg, 2) }} kg remaining)</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400 text-xs font-medium">
                                {{ $harvest->harvest_date ? $harvest->harvest_date->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400 text-xs font-medium max-w-xs truncate">{{ $harvest->notes ?? '—' }}</td>
                            <td class="px-6 py-4 text-slate-550 dark:text-slate-450 text-xs font-semibold"> {{ $harvest->destination_label }} </td>
                            <td class="px-6 py-4">
                                @if($harvest->status === 'active')
                                    <span class="bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 text-[#3A7D44] dark:text-[#3A7D44] text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wider border border-[#3A7D44]/10 dark:border-[#3A7D44]/20">Active</span>
                                @elseif($harvest->status === 'partially_sold')
                                    <span class="bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-450 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wider border border-amber-500/10 dark:border-amber-500/20">Partial Sale</span>
                                @elseif($harvest->status === 'pending')
                                    <span class="bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-450 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wider border border-amber-500/10 dark:border-amber-500/20">Pending</span>
                                @elseif($harvest->status === 'completed')
                                    <span class="bg-[#1F4D25]/10 dark:bg-[#1F4D25]/10 text-[#1F4D25] dark:text-[#1F4D25] text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wider border border-[#1F4D25]/10 dark:border-[#1F4D25]/20">Completed</span>
                                @elseif($harvest->status === 'cancelled')
                                    <span class="bg-rose-50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-450 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wider border border-rose-500/10 dark:border-rose-500/20">Cancelled</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-slate-400 dark:text-slate-500 text-xs font-semibold whitespace-nowrap">{{ $harvest->created_at->format('M d, Y') }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $canEdit = in_array($harvest->status, ['active', 'pending'])
                                        || ($harvest->status === 'partially_sold' && (float) ($harvest->remaining_quantity_kg ?? 0) > 0);
                                @endphp
                                @if($canEdit)
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('harvests.edit', $harvest->id) }}"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-[#3A7D44]/10 text-[#3A7D44] hover:bg-[#3A7D44]/15 dark:bg-[#3A7D44]/10 dark:hover:bg-[#3A7D44]/15 dark:text-[#3A7D44] transition"
                                            title="Edit Harvest">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('harvests.destroy', $harvest->id) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                onclick="swalConfirm(this.closest('form'), {title: 'Remove Post?', text: 'You will no longer appear on the logistics map for this crop.', confirmText: 'Yes, remove', icon: 'warning', confirmColor: '#ef4444'})"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-950/40 dark:text-rose-400 transition active:scale-[0.95]"
                                                title="Remove Harvest">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-slate-350 dark:text-slate-600 text-xs select-none">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
</x-layout>
