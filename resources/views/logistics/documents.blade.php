<x-layout title="My Business Documents">

    <div class="w-full max-w-3xl mx-auto pb-12">

        <header class="pt-8 mb-6 border-b border-slate-200/80 dark:border-slate-700/80 pb-5">
            <a href="{{ route('dashboard') }}" class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 mb-4 inline-block font-semibold transition">
                ← Back to Dashboard
            </a>
            <span class="text-xs font-bold uppercase tracking-wider text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-3 py-1.5 rounded-lg border border-blue-500/10 dark:border-blue-500/20 inline-block mb-2">Document Vault</span>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">My Business Documents</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium">Submit your business registration documents for verification.</p>
        </header>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200/50 dark:border-green-800/30 text-green-700 dark:text-green-400 rounded-xl px-5 py-4 text-sm font-medium">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200/50 dark:border-red-800/30 text-red-700 dark:text-red-400 rounded-xl px-5 py-4 text-sm font-medium">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        {{-- Business Permit Reference Banner --}}
        @if($profile && $profile->business_permit_no)
            <div class="mb-6 bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/60 rounded-2xl px-5 py-4 flex items-center gap-3 flex-wrap">
                <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Declared Business Permit No.:</span>
                <span class="text-sm font-bold text-slate-800 dark:text-slate-200 font-mono">{{ $profile->business_permit_no }}</span>
                @if($profile->business_permit_verified)
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#3A7D44] dark:text-[#3A7D44] bg-[#3A7D44]/10 dark:bg-[#3A7D44]/5 border border-[#3A7D44]/10 dark:border-[#3A7D44]/20 px-2.5 py-1 rounded-lg">Verified</span>
                @else
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 border border-amber-500/10 dark:border-amber-500/20 px-2.5 py-1 rounded-lg">Pending Verification</span>
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
                        class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/30 dark:focus:ring-[#3A7D44]/30 focus:border-[#3A7D44] dark:focus:border-[#3A7D44] transition">
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
                        class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-900/60 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#3A7D44]/10 file:text-[#3A7D44] dark:file:bg-[#3A7D44]/10 dark:file:text-[#3A7D44] hover:file:bg-[#3A7D44]/15 dark:hover:file:bg-[#3A7D44]/15 transition">
                    <p class="text-slate-400 dark:text-slate-500 text-xs mt-1.5 font-medium">
                        Accepted: JPG, PNG, PDF — max 5MB
                    </p>
                    @error('document_file')
                        <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="bg-gradient-to-tr from-[#3A7D44] to-[#2E6336] dark:from-[#3A7D44] dark:to-[#2E6336] text-white dark:text-white text-sm font-bold px-6 py-3.5 rounded-xl border border-[#3A7D44]/20 dark:border-[#3A7D44]/25 shadow-md shadow-[#3A7D44]/15 dark:shadow-[#3A7D44]/30 hover:shadow-lg hover:shadow-[#3A7D44]/25 dark:hover:shadow-[#3A7D44]/30 hover:translate-y-[-1px] active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/30 dark:focus:ring-[#3A7D44]/40 transition-all duration-200"
                    >
                    Upload Document
                </button>
            </form>
        </div>

        {{-- Submitted Documents --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6">
            <h2 class="text-base font-bold text-slate-800 dark:text-slate-200 mb-5 heading-font">Submitted Documents</h2>

            @if($documents->isEmpty())
                <div class="bg-slate-50 dark:bg-slate-900/40 border border-dashed border-slate-300 dark:border-slate-700/80 rounded-xl p-10 text-center">
                    <p class="text-3xl mb-3">📄</p>
                    <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">No documents submitted yet.</p>
                </div>
            @else
                <div class="flex flex-col gap-3">
                    @foreach($documents as $doc)
                        @php
                            $statusStyle = match($doc->status) {
                                'approved' => ['badge' => 'text-[#3A7D44] dark:text-[#3A7D44] bg-[#3A7D44]/10 dark:bg-[#3A7D44]/5 border-[#3A7D44]/10 dark:border-[#3A7D44]/20', 'card' => 'bg-[#3A7D44]/10/30 dark:bg-[#3A7D44]/5 border-[#3A7D44]/20 dark:border-[#3A7D44]/15'],
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
                                    <p class="text-slate-400 dark:text-slate-500 text-xs mt-1 font-mono">
                                        Permit No: {{ $profile->business_permit_no }}
                                    </p>
                                @endif
                                @if($doc->notes)
                                    <p class="text-slate-500 dark:text-slate-400 text-xs mt-1.5 italic">Admin note: {{ $doc->notes }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-3 flex-shrink-0">
                                <span class="text-[10px] font-bold uppercase tracking-wider {{ $statusStyle['badge'] }} border px-2.5 py-1 rounded-lg">
                                    {{ $doc->status }}
                                </span>
                                <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                                    class="text-[#3A7D44] dark:text-[#3A7D44] text-xs font-bold hover:underline transition">
                                    View
                                </a>
                                @if($doc->status !== 'approved')
                                    <form method="POST" action="{{ route('logistics.documents.destroy', $doc->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                            onclick="swalConfirm(this.closest('form'), {title: 'Remove Document?', text: 'This document will be permanently deleted.', confirmText: 'Yes, remove', icon: 'warning', confirmColor: '#ef4444'})"
                                            class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-950/40 dark:text-rose-400 transition"
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
