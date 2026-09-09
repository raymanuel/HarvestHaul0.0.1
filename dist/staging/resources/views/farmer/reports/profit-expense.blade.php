<x-layout title="Profit & Expense Report — HarvestHaul">
    <div class="w-full max-w-7xl mx-auto pb-12 px-4 sm:px-6 lg:px-8">

        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-brand/10 dark:bg-gold-light/10 border border-brand/20 dark:border-gold-light/30 flex items-center justify-center text-[#16283C] dark:text-[#D7BC7A]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-slate-800 dark:text-white heading-font">Financial Reports</h1>
                </div>
            </div>
        </div>

        <x-flash-success />

        <!-- Report Tabs -->
        @php
            $reportTabs = [
                ['label' => 'Sales', 'url' => route('farmer.reports.sales'), 'active' => false],
                ['label' => 'Profit & Expense', 'url' => route('farmer.reports.profit-expense'), 'active' => true],
            ];
        @endphp
        <x-nav-tabs :tabs="$reportTabs" />

        <!-- Date Range Filter -->
        <form method="GET" class="mb-6 flex flex-wrap items-end gap-3" id="profit-filter-form">
            <div>
                <label for="month" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Month</label>
                <select name="month" id="month" onchange="this.form.submit()"
                    class="border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-200 font-bold bg-white dark:bg-slate-800">
                    <option value="">All months</option>
                    @foreach($availableMonths as $ym)
                        <option value="{{ $ym }}" {{ ($month ?? '') === $ym ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('F Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="crop_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Crop</label>
                <select name="crop_id" id="crop_id" onchange="this.form.submit()"
                    class="border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-200 font-bold bg-white dark:bg-slate-800">
                    <option value="">All crops</option>
                    @foreach($soldCrops as $sc)
                        <option value="{{ $sc->id }}" {{ (string) ($cropId ?? '') === (string) $sc->id ? 'selected' : '' }}>{{ $sc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="from" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">From</label>
                <input type="date" name="from" id="from" value="{{ $dateFrom }}"
                    class="border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-200 font-bold focus:outline-none focus:ring-2 focus:ring-[#16283C]/20 focus:border-[#16283C] bg-white dark:bg-slate-800">
            </div>
            <div>
                <label for="to" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">To</label>
                <input type="date" name="to" id="to" value="{{ $dateTo }}"
                    class="border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-200 font-bold focus:outline-none focus:ring-2 focus:ring-[#16283C]/20 focus:border-[#16283C] bg-white dark:bg-slate-800">
            </div>
            <button type="submit" class="px-5 py-2 bg-[#16283C] hover:bg-[#0E1620] text-white dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] text-xs font-bold rounded-xl transition-all shadow-sm">
                Filter
            </button>
            <a href="{{ route('farmer.reports.profit-expense.download', array_filter(['month' => $month ?? null, 'crop_id' => $cropId ?? null])) }}"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl transition-all shadow-sm border border-slate-200 dark:border-slate-600">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 12V4m0 8l-4-4m4 4l4-4" transform="rotate(0 12 12)"/></svg>
                Download CSV
            </a>
        </form>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Revenue</p>
                <p class="text-2xl font-black text-[#16283C] dark:text-[#D7BC7A] heading-font mt-2">₱{{ number_format($totalRevenue, 2) }}</p>
                <p class="text-[10px] text-slate-400 mt-1">{{ $negotiations->count() }} completed deal{{ $negotiations->count() !== 1 ? 's' : '' }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Costs</p>
                <p class="text-2xl font-black text-rose-500 heading-font mt-2">₱{{ number_format($totalCosts, 2) }}</p>
                <p class="text-[10px] text-slate-400 mt-1">Transport ₱{{ number_format($totalTransport, 2) }} + logged ₱{{ number_format($totalLogged, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Net After Expenses</p>
                <p class="text-2xl font-black {{ $netProfit >= 0 ? 'text-[#16283C] dark:text-[#D7BC7A]' : 'text-rose-500 dark:text-rose-400' }} heading-font mt-2">₱{{ number_format($netProfit, 2) }}</p>
                <p class="text-[10px] text-slate-400 mt-1">Sales minus transport &amp; logged expenses — not full farm profit</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Revenue by Crop -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50">
                    <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Revenue by Crop</h2>
                </div>
                <div class="p-5">
                    @forelse($revenueByCrop as $crop => $data)
                        <div class="flex items-center justify-between py-2.5 @if(!$loop->last) border-b border-slate-50 dark:border-slate-700/30 @endif">
                            <div>
                                <p class="text-xs font-bold text-slate-800 dark:text-white">{{ $crop }}</p>
                                <p class="text-[10px] text-slate-400">{{ $data['count'] }} sale{{ $data['count'] !== 1 ? 's' : '' }} &middot; {{ number_format($data['kg'], 1) }} kg @ {{ '₱' . number_format($data['avg_price'], 2) }}/kg</p>
                            </div>
                            <p class="text-sm font-black text-[#16283C] dark:text-[#D7BC7A] heading-font">₱{{ number_format($data['total'], 2) }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-8">No completed sales in this period.</p>
                    @endforelse
                </div>
            </div>

            <!-- Expense Breakdown -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50 flex items-center justify-between flex-wrap gap-2">
                    <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Expense Breakdown</h2>
                    <a href="{{ route('farmer.expenses') }}" class="text-[10px] font-bold text-[#16283C] hover:underline uppercase tracking-wider">Log an expense</a>
                </div>
                <div class="p-5">
                    @if(count($transportBreakdown) > 0)
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Transport (auto-tracked)</p>
                        @foreach($transportBreakdown as $crop => $cost)
                            <div class="flex items-center justify-between py-2 border-b border-slate-50 dark:border-slate-700/30">
                                <p class="text-xs font-bold text-slate-800 dark:text-white">{{ $crop }}</p>
                                <p class="text-sm font-black text-rose-500 heading-font">₱{{ number_format($cost, 2) }}</p>
                            </div>
                        @endforeach
                    @endif
                    @if(count($loggedByCategory) > 0)
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-4 mb-1">Logged Expenses</p>
                        @foreach($loggedByCategory as $cat => $amount)
                            <div class="flex items-center justify-between py-2 border-b border-slate-50 dark:border-slate-700/30">
                                <p class="text-xs font-bold text-slate-800 dark:text-white capitalize">{{ ucfirst($cat) }}</p>
                                <p class="text-sm font-black text-rose-500 heading-font">₱{{ number_format($amount, 2) }}</p>
                            </div>
                        @endforeach
                    @endif
                    @if(count($transportBreakdown) === 0 && count($loggedByCategory) === 0)
                        <p class="text-xs text-slate-400 text-center py-8">No expenses in this period.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Profit by Crop -->
        <div class="mt-6 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50 flex items-center justify-between flex-wrap gap-2">
                <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Profit by Crop</h2>
                <a href="{{ route('farmer.reports.profit-expense.download', array_filter(['month' => $month ?? null, 'crop_id' => $cropId ?? null])) }}"
                   class="text-[10px] font-bold text-[#16283C] hover:underline uppercase tracking-wider">Download CSV</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900/30 border-b border-slate-100 dark:border-slate-700/50">
                            <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Crop</th>
                            <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Sold (kg)</th>
                            <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Revenue</th>
                            <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Expense</th>
                            <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Net</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40">
                        @forelse($cropBreakdown as $row)
                            <tr class="hover:bg-slate-50/40 dark:hover:bg-slate-900/10 transition">
                                <td class="px-5 py-3 font-bold text-slate-800 dark:text-white">{{ $row['crop'] }}</td>
                                <td class="px-5 py-3 text-right text-slate-600 dark:text-slate-300">{{ number_format($row['kg'], 2) }}</td>
                                <td class="px-5 py-3 text-right font-bold text-[#16283C] dark:text-[#D7BC7A]">₱{{ number_format($row['revenue'], 2) }}</td>
                                <td class="px-5 py-3 text-right font-bold text-rose-500">₱{{ number_format($row['expense'], 2) }}</td>
                                <td class="px-5 py-3 text-right font-black {{ $row['net'] >= 0 ? 'text-[#16283C] dark:text-[#D7BC7A]' : 'text-rose-500 dark:text-rose-400' }}">₱{{ number_format($row['net'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-8 text-center text-xs text-slate-400">No per-crop data in this period.</td></tr>
                        @endforelse
                        @if($untaggedExpense > 0)
                            <tr class="bg-amber-50/60 dark:bg-amber-900/10">
                                <td class="px-5 py-3 font-bold text-slate-700 dark:text-slate-200">General / Untagged</td>
                                <td class="px-5 py-3 text-right text-slate-500">—</td>
                                <td class="px-5 py-3 text-right text-slate-400">—</td>
                                <td class="px-5 py-3 text-right font-bold text-rose-500">₱{{ number_format($untaggedExpense, 2) }}</td>
                                <td class="px-5 py-3 text-right font-black text-rose-500">-₱{{ number_format($untaggedExpense, 2) }}</td>
                            </tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-50 dark:bg-slate-900/30 border-t border-slate-100 dark:border-slate-700/50">
                            <td class="px-5 py-3 font-black text-slate-800 dark:text-white uppercase tracking-wider">Total</td>
                            <td class="px-5 py-3 text-right font-bold text-slate-700 dark:text-slate-200">{{ number_format($negotiations->sum(fn($n) => (float) $n->negotiated_volume ?? 0), 2) }}</td>
                            <td class="px-5 py-3 text-right font-black text-[#16283C] dark:text-[#D7BC7A]">₱{{ number_format($totalRevenue, 2) }}</td>
                            <td class="px-5 py-3 text-right font-black text-rose-500">₱{{ number_format($totalCosts, 2) }}</td>
                            <td class="px-5 py-3 text-right font-black {{ ($totalRevenue - $totalCosts) >= 0 ? 'text-[#16283C] dark:text-[#D7BC7A]' : 'text-rose-500 dark:text-rose-400' }}">₱{{ number_format($totalRevenue - $totalCosts, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Expense Logbook -->
        <div id="expense-logbook" class="mt-6 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm scroll-mt-24">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50 flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Expense Logbook</h2>
                    <p class="text-[10px] text-slate-400 mt-0.5">Record production costs (seeds, fertilizer, labor, etc.). Hauling charges are tracked automatically above.</p>
                </div>
                <span class="text-[10px] font-bold text-[var(--color-warning-text)] uppercase tracking-wider">All-time logged: &#8369;{{ number_format($loggedExpenses->sum('amount'), 2) }} &middot; {{ $loggedExpenses->count() }} entr{{ $loggedExpenses->count() === 1 ? 'y' : 'ies' }}</span>
            </div>

            <div class="p-5 border-b border-slate-100 dark:border-slate-700/50">
                <form method="POST" action="{{ route('farmer.expenses.store') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                    @csrf
                    <div>
                        <label for="category" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Category</label>
                        <select name="category" id="category" required
                                class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#16283C]/20 focus:border-[#16283C]">
                            <option value="" disabled selected>Select category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="crop_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Crop (optional)</label>
                        <select name="crop_id" id="crop_id"
                                class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#16283C]/20 focus:border-[#16283C]">
                            <option value="">General</option>
                            @foreach($crops as $crop)
                                <option value="{{ $crop->id }}" {{ old('crop_id') == $crop->id ? 'selected' : '' }}>{{ $crop->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="amount" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Amount (&#8369;)</label>
                        <input type="number" name="amount" id="amount" step="0.01" min="0.01" required value="{{ old('amount') }}"
                               class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#16283C]/20 focus:border-[#16283C]">
                    </div>
                    <div>
                        <label for="expense_date" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Date</label>
                        <input type="date" name="expense_date" id="expense_date" required value="{{ old('expense_date', now()->toDateString()) }}"
                               class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#16283C]/20 focus:border-[#16283C]">
                    </div>
                    <button type="submit" class="w-full px-5 py-2.5 bg-[#16283C] hover:bg-[#0E1620] text-white dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] text-xs font-bold rounded-xl transition-all shadow-sm">
                        Record Expense
                    </button>
                    <div class="sm:col-span-2 lg:col-span-5">
                        <input type="text" name="description" id="description" maxlength="255" placeholder="Description (optional, e.g. 2 bags urea for lot A)" value="{{ old('description') }}"
                               class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#16283C]/20 focus:border-[#16283C]">
                    </div>
                </form>
                @error('category')<p class="mt-2 text-xs text-[var(--color-error-text)]">{{ $message }}</p>@enderror
                @error('amount')<p class="mt-2 text-xs text-[var(--color-error-text)]">{{ $message }}</p>@enderror
                @error('expense_date')<p class="mt-2 text-xs text-[var(--color-error-text)]">{{ $message }}</p>@enderror
            </div>

            @if($loggedExpenses->isEmpty())
                <div class="p-10 text-center">
                    <p class="text-slate-400 text-sm font-semibold">No expenses logged yet.</p>
                    <p class="text-slate-400 dark:text-slate-500 text-xs mt-1">Use the form above to record your first production cost.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/30 border-b border-slate-100 dark:border-slate-700/60 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                <th class="text-left py-3 px-5">Date</th>
                                <th class="text-left py-3 px-3">Category</th>
                                <th class="text-left py-3 px-3">Crop</th>
                                <th class="text-left py-3 px-3">Description</th>
                                <th class="text-right py-3 px-3">Amount</th>
                                <th class="text-right py-3 px-5">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                            @foreach($loggedExpenses as $expense)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition">
                                    <td class="py-3 px-5 text-slate-600 dark:text-slate-300 whitespace-nowrap">{{ $expense->expense_date->format('M d, Y') }}</td>
                                    <td class="py-3 px-3">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-semibold capitalize bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                                            {{ $expense->category }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-slate-600 dark:text-slate-300">{{ $expense->crop?->name ?? 'General' }}</td>
                                    <td class="py-3 px-3 text-slate-500 dark:text-slate-400 max-w-[280px] truncate" title="{{ $expense->description }}">{{ $expense->description ?? '&mdash;' }}</td>
                                    <td class="py-3 px-3 text-right font-extrabold text-[var(--color-warning-text)]">&#8369;{{ number_format($expense->amount, 2) }}</td>
                                    <td class="py-3 px-5 text-right">
                                        <form action="{{ route('farmer.expenses.destroy', $expense) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" onclick="swalConfirm(this.closest('form'), {title: 'Delete Expense?', text: 'This entry will be permanently removed.', confirmText: 'Yes, delete', icon: 'warning', confirmColor: '#ef4444'})"
                                                    class="text-xs font-semibold text-[var(--color-error-text)] hover:opacity-80 transition">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Monthly Trend -->
        @if(count($monthlyTrend) > 0)
            <div class="mt-6 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50">
                    <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Monthly Trend</h2>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                        @foreach($monthlyTrend as $month => $data)
                            <div class="text-center p-3 rounded-xl bg-slate-50 dark:bg-slate-700/30 border border-slate-100 dark:border-slate-700/30">
                                <p class="text-[10px] font-bold text-slate-400 uppercase">{{ Carbon\Carbon::parse($month . '-01')->format('M Y') }}</p>
                                <p class="text-lg font-black text-[#16283C] dark:text-[#D7BC7A] heading-font mt-1">₱{{ number_format($data['revenue'], 0) }}</p>
                                <p class="text-[10px] text-slate-400">{{ $data['count'] }} deal{{ $data['count'] !== 1 ? 's' : '' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Active Harvests -->
        @if($activeHarvests->count() > 0)
            <div class="mt-6 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50">
                    <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Active Harvests (Unsold/Partial)</h2>
                </div>
                <div class="p-5">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-700/50">
                                    <th class="text-left py-2 px-3">Crop</th>
                                    <th class="text-right py-2 px-3">Quantity</th>
                                    <th class="text-right py-2 px-3">Suggested Price</th>
                                    <th class="text-center py-2 px-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeHarvests as $harvest)
                                    <tr class="border-b border-slate-50 dark:border-slate-700/30">
                                        <td class="py-2.5 px-3 font-bold text-slate-800 dark:text-white">{{ $harvest->crop->name ?? 'Unknown' }}</td>
                                        <td class="py-2.5 px-3 text-right font-bold">{{ number_format($harvest->quantity_kg, 1) }} kg</td>
                                        <td class="py-2.5 px-3 text-right font-bold text-[#16283C] dark:text-[#D7BC7A]">₱{{ number_format($harvest->suggested_price_per_kg ?? 0, 2) }}/kg</td>
                                        <td class="py-2.5 px-3 text-center">
                                            <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-md border
                                                @if($harvest->status->value === 'active') bg-[#16283C]/10 text-[#16283C] border-[#16283C]/10 dark:bg-gold-light/10 dark:text-[#D7BC7A] dark:border-gold-light/20
                                                @elseif($harvest->status->value === 'negotiating') bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border-[var(--color-warning-border)] dark:bg-[var(--color-warning-bg-dark)] dark:text-[var(--color-warning-text-dark)] dark:border-[var(--color-warning-border-dark)]
                                                @elseif($harvest->status->value === 'partially_sold') bg-[var(--color-info-bg)] text-[var(--color-info-text)] border-[var(--color-info-border)] dark:bg-[var(--color-info-bg-dark)] dark:text-[var(--color-info-text-dark)] dark:border-[var(--color-info-border-dark)]
                                                @endif">
                                                {{ ucfirst(str_replace('_', ' ', $harvest->status->value)) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-layout>
