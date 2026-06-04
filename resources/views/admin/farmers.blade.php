<x-layout>
<div class="w-full max-w-7xl mx-auto">

    <!-- Nice Admin Page Header -->
    <header class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white heading-font tracking-tight">Farmer Verification</h1>
                <p class="text-sm text-slate-400 dark:text-slate-500 mt-1 font-semibold">Review, approve, or reject farmer registrations</p>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-widest text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/20 px-3 py-1.5 rounded-lg border border-amber-500/10 dark:border-amber-500/20 self-start">{{ $farmers->count() }} Farmers</span>
        </div>
    </header>

    @if (session('success'))
        <div class="mb-6 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 rounded-xl px-5 py-4 text-sm font-semibold flex items-center gap-2">
            <span>✅</span> {{ session('success') }}
        </div>
    @endif

    <!-- Nice Admin Card Table -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left" style="min-width: 800px;">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/40">
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Name</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Email</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Farm Location</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">Email Verified</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">Profile</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">Account</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/40">
                    @foreach($farmers as $farmer)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-emerald-100 to-emerald-50 dark:from-emerald-950/20 dark:to-emerald-900/20 border border-emerald-200/50 dark:border-emerald-800/30 flex items-center justify-center text-[10px] font-extrabold text-emerald-700 dark:text-emerald-400 uppercase">{{ substr($farmer->name, 0, 2) }}</div>
                                <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ $farmer->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400 text-xs font-medium">{{ $farmer->email }}</td>
                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400 text-xs font-medium">{{ $farmer->farmerProfile->farm_location ?? '—' }}</td>

                        {{-- Email verification --}}
                        <td class="px-6 py-4">
                            @if($farmer->hasVerifiedEmail())
                                <span class="bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide">Verified</span>
                            @else
                                <span class="bg-red-50 dark:bg-red-950/20 text-red-600 dark:text-red-400 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide">Unverified</span>
                            @endif
                        </td>

                        {{-- Profile verification --}}
                        <td class="px-6 py-4">
                            @if($farmer->farmerProfile?->is_verified)
                                <span class="bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide">Approved</span>
                            @else
                                <span class="bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide">Pending</span>
                            @endif
                        </td>

                        {{-- Account status --}}
                        <td class="px-6 py-4">
                            @if(($farmer->status ?? 'active') === 'inactive')
                                <span class="bg-slate-100 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide">Archived</span>
                            @else
                                <span class="bg-sky-50 dark:bg-sky-950/20 text-sky-600 dark:text-sky-400 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide">Active</span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                @if(!$farmer->farmerProfile?->is_verified)
                                    <form method="POST" action="{{ route('admin.farmers.verify', $farmer->id) }}">
                                        @csrf
                                        <button type="submit" class="text-emerald-600 hover:text-emerald-800 font-bold text-xs hover:underline transition">Approve</button>
                                    </form>
                                @endif
                                @if($farmer->farmerProfile?->is_verified)
                                    <form method="POST" action="{{ route('admin.farmers.reject', $farmer->id) }}">
                                        @csrf
                                        <button type="submit"
                                            onclick="return confirm('Reject {{ addslashes($farmer->name) }}?')"
                                            class="text-red-500 hover:text-red-700 font-bold text-xs hover:underline transition">Reject</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-layout>
