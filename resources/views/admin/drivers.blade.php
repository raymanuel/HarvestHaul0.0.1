<x-layout title="Driver Accounts">
<div class="w-full max-w-7xl mx-auto">

    <!-- Nice Admin Page Header -->
    <header class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white heading-font tracking-tight">Driver Accounts</h1>
                <p class="text-sm text-slate-400 dark:text-slate-500 mt-1 font-semibold">All registered drivers across logistics partners</p>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-widest text-sky-700 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/20 px-3 py-1.5 rounded-lg border border-sky-500/10 dark:border-sky-500/20 self-start">{{ $drivers->count() }} Drivers</span>
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
            <table class="w-full text-sm text-left" style="min-width:600px;">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/40">
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Name</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Email</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Joined</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/40">
                    @forelse($drivers as $driver)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-sky-100 to-sky-50 dark:from-sky-950/20 dark:to-sky-900/20 border border-sky-200/50 dark:border-sky-800/30 flex items-center justify-center text-[10px] font-extrabold text-sky-700 dark:text-sky-400 uppercase">{{ substr($driver->name, 0, 2) }}</div>
                                <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ $driver->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400 text-xs font-medium">{{ $driver->email }}</td>
                        <td class="px-6 py-4">
                            @if($driver->status === 'active')
                                <span class="bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide">Active</span>
                            @else
                                <span class="bg-red-50 dark:bg-red-950/20 text-red-600 dark:text-red-400 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-slate-400 dark:text-slate-500 text-xs font-semibold">{{ $driver->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4">
                            <form method="POST" action="{{ route('admin.users.status', $driver->id) }}">
                                @csrf
                                <button type="submit"
                                    onclick="return confirm('{{ $driver->status === 'active' ? 'Archive' : 'Reactivate' }} {{ addslashes($driver->name) }}?')"
                                    class="{{ $driver->status === 'active' ? 'text-red-500 hover:text-red-700' : 'text-emerald-600 hover:text-emerald-800' }} font-bold text-xs hover:underline transition">
                                    {{ $driver->status === 'active' ? 'Archive' : 'Reactivate' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-10 h-10 text-slate-200 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                <span class="text-slate-400 dark:text-slate-500 text-sm font-semibold">No drivers registered yet</span>
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
