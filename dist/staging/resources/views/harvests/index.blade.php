<x-layout>
<div class="w-full">

    <header class="pt-8 mb-8">
        <a href="{{ route('dashboard') }}" class="text-sm text-slate-400 hover:text-slate-700 dark:hover:text-slate-400 mb-4 inline-block font-semibold transition">
            ← Back to Dashboard
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">My Posts</h1>
            </div>

            @if (Auth::user()->farmerProfile?->is_verified)
                <a href="{{ route('harvests.create') }}"
                    class="bg-brand text-white dark:bg-gold-light dark:text-[#17202B] dark:hover:bg-gold px-6 py-3 rounded-xl font-semibold hover:bg-brand-dark transition shadow-md text-sm self-start sm:self-center whitespace-nowrap">
                    + Post New Harvest
                </a>
            @else
                <div class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-5 py-3 self-start sm:self-center">
                    <span class="text-slate-500 dark:text-slate-400 text-sm font-semibold">+ Post New Harvest</span>
                    <span class="text-xs bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border border-[var(--color-warning-border)] font-bold px-2.5 py-1 rounded-md uppercase tracking-wider">
                        Pending Approval
                    </span>
                </div>
            @endif
        </div>
    </header>

    {{-- TABS: Active / History --}}
    <div class="flex items-center gap-2 mb-6 border-b border-slate-200/70 dark:border-slate-700/70">
        <a href="{{ route('harvests.index', ['tab' => 'active']) }}"
            class="px-4 py-2.5 text-sm font-semibold transition border-b-2 -mb-px {{ ($tab ?? 'active') === 'active' ? 'border-[#16283C] text-[#16283C] dark:border-gold-light dark:text-[#D7BC7A]' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}">
            Active
        </a>
        <a href="{{ route('harvests.index', ['tab' => 'history']) }}"
            class="px-4 py-2.5 text-sm font-semibold transition border-b-2 -mb-px {{ ($tab ?? 'active') === 'history' ? 'border-[#16283C] text-[#16283C] dark:border-gold-light dark:text-[#D7BC7A]' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}">
            History
        </a>
    </div>

    {{-- PENDING VERIFICATION BANNER --}}
    @if (!Auth::user()->farmerProfile?->is_verified)
        <div class="mb-6 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] text-[var(--color-warning-text)] rounded-2xl px-5 py-4 flex gap-3.5 items-start shadow-sm">
            <span class="text-amber-500 mt-0.5 select-none"><x-icon name="document" class="w-5 h-5" /></span>
            <div>
                <p class="text-sm font-bold text-[var(--color-warning-text)] heading-font">Account Pending Verification</p>
                <p class="text-xs text-[var(--color-warning-text)] mt-1 leading-relaxed font-medium">
                    An administrator needs to approve your farmer account before you can post harvests.
                </p>
            </div>
        </div>
    @endif

    {{-- Flash Messages --}}
    <x-flash-success />
    <x-flash-error />

    {{-- Posts Table --}}
    @if($harvests->isEmpty())
        <div class="bg-slate-50 dark:bg-slate-900/40 border border-dashed border-slate-300 dark:border-slate-700/80 rounded-xl p-16 text-center">
            
            <p class="text-slate-500 dark:text-slate-400 font-medium mb-4">{{ ($tab ?? 'active') === 'history' ? 'No completed or cancelled harvests yet.' : 'You have no active harvest posts yet.' }}</p>
            @if (Auth::user()->farmerProfile?->is_verified && ($tab ?? 'active') === 'active')
                <a href="{{ route('harvests.create') }}"
                    class="bg-brand text-white dark:bg-gold-light dark:text-[#17202B] dark:hover:bg-gold px-6 py-3 rounded-xl font-semibold hover:bg-brand-dark transition shadow-md text-sm inline-block">
                    Post Your First Harvest
                </a>
            @endif
        </div>
    @else
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm overflow-hidden mb-10">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left" style="min-width: 640px;">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-slate-900/40 border-b border-slate-100 dark:border-slate-700/60">
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Crop</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Variety</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Est. Quantity</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Harvest Date</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Notes</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Destination</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Posted</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-700/40">
                        @foreach($harvests as $harvest)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                            <td class="px-6 py-4 font-bold text-slate-800 dark:text-slate-200">
                                {{ $harvest->crop->name ?? $harvest->crop_type ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-400 font-medium">
                                {{ $harvest->cropVariety->name ?? $harvest->variety ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-400 font-semibold">{{ number_format($harvest->quantity_kg, 2) }} kg
                                @if($harvest->remaining_quantity_kg && (float)$harvest->remaining_quantity_kg < (float)$harvest->quantity_kg)
                                    <span class="text-[9px] text-[var(--color-warning-text)] block">({{ number_format($harvest->remaining_quantity_kg, 2) }} kg remaining)</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400 text-xs font-medium">
                                {{ $harvest->harvest_date ? $harvest->harvest_date->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400 text-xs font-medium max-w-xs truncate">{{ $harvest->notes ?? '—' }}</td>
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-500 text-xs font-semibold"> {{ $harvest->destination_label }} </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusClasses = [
                                        'active'         => 'bg-[#16283C]/10 text-[#16283C] border-[#16283C]/10 dark:bg-gold-light/10 dark:text-[#D7BC7A] dark:border-gold-light/20',
                                        'negotiating'    => 'bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border-[var(--color-warning-border)]',
                                        'partially_sold' => 'bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border-[var(--color-warning-border)]',
                                        'sold'           => 'bg-indigo-50 text-indigo-700 border-indigo-500/10 dark:bg-indigo-950/20 dark:text-indigo-400',
                                        'booked'         => 'bg-purple-50 text-purple-700 border-purple-500/10 dark:bg-purple-950/20 dark:text-purple-400',
                                        'assigned'       => 'bg-purple-50 text-purple-700 border-purple-500/10 dark:bg-purple-950/20 dark:text-purple-400',
                                        'in_progress'    => 'bg-orange-50 text-orange-700 border-orange-500/10 dark:bg-orange-950/20 dark:text-orange-400',
                                        'completed'      => 'bg-brand-dark/10 text-brand-dark border-brand-dark/10 dark:bg-slate-800 dark:text-[#E9EEF4] dark:border-slate-700',
                                        'cancelled'      => 'bg-rose-50 text-rose-700 border-rose-500/10 dark:bg-rose-950/20 dark:text-rose-400',
                                    ];
                                    $statusKey = $harvest->status->value ?? 'active';
                                    $statusClass = $statusClasses[$statusKey] ?? $statusClasses['active'];
                                    $statusLabel = match($statusKey) {
                                        'active'         => 'Active',
                                        'negotiating'    => 'Under Negotiation',
                                        'partially_sold' => 'Partial Sale',
                                        'sold'           => 'Sold',
                                        'booked'         => 'Booked',
                                        'assigned'       => 'Assigned',
                                        'in_progress'    => 'In Transit',
                                        'completed'      => 'Completed',
                                        'cancelled'      => 'Cancelled',
                                        default          => ucfirst($statusKey),
                                    };
                                @endphp
                                <span class="{{ $statusClass }} text-[10px] font-bold px-2.5 py-1 rounded-md uppercase tracking-wider border">{{ $statusLabel }}</span>
                            </td>

                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400 text-xs font-semibold whitespace-nowrap">{{ $harvest->created_at->format('M d, Y') }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $canEdit = $harvest->status->value === 'active';
                                    $canMarkSold = in_array($harvest->status->value, ['active', 'partially_sold']) && $harvest->visibility === 'buyers_only';
                                    $isCoopFarmer = auth()->user()->farmerProfile?->affiliation_type === 'cooperative';
                                    $canCreateHaulRequest = !$isCoopFarmer && $harvest->status->value === 'sold' && !$harvest->haulRequest;
                                @endphp
                                <div class="flex items-center gap-2">
                                    @if($canEdit)
                                        <a href="{{ route('harvests.edit', $harvest->id) }}"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-brand/10 text-[#16283C] hover:bg-brand/15 dark:bg-gold-light/10 dark:hover:bg-gold-light/15 dark:text-[#D7BC7A] transition"
                                            title="Edit Harvest">
                                            <x-icon name="edit" class="w-4 h-4" />
                                        </a>
                                        <form method="POST" action="{{ route('harvests.destroy', $harvest->id) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                onclick="swalConfirm(this.closest('form'), {title: 'Remove Post?', text: 'This post will no longer appear on the crop board.', confirmText: 'Yes, remove', icon: 'warning', confirmColor: '#ef4444'})"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-950/40 dark:text-rose-400 transition active:scale-[0.95]"
                                                title="Remove Harvest">
                                                <x-icon name="trash" class="w-4 h-4" />
                                            </button>
                                        </form>
                                    @endif
                                    @if($canMarkSold)
                                        <form method="POST" action="{{ route('harvests.mark-as-sold', $harvest->id) }}" class="inline">
                                            @csrf
                                            <button type="button"
                                                onclick="swalConfirm(this.closest('form'), {title: 'Mark as Sold?', text: 'This harvest was sold outside the platform. It will be hidden from buyers and shown to logistics partners.', confirmText: 'Yes, mark as sold', icon: 'info', confirmColor: '#16283C'})"
                                                class="inline-flex items-center gap-1 text-[10px] font-bold text-[#16283C] dark:text-[#D7BC7A] bg-brand/10 hover:bg-brand/15 dark:bg-gold-light/10 dark:hover:bg-gold-light/15 px-2.5 py-1.5 rounded-md transition active:scale-[0.97]"
                                                title="Mark as Sold">
                                                <x-icon name="check" class="w-3.5 h-3.5" />
                                                Sold
                                            </button>
                                        </form>
                                    @endif
                                    @if($canCreateHaulRequest)
                                        <button type="button"
                                            onclick="showCreateHaulRequest({{ $harvest->id }}, '{{ $harvest->crop->name ?? $harvest->crop_type }}')"
                                            class="inline-flex items-center gap-1 text-[10px] font-bold text-purple-700 dark:text-purple-400 bg-purple-50 hover:bg-purple-100 dark:bg-purple-950/20 dark:hover:bg-purple-950/40 px-2.5 py-1.5 rounded-xl transition active:scale-[0.97]"
                                            title="Request Haul">
                                            <x-icon name="truck" class="w-3.5 h-3.5" />
                                            Request Haul
                                        </button>
                                    @endif
                                    @if(!$canEdit && !$canMarkSold && !$canCreateHaulRequest)
                                        <span class="text-slate-400 dark:text-slate-600 text-xs select-none">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>

<script>
function showCreateHaulRequest(harvestId, cropName) {
    Swal.fire({
        title: 'Request Haul',
        html: '<p class="text-xs text-slate-500 mb-3">Request transport for <strong>' + cropName + '</strong> (sold outside platform)</p>' +
            '<input id="swal-date" type="date" class="swal2-input text-xs" placeholder="Pickup date" style="font-size:12px">' +
            '<textarea id="swal-notes" class="swal2-textarea text-xs" placeholder="Notes for logistics partners (optional)" rows="2" style="font-size:12px"></textarea>',
        showCancelButton: true,
        confirmButtonText: 'Post Request',
        confirmButtonColor: '#16283C',
        customClass: { popup: 'rounded-2xl' },
        preConfirm: function() {
            var date = document.getElementById('swal-date').value;
            var notes = document.getElementById('swal-notes').value;
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '/harvests/' + harvestId + '/request-haul';
            var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            form.innerHTML = '<input type="hidden" name="_token" value="' + csrf + '">' +
                '<input type="hidden" name="pickup_date" value="' + date + '">' +
                '<input type="hidden" name="notes" value="' + notes + '">';
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
</x-layout>
