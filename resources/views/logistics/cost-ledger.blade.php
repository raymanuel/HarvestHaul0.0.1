{{--
    Logistics Cost Ledger View
    
    PURPOSE:
    This view presents a detailed financial breakdown of transport costs for a specific PoolingJob.
    It displays how the overall transportation cost (negotiated or reference) is split among the 
    participating farmers.
    
    FORMULA:
    Farmer Cost Share = (Farmer Harvest quantity_kg / Total Job total_kg) * Final Agreed Price
--}}
<x-layout>
    <div class="w-full max-w-4xl mx-auto pb-12">

        {{-- Header --}}
        <x-flash-success />
        <header class="mb-8 pt-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <a href="{{ url()->previous() }}" class="text-slate-400 hover:text-brand dark:hover:text-brand-light transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                        <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold">Back</span>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">
                        Cost Ledger — Job #{{ $poolingJob->id }}
                    </h1>
                </div>
                <div class="flex flex-col items-start sm:items-end gap-2 self-start">
                    <span class="text-xs font-semibold uppercase tracking-wider text-brand dark:text-brand-light bg-brand/10 dark:bg-brand/10 px-3 py-1.5 rounded-lg border border-brand/10 dark:border-brand/20">
                        Cost Ledger
                    </span>
                    @if($invoice)
                        <a href="{{ route('invoices.download', $invoice) }}" target="_blank"
                           class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 dark:text-slate-300 hover:text-brand dark:hover:text-brand-light bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-1.5 shadow-sm transition">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                            </svg>
                            Hauling Invoice {{ $invoice->invoice_number }}
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ ucfirst($invoice->status->value ?? $invoice->status) }}</span>
                        </a>
                    @endif
                </div>
            </div>
        </header>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Payload</p>
                <p class="text-xl font-bold text-slate-900 dark:text-white">{{ number_format($poolingJob->total_kg, 1) }}<span class="text-sm font-semibold text-slate-400 ml-1">kg</span></p>
            </div>
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Hauling Cost</p>
                <p class="text-xl font-bold {{ $totalPrice > 0 ? ($poolingJob->status->value === 'completed' ? 'text-[var(--color-success-text)] dark:text-[var(--color-success-text-dark)]' : 'text-brand dark:text-brand-light') : 'text-slate-400 dark:text-slate-500' }}">
                    {{ $totalPrice > 0 ? '₱' . number_format($totalPrice, 2) : '—' }}
                </p>
            </div>
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Farms Included</p>
                <p class="text-xl font-bold text-slate-900 dark:text-white">{{ $ledgerEntries->count() }}<span class="text-sm font-semibold text-slate-400 ml-1">stops</span></p>
            </div>
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Status</p>
                @php
                    $statusColor = match($poolingJob->status->value) {
                        'confirmed'   => 'text-brand dark:text-brand-light',
                        'in_progress' => 'text-[var(--color-info-text)] dark:text-[var(--color-info-text-dark)]',
                        'completed'   => 'text-[var(--color-success-text)] dark:text-[var(--color-success-text-dark)]',
                        default       => 'text-slate-500',
                    };
                @endphp
                <p class="text-xl font-bold {{ $statusColor }} capitalize">{{ str_replace('_', ' ', $poolingJob->status->value) }}</p>
            </div>
        </div>

        {{-- Ledger Table --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 heading-font">Per-Farmer Cost Breakdown</h2>
                @if($totalPrice > 0)
                    <span class="text-[10px] font-bold text-slate-400 bg-slate-50 dark:bg-slate-900/50 px-2.5 py-1 rounded-lg border border-slate-200/60 dark:border-slate-700">
                        Weight &times; distance split
                    </span>
                @else
                    <span class="text-[10px] font-bold text-[var(--color-warning-text)] bg-[var(--color-warning-bg)] px-2.5 py-1 rounded-lg border border-[var(--color-warning-border)]">
                        Price pending negotiation
                    </span>
                @endif
            </div>

            @if($ledgerEntries->isEmpty())
                <div class="p-12 text-center">
                    <p class="text-slate-400 text-sm font-semibold">No harvest entries found for this job.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/30 border-b border-slate-100 dark:border-slate-700/60">
                                <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">#</th>
                                <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Farmer</th>
                                <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Crop & Destination</th>
                                <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-wider">Weight Share</th>
                                <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-wider">Hauling Share</th>
                                <th class="px-5 py-3 text-center text-[10px] font-bold text-slate-400 uppercase tracking-wider">Payment Status</th>
                                <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                            @foreach($ledgerEntries as $i => $entry)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition">
                                    <td class="px-5 py-4 font-mono text-xs text-slate-400">{{ $i + 1 }}</td>
                                    <td class="px-5 py-4">
                                        <p class="font-bold text-slate-800 dark:text-slate-200">{{ $entry['farmer_name'] }}</p>
                                    </td>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-slate-700 dark:text-slate-300">{{ $entry['crop'] }}</p>
                                        @if($entry['variety'] !== '—')
                                            <p class="text-[10px] text-slate-400 mt-0.5">{{ $entry['variety'] }}</p>
                                        @endif
                                        <p class="text-[10px] text-slate-500 mt-1 italic max-w-[200px] truncate" title="{{ $entry['destination'] }}">
                                            <x-icon name="pin" class="w-4 h-4" /> {{ $entry['destination'] }}
                                        </p>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ number_format($entry['quantity_kg'], 1) }} kg</span>
                                        <p class="text-[10px] text-slate-400 font-semibold mt-0.5">{{ $entry['proportion'] }}% share</p>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        @if($totalPrice > 0)
                                            <span class="text-sm font-extrabold text-brand dark:text-brand-light">₱{{ number_format($entry['cost_share'], 2) }}</span>
                                        @else
                                            <span class="text-xs text-slate-400 italic">TBD</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        @php
                                            $status = $entry['payment_status'] ?? 'unpaid';
                                            $badgeClasses = match($status) {
                                                'paid' => 'bg-[var(--color-success-bg)] text-[var(--color-success-text)] dark:bg-[var(--color-success-bg-dark)] dark:text-[var(--color-success-text-dark)] border border-[var(--color-success-border)] dark:border-[var(--color-success-border-dark)]',
                                                'submitted' => 'bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] dark:bg-[var(--color-warning-bg)] dark:text-[var(--color-warning-text)] border border-[var(--color-warning-border)] dark:border-[var(--color-warning-border)]',
                                                default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-semibold {{ $badgeClasses }}">
                                            {{ ucfirst($status) }}
                                        </span>
                                        @if($entry['receipt_path'])
                                            <div class="mt-1">
                                                <a href="{{ route('files.show', ['type' => 'payment-receipt', 'id' => $entry['harvest_id']]) }}" target="_blank" class="text-[10px] text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 underline font-medium inline-flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                    View Receipt
                                                </a>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        @if(Auth::id() === $entry['farmer_id'])
                                            @if($entry['payment_status'] !== 'paid')
                                                <form action="{{ route('pooling.cost-ledger.upload-receipt', [$poolingJob->id, $entry['harvest_id']]) }}" method="POST" enctype="multipart/form-data" class="flex items-center justify-end">
                                                    @csrf
                                                    <label class="cursor-pointer bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 border border-slate-300 dark:border-slate-600 rounded-lg px-2.5 py-1 text-xs text-slate-700 dark:text-slate-200 font-semibold transition inline-flex items-center gap-1.5 shadow-sm">
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                                        </svg>
                                                        <span>{{ $entry['payment_status'] === 'submitted' ? 'Re-upload Hauling Receipt' : 'Upload Hauling Receipt' }}</span>
                                                        <input type="file" name="payment_receipt" class="hidden" onchange="handleReceipt(this)" accept="image/*">
                                                    </label>
                                                </form>
                                            @else
                                                <span class="text-xs text-brand dark:text-brand-light font-bold inline-flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    Completed
                                                </span>
                                            @endif
                                        @elseif($isOwner)
                                            @if($entry['payment_status'] === 'submitted')
                                                <form action="{{ route('pooling.cost-ledger.mark-paid', [$poolingJob->id, $entry['harvest_id']]) }}" method="POST" class="flex items-center justify-end gap-2" onsubmit="event.preventDefault(); swalConfirm(this, {title:'Mark as Paid?', text:'Confirm this farmer has been paid this amount?', icon:'question', confirmText:'Yes, mark paid', cancelText:'Cancel', confirmColor:'#16283C'});">
                                                    @csrf
                                                    <label class="text-xs text-slate-500 dark:text-slate-400 sr-only" for="amount-paid-{{ $entry['harvest_id'] }}">Amount paid (₱)</label>
                                                    <input id="amount-paid-{{ $entry['harvest_id'] }}" type="number" name="amount_paid" min="0.01" step="0.01" placeholder="₱ amount"
                                                           class="w-28 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-2 py-1.5 text-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-brand">
                                                    <button type="submit" class="bg-brand hover:bg-brand-dark text-white rounded-lg px-3 py-1.5 text-xs font-bold shadow-sm transition hover:shadow-md inline-flex items-center gap-1">
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                        Verify Paid
                                                    </button>
                                                </form>
                                            @elseif($entry['payment_status'] === 'paid')
                                                <span class="text-xs text-brand dark:text-brand-light font-bold inline-flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    Paid{{ $entry['amount_paid'] ? '  ₱' . number_format($entry['amount_paid'], 2) : '' }}
                                                </span>
                                            @else
                                                <span class="text-xs text-slate-400 italic">Awaiting upload</span>
                                            @endif
                                        @else
                                            <span class="text-slate-400 text-xs">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        @if($totalPrice > 0)
                        <tfoot>
                            <tr class="bg-gold/10 dark:bg-gold/5 border-t-2 border-gold/20 dark:border-gold/15">
                                <td colspan="3" class="px-5 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total</td>
                                <td class="px-5 py-4 text-right font-bold text-slate-800 dark:text-slate-200">
                                    {{ number_format($poolingJob->total_kg, 1) }} kg
                                    <p class="text-[10px] text-slate-400 font-semibold mt-0.5">100% share</p>
                                </td>
                                <td class="px-5 py-4 text-right font-extrabold text-brand dark:text-brand-light">
                                    ₱{{ number_format($sumOfShares, 2) }}
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            @endif
        </div>

        {{-- Logistics info: truck + operator --}}
        @if($isOwner)
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-5">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Fleet & Operator Info</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Company</p>
                    <p class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ $poolingJob->logisticsProfile->company_name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Truck</p>
                    <p class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5">
                        {{ $poolingJob->truck->truck_name ?? '—' }}
                        @if($poolingJob->truck->plate_number)
                            <span class="text-slate-400 font-normal ml-1">({{ $poolingJob->truck->plate_number }})</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Reference Price</p>
                    <p class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5">
                        {{ $poolingJob->price_reference ? '₱' . number_format($poolingJob->price_reference, 2) : '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Negotiated Price</p>
                    <p class="font-extrabold text-brand dark:text-brand-light mt-0.5">
                        {{ $poolingJob->negotiated_price ? '₱' . number_format($poolingJob->negotiated_price, 2) : 'Pending' }}
                    </p>
                </div>
            </div>
        </div>
        @endif

    </div>

    @push('scripts')
    <script>
      async function handleReceipt(input) {
        if (!input.files.length) return;
        if (typeof window.compressImage === 'function') {
          const out = [];
          for (const f of Array.from(input.files)) out.push(await window.compressImage(f));
          const dt = new DataTransfer();
          out.forEach((f) => dt.items.add(f));
          input.files = dt.files;
        }
        swalConfirm(input.form, {
          title: 'Upload Hauling Receipt?',
          text: 'Submit this receipt to confirm your hauling payment?',
          icon: 'question',
          confirmText: 'Yes, upload',
          cancelText: 'Cancel',
          confirmColor: '#16283C'
        });
      }
    </script>
    @endpush
</x-layout>
