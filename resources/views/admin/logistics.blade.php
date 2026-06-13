<x-layout>
<div class="w-full max-w-7xl mx-auto">

    <!-- Nice Admin Page Header -->
    <header class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white heading-font tracking-tight">Logistics Verification</h1>
                <p class="text-sm text-slate-400 dark:text-slate-500 mt-1 font-semibold">Approve or reject logistics partner accounts</p>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-widest text-violet-700 dark:text-violet-400 bg-violet-50 dark:bg-violet-950/20 px-3 py-1.5 rounded-lg border border-violet-500/10 dark:border-violet-500/20 self-start">{{ $partners->count() }} Partners</span>
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
            <table class="w-full text-sm text-left" style="min-width: 700px;">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/40">
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Name</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Email</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Company</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Permit No.</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Verified</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/40">
                    @foreach($partners as $partner)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-violet-100 to-violet-50 dark:from-violet-950/20 dark:to-violet-900/20 border border-violet-200/50 dark:border-violet-800/30 flex items-center justify-center text-[10px] font-extrabold text-violet-700 dark:text-violet-400 uppercase">{{ substr($partner->name, 0, 2) }}</div>
                                <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ $partner->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400 text-xs font-medium">{{ $partner->email }}</td>
                        <td class="px-6 py-4 text-slate-600 dark:text-slate-400 text-xs font-semibold">{{ $partner->logisticsProfile->company_name ?? '—' }}</td>
                        <td class="px-6 py-4">
                            <span class="text-xs font-mono bg-slate-100/80 dark:bg-slate-900/50 text-slate-600 dark:text-slate-300 px-2 py-0.5 rounded-md border border-transparent dark:border-slate-700">{{ $partner->logisticsProfile->business_permit_no ?? '—' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($partner->logisticsProfile?->is_verified)
                                <span class="bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide">Verified</span>
                            @else
                                <span class="bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide">Pending</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                @if(!$partner->logisticsProfile?->is_verified)
                                    <form method="POST" action="{{ route('admin.logistics.verify', $partner->id) }}">
                                        @csrf
                                        <button type="button" onclick="swalConfirm(this.closest('form'), {title: 'Approve Partner?', text: 'Verify {{ addslashes($partner->name) }} as a logistics partner?', confirmText: 'Yes, approve', icon: 'question', confirmColor: '#10b981'})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-950/20 dark:hover:bg-emerald-950/40 dark:text-emerald-400 transition" title="Approve Partner">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                                @if($partner->logisticsProfile?->is_verified)
                                    <form method="POST" action="{{ route('admin.logistics.reject', $partner->id) }}">
                                        @csrf
                                        <button type="button" onclick="swalConfirm(this.closest('form'), {title: 'Reject Partner?', text: 'Reject {{ addslashes($partner->name) }}? Their verification will be revoked.', confirmText: 'Yes, reject', icon: 'warning', confirmColor: '#ef4444'})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-950/40 dark:text-rose-400 transition" title="Reject Partner">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
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
