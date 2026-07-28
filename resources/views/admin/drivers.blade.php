<x-layout title="Driver Accounts">
<div class="w-full max-w-7xl mx-auto">

    <header class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white heading-font tracking-tight">Driver Accounts</h1>
                <p class="text-sm text-slate-400 dark:text-slate-500 mt-1 font-semibold">All registered drivers across logistics partners</p>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-widest text-[#1F4D25] dark:text-[#1F4D25] bg-[#1F4D25]/10 dark:bg-[#1F4D25]/10 px-3 py-1.5 rounded-lg border border-[#1F4D25]/10 dark:border-[#1F4D25]/20 self-start">{{ $drivers->count() }} Drivers</span>
        </div>
    </header>

    <x-flash-success />

    <x-data-table empty-message="No drivers registered yet">
        <x-slot:header>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Name</th>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Email</th>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Identity</th>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Status</th>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Joined</th>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Actions</th>
        </x-slot:header>

        @forelse($drivers as $driver)
        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
            <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-[#1F4D25]/15 to-[#1F4D25]/10 dark:from-[#1F4D25]/10 dark:to-[#1F4D25]/5 border border-[#1F4D25]/20 dark:border-[#1F4D25]/15 flex items-center justify-center text-[10px] font-extrabold text-[#1F4D25] dark:text-[#1F4D25] uppercase">{{ substr($driver->name, 0, 2) }}</div>
                    <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ $driver->name }}</span>
                </div>
            </td>
            <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs font-medium">{{ $driver->email }}</td>
            <td class="px-4 py-3">
                @if($driver->driverProfile?->identity_verified)
                    <x-badge status="verified" />
                @elseif($driver->driverProfile?->id_photo_path)
                    <x-badge status="pending" />
                @else
                    <span class="text-slate-400 dark:text-slate-500 text-[10px] font-bold">—</span>
                @endif
            </td>
            <td class="px-4 py-3">
                <x-badge :status="$driver->status === 'active' ? 'active' : 'inactive'" />
            </td>
            <td class="px-4 py-3 text-slate-400 dark:text-slate-500 text-xs font-semibold">{{ $driver->created_at->format('M d, Y') }}</td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                    @if($driver->driverProfile?->id_photo_path && !$driver->driverProfile?->identity_verified)
                        <form method="POST" action="{{ route('admin.drivers.verify-identity', $driver->id) }}" class="inline">
                            @csrf
                            <button type="button" onclick="swalConfirm(this.closest('form'), {title: 'Verify Identity?', text: 'Verify identity for {{ addslashes($driver->name) }}?', confirmText: 'Yes, verify', icon: 'success', confirmColor: '#3A7D44'})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-[#3A7D44]/10 text-[#3A7D44] hover:bg-[#3A7D44]/15 dark:bg-[#3A7D44]/10 dark:hover:bg-[#3A7D44]/15 dark:text-[#3A7D44] transition" title="Verify Identity">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.drivers.reject-identity', $driver->id) }}" class="inline">
                            @csrf
                            <button type="button" onclick="swalConfirm(this.closest('form'), {title: 'Reject Identity?', text: 'Reject identity for {{ addslashes($driver->name) }}?', confirmText: 'Yes, reject', icon: 'warning', confirmColor: '#ef4444'})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-950/20 dark:hover:bg-red-950/40 dark:text-red-400 transition" title="Reject Identity">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.users.status', $driver->id) }}">
                        @csrf
                        @if($driver->status === 'active')
                            <button type="button" onclick="swalConfirm(this.closest('form'), {title: 'Archive Driver?', text: 'Archive {{ addslashes($driver->name) }}?', confirmText: 'Yes, archive', icon: 'warning', confirmColor: '#f59e0b'})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 dark:bg-amber-950/20 dark:hover:bg-amber-950/40 dark:text-amber-400 transition" title="Archive Driver">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </button>
                        @else
                            <button type="button" onclick="swalConfirm(this.closest('form'), {title: 'Reactivate Driver?', text: 'Reactivate {{ addslashes($driver->name) }}?', confirmText: 'Yes, reactivate', icon: 'question', confirmColor: '#3A7D44'})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-[#3A7D44]/10 text-[#3A7D44] hover:bg-[#3A7D44]/15 dark:bg-[#3A7D44]/10 dark:hover:bg-[#3A7D44]/15 dark:text-[#3A7D44] transition" title="Reactivate Driver">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                            </button>
                        @endif
                    </form>
                </div>
            </td>
        </tr>
        @empty
        @endforelse
    </x-data-table>
</div>
</x-layout>
