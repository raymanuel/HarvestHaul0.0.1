<x-layout title="Logistics Documents">
<div class="w-full max-w-5xl mx-auto">

    <!-- Nice Admin Page Header -->
    <header class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white heading-font tracking-tight">Logistics Documents</h1>
                <p class="text-sm text-slate-400 dark:text-slate-500 mt-1 font-semibold">Review and approve submitted logistics company verification documents</p>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-widest text-violet-700 dark:text-violet-400 bg-violet-50 dark:bg-violet-950/20 px-3 py-1.5 rounded-lg border border-violet-500/10 dark:border-violet-500/20 self-start">Verification</span>
        </div>
    </header>

    @if(session('success'))
        <div class="mb-6 bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 border border-[#3A7D44]/20 dark:border-[#3A7D44]/20 text-[#3A7D44] dark:text-[#3A7D44] rounded-xl px-5 py-4 text-sm font-semibold flex items-center gap-2">
            <span>✅</span> {{ session('success') }}
        </div>
    @endif

    @if($documents->isEmpty())
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-12 text-center">
            <svg class="w-12 h-12 text-slate-200 dark:text-slate-700 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            <p class="text-slate-400 dark:text-slate-500 text-sm font-semibold">No documents submitted yet</p>
        </div>
    @else
        @foreach($documents as $userId => $docs)
            @php
                $partner = $docs->first()->logisticsPartner;
                $profile = $partner->logisticsProfile ?? null;
                $pendingCount = $docs->where('status', 'pending')->count();
            @endphp

            <div class="bg-white dark:bg-slate-800 border rounded-2xl shadow-sm overflow-hidden mb-6 {{ $pendingCount > 0 ? 'border-amber-200/70 dark:border-amber-800/80' : 'border-slate-200/70 dark:border-slate-700/80' }}">

                {{-- Partner Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/40 flex-wrap gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-tr from-violet-100 to-violet-50 dark:from-violet-950/20 dark:to-violet-900/20 border border-violet-200/50 dark:border-violet-800/30 flex items-center justify-center text-[10px] font-extrabold text-violet-700 dark:text-violet-400 uppercase">{{ substr($profile->company_name ?? $partner->name ?? '?', 0, 2) }}</div>
                        <div>
                            <p class="text-sm font-extrabold text-slate-800 dark:text-slate-200">{{ $profile->company_name ?? $partner->name }}</p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold">{{ $partner->email }} — ID #{{ $userId }}</p>
                            @if($profile && $profile->business_permit_no)
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 font-mono mt-0.5">
                                    Permit: {{ $profile->business_permit_no }}
                                    @if($profile->business_permit_verified)
                                        <span class="bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 text-[#3A7D44] dark:text-[#3A7D44] border border-[#3A7D44]/20 dark:border-[#3A7D44]500/20 text-[8px] font-bold px-1.5 py-0.5 rounded-md uppercase tracking-wide ml-1">Verified</span>
                                    @else
                                        <span class="bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 border border-amber-200/50 dark:border-amber-500/20 text-[8px] font-bold px-1.5 py-0.5 rounded-md uppercase tracking-wide ml-1">Unverified</span>
                                    @endif
                                </p>
                            @endif
                        </div>
                    </div>
                    @if($pendingCount > 0)
                        <span class="bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 border border-amber-200/50 dark:border-amber-500/20 text-[10px] font-bold px-3 py-1 rounded-lg uppercase tracking-widest">{{ $pendingCount }} Pending</span>
                    @else
                        <span class="bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 text-[#3A7D44] dark:text-[#3A7D44] border border-[#3A7D44]/20 dark:border-[#3A7D44]500/20 text-[10px] font-bold px-3 py-1 rounded-lg uppercase tracking-widest">Reviewed</span>
                    @endif
                </div>

                {{-- Documents --}}
                <div class="p-5 flex flex-col gap-4">
                    @foreach($docs as $doc)
                        @php
                            $statusStyle = match($doc->status) {
                                'approved' => 'bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 border-[#3A7D44]/20 dark:border-[#3A7D44]/15 text-[#3A7D44] dark:text-[#3A7D44]',
                                'rejected' => 'bg-red-50 dark:bg-red-950/20 border-red-100 dark:border-red-900/30 text-red-600 dark:text-red-400',
                                default    => 'bg-slate-50 dark:bg-slate-900/40 border-slate-100 dark:border-slate-800/50 text-slate-600 dark:text-slate-400',
                            };
                            $badgeStyle = match($doc->status) {
                                'approved' => 'bg-white dark:bg-slate-900 border-[#3A7D44]/20/20 dark:border-[#3A7D44]/20 text-[#3A7D44] dark:text-[#3A7D44]',
                                'rejected' => 'bg-white dark:bg-slate-900 border-red-200/20 dark:border-red-800/50 text-red-600 dark:text-red-400',
                                default    => 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400',
                            };
                            $typeLabel = match($doc->document_type) {
                                'dti_sec'         => 'DTI / SEC Registration',
                                'business_permit' => 'Business Permit',
                                'bir_cert'        => 'BIR Certificate of Registration',
                                'mayors_permit'   => "Mayor's Permit",
                            };
                        @endphp

                        <div class="border rounded-xl p-4 {{ $statusStyle }}">
                            <div class="flex items-center justify-between flex-wrap gap-3 {{ $doc->status === 'pending' ? 'mb-4' : '' }}">
                                <div>
                                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ $typeLabel }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">{{ $doc->original_filename }}</p>
                                    @if($doc->notes)
                                        <p class="text-xs text-slate-400 dark:text-slate-500 italic mt-1">Note: {{ $doc->notes }}</p>
                                    @endif
                                    @if($doc->document_type === 'business_permit' && $doc->business_permit_match_confirmed)
                                        <p class="text-xs text-[#3A7D44] dark:text-[#3A7D44] font-bold mt-1">✓ Permit number match confirmed</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide border {{ $badgeStyle }}">{{ $doc->status }}</span>
                                    <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                                        class="text-violet-600 dark:text-violet-400 hover:text-violet-800 dark:hover:text-violet-350 text-xs font-bold hover:underline transition">View File</a>
                                </div>
                            </div>

                            @if($doc->status === 'pending')
                                <div class="flex flex-col gap-3">
                                    {{-- Approve --}}
                                    <form method="POST" action="{{ route('admin.logistics-documents.approve', $doc->id) }}" class="flex items-center gap-2 flex-wrap">
                                        @csrf @method('PATCH')
                                        <input type="text" name="notes" placeholder="Admin note (optional)"
                                            class="border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-800 dark:text-slate-200 w-48 focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] bg-white dark:bg-slate-900 transition">
                                        @if($doc->document_type === 'business_permit')
                                            <label class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400 font-semibold cursor-pointer">
                                                <input type="checkbox" name="permit_match_confirmed" value="1"
                                                    class="w-3.5 h-3.5 accent-[#3A7D44] rounded">
                                                Permit No. matches
                                            </label>
                                        @endif
                                        <button type="submit"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-[#3A7D44]/10 text-[#3A7D44] hover:bg-[#3A7D44]/15 dark:bg-[#3A7D44]/10 dark:hover:bg-[#3A7D44]/15 dark:text-[#3A7D44] transition cursor-pointer"
                                            title="Approve Document">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </form>
                                    {{-- Reject --}}
                                    <form method="POST" action="{{ route('admin.logistics-documents.reject', $doc->id) }}" class="flex items-center gap-2">
                                        @csrf @method('PATCH')
                                        <input type="text" name="notes" placeholder="Reason for rejection (required)"
                                            class="border border-red-200 dark:border-red-900/30 rounded-lg px-3 py-2 text-xs text-slate-800 dark:text-slate-200 w-56 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 bg-white dark:bg-slate-900 transition">
                                        <button type="submit"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-950/40 dark:text-rose-400 transition cursor-pointer"
                                            title="Reject Document">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif

</div>
</x-layout>
