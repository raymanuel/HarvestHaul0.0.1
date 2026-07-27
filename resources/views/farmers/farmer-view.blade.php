<x-layout>
@push('head')
    <style>
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }
    </style>
@endpush
<div class="w-full max-w-7xl mx-auto pb-12 overflow-hidden">
    <x-ambient-glow color="brand" />

    <div class="relative z-10">
        <x-page-header
            portal="Farmer Portal"
            title="Welcome, {{ Auth::user()->name }}"
            subtitle="Manage harvests, coordinate B2B highway pooling, and monitor active shipments."
            :showDate="true"
        />

        @if (!Auth::user()->farmerProfile?->is_verified)
            <x-pending-banner
                type="farmer"
                title="Account Pending Board Verification"
                description="Your submitted credentials and cooperative licensing records are currently undergoing verification audit. Full route pooling capabilities and crop posts will unlock once verified."
            />
        @endif

        <x-flash-error />

        <x-section-label title="Farmer Console Dashboard" />

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <x-stat-card
                accent="brand"
                badge="In Stock"
                title="Active Harvest Posts"
                :value="$activeHarvestsCount ?? 0"
                unit="active posts"
                href="{{ route('harvests.index') }}"
                linkText="Manage Harvests"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </x-stat-card>

            <x-stat-card
                accent="brand"
                badge="In Transit"
                title="Shipments En Route"
                :value="$activeShipmentsCount ?? 0"
                unit="active runs"
                href="{{ route('tracking.index') }}"
                linkText="View Live Map"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10M13 16h6m-6 0H6m13 0a2 2 0 002-2v-4a1 1 0 00-1-1h-6.18c-.09-.27-.27-.49-.52-.61l-2.6-1.3a1 1 0 00-1.12.18l-1.6 1.6" />
                </svg>
            </x-stat-card>

            <x-stat-card
                accent="harvest"
                badge="Action Required"
                title="Pooling Proposals"
                :value="$pendingProposalsCount ?? 0"
                unit="pending deals"
                href="{{ route('farmer.proposals') }}"
                linkText="Review Cost Splits"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </x-stat-card>
        </div>

        <div class="mb-10">
            <x-section-label title="DA RFO12 Market Prices" />
            <x-market-prices-card :daPrices="$daPrices" :priceTrends="$priceTrends" :latestDate="$latestDaDate" :scraperStatus="$scraperStatus" />
        </div>

        <x-section-label title="Analytics & Decision Intelligence" width="w-32" />

        <div class="mb-12">
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:-translate-y-1 hover:shadow-xl hover:shadow-brand/5 hover:border-brand/30 dark:hover:border-brand/30 transition-all duration-300 group shadow-sm flex flex-col justify-between relative overflow-hidden" id="profit-calculator">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-brand/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                <div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white heading-font">Interactive Net Profit Calculator</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">Estimate yield revenue, subtract production expenses, and discover your profit margin.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Crop Selection / Name</label>
                                <input type="text" id="calc-crop-name" value="General Crops"
                                    class="w-full border border-slate-200 dark:border-slate-700 dark:bg-slate-900 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-350 font-bold focus:outline-none focus:ring-2 focus:ring-brand/10 focus:border-brand">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Sale Price (₱/kg)</label>
                                    <input type="number" id="calc-sale-price" value="45.00" step="0.5"
                                        class="w-full border border-slate-200 dark:border-slate-700 dark:bg-slate-900 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-350 font-bold focus:outline-none focus:ring-2 focus:ring-brand/10 focus:border-brand">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Yield Volume (kg)</label>
                                    <input type="number" id="calc-yield" value="1500" step="50"
                                        class="w-full border border-slate-200 dark:border-slate-700 dark:bg-slate-900 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-350 font-bold focus:outline-none focus:ring-2 focus:ring-brand/10 focus:border-brand">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Seeds & Inputs (₱)</label>
                                    <input type="number" id="calc-seeds" value="5000" step="100"
                                        class="w-full border border-slate-200 dark:border-slate-700 dark:bg-slate-900 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-350 font-bold focus:outline-none focus:ring-2 focus:ring-brand/10 focus:border-brand">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Fertilizer (₱)</label>
                                    <input type="number" id="calc-fertilizer" value="8000" step="100"
                                        class="w-full border border-slate-200 dark:border-slate-700 dark:bg-slate-900 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-350 font-bold focus:outline-none focus:ring-2 focus:ring-brand/10 focus:border-brand">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Labor & Farming (₱)</label>
                                    <input type="number" id="calc-labor" value="12000" step="100"
                                        class="w-full border border-slate-200 dark:border-slate-700 dark:bg-slate-900 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-350 font-bold focus:outline-none focus:ring-2 focus:ring-brand/10 focus:border-brand">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Logistics & Haul (₱)</label>
                                    <input type="number" id="calc-logistics" value="6500" step="100"
                                        class="w-full border border-slate-200 dark:border-slate-700 dark:bg-slate-900 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-350 font-bold focus:outline-none focus:ring-2 focus:ring-brand/10 focus:border-brand">
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-900/40 border border-slate-150 dark:border-slate-700 rounded-2xl p-5 flex flex-col justify-between text-center relative overflow-hidden group">
                            <div class="absolute -right-12 -bottom-12 w-36 h-36 bg-brand/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>

                            <div>
                                <h4 class="text-xs font-bold text-slate-400 dark:text-slate-550 uppercase tracking-wider mb-4">Estimated Earnings Summary</h4>
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 uppercase font-semibold">Gross Revenue</p>
                                        <p class="text-2xl font-black text-slate-805 dark:text-white mt-0.5" id="calc-gross">₱67,500.00</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 border-t border-b border-slate-200/60 dark:border-slate-700/60 py-3">
                                        <div>
                                            <p class="text-[9px] text-slate-400 dark:text-slate-550 uppercase font-semibold">Expenses</p>
                                            <p class="text-base font-bold text-[#C1694F] mt-0.5" id="calc-expenses">₱31,500.00</p>
                                        </div>
                                        <div>
                                            <p class="text-[9px] text-slate-400 dark:text-slate-550 uppercase font-semibold">Net Profit</p>
                                            <p class="text-base font-bold text-brand dark:text-brand mt-0.5" id="calc-profit">₱36,000.00</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex flex-col items-center justify-center">
                                <div class="relative inline-flex items-center justify-center">
                                    <div class="w-24 h-24 rounded-full border-[6px] border-slate-200 dark:border-slate-700 flex flex-col items-center justify-center relative">
                                        <span class="text-lg font-extrabold text-brand dark:text-brand" id="calc-margin">53.3%</span>
                                        <span class="text-[8px] font-extrabold uppercase text-slate-400 tracking-wider">Margin</span>
                                    </div>
                                </div>
                                <p class="text-[10px] text-brand dark:text-brand mt-3 font-bold" id="calc-crop-label">Profitability status: Healthy margin</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function calculateProfit() {
                const cropName = document.getElementById('calc-crop-name').value;
                const salePrice = parseFloat(document.getElementById('calc-sale-price').value) || 0;
                const yieldVol = parseFloat(document.getElementById('calc-yield').value) || 0;
                const seeds = parseFloat(document.getElementById('calc-seeds').value) || 0;
                const fertilizer = parseFloat(document.getElementById('calc-fertilizer').value) || 0;
                const labor = parseFloat(document.getElementById('calc-labor').value) || 0;
                const logistics = parseFloat(document.getElementById('calc-logistics').value) || 0;

                const grossRevenue = salePrice * yieldVol;
                const totalExpenses = seeds + fertilizer + labor + logistics;
                const netProfit = grossRevenue - totalExpenses;
                const margin = grossRevenue > 0 ? (netProfit / grossRevenue) * 100 : 0;

                document.getElementById('calc-gross').textContent = '₱' + grossRevenue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                document.getElementById('calc-expenses').textContent = '₱' + totalExpenses.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                const profitEl = document.getElementById('calc-profit');
                profitEl.textContent = '₱' + netProfit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                if (netProfit < 0) {
                    profitEl.className = 'text-base font-bold text-[#C1694F] dark:text-[#E8956F] mt-0.5';
                } else {
                    profitEl.className = 'text-base font-bold text-brand dark:text-brand mt-0.5';
                }

                const marginEl = document.getElementById('calc-margin');
                marginEl.textContent = margin.toFixed(1) + '%';
                if (margin < 0) {
                    marginEl.className = 'text-lg font-extrabold text-[#C1694F] dark:text-[#E8956F]';
                } else {
                    marginEl.className = 'text-lg font-extrabold text-brand dark:text-brand';
                }

                const labelEl = document.getElementById('calc-crop-label');
                if (netProfit < 0) {
                    labelEl.textContent = 'Profitability status: Net loss. Check costs!';
                    labelEl.className = 'text-[10px] text-[#C1694F] dark:text-[#E8956F] mt-3 font-bold';
                } else if (margin < 15) {
                    labelEl.textContent = 'Profitability status: Low margin. Optimize costs or negotiate better prices.';
                    labelEl.className = 'text-[10px] text-harvest dark:text-harvest mt-3 font-bold';
                } else {
                    labelEl.textContent = 'Profitability status: Healthy margin. Excellent return.';
                    labelEl.className = 'text-[10px] text-brand dark:text-brand mt-3 font-bold';
                }
            }

            document.querySelectorAll('#profit-calculator input').forEach(input => {
                input.addEventListener('input', calculateProfit);
            });

            window.addEventListener('load', () => {
                calculateProfit();
            });
        </script>
    </div>
</div>
</x-layout>
