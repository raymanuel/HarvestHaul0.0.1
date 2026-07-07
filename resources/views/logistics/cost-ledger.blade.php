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
        <header class="mb-8 pt-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <a href="{{ url()->previous() }}" class="text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                        <span class="text-xs text-slate-400 dark:text-slate-500 font-semibold">Back</span>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">
                        Cost Ledger — Job #{{ $poolingJob->id }}
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">
                        Proportional freight cost breakdown per farmer based on cargo weight share
                    </p>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 px-3 py-1.5 rounded-lg border border-emerald-500/10 dark:border-emerald-500/20 self-start">
                    Cost Ledger
                </span>
            </div>
        </header>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Payload</p>
                <p class="text-xl font-bold text-slate-900 dark:text-white">{{ number_format($poolingJob->total_kg, 1) }}<span class="text-sm font-semibold text-slate-400 ml-1">kg</span></p>
            </div>
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Cost</p>
                <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">₱{{ number_format($totalPrice, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Farms Included</p>
                <p class="text-xl font-bold text-slate-900 dark:text-white">{{ $ledgerEntries->count() }}<span class="text-sm font-semibold text-slate-400 ml-1">stops</span></p>
            </div>
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Status</p>
                @php
                    $statusColor = match($poolingJob->status) {
                        'confirmed'   => 'text-amber-600 dark:text-amber-400',
                        'in_progress' => 'text-sky-600 dark:text-sky-400',
                        'completed'   => 'text-emerald-600 dark:text-emerald-400',
                        default       => 'text-slate-500',
                    };
                @endphp
                <p class="text-xl font-bold {{ $statusColor }} capitalize">{{ str_replace('_', ' ', $poolingJob->status) }}</p>
            </div>
        </div>

        {{-- Ledger Table --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 heading-font">Per-Farmer Cost Breakdown</h2>
                @if($totalPrice > 0)
                    <span class="text-[10px] font-bold text-slate-400 bg-slate-50 dark:bg-slate-900/50 px-2.5 py-1 rounded-lg border border-slate-200/60 dark:border-slate-700">
                        Proportional to cargo weight
                    </span>
                @else
                    <span class="text-[10px] font-bold text-amber-600 bg-amber-50 dark:bg-amber-950/20 px-2.5 py-1 rounded-lg border border-amber-200/40 dark:border-amber-800/30">
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
                                <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-wider">Cost Share</th>
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
                                            📍 {{ $entry['destination'] }}
                                        </p>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ number_format($entry['quantity_kg'], 1) }} kg</span>
                                        <p class="text-[10px] text-slate-400 font-semibold mt-0.5">{{ $entry['proportion'] }}% share</p>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        @if($totalPrice > 0)
                                            <span class="text-sm font-extrabold text-emerald-600 dark:text-emerald-400">₱{{ number_format($entry['cost_share'], 2) }}</span>
                                        @else
                                            <span class="text-xs text-slate-400 italic">TBD</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        @php
                                            $status = $entry['payment_status'] ?? 'unpaid';
                                            $badgeClasses = match($status) {
                                                'paid' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-800/30',
                                                'submitted' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-200/50 dark:border-amber-800/30',
                                                default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-semibold {{ $badgeClasses }}">
                                            {{ ucfirst($status) }}
                                        </span>
                                        @if($entry['receipt_path'])
                                            <div class="mt-1">
                                                <a href="{{ asset('storage/' . $entry['receipt_path']) }}" target="_blank" class="text-[10px] text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 underline font-medium inline-flex items-center gap-1">
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
                                                        <span>{{ $entry['payment_status'] === 'submitted' ? 'Re-upload' : 'Upload Receipt' }}</span>
                                                        <input type="file" name="payment_receipt" class="hidden" onchange="this.form.submit()" accept="image/*">
                                                    </label>
                                                </form>
                                            @else
                                                <span class="text-xs text-emerald-600 dark:text-emerald-400 font-bold inline-flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    Completed
                                                </span>
                                            @endif
                                        @elseif($isOwner)
                                            @if($entry['payment_status'] === 'submitted')
                                                <form action="{{ route('pooling.cost-ledger.mark-paid', [$poolingJob->id, $entry['harvest_id']]) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-3 py-1.5 text-xs font-bold shadow-sm transition hover:shadow-md inline-flex items-center gap-1">
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                        Verify Paid
                                                    </button>
                                                </form>
                                            @elseif($entry['payment_status'] === 'paid')
                                                <span class="text-xs text-emerald-600 dark:text-emerald-400 font-bold inline-flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    Paid
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
                            <tr class="bg-emerald-50/50 dark:bg-emerald-950/10 border-t-2 border-emerald-200/60 dark:border-emerald-800/30">
                                <td colspan="3" class="px-5 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total</td>
                                <td class="px-5 py-4 text-right font-bold text-slate-800 dark:text-slate-200">
                                    {{ number_format($poolingJob->total_kg, 1) }} kg
                                    <p class="text-[10px] text-slate-400 font-semibold mt-0.5">100% share</p>
                                </td>
                                <td class="px-5 py-4 text-right font-extrabold text-emerald-700 dark:text-emerald-400">
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
                    <p class="font-extrabold text-emerald-600 dark:text-emerald-400 mt-0.5">
                        {{ $poolingJob->negotiated_price ? '₱' . number_format($poolingJob->negotiated_price, 2) : 'Pending' }}
                    </p>
                </div>
            </div>
        </div>
        @endif

    </div>
</x-layout>
