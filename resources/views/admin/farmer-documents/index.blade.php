<x-layout title="Farmer Documents">

    <div style="max-width:960px; margin:0 auto;">

        <h1 style="font-size:1.6rem; font-weight:800; color:#0f172a; margin-bottom:0.25rem;">Farmer Documents</h1>
        <p style="color:#64748b; font-size:0.9rem; margin-bottom:2rem;">
            Review and approve submitted farmer verification documents.
        </p>

        {{-- Flash --}}
        @if(session('success'))
            <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; padding:0.85rem 1rem; border-radius:0.75rem; margin-bottom:1.5rem; font-size:0.875rem;">
                {{ session('success') }}
            </div>
        @endif

        @if($documents->isEmpty())
            <div style="background:white; border-radius:1.25rem; padding:2rem; border:1px solid rgba(0,0,0,0.07); text-align:center;">
                <p style="color:#94a3b8; font-size:0.9rem;">No documents submitted yet.</p>
            </div>
        @else
            @foreach($documents as $userId => $docs)
                @php
                    $farmer = $docs->first()->farmer;
                    $pendingCount = $docs->where('status', 'pending')->count();
                @endphp

                <div style="background:white; border-radius:1.25rem; padding:1.75rem; border:1px solid {{ $pendingCount > 0 ? 'rgba(234,179,8,0.4)' : 'rgba(0,0,0,0.07)' }}; box-shadow:0 2px 8px rgba(0,0,0,0.04); margin-bottom:1.5rem;">

                    {{-- Farmer Header --}}
                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.5rem; margin-bottom:1.25rem; padding-bottom:1rem; border-bottom:1px solid #f1f5f9;">
                        <div>
                            <p style="font-size:1rem; font-weight:700; color:#0f172a; margin:0 0 0.2rem;">
                                {{ $farmer->name ?? 'Farmer #' . $userId }}
                            </p>
                            <p style="font-size:0.8rem; color:#64748b; margin:0;">
                                {{ $farmer->email ?? '' }} &mdash; ID #{{ $userId }}
                            </p>
                        </div>
                        @if($pendingCount > 0)
                            <span style="background:#fef9c3; color:#854d0e; border:1px solid #fde68a; padding:0.3rem 0.75rem; border-radius:999px; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em;">
                                {{ $pendingCount }} Pending
                            </span>
                        @else
                            <span style="background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; padding:0.3rem 0.75rem; border-radius:999px; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em;">
                                Reviewed
                            </span>
                        @endif
                    </div>

                    {{-- Documents --}}
                    <div style="display:flex; flex-direction:column; gap:1rem;">
                        @foreach($docs as $doc)
                            @php
                                $statusColor = match($doc->status) {
                                    'approved' => ['bg'=>'#f0fdf4','border'=>'#bbf7d0','text'=>'#166534'],
                                    'rejected' => ['bg'=>'#fef2f2','border'=>'#fecaca','text'=>'#991b1b'],
                                    default    => ['bg'=>'#f8fafc','border'=>'#e2e8f0','text'=>'#475569'],
                                };
                                $typeLabel = match($doc->document_type) {
                                    'government_id' => 'Government ID',
                                    'rsbsa'         => 'RSBSA Certificate',
                                    'land_title'    => 'Land Title / Tax Declaration',
                                    'barangay_cert' => 'Barangay Certification',
                                    'mao_cert'      => 'MAO Certificate',
                                    default         => 'Other',
                                };
                            @endphp

                            <div style="border:1px solid {{ $statusColor['border'] }}; background:{{ $statusColor['bg'] }}; border-radius:0.875rem; padding:1rem 1.25rem;">

                                {{-- Doc Info Row --}}
                                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.75rem; margin-bottom:{{ $doc->status === 'pending' ? '1rem' : '0' }};">
                                    <div>
                                        <p style="font-weight:600; color:#0f172a; font-size:0.9rem; margin:0 0 0.2rem;">{{ $typeLabel }}</p>
                                        <p style="color:#64748b; font-size:0.8rem; margin:0;">{{ $doc->original_filename }}</p>
                                        @if($doc->notes)
                                            <p style="color:#64748b; font-size:0.78rem; margin:0.3rem 0 0; font-style:italic;">
                                                Note: {{ $doc->notes }}
                                            </p>
                                        @endif
                                    </div>
                                    <div style="display:flex; align-items:center; gap:0.75rem;">
                                        <span style="color:{{ $statusColor['text'] }}; border:1px solid {{ $statusColor['border'] }}; background:white; padding:0.3rem 0.75rem; border-radius:999px; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em;">
                                            {{ $doc->status }}
                                        </span>
                                        <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                                            style="color:#2D8A37; font-size:0.8rem; font-weight:600; text-decoration:none;">
                                            View File
                                        </a>
                                    </div>
                                </div>

                                {{-- Action Forms — only if pending --}}
                                @if($doc->status === 'pending')
                                    <div style="display:flex; gap:0.75rem; flex-wrap:wrap; align-items:flex-start;">

                                        {{-- Approve --}}
                                        <form method="POST" action="{{ route('admin.farmer-documents.approve', $doc->id) }}" style="display:flex; gap:0.5rem; align-items:center;">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="notes" placeholder="Admin note (optional)"
                                                style="padding:0.5rem 0.75rem; border:1px solid #e2e8f0; border-radius:0.6rem; font-size:0.8rem; color:#0f172a; width:220px;">
                                            <button type="submit"
                                                style="background:#2D8A37; color:white; border:none; padding:0.5rem 1rem; border-radius:0.6rem; font-size:0.8rem; font-weight:600; cursor:pointer;">
                                                Approve
                                            </button>
                                        </form>

                                        {{-- Reject --}}
                                        <form method="POST" action="{{ route('admin.farmer-documents.reject', $doc->id) }}" style="display:flex; gap:0.5rem; align-items:center;">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="notes" placeholder="Reason for rejection (required)"
                                                style="padding:0.5rem 0.75rem; border:1px solid #fecaca; border-radius:0.6rem; font-size:0.8rem; color:#0f172a; width:220px;">
                                            <button type="submit"
                                                style="background:#ef4444; color:white; border:none; padding:0.5rem 1rem; border-radius:0.6rem; font-size:0.8rem; font-weight:600; cursor:pointer;">
                                                Reject
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
