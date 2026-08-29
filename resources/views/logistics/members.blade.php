<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">
    <h1 class="sr-only">Member Management</h1>

    <div class="relative z-10">
        <x-page-header
            variant="back-link"
            backHref="{{ route('dashboard') }}"
            title="Member Management"
        />

        <x-flash-success />
        <x-flash-error />

        <div class="space-y-8">
            {{-- ═══════════════════════════════════════════ --}}
            {{-- PENDING REQUESTS --}}
            {{-- ═══════════════════════════════════════════ --}}
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <h2 class="text-base font-bold text-slate-800 dark:text-white heading-font">Pending Requests</h2>
                    <span class="inline-flex items-center justify-center min-w-[22px] h-[22px] px-2 bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 rounded-lg text-[10px] font-extrabold">{{ $pendingRequests->count() }}</span>
                </div>

                @if($pendingRequests->isEmpty())
                    <div class="p-12 text-center shadow-sm bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl">
                        <svg class="mx-auto h-12 w-12 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <h3 class="mt-3 text-sm font-semibold text-slate-800 dark:text-slate-200">No pending membership requests.</h3>
                    </div>
                @else
                    <x-data-table>
                        <x-slot:header>
                            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Farmer Name</th>
                            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Farm Location</th>
                            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Requested On</th>
                            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest text-right">Actions</th>
                        </x-slot:header>

                        @foreach($pendingRequests as $req)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-slate-100 to-slate-50 dark:from-slate-700 dark:to-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-[10px] font-extrabold text-slate-600 dark:text-slate-300 uppercase shrink-0">{{ substr($req->user->name, 0, 2) }}</div>
                                        <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ $req->user->name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs font-medium">{{ $req->farm_location ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs font-semibold">{{ $req->created_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <form action="{{ route('logistics.members.approve', $req->id) }}" method="POST" class="inline" id="approve-form-{{ $req->id }}">
                                            @csrf
                                            <button type="button" onclick="swalConfirm(document.getElementById('approve-form-{{ $req->id }}'), {title: 'Approve Member?', text: 'Approve {{ addslashes($req->user->name) }} as a cooperative member?', confirmText: 'Yes, approve', icon: 'question', confirmColor: '#16283C'})" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-[#16283C]/10 text-[#16283C] hover:bg-[#16283C]/15 dark:bg-[#16283C]/10 dark:hover:bg-[#16283C]/15 dark:text-[#D7BC7A] rounded-lg text-xs font-bold transition cursor-pointer">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('logistics.members.reject', $req->id) }}" method="POST" class="inline" id="reject-form-{{ $req->id }}">
                                            @csrf
                                            <button type="button" onclick="swalConfirm(document.getElementById('reject-form-{{ $req->id }}'), {title: 'Reject Request?', text: 'Reject {{ addslashes($req->user->name) }}\'s membership request?', confirmText: 'Yes, reject', icon: 'warning', confirmColor: '#ef4444'})" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-950/20 dark:hover:bg-red-950/40 dark:text-red-400 rounded-lg text-xs font-bold transition cursor-pointer">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </x-data-table>
                @endif
            </div>

            {{-- ═══════════════════════════════════════════ --}}
            {{-- CURRENT MEMBERS --}}
            {{-- ═══════════════════════════════════════════ --}}
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <h2 class="text-base font-bold text-slate-800 dark:text-white heading-font">Current Members</h2>
                    <span class="inline-flex items-center justify-center min-w-[22px] h-[22px] px-2 bg-[#16283C]/10 text-[#16283C] dark:bg-[#16283C]/10 dark:text-[#D7BC7A] rounded-lg text-[10px] font-extrabold">{{ $members->count() }}</span>
                </div>

                @if($members->isEmpty())
                    <div class="p-12 text-center shadow-sm bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl">
                        <svg class="mx-auto h-12 w-12 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <h3 class="mt-3 text-sm font-semibold text-slate-800 dark:text-slate-200">No members yet.</h3>
                    </div>
                @else
                    <x-data-table>
                        <x-slot:header>
                            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Farmer Name</th>
                            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Farm Location</th>
                            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Member Since</th>
                        </x-slot:header>

                        @foreach($members as $member)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-slate-100 to-slate-50 dark:from-slate-700 dark:to-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-[10px] font-extrabold text-slate-600 dark:text-slate-300 uppercase shrink-0">{{ substr($member->user->name, 0, 2) }}</div>
                                        <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ $member->user->name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs font-medium">{{ $member->farm_location ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs font-semibold">{{ $member->updated_at->format('M d, Y') }}</td>
                            </tr>
                        @endforeach
                    </x-data-table>
                @endif
            </div>
        </div>
    </div>
</div>
</x-layout>
