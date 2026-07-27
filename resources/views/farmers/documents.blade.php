{{--
    Farmer Documents Management View
    
    PURPOSE:
    This view allows farmers to upload verification documents (e.g. Government ID, RSBSA certification, etc.)
    and monitor their verification status.
    
    SYSTEM FLOW:
    1. Farmer selects a document type and uploads a file.
    2. Document is stored via FarmerDocumentController@store.
    3. Document status defaults to 'pending'.
    4. Admin views submitted documents in the Admin Panel and either approves or rejects.
    5. Approved documents mark the farmer profile verification status.
--}}
<x-layout title="My Documents">

    <div class="w-full max-w-3xl mx-auto">

        <!-- Page Header -->
        <header class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">My Documents</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium font-semibold">Submit your government ID and proof of farming activity for verification</p>
            </div>
            <span class="text-xs font-semibold uppercase tracking-wider text-[#3A7D44] dark:text-[#3A7D44] bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 px-3 py-1.5 rounded-lg border border-[#3A7D44]/10 dark:border-[#3A7D44]/20 self-start">Verifications</span>
        </header>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-6 bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 border border-[#3A7D44]/20 dark:border-[#3A7D44]/20 text-[#3A7D44] dark:text-[#3A7D44] rounded-xl px-5 py-4 text-sm font-semibold flex items-center gap-2 shadow-sm">
                <span class="text-xs">✓</span>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-50 dark:bg-red-950/20 border border-red-200/50 dark:border-red-900/30 text-red-700 dark:text-red-400 rounded-xl px-5 py-4 text-sm font-semibold flex items-center gap-2 shadow-sm">
                <span class="text-xs">⚠️</span>
                {{ session('error') }}
            </div>
        @endif

        {{-- Upload Form --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-7 mb-8">
            <h2 class="text-sm font-extrabold text-slate-800 dark:text-white heading-font mb-4 uppercase tracking-wider">Upload a Document</h2>

            <form method="POST" action="{{ route('farmer.documents.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                {{-- Document Type --}}
                <div class="form-group space-y-1.5">
                    <label class="text-[10px] font-bold text-slate-450 dark:text-slate-500 uppercase tracking-wider block">
                        Document Type <span class="text-red-500">*</span>
                    </label>
                    <select name="document_type" required
                        class="py-3 px-4 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] transition cursor-pointer text-sm text-slate-700 dark:text-slate-200">
                        <option value="" disabled selected>Select document type</option>
                        <optgroup label="Government ID (Primary)">
                            <option value="government_id" {{ old('document_type') === 'government_id' ? 'selected' : '' }}>
                                Government ID (PhilSys, UMID, Voter's ID, Passport, etc.)
                            </option>
                        </optgroup>
                        <optgroup label="Proof of Farming Activity (Secondary)">
                            <option value="rsbsa" {{ old('document_type') === 'rsbsa' ? 'selected' : '' }}>
                                RSBSA Certificate / Registration Stub
                            </option>
                            <option value="land_title" {{ old('document_type') === 'land_title' ? 'selected' : '' }}>
                                Land Title / Tax Declaration
                            </option>
                            <option value="barangay_cert" {{ old('document_type') === 'barangay_cert' ? 'selected' : '' }}>
                                Barangay Certification (Farmer / Tenant)
                            </option>
                            <option value="mao_cert" {{ old('document_type') === 'mao_cert' ? 'selected' : '' }}>
                                Certificate from Municipal Agriculture Office (MAO)
                            </option>
                        </optgroup>
                        <optgroup label="Other">
                            <option value="other" {{ old('document_type') === 'other' ? 'selected' : '' }}>
                                Other
                            </option>
                        </optgroup>
                    </select>
                    @error('document_type')
                        <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                {{-- File Upload --}}
                <div class="form-group space-y-1.5">
                    <label class="text-[10px] font-bold text-slate-450 dark:text-slate-500 uppercase tracking-wider block">
                        File <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="document_file" required accept=".jpg,.jpeg,.png,.pdf"
                        class="py-2 px-4 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] transition text-sm text-slate-700 dark:text-slate-200 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-50 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-200 hover:file:bg-slate-100 dark:hover:file:bg-slate-700">
                    <p class="text-[10px] text-slate-400 mt-1.5 font-medium">
                        Accepted Formats: JPG, PNG, PDF — Maximum file size: 5MB
                    </p>
                    @error('document_file')
                        <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="py-3 px-6 bg-[#3A7D44] hover:bg-[#2E6336] text-white font-bold rounded-xl text-xs transition cursor-pointer shadow-sm shadow-[#3A7D44]/10">
                        Upload Document
                    </button>
                </div>
            </form>
        </div>

        {{-- Submitted Documents --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-7">
            <h2 class="text-sm font-extrabold text-slate-800 dark:text-white heading-font mb-4 uppercase tracking-wider">Submitted Documents</h2>

            @if($documents->isEmpty())
                <p class="text-sm text-slate-400 dark:text-slate-500 font-semibold text-center py-6">No documents submitted yet.</p>
            @else
                <div class="flex flex-col gap-4">
                    @foreach($documents as $doc)
                        @php
                            $statusStyle = match($doc->status) {
                                'approved' => 'bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 border-[#3A7D44]/20 dark:border-[#3A7D44]/20 text-[#3A7D44] dark:text-[#3A7D44]',
                                'rejected' => 'bg-red-50 dark:bg-red-950/20 border-red-100 dark:border-red-900/30 text-red-600 dark:text-red-400',
                                default    => 'bg-slate-50 dark:bg-slate-900/40 border-slate-100 dark:border-slate-800/50 text-slate-600 dark:text-slate-400',
                            };
                            $badgeStyle = match($doc->status) {
                                'approved' => 'bg-white dark:bg-slate-900 border-[#3A7D44]/20 dark:border-[#3A7D44]/20 text-[#3A7D44] dark:text-[#3A7D44]',
                                'rejected' => 'bg-white dark:bg-slate-900 border-red-250/60 dark:border-red-800/50 text-red-650 dark:text-red-400',
                                default    => 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400',
                            };
                            $typeLabel = match($doc->document_type) {
                                'government_id'  => 'Government ID',
                                'rsbsa'          => 'RSBSA Certificate',
                                'land_title'     => 'Land Title / Tax Declaration',
                                'barangay_cert'  => 'Barangay Certification',
                                'mao_cert'       => 'MAO Certificate',
                                default          => 'Other',
                            };
                        @endphp

                        <div class="border rounded-xl p-4 flex items-center justify-between flex-wrap gap-3 {{ $statusStyle }}">
                            <div>
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ $typeLabel }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-450 font-medium mt-0.5">{{ $doc->original_filename }}</p>
                                @if($doc->notes)
                                    <p class="text-xs text-slate-500 dark:text-slate-450 italic mt-1.5 font-medium">Admin note: {{ $doc->notes }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-3.5">
                                <span class="text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide border {{ $badgeStyle }}">
                                    {{ $doc->status }}
                                </span>
                                <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                                    class="text-brand dark:text-brand-light hover:text-brand-dark dark:hover:text-brand-light text-xs font-bold hover:underline transition">
                                    View
                                </a>
                                @if($doc->status !== 'approved')
                                    <form method="POST" action="{{ route('farmer.documents.destroy', $doc->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                            onclick="swalConfirm(this.closest('form'), {title: 'Remove Document?', text: 'This document will be permanently deleted.', confirmText: 'Yes, remove', icon: 'warning', confirmColor: '#ef4444'})"
                                            class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-950/40 dark:text-rose-400 transition cursor-pointer"
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
