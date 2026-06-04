<x-layout title="My Documents">

    <div class="w-full max-w-3xl mx-auto">

        <!-- Page Header -->
        <header class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">My Documents</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium font-semibold">Submit your government ID and proof of farming activity for verification</p>
            </div>
            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 px-3 py-1.5 rounded-lg border border-emerald-500/10 dark:border-emerald-500/20 self-start">Verifications</span>
        </header>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-6 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200/50 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 rounded-xl px-5 py-4 text-sm font-semibold flex items-center gap-2 shadow-sm">
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
                        class="py-3 px-4 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition cursor-pointer text-sm text-slate-700 dark:text-slate-200">
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
                        class="py-2 px-4 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition text-sm text-slate-700 dark:text-slate-200 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-50 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-200 hover:file:bg-slate-100 dark:hover:file:bg-slate-700">
                    <p class="text-[10px] text-slate-400 mt-1.5 font-medium">
                        Accepted Formats: JPG, PNG, PDF — Maximum file size: 5MB
                    </p>
                    @error('document_file')
                        <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="py-3 px-6 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition cursor-pointer shadow-sm shadow-emerald-500/10">
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
                                'approved' => 'bg-emerald-50 dark:bg-emerald-950/20 border-emerald-100 dark:border-emerald-900/30 text-emerald-700 dark:text-emerald-400',
                                'rejected' => 'bg-red-50 dark:bg-red-950/20 border-red-100 dark:border-red-900/30 text-red-600 dark:text-red-400',
                                default    => 'bg-slate-50 dark:bg-slate-900/40 border-slate-100 dark:border-slate-800/50 text-slate-600 dark:text-slate-400',
                            };
                            $badgeStyle = match($doc->status) {
                                'approved' => 'bg-white dark:bg-slate-900 border-emerald-250/60 dark:border-emerald-800/50 text-emerald-700 dark:text-emerald-400',
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
                                    class="text-violet-600 dark:text-violet-400 hover:text-violet-850 dark:hover:text-violet-300 text-xs font-bold hover:underline transition">
                                    View
                                </a>
                                @if($doc->status !== 'approved')
                                    <form method="POST" action="{{ route('farmer.documents.destroy', $doc->id) }}"
                                        onsubmit="return confirm('Remove this document?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="text-red-500 hover:text-red-700 dark:hover:text-red-400 text-xs font-bold hover:underline transition cursor-pointer">
                                            Remove
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
