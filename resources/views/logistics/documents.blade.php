<x-layout title="My Business Documents">

    <div class="w-full max-w-3xl mx-auto pb-12">

        <header class="pt-8 mb-6 border-b border-slate-200/80 dark:border-slate-700/80 pb-5">
            <a href="{{ route('dashboard') }}" class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 mb-4 inline-block font-semibold transition">
                ← Back to Dashboard
            </a>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">My Business Documents</h1>
        </header>

        {{-- Flash Messages --}}
        <x-flash-success />
        <x-flash-error />

        {{-- Business Permit Reference Banner --}}
        @if($profile && $profile->business_permit_no)
            <div class="mb-6 bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/60 rounded-2xl px-5 py-4 flex items-center gap-3 flex-wrap">
                <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Declared Business Permit No.:</span>
                <span class="text-sm font-bold text-slate-800 dark:text-slate-200 font-mono">{{ $profile->business_permit_no }}</span>
                @if($profile->business_permit_verified)
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#16283C] dark:text-[#D7BC7A] bg-[#16283C]/10 dark:bg-[#16283C]/5 border border-[#16283C]/10 dark:border-[#16283C]/20 px-2.5 py-1 rounded-md">Verified</span>
                @else
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[var(--color-warning-text)] bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] px-2.5 py-1 rounded-md">Pending Verification</span>
                @endif
            </div>
        @endif

        {{-- Upload Form --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6 mb-6">
            <h2 class="text-base font-bold text-slate-800 dark:text-slate-200 mb-5 heading-font">Upload a Document</h2>

            <form method="POST" action="{{ route('logistics.documents.store') }}" enctype="multipart/form-data">
                @csrf

                {{-- Document Type --}}
                <div class="mb-5">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                        Document Type <span class="text-red-500">*</span>
                    </label>
                    <select name="document_type" required
                        class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#16283C]/30 dark:focus:ring-[#16283C]/30 focus:border-[#16283C] dark:focus:border-[#16283C] transition">
                        <option value="" disabled selected>Select document type</option>
                        <option value="dti_sec" {{ old('document_type') === 'dti_sec' ? 'selected' : '' }}>
                            DTI / SEC Registration
                        </option>
                        <option value="business_permit" {{ old('document_type') === 'business_permit' ? 'selected' : '' }}>
                            Business Permit
                        </option>
                        <option value="bir_cert" {{ old('document_type') === 'bir_cert' ? 'selected' : '' }}>
                            BIR Certificate of Registration
                        </option>
                        <option value="mayors_permit" {{ old('document_type') === 'mayors_permit' ? 'selected' : '' }}>
                            Mayor's Permit
                        </option>
                    </select>
                    @error('document_type')
                        <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- File Upload --}}
                <div class="mb-6">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                        File <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="document_file" required accept=".jpg,.jpeg,.png,.pdf"
                        class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-900/60 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-bold file:bg-[#16283C]/10 file:text-[#16283C] dark:file:bg-[#16283C]/10 dark:file:text-[#16283C] hover:file:bg-[#16283C]/15 dark:hover:file:bg-[#16283C]/15 transition">
                    <p class="text-slate-500 dark:text-slate-400 text-xs mt-1.5 font-medium">
                        Accepted: JPG, PNG, PDF — max 5MB
                    </p>
                    @error('document_file')
                        <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <x-button type="submit" size="lg" class=".5 border border-[#16283C]/20 dark:border-[#16283C]/25 dark:focus:ring-[#16283C]/40">
                    Upload Document
                </x-button>
            </form>
        </div>

        {{-- Submitted Documents --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6">
            <h2 class="text-base font-bold text-slate-800 dark:text-slate-200 mb-5 heading-font">Submitted Documents</h2>

            @if($documents->isEmpty())
                <div class="bg-slate-50 dark:bg-slate-900/40 border border-dashed border-slate-300 dark:border-slate-700/80 rounded-xl p-10 text-center">
                    <p class="text-2xl mb-3"><x-icon name="document" class="w-8 h-8" /></p>
                    <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">No documents submitted yet.</p>
                </div>
            @else
                <div class="flex flex-col gap-3">
                    @foreach($documents as $doc)
                        @php
                            $statusStyle = match($doc->status) {
                                'approved' => ['badge' => 'text-[#16283C] dark:text-[#D7BC7A] bg-[#16283C]/10 dark:bg-[#16283C]/5 border-[#16283C]/10 dark:border-[#16283C]/20', 'card' => 'bg-[#16283C]/10/30 dark:bg-[#16283C]/5 border-[#16283C]/20 dark:border-[#16283C]/15'],
                                'rejected' => ['badge' => 'text-rose-700 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20 border-rose-500/10 dark:border-rose-500/20', 'card' => 'bg-rose-50/30 dark:bg-rose-900/10 border-rose-200/50 dark:border-rose-800/30'],
                                default    => ['badge' => 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-700/40 border-slate-200/50 dark:border-slate-600/40', 'card' => 'bg-slate-50/50 dark:bg-slate-900/30 border-slate-200/50 dark:border-slate-700/40'],
                            };
                            $typeLabel = match($doc->document_type) {
                                'dti_sec'        => 'DTI / SEC Registration',
                                'business_permit' => 'Business Permit',
                                'bir_cert'       => 'BIR Certificate of Registration',
                                'mayors_permit'  => "Mayor's Permit",
                                default          => $doc->document_type,
                            };
                        @endphp

                        <div class="border {{ $statusStyle['card'] }} rounded-xl px-5 py-4 flex items-center justify-between flex-wrap gap-3">
                            <div class="min-w-0">
                                <p class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ $typeLabel }}</p>
                                <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5 truncate">{{ $doc->original_filename }}</p>
                                @if($doc->document_type === 'business_permit' && $profile)
                                    <p class="text-slate-500 dark:text-slate-400 text-xs mt-1 font-mono">
                                        Permit No: {{ $profile->business_permit_no }}
                                    </p>
                                @endif
                                @if($doc->notes)
                                    <p class="text-slate-500 dark:text-slate-400 text-xs mt-1.5 italic">Admin note: {{ $doc->notes }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-3 flex-shrink-0">
                                <span class="text-[10px] font-bold uppercase tracking-wider {{ $statusStyle['badge'] }} border px-2.5 py-1 rounded-md">
                                    {{ $doc->status }}
                                </span>
                                <a href="{{ route('files.show', ['type' => 'logistics-document', 'id' => $doc->id]) }}" target="_blank"
                                    class="text-[#16283C] dark:text-[#D7BC7A] text-xs font-bold hover:underline transition">
                                    View
                                </a>
                                @if($doc->status !== 'approved')
                                    <form method="POST" action="{{ route('logistics.documents.destroy', $doc->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                            onclick="swalConfirm(this.closest('form'), {title: 'Remove Document?', text: 'This document will be permanently deleted.', confirmText: 'Yes, remove', icon: 'warning', confirmColor: '#ef4444'})"
                                            class="inline-flex items-center justify-center w-7 h-7 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-950/40 dark:text-rose-400 transition"
                                            title="Remove Document">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

</x-layout>
