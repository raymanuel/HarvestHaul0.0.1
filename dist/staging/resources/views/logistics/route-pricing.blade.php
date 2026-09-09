<x-layout title="Route Pricing">
<div class="w-full max-w-4xl mx-auto">

    <header class="pt-8 mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white heading-font tracking-tight">Route Pricing</h1>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-widest text-harvest-dark dark:text-harvest-light bg-harvest/10 dark:bg-harvest/20 px-3 py-1.5 rounded-md border border-harvest/10 dark:border-harvest/20 self-start">Settings</span>
        </div>
    </header>

    <x-flash-success />

    {{-- Rate Setting --}}
    <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6 mb-6">
        <h2 class="text-sm font-extrabold text-slate-800 dark:text-slate-200 mb-1">Default Hauling Rate</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-5">This rate pre-fills the hauling rate input on the Route Planning page. You can override it per route.</p>

        <form method="POST" action="{{ route('profile.route-pricing.update') }}">
            @csrf
            <div class="flex items-end gap-4 flex-wrap">
                <div class="w-48">
                    <label for="default_hauling_rate" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Rate (PHP/kg)</label>
                    <input type="number" name="default_hauling_rate" id="default_hauling_rate"
                        step="0.01" min="0.10" max="99.99"
                        value="{{ old('default_hauling_rate', $profile->default_hauling_rate ?? '1.50') }}"
                        class="w-full bg-slate-50/50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm font-mono text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#16283C]/25 focus:border-[#16283C] transition" />
                    @error('default_hauling_rate')
                        <p class="text-xs text-[var(--color-error-text)] mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="bg-[#16283C] hover:opacity-90 text-white dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] text-xs font-bold px-5 py-2.5 rounded-xl shadow-md transition cursor-pointer">Save Rate</button>
            </div>

            <div class="mt-4 p-3 bg-slate-50 dark:bg-slate-900/60 rounded-xl border border-slate-100 dark:border-slate-800">
                <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">
                    <span class="font-bold">How it works:</span> This is the default flat rate (₱/kg) applied to independent and company-hauled routes, where total cost = rate × total kg and each farmer's share is split by cargo weight × haul distance. For cooperative routes, each farmer's share is instead set by the hauling rate agreed in their negotiation chat (rate × their kg).
                </p>
            </div>
        </form>
    </div>

    {{-- NFA Reference Rates --}}
    <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6 mb-6">
        <h2 class="text-sm font-extrabold text-slate-800 dark:text-slate-200 mb-1">NFA Approved Hauling Rates</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">The National Food Authority publishes approved hauling rate schedules used in government grain transport contracts. These serve as industry benchmarks for setting your own rates.</p>

        {{-- Region VIII --}}
        <div class="mb-5">
            <h3 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Region VIII — Outside 10km Radius</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-700/60">
                            <th class="text-left py-2 pr-4 font-bold text-slate-500 dark:text-slate-400">Distance Range</th>
                            <th class="text-right py-2 font-bold text-slate-500 dark:text-slate-400">Rate (P/MT/km)</th>
                        </tr>
                    </thead>
                    <tbody class="text-slate-700 dark:text-slate-300 font-medium">
                        <tr class="border-b border-slate-50 dark:border-slate-800/60"><td class="py-1.5 pr-4">Within 10 km radius</td><td class="py-1.5 text-right font-mono">0.50/bag</td></tr>
                        <tr class="border-b border-slate-50 dark:border-slate-800/60"><td class="py-1.5 pr-4">10 – 20 km</td><td class="py-1.5 text-right font-mono">0.38</td></tr>
                        <tr class="border-b border-slate-50 dark:border-slate-800/60"><td class="py-1.5 pr-4">20 – 30 km</td><td class="py-1.5 text-right font-mono">0.37</td></tr>
                        <tr class="border-b border-slate-50 dark:border-slate-800/60"><td class="py-1.5 pr-4">30 – 40 km</td><td class="py-1.5 text-right font-mono">0.35</td></tr>
                        <tr class="border-b border-slate-50 dark:border-slate-800/60"><td class="py-1.5 pr-4">40 – 50 km</td><td class="py-1.5 text-right font-mono">0.33</td></tr>
                        <tr class="border-b border-slate-50 dark:border-slate-800/60"><td class="py-1.5 pr-4">50 – 60 km</td><td class="py-1.5 text-right font-mono">0.31</td></tr>
                        <tr class="border-b border-slate-50 dark:border-slate-800/60"><td class="py-1.5 pr-4">60 – 70 km</td><td class="py-1.5 text-right font-mono">0.29</td></tr>
                        <tr class="border-b border-slate-50 dark:border-slate-800/60"><td class="py-1.5 pr-4">70 – 80 km</td><td class="py-1.5 text-right font-mono">0.27</td></tr>
                        <tr class="border-b border-slate-50 dark:border-slate-800/60"><td class="py-1.5 pr-4">80 – 90 km</td><td class="py-1.5 text-right font-mono">0.25</td></tr>
                        <tr class="border-b border-slate-50 dark:border-slate-800/60"><td class="py-1.5 pr-4">90 – 100 km</td><td class="py-1.5 text-right font-mono">0.23</td></tr>
                        <tr><td class="py-1.5 pr-4">Over 100 km</td><td class="py-1.5 text-right font-mono">0.21</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Region I Flat Terrain --}}
        <div class="mb-5">
            <h3 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Region I — Flat Terrain</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-700/60">
                            <th class="text-left py-2 pr-4 font-bold text-slate-500 dark:text-slate-400">Distance Range</th>
                            <th class="text-right py-2 font-bold text-slate-500 dark:text-slate-400">Rate (P/MT/km)</th>
                        </tr>
                    </thead>
                    <tbody class="text-slate-700 dark:text-slate-300 font-medium">
                        <tr class="border-b border-slate-50 dark:border-slate-800/60"><td class="py-1.5 pr-4">0 – 10 km</td><td class="py-1.5 text-right font-mono">0.85</td></tr>
                        <tr class="border-b border-slate-50 dark:border-slate-800/60"><td class="py-1.5 pr-4">10 – 20 km</td><td class="py-1.5 text-right font-mono">0.80</td></tr>
                        <tr class="border-b border-slate-50 dark:border-slate-800/60"><td class="py-1.5 pr-4">20 – 30 km</td><td class="py-1.5 text-right font-mono">0.77</td></tr>
                        <tr class="border-b border-slate-50 dark:border-slate-800/60"><td class="py-1.5 pr-4">30 – 40 km</td><td class="py-1.5 text-right font-mono">0.74</td></tr>
                        <tr class="border-b border-slate-50 dark:border-slate-800/60"><td class="py-1.5 pr-4">40 – 50 km</td><td class="py-1.5 text-right font-mono">0.71</td></tr>
                        <tr class="border-b border-slate-50 dark:border-slate-800/60"><td class="py-1.5 pr-4">50 – 100 km</td><td class="py-1.5 text-right font-mono">0.69</td></tr>
                        <tr class="border-b border-slate-50 dark:border-slate-800/60"><td class="py-1.5 pr-4">100 – 150 km</td><td class="py-1.5 text-right font-mono">0.67</td></tr>
                        <tr class="border-b border-slate-50 dark:border-slate-800/60"><td class="py-1.5 pr-4">150 – 200 km</td><td class="py-1.5 text-right font-mono">0.63</td></tr>
                        <tr class="border-b border-slate-50 dark:border-slate-800/60"><td class="py-1.5 pr-4">200 – 250 km</td><td class="py-1.5 text-right font-mono">0.61</td></tr>
                        <tr class="border-b border-slate-50 dark:border-slate-800/60"><td class="py-1.5 pr-4">250 – 350 km</td><td class="py-1.5 text-right font-mono">0.59</td></tr>
                        <tr class="border-b border-slate-50 dark:border-slate-800/60"><td class="py-1.5 pr-4">350 – 550 km</td><td class="py-1.5 text-right font-mono">0.57</td></tr>
                        <tr><td class="py-1.5 pr-4">Over 550 km</td><td class="py-1.5 text-right font-mono">0.55</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mountainous --}}
        <div class="mb-5">
            <h3 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Mountainous Terrain (All Regions, 100 km or less)</h3>
            <p class="text-xs text-slate-700 dark:text-slate-300 font-medium font-mono bg-slate-50 dark:bg-slate-900/60 px-3 py-2 rounded-md border border-slate-100 dark:border-slate-800">P1.20 / MT / km</p>
        </div>

        {{-- Procurement Reference --}}
        <div class="mb-5">
            <h3 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">NFA Procurement Bid Prices (Mindanao)</h3>
            <div class="space-y-2">
                <div class="flex items-start gap-2 text-xs text-slate-700 dark:text-slate-300">
                    <span class="shrink-0 w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-600 mt-1.5"></span>
                    <span><span class="font-mono font-bold">P22.25/bag</span> (50kg) — Surigao City to Alegria, Agusan del Sur, ~70km (NFA Caraga, 2015 bid)</span>
                </div>
                <div class="flex items-start gap-2 text-xs text-slate-700 dark:text-slate-300">
                    <span class="shrink-0 w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-600 mt-1.5"></span>
                    <span><span class="font-mono font-bold">P34.55/bag</span> (50kg) — Same route, separate truck allocation (NFA Caraga, 2015 bid)</span>
                </div>
                <div class="flex items-start gap-2 text-xs text-slate-700 dark:text-slate-300">
                    <span class="shrink-0 w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-600 mt-1.5"></span>
                    <span><span class="font-mono font-bold">P0.45–0.69/kg</span> equivalent for intra-regional 50–100km grain transport (2015 bid range, Region XII and Caraga)</span>
                </div>
            </div>
        </div>

        {{-- Study Reference --}}
        <div>
            <h3 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Academic Reference</h3>
            <div class="p-3 bg-slate-50 dark:bg-slate-900/60 rounded-xl border border-slate-100 dark:border-slate-800">
                <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed">
                    A PIDS/SEARCA study of Mindanao vegetable trucking operations (covering GenSan, Tupi, and Bukidnon routes) found that transport cost per kilogram rises steeply with distance and deteriorating road conditions. Mountainous terrain and unpaved road segments increase per-km cost by 40–70% over flat, paved routes. The study recommends that haulers factor in not just fuel but also vehicle depreciation, maintenance, and driver allowances when setting per-kg rates. Set your actual rate based on your fleet's real operating costs.
                </p>
            </div>
        </div>
    </div>

    {{-- Conversion Note --}}
    <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6">
        <h2 class="text-sm font-extrabold text-slate-800 dark:text-slate-200 mb-2">Understanding the Rates</h2>
        <div class="space-y-3 text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
            <p>The NFA rates above are expressed in <span class="font-bold">P/MT/km</span> (pesos per metric ton per kilometer). To convert to a per-kg flat rate for HarvestHaul:</p>
            <div class="p-3 bg-slate-50 dark:bg-slate-900/60 rounded-xl border border-slate-100 dark:border-slate-800 font-mono text-slate-700 dark:text-slate-300">
                <p>Example: NFA rate = P0.33/MT/km for 40–50km distance</p>
                <p>For a 50km haul: P0.33 x 50 = P16.50/MT = <span class="font-bold">P0.0165/kg</span></p>
                <p class="mt-2">NFA rates cover government-subsidized grain transport only. Private haulers typically charge <span class="font-bold">P0.50–2.00/kg</span> depending on distance, terrain, and cargo type.</p>
            </div>
            <p>The NFA rates are a floor benchmark. Your rate should cover fuel, driver wages, vehicle depreciation, maintenance, tolls, and profit margin. The default of <span class="font-bold">P1.50/kg</span> is a common mid-range rate for short-haul agricultural transport in Mindanao.</p>
        </div>
    </div>

</div>
</x-layout>
