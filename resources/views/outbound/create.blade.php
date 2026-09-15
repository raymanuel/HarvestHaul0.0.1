<x-layout title="New Customer Order">

    <div class="w-full max-w-4xl mx-auto pb-12">

        <header class="pt-8 mb-6 border-b border-slate-200/80 dark:border-slate-700/80 pb-5">
            <a href="{{ route('coop.outbound.index') }}" class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 mb-4 inline-block font-semibold transition">
                ← Back to Customer Orders
            </a>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">New Customer Order</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Build an order for a customer — the total is rate × kg per line.</p>
        </header>

        <x-flash-success />
        <x-flash-error />

        @if($errors->any())
            <div class="mb-6 bg-red-50 dark:bg-red-950/30 border border-[var(--color-error-border)] rounded-2xl p-4">
                <p class="text-xs font-bold text-[var(--color-error-text)] uppercase tracking-wider mb-2">Please fix the following</p>
                <ul class="list-disc list-inside space-y-1 text-xs text-[var(--color-error-text)] font-medium">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('coop.outbound.store') }}" id="outbound-form">
            @csrf

            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6 mb-6">
                <label for="customer_card_id" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                    Customer <span class="text-red-500">*</span>
                </label>
                <select name="customer_card_id" id="customer_card_id" required
                    class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#16283C]/30 focus:border-[#16283C] transition">
                    <option value="">Select Customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ (string) old('customer_card_id', $preselectedCustomerId ?? '') === (string) $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }} — {{ $customer->address ?? 'No address' }}
                        </option>
                    @endforeach
                </select>
                @error('customer_card_id')
                    <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                @enderror

                @if($customers->isEmpty())
                    <div class="mt-4 bg-slate-50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-700/60 rounded-xl p-4 text-center">
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-semibold">You have no customers yet.</p>
                        <a href="{{ route('coop.customers.create') }}" class="mt-2 inline-block text-xs font-bold text-[#16283C] dark:text-[#D7BC7A] hover:underline transition">Add a customer first <span>→</span></a>
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Order Items</h2>
                    <button type="button" onclick="addLine()"
                        class="inline-flex items-center gap-1.5 text-xs font-bold text-[#16283C] dark:text-[#D7BC7A] bg-[#16283C]/10 dark:bg-[#16283C]/15 px-3 py-2 rounded-xl hover:bg-[#16283C]/15 dark:hover:bg-[#16283C]/25 transition cursor-pointer">
                        <x-icon name="plus" class="w-3.5 h-3.5" /> Add line
                    </button>
                </div>

                <div id="lines-container" class="space-y-3">
                    @php
                        $oldLines = old('lines', [['crop_type' => '', 'quantity_kg' => '', 'rate_per_kg' => '']]);
                    @endphp
                    @foreach($oldLines as $idx => $line)
                        <div class="line-row grid grid-cols-12 gap-3 items-center">
                            <div class="col-span-12 sm:col-span-5">
                                <input type="text" name="lines[{{ $idx }}][crop_type]" value="{{ $line['crop_type'] ?? '' }}" placeholder="Crop (e.g. Banana)" required
                                    class="w-full px-3 py-2.5 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#16283C]/30 focus:border-[#16283C] transition">
                            </div>
                            <div class="col-span-5 sm:col-span-3">
                                <input type="number" step="0.01" min="0.01" name="lines[{{ $idx }}][quantity_kg]" value="{{ $line['quantity_kg'] ?? '' }}" placeholder="Kg" required oninput="recalcTotal()"
                                    class="w-full px-3 py-2.5 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#16283C]/30 focus:border-[#16283C] transition">
                            </div>
                            <div class="col-span-5 sm:col-span-3">
                                <input type="number" step="0.01" min="0.01" name="lines[{{ $idx }}][rate_per_kg]" value="{{ $line['rate_per_kg'] ?? '' }}" placeholder="₱/kg" required oninput="recalcTotal()"
                                    class="w-full px-3 py-2.5 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#16283C]/30 focus:border-[#16283C] transition">
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <button type="button" onclick="removeLine(this)" title="Remove line"
                                    class="inline-flex items-center justify-center w-9 h-9 text-red-500 hover:text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-950/20 dark:hover:bg-red-950/40 rounded-xl transition cursor-pointer">
                                    <x-icon name="trash" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-700/60 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-2 text-sm">
                    <span class="text-slate-500 dark:text-slate-400 text-xs font-semibold">Total: <span id="total-kg">0.0</span> kg</span>
                    <span class="text-slate-800 dark:text-slate-200 text-base font-black">₱<span id="total-amount">0.00</span></span>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6 mb-6">
                <label for="notes" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">Notes</label>
                <textarea name="notes" id="notes" rows="3" placeholder="e.g. Deliver to receiving dock before noon"
                    class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#16283C]/30 focus:border-[#16283C] transition">{{ old('notes') }}</textarea>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <x-button type="submit" size="lg">
                    Save Order
                </x-button>
                <a href="{{ route('coop.outbound.index') }}"
                    class="text-xs font-bold text-slate-500 dark:text-slate-400 hover:text-slate-600 dark:hover:text-slate-400 px-4 py-3.5 rounded-xl border border-slate-200/60 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-900/30 transition">
                    Cancel
                </a>
            </div>
        </form>

        @if($suggestions->isNotEmpty())
            <div class="mt-8 bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6">
                <div class="flex items-center gap-2 mb-1">
                    <span class="w-2 h-2 rounded-full bg-harvest animate-pulse"></span>
                    <h2 class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Suggested from your completed deals</h2>
                </div>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 font-medium mb-4">One-tap prefills for crops your cooperative already secured with its farmers. Rates are advisory — confirm before saving.</p>

                <ul class="divide-y divide-slate-100 dark:divide-slate-700/40">
                    @foreach($suggestions as $suggestion)
                        <li class="py-3 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ $suggestion['crop_type'] }}</p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                                    {{ number_format((float) $suggestion['quantity_kg'], 1) }} kg @ ₱{{ number_format((float) $suggestion['rate_per_kg'], 2) }}/kg
                                </p>
                            </div>
                            <button type="button" onclick="useSuggestion('{{ addslashes($suggestion['crop_type']) }}', '{{ $suggestion['quantity_kg'] }}', '{{ $suggestion['rate_per_kg'] }}')"
                                class="inline-flex items-center justify-center px-3 py-2 bg-[#16283C]/10 text-[#16283C] hover:bg-[#16283C]/15 dark:bg-[#16283C]/10 dark:hover:bg-[#16283C]/15 dark:text-[#D7BC7A] rounded-xl text-xs font-bold transition cursor-pointer">
                                Use
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                recalcTotal();
            });

            function addLine(values) {
                values = values || { crop_type: '', quantity_kg: '', rate_per_kg: '' };
                var container = document.getElementById('lines-container');
                var index = container.querySelectorAll('.line-row').length;
                var div = document.createElement('div');
                div.className = 'line-row grid grid-cols-12 gap-3 items-center';
                div.innerHTML =
                    '<div class="col-span-12 sm:col-span-5">' +
                        '<input type="text" name="lines[' + index + '][crop_type]" value="' + values.crop_type + '" placeholder="Crop (e.g. Banana)" required class="w-full px-3 py-2.5 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#16283C]/30 focus:border-[#16283C] transition">' +
                    '</div>' +
                    '<div class="col-span-5 sm:col-span-3">' +
                        '<input type="number" step="0.01" min="0.01" name="lines[' + index + '][quantity_kg]" value="' + values.quantity_kg + '" placeholder="Kg" required oninput="recalcTotal()" class="w-full px-3 py-2.5 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#16283C]/30 focus:border-[#16283C] transition">' +
                    '</div>' +
                    '<div class="col-span-5 sm:col-span-3">' +
                        '<input type="number" step="0.01" min="0.01" name="lines[' + index + '][rate_per_kg]" value="' + values.rate_per_kg + '" placeholder="₱/kg" required oninput="recalcTotal()" class="w-full px-3 py-2.5 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#16283C]/30 focus:border-[#16283C] transition">' +
                    '</div>' +
                    '<div class="col-span-2 sm:col-span-1">' +
                        '<button type="button" onclick="removeLine(this)" title="Remove line" class="inline-flex items-center justify-center w-9 h-9 text-red-500 hover:text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-950/20 dark:hover:bg-red-950/40 rounded-xl transition cursor-pointer">' +
                            '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>' +
                        '</button>' +
                    '</div>';
                container.appendChild(div);
                recalcTotal();
            }

            function removeLine(btn) {
                var container = document.getElementById('lines-container');
                if (container.querySelectorAll('.line-row').length <= 1) return;
                btn.closest('.line-row').remove();
                container.querySelectorAll('.line-row').forEach(function (row, i) {
                    row.querySelector('input[name$="[crop_type]"]').name = 'lines[' + i + '][crop_type]';
                    row.querySelector('input[name$="[quantity_kg]"]').name = 'lines[' + i + '][quantity_kg]';
                    row.querySelector('input[name$="[rate_per_kg]"]').name = 'lines[' + i + '][rate_per_kg]';
                });
                recalcTotal();
            }

            function useSuggestion(cropType, kg, rate) {
                addLine({ crop_type: cropType, quantity_kg: kg, rate_per_kg: rate });
                document.getElementById('lines-container').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            function recalcTotal() {
                var kg = 0, amount = 0;
                document.querySelectorAll('.line-row').forEach(function (row) {
                    var q = parseFloat(row.querySelector('input[name$="[quantity_kg]"]').value) || 0;
                    var r = parseFloat(row.querySelector('input[name$="[rate_per_kg]"]').value) || 0;
                    kg += q;
                    amount += q * r;
                });
                document.getElementById('total-kg').textContent = kg.toFixed(1);
                document.getElementById('total-amount').textContent = amount.toFixed(2);
            }
        </script>
    @endpush

</x-layout>