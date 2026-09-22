<x-layout title="Outbound Sales Report — HarvestHaul">
    <div class="w-full max-w-7xl mx-auto pb-12 px-4 sm:px-6 lg:px-8">

        <!-- Page Header -->
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white heading-font">Outbound Sales Report</h1>
                <p class="text-sm text-slate-400 mt-1">Sales of customer orders across the distribution cycle, from dispatch to completion.</p>
            </div>
            <a href="{{ route('coop.reports.outbound-sales.download', request()->query()) }}"
               class="px-5 py-2 bg-brand hover:bg-brand-dark text-white dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] text-xs font-bold rounded-xl transition-all shadow-sm">
                Download CSV
            </a>
        </div>

        <!-- Filters -->
        <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
            <div>
                <label for="from" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">From</label>
                <input type="date" name="from" id="from" value="{{ $dateFrom }}"
                    class="border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-200 font-bold focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand bg-white dark:bg-slate-800">
            </div>
            <div>
                <label for="to" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">To</label>
                <input type="date" name="to" id="to" value="{{ $dateTo }}"
                    class="border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-200 font-bold focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand bg-white dark:bg-slate-800">
            </div>
            <div>
                <label for="customer_card_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Customer</label>
                <select name="customer_card_id" id="customer_card_id"
                    class="border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-200 font-bold focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand bg-white dark:bg-slate-800">
                    <option value="">All customers</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((int) $customer->id === (int) $customerCardId)>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="crop_type" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Crop</label>
                <select name="crop_type" id="crop_type"
                    class="border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-200 font-bold focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand bg-white dark:bg-slate-800">
                    <option value="">All crops</option>
                    @foreach($cropsSold as $crop)
                        <option value="{{ $crop }}" @selected($crop === $cropType)>{{ $crop }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-5 py-2 bg-brand hover:bg-brand-dark text-white dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] text-xs font-bold rounded-xl transition-all shadow-sm">
                Filter
            </button>
        </form>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Customer Orders</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white heading-font mt-2">{{ $totalOrders }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Sales</p>
                <p class="text-2xl font-black text-brand dark:text-brand-light heading-font mt-2">₱{{ number_format($totalAmount, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Volume Delivered</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white heading-font mt-2">{{ number_format($totalKg, 1) }} kg</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Avg Rate</p>
                <p class="text-2xl font-black text-brand dark:text-brand-light heading-font mt-2">₱{{ number_format($avgRate, 2) }}</p>
                <p class="text-[10px] text-slate-400 mt-1">per kg</p>
            </div>
        </div>

        <!-- Sales by Crop -->
        @if(count($perCrop) > 0)
            <div class="mb-6 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50">
                    <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Sales by Crop</h2>
                </div>
                <div class="p-5">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-700/50">
                                    <th class="text-left py-2 px-3">Crop</th>
                                    <th class="text-right py-2 px-3">Orders</th>
                                    <th class="text-right py-2 px-3">Kg</th>
                                    <th class="text-right py-2 px-3">Avg Rate</th>
                                    <th class="text-right py-2 px-3">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($perCrop as $row)
                                    <tr class="border-b border-slate-50 dark:border-slate-700/30">
                                        <td class="py-2.5 px-3 font-bold text-slate-800 dark:text-white">{{ $row['crop'] }}</td>
                                        <td class="py-2.5 px-3 text-right font-bold">{{ $row['orders'] }}</td>
                                        <td class="py-2.5 px-3 text-right font-bold">{{ number_format($row['kg'], 1) }}</td>
                                        <td class="py-2.5 px-3 text-right font-bold">₱{{ number_format($row['avg'], 2) }}</td>
                                        <td class="py-2.5 px-3 text-right font-bold text-brand dark:text-brand-light">₱{{ number_format($row['amount'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Sales by Customer -->
        @if(count($perCustomer) > 0)
            <div class="mb-6 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50">
                    <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Sales by Customer</h2>
                </div>
                <div class="p-5">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-700/50">
                                    <th class="text-left py-2 px-3">Customer</th>
                                    <th class="text-right py-2 px-3">Orders</th>
                                    <th class="text-right py-2 px-3">Kg</th>
                                    <th class="text-right py-2 px-3">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($perCustomer as $row)
                                    <tr class="border-b border-slate-50 dark:border-slate-700/30">
                                        <td class="py-2.5 px-3 font-bold text-slate-800 dark:text-white">{{ $row['customer'] }}</td>
                                        <td class="py-2.5 px-3 text-right font-bold">{{ $row['orders'] }}</td>
                                        <td class="py-2.5 px-3 text-right font-bold">{{ number_format($row['kg'], 1) }}</td>
                                        <td class="py-2.5 px-3 text-right font-bold text-brand dark:text-brand-light">₱{{ number_format($row['amount'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Order Log -->
        @if($orders->count() > 0)
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50">
                    <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Dispatched Orders</h2>
                </div>
                <div class="p-5">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-700/50">
                                    <th class="text-left py-2 px-3">Order</th>
                                    <th class="text-left py-2 px-3">Customer</th>
                                    <th class="text-left py-2 px-3">Status</th>
                                    <th class="text-right py-2 px-3">Kg</th>
                                    <th class="text-right py-2 px-3">Amount</th>
                                    <th class="text-center py-2 px-3">Dispatched</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orders->take(50) as $order)
                                    <tr class="border-b border-slate-50 dark:border-slate-700/30">
                                        <td class="py-2.5 px-3 font-bold text-slate-800 dark:text-white">#{{ $order->id }}</td>
                                        <td class="py-2.5 px-3 font-bold">{{ $order->customerCard->name ?? '—' }}</td>
                                        <td class="py-2.5 px-3">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest bg-brand/10 text-brand dark:text-brand-light border border-brand/15">
                                                {{ ucwords(str_replace('_', ' ', $order->status)) }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-bold">{{ number_format($order->total_kg, 1) }}</td>
                                        <td class="py-2.5 px-3 text-right font-bold text-brand dark:text-brand-light">₱{{ number_format($order->total_amount, 2) }}</td>
                                        <td class="py-2.5 px-3 text-center text-slate-400">{{ Carbon\Carbon::parse($order->dispatched_at)->format('M d, Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($orders->count() > 50)
                        <p class="text-[10px] text-slate-400 text-center mt-3">Showing 50 of {{ $orders->count() }} orders</p>
                    @endif
                </div>
            </div>
        @endif

        @if($orders->count() === 0)
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-12 text-center shadow-sm">
                <p class="text-sm font-bold text-slate-500 dark:text-slate-300">No dispatched customer orders in this period.</p>
                <p class="text-xs text-slate-400 mt-1">Draft and dispatch a customer order from the Customer Orders page to see sales here.</p>
                <a href="{{ route('coop.outbound.index') }}" class="mt-4 inline-block text-xs font-bold text-brand dark:text-brand-light hover:underline transition">View Customer Orders →</a>
            </div>
        @endif

    </div>
</x-layout>