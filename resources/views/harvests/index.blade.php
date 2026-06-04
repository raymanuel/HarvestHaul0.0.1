<x-layout>
<div class="w-full">

    <header class="pt-8 mb-8">
        <a href="{{ route('dashboard') }}" class="text-sm text-slate-400 hover:text-slate-650 dark:hover:text-slate-350 mb-4 inline-block font-semibold transition">
            ← Back to Dashboard
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">My Harvest Listings</h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium">Manage your active crop listings. Active listings are visible on the logistics map.</p>
            </div>

            @if (Auth::user()->farmerProfile?->is_verified)
                <a href="{{ route('harvests.create') }}"
                    class="bg-emerald-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-emerald-705 dark:bg-emerald-600 dark:hover:bg-emerald-700 transition shadow-md text-sm self-start sm:self-center whitespace-nowrap">
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
                    An administrator needs to approve your farmer account before you can post harvest listings.
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

    {{-- Listings Table --}}
    @if($harvests->isEmpty())
        <div class="bg-slate-50 dark:bg-slate-900/40 border border-dashed border-slate-300 dark:border-slate-700/80 rounded-xl p-16 text-center">
            <p class="text-4xl mb-4">🌾</p>
            <p class="text-slate-500 dark:text-slate-400 font-medium mb-4">You have no harvest listings yet.</p>
            @if (Auth::user()->farmerProfile?->is_verified)
                <a href="{{ route('harvests.create') }}"
                    class="bg-emerald-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-emerald-700 transition shadow-md text-sm inline-block">
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
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-350 font-semibold">{{ number_format($harvest->quantity_kg, 2) }} kg</td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400 text-xs font-medium">
                                {{ $harvest->harvest_date ? $harvest->harvest_date->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400 text-xs font-medium max-w-xs truncate">{{ $harvest->notes ?? '—' }}</td>
                            <td class="px-6 py-4 text-slate-550 dark:text-slate-450 text-xs font-semibold"> {{ $harvest->destination_label }} </td>
                            <td class="px-6 py-4">
                                @if($harvest->status === 'active')
                                    <span class="bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-450 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wider border border-emerald-500/10 dark:border-emerald-500/20">Active</span>
                                @elseif($harvest->status === 'pending')
                                    <span class="bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-450 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wider border border-amber-500/10 dark:border-amber-500/20">Pending</span>
                                @elseif($harvest->status === 'completed')
                                    <span class="bg-sky-50 dark:bg-sky-950/20 text-sky-700 dark:text-sky-450 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wider border border-sky-500/10 dark:border-sky-500/20">Completed</span>
                                @elseif($harvest->status === 'cancelled')
                                    <span class="bg-rose-50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-450 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wider border border-rose-500/10 dark:border-rose-500/20">Cancelled</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-slate-400 dark:text-slate-500 text-xs font-semibold whitespace-nowrap">{{ $harvest->created_at->format('M d, Y') }}</td>
                            <td class="px-6 py-4">
                                @if(in_array($harvest->status, ['active', 'pending']))
                                    <form method="POST" action="{{ route('harvests.destroy', $harvest->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            onclick="return confirm('Remove this listing? You will no longer appear on the logistics map for this crop.')"
                                            class="text-rose-600 dark:text-rose-400 hover:text-rose-750 dark:hover:text-rose-350 hover:underline font-bold text-xs transition">
                                            Remove
                                        </button>
                                    </form>
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
