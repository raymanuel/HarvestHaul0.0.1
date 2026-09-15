<x-layout>
<div class="w-full max-w-7xl mx-auto">

    <header class="pt-8 mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white heading-font tracking-tight">Farmer Verification</h1>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-widest text-[var(--color-warning-text)] bg-[var(--color-warning-bg)] px-3 py-1.5 rounded-md border border-[var(--color-warning-border)] self-start">{{ $farmers->count() }} Farmers</span>
        </div>
    </header>

    @php
        $farmerVerifyTabs = [
            ['label' => 'Registry', 'url' => route('admin.farmers'), 'active' => true],
            ['label' => 'Documents', 'url' => route('admin.farmer-documents'), 'active' => false],
        ];
    @endphp
    <x-nav-tabs :tabs="$farmerVerifyTabs" />

    <x-flash-success />

    <x-data-table>
        <x-slot:header>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Name</th>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Email</th>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Farm Location</th>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Email Verified</th>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Profile</th>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Account</th>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Action</th>
        </x-slot:header>

        @foreach($farmers as $farmer)
        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
            <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-md bg-[#16283C]/10 dark:bg-[#16283C]/10 border border-[#16283C]/20 dark:border-[#16283C]/15 flex items-center justify-center text-[10px] font-extrabold text-[#16283C] dark:text-[#D7BC7A] uppercase">{{ substr($farmer->name, 0, 2) }}</div>
                    <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ $farmer->name }}</span>
                </div>
            </td>
            <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs font-medium">{{ $farmer->email }}</td>
            <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs font-medium">{{ $farmer->farmerProfile->farm_location ?? '—' }}</td>
            <td class="px-4 py-3">
                <x-badge :status="$farmer->hasVerifiedEmail() ? 'verified' : 'rejected'" :label="$farmer->hasVerifiedEmail() ? 'Verified' : 'Unverified'" />
            </td>
            <td class="px-4 py-3">
                <x-badge :status="$farmer->farmerProfile?->is_verified ? 'active' : 'pending'" :label="$farmer->farmerProfile?->is_verified ? 'Approved' : 'Pending'" />
            </td>
            <td class="px-4 py-3">
                <x-badge :status="($farmer->status ?? 'active') === 'inactive' ? 'archived' : 'active'" :label="($farmer->status ?? 'active') === 'inactive' ? 'Archived' : 'Active'" />
            </td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                    @if(!$farmer->farmerProfile?->is_verified)
                        <form method="POST" action="{{ route('admin.farmers.verify', $farmer->id) }}">
                            @csrf
                            <button type="button" onclick="swalConfirm(this.closest('form'), {title: 'Approve Farmer?', text: 'Verify {{ addslashes($farmer->name) }} as a registered farmer?', confirmText: 'Yes, approve', icon: 'question', confirmColor: '#16283C'})" class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-[#16283C]/10 text-[#16283C] hover:bg-[#16283C]/15 dark:bg-[#16283C]/10 dark:hover:bg-[#16283C]/15 dark:text-[#D7BC7A] transition" title="Approve Farmer">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        </form>
                    @endif
                    @if($farmer->farmerProfile?->is_verified)
                        <form method="POST" action="{{ route('admin.farmers.reject', $farmer->id) }}">
                            @csrf
                            <button type="button" onclick="swalConfirm(this.closest('form'), {title: 'Reject Farmer?', text: 'Reject {{ addslashes($farmer->name) }}? Their verification will be revoked.', confirmText: 'Yes, reject', icon: 'warning', confirmColor: '#ef4444'})" class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-950/40 dark:text-rose-400 transition" title="Reject Farmer">
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
    </x-data-table>
</div>
</x-layout>
