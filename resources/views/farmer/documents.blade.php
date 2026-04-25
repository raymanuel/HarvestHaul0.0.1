<x-layout title="My Documents">

    <div style="max-width: 760px; margin: 0 auto;">

        <h1 style="font-size:1.6rem; font-weight:800; color:#0f172a; margin-bottom:0.25rem;">My Documents</h1>
        <p style="color:#64748b; font-size:0.9rem; margin-bottom:2rem;">
            Submit your government ID and proof of farming activity for verification.
        </p>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; padding:0.85rem 1rem; border-radius:0.75rem; margin-bottom:1.5rem; font-size:0.875rem;">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:0.85rem 1rem; border-radius:0.75rem; margin-bottom:1.5rem; font-size:0.875rem;">
                {{ session('error') }}
            </div>
        @endif

        {{-- Upload Form --}}
        <div style="background:white; border-radius:1.25rem; padding:1.75rem; border:1px solid rgba(0,0,0,0.07); box-shadow:0 2px 8px rgba(0,0,0,0.04); margin-bottom:2rem;">
            <h2 style="font-size:1rem; font-weight:700; color:#0f172a; margin-bottom:1.25rem;">Upload a Document</h2>

            <form method="POST" action="{{ route('farmer.documents.store') }}" enctype="multipart/form-data">
                @csrf

                {{-- Document Type --}}
                <div style="margin-bottom:1.25rem;">
                    <label style="display:block; font-size:0.8rem; font-weight:600; color:#374151; margin-bottom:0.4rem; text-transform:uppercase; letter-spacing:0.05em;">
                        Document Type <span style="color:#ef4444;">*</span>
                    </label>
                    <select name="document_type" required
                        style="width:100%; padding:0.75rem 1rem; border:1px solid #e2e8f0; border-radius:0.75rem; font-size:0.9rem; color:#0f172a; background:white; outline:none;">
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
                        <p style="color:#ef4444; font-size:0.8rem; margin-top:0.35rem;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- File Upload --}}
                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; font-size:0.8rem; font-weight:600; color:#374151; margin-bottom:0.4rem; text-transform:uppercase; letter-spacing:0.05em;">
                        File <span style="color:#ef4444;">*</span>
                    </label>
                    <input type="file" name="document_file" required accept=".jpg,.jpeg,.png,.pdf"
                        style="width:100%; padding:0.75rem 1rem; border:1px solid #e2e8f0; border-radius:0.75rem; font-size:0.875rem; color:#0f172a; background:white; box-sizing:border-box;">
                    <p style="color:#94a3b8; font-size:0.78rem; margin-top:0.35rem;">
                        Accepted: JPG, PNG, PDF — max 5MB
                    </p>
                    @error('document_file')
                        <p style="color:#ef4444; font-size:0.8rem; margin-top:0.35rem;">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    style="background:#2D8A37; color:white; padding:0.75rem 1.5rem; border:none; border-radius:0.75rem; font-weight:600; font-size:0.9rem; cursor:pointer;">
                    Upload Document
                </button>
            </form>
        </div>

        {{-- Submitted Documents --}}
        <div style="background:white; border-radius:1.25rem; padding:1.75rem; border:1px solid rgba(0,0,0,0.07); box-shadow:0 2px 8px rgba(0,0,0,0.04);">
            <h2 style="font-size:1rem; font-weight:700; color:#0f172a; margin-bottom:1.25rem;">Submitted Documents</h2>

            @if($documents->isEmpty())
                <p style="color:#94a3b8; font-size:0.875rem;">No documents submitted yet.</p>
            @else
                <div style="display:flex; flex-direction:column; gap:1rem;">
                    @foreach($documents as $doc)
                        @php
                            $statusColor = match($doc->status) {
                                'approved' => ['bg'=>'#f0fdf4','border'=>'#bbf7d0','text'=>'#166534'],
                                'rejected' => ['bg'=>'#fef2f2','border'=>'#fecaca','text'=>'#991b1b'],
                                default    => ['bg'=>'#f8fafc','border'=>'#e2e8f0','text'=>'#475569'],
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

                        <div style="border:1px solid {{ $statusColor['border'] }}; background:{{ $statusColor['bg'] }}; border-radius:0.875rem; padding:1rem 1.25rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.75rem;">
                            <div>
                                <p style="font-weight:600; color:#0f172a; font-size:0.9rem; margin:0 0 0.2rem;">{{ $typeLabel }}</p>
                                <p style="color:#64748b; font-size:0.8rem; margin:0 0 0.2rem;">{{ $doc->original_filename }}</p>
                                @if($doc->notes)
                                    <p style="color:#64748b; font-size:0.78rem; margin:0.3rem 0 0; font-style:italic;">Admin note: {{ $doc->notes }}</p>
                                @endif
                            </div>
                            <div style="display:flex; align-items:center; gap:0.75rem;">
                                <span style="background:{{ $statusColor['bg'] }}; color:{{ $statusColor['text'] }}; border:1px solid {{ $statusColor['border'] }}; padding:0.3rem 0.75rem; border-radius:999px; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em;">
                                    {{ $doc->status }}
                                </span>
                                <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                                    style="color:#2D8A37; font-size:0.8rem; font-weight:600; text-decoration:none;">
                                    View
                                </a>
                                @if($doc->status !== 'approved')
                                    <form method="POST" action="{{ route('farmer.documents.destroy', $doc->id) }}"
                                        onsubmit="return confirm('Remove this document?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            style="background:none; border:none; color:#ef4444; font-size:0.8rem; font-weight:600; cursor:pointer; padding:0;">
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
