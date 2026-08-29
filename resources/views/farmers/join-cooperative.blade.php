<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">
    <h1 class="sr-only">Join Cooperative</h1>

    <div class="relative z-10">
        <x-page-header
            variant="back-link"
            backHref="{{ route('dashboard') }}"
            title="Join a Cooperative"
        />

        <x-flash-success />
        <x-flash-error />

        {{-- Status Banners --}}
        @if($currentRequest && $currentRequest->membership_status === 'pending' && $currentRequest->cooperative_id)
            <div class="mb-6 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] rounded-2xl px-5 py-4 flex gap-3.5 items-start shadow-sm">
                <span class="text-[var(--color-warning-text)] mt-0.5 select-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </span>
                <div>
                    <p class="text-sm font-bold text-[var(--color-warning-text)] heading-font">Request Pending</p>
                    <p class="text-xs text-[var(--color-warning-text)] mt-1 leading-relaxed font-medium opacity-80">Your request to join {{ $currentRequest->cooperative->company_name }} is pending approval.</p>
                </div>
            </div>
        @elseif($currentRequest && $currentRequest->membership_status === 'approved')
            <div class="mb-6 bg-[#16283C]/10 dark:bg-[#16283C]/10 border border-[#16283C]/20 dark:border-[#16283C]/30 rounded-2xl px-5 py-4 flex gap-3.5 items-start shadow-sm">
                <span class="text-[#16283C] mt-0.5 select-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </span>
                <div>
                    <p class="text-sm font-bold text-[#16283C] dark:text-[#D7BC7A] heading-font">Member</p>
                    <p class="text-xs text-[#16283C]/70 dark:text-[#D7BC7A] mt-1 leading-relaxed font-medium">You are a member of {{ $currentRequest->cooperative->company_name }}.</p>
                </div>
            </div>
        @elseif($currentRequest && $currentRequest->membership_status === 'rejected')
            <div class="mb-6 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] rounded-2xl px-5 py-4 flex gap-3.5 items-start shadow-sm">
                <span class="text-[var(--color-warning-text)] mt-0.5 select-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                </span>
                <div>
                    <p class="text-sm font-bold text-[var(--color-warning-text)] heading-font">Request Not Approved</p>
                    <p class="text-xs text-[var(--color-warning-text)] mt-1 leading-relaxed font-medium opacity-80">Your previous request was not approved. You may request to join another cooperative.</p>
                </div>
            </div>
        @endif

        {{-- Cooperative Grid --}}
        @if($cooperatives->isEmpty())
            <div class="p-12 text-center shadow-sm bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl">
                <svg class="mx-auto h-12 w-12 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <h3 class="mt-3 text-sm font-semibold text-slate-800 dark:text-slate-200">No verified cooperatives available yet.</h3>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($cooperatives as $coop)
                    @php
                        $isPendingForThis = $currentRequest && $currentRequest->membership_status === 'pending' && $currentRequest->cooperative_id === $coop->id;
                        $isApprovedMember = $currentRequest && $currentRequest->membership_status === 'approved' && $currentRequest->cooperative_id === $coop->id;
                    @endphp
                    <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm flex flex-col">
                        <div class="flex items-start gap-3 mb-4">
                            <div class="w-10 h-10 rounded-2xl bg-[#16283C]/10 border border-[#16283C]/15 flex items-center justify-center text-[#16283C] dark:text-[#D7BC7A] shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-bold text-slate-800 dark:text-white heading-font truncate">{{ $coop->company_name }}</h3>
                                @if($coop->cda_registration_no)
                                    <p class="text-[10px] font-bold text-slate-400 dark:text-slate-550 uppercase tracking-wider mt-0.5">CDA: {{ $coop->cda_registration_no }}</p>
                                @endif
                            </div>
                        </div>

                        @if($coop->office_address)
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mb-4 line-clamp-2">{{ $coop->office_address }}</p>
                        @else
                            <div class="mb-4"></div>
                        @endif

                        <div class="mt-auto">
                            @if($isPendingForThis)
                                <span class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] rounded-xl text-xs font-bold">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    Request Pending
                                </span>
                            @elseif($isApprovedMember)
                                <span class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[#16283C]/10 text-[#16283C] dark:bg-[#16283C]/10 dark:text-[#D7BC7A] rounded-xl text-xs font-bold">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    Your Cooperative
                                </span>
                            @else
                                <form action="{{ route('farmer.join-cooperative.request', $coop->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-[#16283C] hover:bg-[#0E1620] text-white text-xs font-bold rounded-xl shadow-md shadow-[#16283C]/10 hover:shadow-lg transition-all cursor-pointer">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                        Request to Join
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Cancel Pending Request (if pending to a different coop) --}}
        @if($currentRequest && $currentRequest->membership_status === 'pending' && $currentRequest->cooperative_id)
            @php $pendingCoop = $cooperatives->firstWhere('id', $currentRequest->cooperative_id); @endphp
            @if(!$isPendingForThis ?? true)
                <div class="mt-8 p-5 bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-2xl shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-[10px] bg-[var(--color-warning-bg)] flex items-center justify-center shrink-0 text-[var(--color-warning-text)]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-800 dark:text-white heading-font">Pending Request</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">You have a pending request to join {{ $currentRequest->cooperative->company_name }}.</p>
                        </div>
                    </div>
                    <form action="{{ route('farmer.join-cooperative.cancel', $currentRequest->cooperative_id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="button" onclick="swalConfirm(this.closest('form'), {title: 'Cancel Request?', text: 'Your pending request to {{ addslashes($currentRequest->cooperative->company_name) }} will be cancelled.', confirmText: 'Yes, cancel', icon: 'warning', confirmColor: '#f59e0b'})" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] hover:opacity-80 rounded-xl text-xs font-bold transition cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            Cancel Request
                        </button>
                    </form>
                </div>
            @endif
        @endif
    </div>
</div>
</x-layout>
