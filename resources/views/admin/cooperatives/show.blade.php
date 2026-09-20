<x-layout :title="$cooperative->name . ' — Cooperatives'">
    <x-page-header variant="back-link" :title="$cooperative->name"
        :back-href="route('admin.cooperatives.index')" back-label="← Back to Cooperatives">
        <x-badge :status="$cooperative->status" dot />
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card>
                <x-section-label title="Cooperative Information" width="w-24" />
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Type</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ ucfirst($cooperative->type ?? '—') }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Year Established</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $cooperative->year_established ?? '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Address</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $cooperative->fullAddress() ?: '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Contact Number</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $cooperative->contact_number ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Official Email</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $cooperative->official_email ?? '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Business Activities</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $cooperative->business_activities ?? '—' }}</dd></div>
                </dl>
            </x-card>

            <x-card>
                <x-section-label title="Legal & Registration" width="w-24" />
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">CDA Registration No.</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $cooperative->cda_registration_number ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Registration Date</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $cooperative->registration_date?->format('M d, Y') ?? '—' }}</dd></div>
                </dl>

                @php
                    $documents = [
                        'cert' => 'Certificate of Registration',
                        'articles' => 'Articles of Cooperation',
                        'bylaws' => 'By-Laws',
                        'rep_id' => 'Representative ID',
                        'rep_auth' => 'Authorization Document',
                    ];
                @endphp

                <div class="mt-5 flex flex-wrap gap-2">
                    @foreach($documents as $slot => $label)
                        @php($path = match($slot) {
                            'cert' => $cooperative->cert_document_path,
                            'articles' => $cooperative->articles_document_path,
                            'bylaws' => $cooperative->bylaws_document_path,
                            'rep_id' => $cooperative->rep_id_document_path,
                            'rep_auth' => $cooperative->rep_authorization_document_path,
                        })
                        @if($path)
                            <a href="{{ route('files.show', ['type' => 'coop-document', 'id' => $cooperative->id, 'slot' => $slot]) }}" target="_blank"
                               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:border-brand-700/40 transition">
                                {{ $label }}
                            </a>
                        @else
                            <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold border border-dashed border-slate-200 dark:border-slate-700 text-slate-400">
                                {{ $label }} · missing
                            </span>
                        @endif
                    @endforeach
                </div>
            </x-card>

            <x-card>
                <x-section-label title="Authorized Representative" width="w-24" />
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Name</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $cooperative->rep_name ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Position</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $cooperative->rep_position ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Contact</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $cooperative->rep_contact ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Email</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $cooperative->rep_email ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">ID Type</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $cooperative->rep_id_type ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">ID Number</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $cooperative->rep_id_number ?? '—' }}</dd></div>
                </dl>
            </x-card>

            @if($cooperative->admin_notes || $cooperative->rejection_reason)
                <x-card>
                    <x-section-label title="Review Notes" width="w-24" />
                    @if($cooperative->admin_notes)
                        <p class="text-sm text-slate-700 dark:text-slate-200"><span class="font-bold">Information requested:</span> {{ $cooperative->admin_notes }}</p>
                    @endif
                    @if($cooperative->rejection_reason)
                        <p class="text-sm text-[var(--color-error-text)] mt-2"><span class="font-bold">Rejection reason:</span> {{ $cooperative->rejection_reason }}</p>
                    @endif
                    @if($cooperative->reviewed_at)
                        <p class="text-xs text-slate-400 mt-3">Last reviewed {{ $cooperative->reviewed_at->format('M d, Y g:i A') }}</p>
                    @endif
                </x-card>
            @endif
        </div>

        <div class="space-y-6">
            <x-card>
                <x-section-label title="Decision" width="w-16" />

                @if(! in_array($cooperative->status, [\App\Models\Cooperative::STATUS_APPROVED, \App\Models\Cooperative::STATUS_SUSPENDED], true))
                    <form method="POST" action="{{ route('admin.cooperatives.approve', $cooperative) }}" class="mb-3">
                        @csrf
                        <x-button variant="primary" full>Approve Cooperative</x-button>
                    </form>

                    <button type="button" onclick="openModal('request-info-modal')" class="w-full mb-3 px-5 py-2.5 rounded-xl text-xs font-bold border-2 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:border-brand-700/40 transition">
                        Request More Information
                    </button>

                    <button type="button" onclick="openModal('reject-modal')" class="w-full mb-3 px-5 py-2.5 rounded-xl text-xs font-bold bg-[var(--color-error-bg)] text-[var(--color-error-text)] border border-[var(--color-error-border)] hover:opacity-90 transition">
                        Reject Application
                    </button>
                @endif

                @if($cooperative->status === \App\Models\Cooperative::STATUS_APPROVED)
                    <form method="POST" action="{{ route('admin.cooperatives.suspend', $cooperative) }}" id="suspend-form">
                        @csrf
                        <button type="button"
                            onclick="swalConfirm(document.getElementById('suspend-form'), {title:'Suspend Cooperative', text:'Suspend {{ $cooperative->name }}? Its admin and staff cannot operate until you approve it again.', icon:'warning', confirmText:'Yes, suspend', cancelText:'Keep active', confirmColor:'#ef4444'})"
                            class="w-full px-5 py-2.5 rounded-xl text-xs font-bold bg-[var(--color-error-bg)] text-[var(--color-error-text)] border border-[var(--color-error-border)] hover:opacity-90 transition cursor-pointer">
                            Suspend Cooperative
                        </button>
                    </form>
                @elseif($cooperative->status === \App\Models\Cooperative::STATUS_SUSPENDED)
                    <form method="POST" action="{{ route('admin.cooperatives.reactivate', $cooperative) }}">
                        @csrf
                        <x-button variant="primary" full>Reactivate Cooperative</x-button>
                    </form>
                @endif
            </x-card>

            @if($cooperative->coopAdminUser)
                <x-card>
                    <x-section-label title="Account Owner" width="w-16" />
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $cooperative->coopAdminUser->name }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $cooperative->coopAdminUser->email }}</p>
                </x-card>
            @endif
        </div>
    </div>

    <x-modal id="request-info-modal" title="Request More Information">
        <form method="POST" action="{{ route('admin.cooperatives.request-info', $cooperative) }}">
            @csrf
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Tell the cooperative exactly what is missing. It stays Under Review until you decide.</p>
            <textarea name="admin_notes" rows="4" required class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-700/25" placeholder="Example: Please upload a clearer copy of your CDA registration.">{{ old('admin_notes') }}</textarea>
            <div class="flex justify-end gap-3 mt-5">
                <button type="button" onclick="closeModal('request-info-modal')" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-800">Cancel</button>
                <x-button variant="primary" size="sm">Send Request</x-button>
            </div>
        </form>
    </x-modal>

    <x-modal id="reject-modal" title="Reject Application">
        <form method="POST" action="{{ route('admin.cooperatives.reject', $cooperative) }}">
            @csrf
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Give a clear reason. The applicant sees it and can register again with corrected documents.</p>
            <textarea name="rejection_reason" rows="4" required class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-700/25" placeholder="Example: CDA registration number does not match the submitted certificate.">{{ old('rejection_reason') }}</textarea>
            <div class="flex justify-end gap-3 mt-5">
                <button type="button" onclick="closeModal('reject-modal')" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-800">Cancel</button>
                <x-button variant="danger" size="sm">Reject Application</x-button>
            </div>
        </form>
    </x-modal>
</x-layout>
