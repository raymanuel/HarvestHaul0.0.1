<x-layout>
    @push('head')
        <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />
    @endpush
    @push('scripts')
        <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
        <script src="{{ asset('vendor/turf/turf.min.js') }}"></script>
    @endpush

    <div class="w-full pb-12">
        @php
            $doneDealFarms = collect($farmersData)
                ->filter(fn($f) => collect($f['harvests'] ?? [])->contains(fn($h) => !empty($h['completed_negotiation'])))
                ->values();
        @endphp
        <header class="pt-8 mb-6 border-b border-slate-200/80 dark:border-slate-700/80 pb-5">
            <span class="text-xs font-bold uppercase tracking-wider text-brand dark:text-brand-light bg-brand/10 dark:bg-brand/10 px-3 py-1.5 rounded-md border border-brand/10 dark:border-brand/20 self-start">Routing</span>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white heading-font mt-2">Route Planning</h1>

        </header>

        <x-flash-success />

        {{-- ─── Truck Selector + Generate Plan Bar ─── --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-5 mb-6 flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[220px]">
                <label for="truck-select" class="block text-xs font-bold text-slate-400 dark:text-slate-600 uppercase tracking-widest mb-2">Select Truck</label>
                <div class="relative">
                    <select id="truck-select" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl px-4 py-3 text-sm focus:border-brand focus:ring-4 focus:ring-brand/10 transition outline-none appearance-none cursor-pointer">
                        <option value="">— Choose a truck —</option>
                        @forelse($trucks as $truck)
                            <option value="{{ $truck['id'] }}"
                                    data-capacity="{{ $truck['capacity_kg'] }}"
                                    data-volume="{{ $truck['capacity_volume_cubic_m'] ?? '' }}"
                                    data-driver="{{ $truck['driver'] }}"
                                    @if($suggestedTruckId === $truck['id']) selected @endif>
                                {{ $truck['label'] }} ({{ number_format($truck['capacity_kg']) }} kg)
                                @if($suggestedTruckId === $truck['id']) <x-icon name="sun" class="w-4 h-4" /> Recommended @endif
                            </option>
                        @empty
                            <option disabled>No available trucks</option>
                        @endforelse
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                    </div>
                </div>
            </div>

            <div class="flex-1 min-w-[220px]">
                <label for="driver-select" class="block text-xs font-bold text-slate-400 dark:text-slate-600 uppercase tracking-widest mb-2">Assign Driver</label>
                <div class="relative">
                    <select id="driver-select" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl px-4 py-3 text-sm focus:border-brand focus:ring-4 focus:ring-brand/10 transition outline-none appearance-none cursor-pointer">
                        <option value="">Auto-assign (nearest)</option>
                        @forelse($availableDrivers as $driver)
                            <option value="{{ $driver['id'] }}"
                                    data-active-jobs="{{ $driver['active_jobs'] }}"
                                    data-last-assigned="{{ $driver['last_assigned'] ?? 'Never' }}">
                                {{ $driver['name'] }} ({{ $driver['active_jobs'] }} active jobs)
                            </option>
                        @empty
                            <option disabled>No available drivers</option>
                        @endforelse
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                    </div>
                </div>
            </div>

            <div id="truck-info" class="hidden text-sm border rounded-xl px-5 py-3 transition-all duration-200">
                <span id="truck-info-driver"></span> &bull;
                <span id="truck-info-capacity"></span>
            </div>

            <button id="btn-show-map" type="button"
                    class="text-sm font-bold text-brand dark:text-brand-light bg-brand/10 dark:bg-brand/10 border border-brand/20 dark:border-brand/15 hover:bg-brand/20 dark:hover:bg-brand/20 rounded-xl px-6 py-3.5 transition flex items-center gap-2">
                <span><x-icon name="pin" class="w-4 h-4" /></span> Show Map
            </button>

            <button id="btn-hide-map" type="button"
                    class="hidden text-sm font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-800 rounded-xl px-6 py-3.5 transition flex items-center gap-2">
                <span><x-icon name="pin" class="w-4 h-4" /></span> Hide Map
            </button>

            <button id="btn-toggle-options" type="button"
                    class="text-sm font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-800 rounded-xl px-4 py-3.5 transition flex items-center gap-2"
                    aria-expanded="false" aria-controls="routing-options">
                <span class="translate-y-[-1px] leading-none">&#9881;</span> Options
            </button>

            <x-button id="btn-generate-plan" disabled size="lg">
                <span><x-icon name="calculator" class="w-4 h-4" /></span> Generate Route Plan
            </x-button>
        </div>

        {{-- Route-ready cue shown while the map stays hidden (Layout #1) --}}
        <div id="route-ready-cue" class="hidden bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm px-5 py-4 mb-6 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-700 dark:text-slate-300 font-semibold flex items-center gap-2">
                <span class="text-brand"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg></span>
                Route ready — pick your truck and open the map to review pickups before generating.
            </p>
            <button id="btn-open-map-cue" type="button"
                    class="text-sm font-bold text-brand dark:text-brand-light bg-brand/10 dark:bg-brand/10 border border-brand/20 dark:border-brand/15 hover:bg-brand/20 dark:hover:bg-brand/20 rounded-xl px-6 py-3 transition flex items-center gap-2">
                <span><x-icon name="pin" class="w-4 h-4" /></span> View on map
            </button>
        </div>

        {{-- ─── Collapsible Options: rate + radius + routing hints ─── --}}
        <div id="routing-options" class="hidden bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-5 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-4">
                <div>
                    <label for="radius-select" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Search Radius</label>
                    <div class="relative">
                        <select id="radius-select" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl px-3 py-2.5 text-xs focus:border-brand focus:ring-4 focus:ring-brand/10 transition outline-none appearance-none cursor-pointer">
                            <option value="1">Within 1 km</option>
                            <option value="3">Within 3 km</option>
                            <option value="5" selected>Within 5 km</option>
                            <option value="10">Within 10 km</option>
                            <option value="20">Within 20 km</option>
                            <option value="50">Within 50 km</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                            <svg class="fill-current h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                    <p id="radius-description" class="text-[11px] text-slate-500 dark:text-slate-500 mt-2 leading-relaxed">Farms within this buffer off the planned road segments auto-detect.</p>
                </div>

                <div>
                    <label for="terrain-select" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Road Terrain</label>
                    <div class="relative">
                        <select id="terrain-select" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl px-3 py-2.5 text-xs focus:border-brand focus:ring-4 focus:ring-brand/10 transition outline-none appearance-none cursor-pointer">
                            <option value="flat" selected>Mostly flat</option>
                            <option value="rolling">Rolling hills</option>
                            <option value="mountainous">Mountainous</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                            <svg class="fill-current h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-500 mt-2 leading-relaxed">Hilly terrain adds fuel and vehicle wear — the fair rate suggestion rises to match.</p>
                </div>

                @unless($isCoop)
                <div>
                    <label for="hauling-rate" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Flat Hauling Rate (&#8369;/kg)</label>
                    <input type="number" id="hauling-rate" step="0.01" min="0.1" value="{{ $logisticsProfile->default_hauling_rate ?? '1.50' }}"
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl px-3 py-2.5 text-xs font-mono focus:border-brand focus:ring-4 focus:ring-brand/10 transition outline-none"
                        placeholder="e.g. 1.50" />
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">Total price = rate &#215; total kg. Split across farmers by cargo weight &#215; haul distance.</p>
                </div>
                @endunless
            </div>

            @if($isCoop)
            <p class="text-[10px] text-slate-600 dark:text-slate-300 leading-relaxed font-semibold bg-brand/5 dark:bg-slate-900/40 border border-brand/15 dark:border-slate-700/60 rounded-xl px-4 py-3 mb-4">
                Each farmer's hauling rate was agreed in their negotiation chat and is read automatically. No flat rate needed.
            </p>
            @endif

            <div class="flex flex-wrap gap-2">
                @if($suggestedTruckId)
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand dark:text-brand-light bg-brand/10 border border-brand/20 rounded-md px-3 py-1.5">
                        <span><x-icon name="sun" class="w-3.5 h-3.5" /></span> Auto-Recommended truck
                    </span>
                @endif
                @if($nearestDriver)
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-md px-3 py-1.5">
                        <span><x-icon name="pin" class="w-3.5 h-3.5" /></span> Nearest Driver: {{ $nearestDriver['driver']->name }} ({{ $nearestDriver['distance_km'] }} km)
                    </span>
                @endif
            </div>
        </div>

        {{-- ─── Main Grid: Map + Sidebar (hidden until "Show Map" / route planning) ─── --}}
        <div id="map-section" class="hidden">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Map --}}
            <div class="lg:col-span-2">
                <div id="routing-map" class="w-full rounded-2xl border border-slate-200 dark:border-slate-700 relative z-10 shadow-sm h-[400px] sm:h-[600px]"></div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-3 flex items-center gap-1">
                    <span><x-icon name="sun" class="w-4 h-4" /></span> <b>Route auto-populated from your hub and deal destinations. If a route hasn't formed, click the map to drop start and end pins.</b>
                </p>
            </div>

            {{-- Sidebar --}}
            <div class="bg-slate-50 dark:bg-slate-900/40 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 p-5 flex flex-col h-[400px] sm:h-[600px]">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-250 heading-font mb-4 flex items-center gap-2">
                    <span class="text-brand"><x-icon name="pin" class="w-4 h-4" /></span> Route Pickups
                </h3>

                {{-- Weather Widget --}}
                <div id="weather-widget" class="hidden mb-4 bg-white dark:bg-slate-800 p-3 rounded-xl border border-slate-200/60 dark:border-slate-700/60 shadow-sm">
                    <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">
                        <span><x-icon name="cloud-sun" class="w-4 h-4" /></span> Weather at Route Origin
                    </div>
                    <div class="flex items-center gap-3">
                        <span id="weather-icon" class="text-2xl">—</span>
                        <div>
                            <p id="weather-condition" class="text-sm font-bold text-slate-700 dark:text-slate-300">—</p>
                            <p id="weather-temp" class="text-[11px] text-slate-500">—</p>
                        </div>
                    </div>
                    <p id="weather-advisory" class="text-[10px] text-slate-500 mt-2 italic">—</p>
                </div>

                <div id="pickup-queue" class="flex-1 overflow-y-auto space-y-3 pr-1 custom-scroll">
                    <div class="text-center text-slate-500 dark:text-slate-400 mt-10 italic text-xs">Awaiting route coordinates...</div>
                </div>

                <button id="reset-map" class="w-full mt-4 bg-[var(--color-error-bg)] text-[var(--color-error-text)] font-bold py-2.5 rounded-xl border border-[var(--color-error-border)] hover:bg-red-100 dark:hover:bg-red-950/40 transition text-xs hidden">
                    Clear Route & Restart
                </button>
            </div>
        </div>
        </div>

        {{-- ─── Pooling Plan Panel ─── --}}
        <div id="plan-panel" class="hidden mt-8 bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 heading-font"><x-icon name="calculator" class="w-5 h-5" /> Consolidated Pooling Plan</h2>
                <span id="plan-status-badge" class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-md bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border border-[var(--color-warning-border)]">Unconfirmed</span>
            </div>

            {{-- Capacity honesty: knapsack dropped some checked deals --}}
            <div id="plan-capacity-banner" class="hidden mb-6 p-4 rounded-xl text-sm font-bold bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border border-[var(--color-warning-border)] flex items-start gap-2">
                <span><svg class="w-5 h-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></span>
                <span id="plan-capacity-banner-text"></span>
            </div>

            {{-- Rate sanity: how the quoted rate compares to road-cost suggestion --}}
            <div id="plan-rate-sanity-banner" class="hidden mb-6 p-4 rounded-xl text-sm font-bold bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border border-[var(--color-warning-border)] flex items-start gap-2">
                <span><svg class="w-5 h-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></span>
                <span id="plan-rate-sanity-text"></span>
            </div>

            {{-- Summary row --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-100 dark:border-slate-700/40 text-center hover:shadow-sm transition-shadow duration-200">
                    <p class="text-[10px] text-slate-400 dark:text-slate-600 uppercase tracking-widest font-bold">Farms Selected</p>
                    <p id="plan-farm-count" class="text-2xl font-black text-slate-800 dark:text-white mt-1">—</p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-100 dark:border-slate-700/40 text-center hover:shadow-sm transition-shadow duration-200">
                    <p class="text-[10px] text-slate-400 dark:text-slate-600 uppercase tracking-widest font-bold">Total Load</p>
                    <p id="plan-total-kg" class="text-2xl font-black text-brand dark:text-brand-light mt-1">—</p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-100 dark:border-slate-700/40 text-center hover:shadow-sm transition-shadow duration-200">
                    <p class="text-[10px] text-slate-400 dark:text-slate-600 uppercase tracking-widest font-bold">Capacity Used</p>
                    <p id="plan-load-pct" class="text-2xl font-black text-[var(--color-info-text)] mt-1">—</p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-100 dark:border-slate-700/40 text-center hover:shadow-sm transition-shadow duration-200">
                    <p class="text-[10px] text-slate-400 dark:text-slate-600 uppercase tracking-widest font-bold">Est. Distance</p>
                    <p id="plan-distance" class="text-2xl font-black text-slate-700 dark:text-slate-400 mt-1">—</p>
                </div>
                <div class="bg-gold/10 dark:bg-gold/10 rounded-xl p-4 border border-gold/25 dark:border-gold/25 text-center ring-2 ring-gold/10 hover:shadow-sm transition-shadow duration-200">
                    <p class="text-[10px] text-gold-700 dark:text-gold-light font-bold uppercase tracking-widest">Total Haul Cost</p>
                    <p id="plan-price-ref" class="text-2xl font-black text-gold-700 dark:text-gold-light mt-1">—</p>
                    <p id="plan-rate" class="text-[10px] text-gold-700/80 dark:text-gold-light/80 font-bold mt-1">—</p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-100 dark:border-slate-700/40 text-center hover:shadow-sm transition-shadow duration-200">
                    <p class="text-[10px] text-slate-400 dark:text-slate-600 uppercase tracking-widest font-bold">Assigned Truck</p>
                    <p id="plan-truck-label" class="text-xs font-bold text-slate-600 dark:text-slate-400 mt-2.5 truncate">—</p>
                </div>
            </div>

            {{-- Road-distance rate suggestion (plain label→value row, shown after route is generated) --}}
            <p id="plan-rate-suggestion" class="hidden text-xs text-slate-600 dark:text-slate-300 mb-6 bg-slate-50 dark:bg-slate-900/50 border border-slate-200/60 dark:border-slate-700/50 rounded-xl px-4 py-3">
                <span class="font-bold">Suggested rate:</span>
                <span id="plan-suggested-rate" class="font-mono font-bold text-brand dark:text-brand-light">—</span>
                <span class="text-slate-500 dark:text-slate-400">/kg</span>
                <span class="text-slate-400 dark:text-slate-500 mx-1">·</span>
                <span id="plan-suggested-basis" class="text-slate-500 dark:text-slate-400">based on OSRM road distance + terrain</span>
            </p>

            {{-- Farm pickup order table --}}
            <div class="overflow-x-auto border border-slate-200/60 dark:border-slate-700/60 rounded-2xl mb-6">
                <table class="w-full text-sm text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/40 text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-widest font-bold">
                            <th class="py-3.5 px-4">Order</th>
                            <th class="py-3 px-4">Farm</th>
                            <th class="py-3 px-4">Location</th>
                            <th class="py-3 px-4">Crop(s)</th>
                            <th class="py-3 px-4">Drop-off</th>
                            <th class="py-3 px-4">Load</th>
                            <th class="py-3 px-4">Pickup Window</th>
                            <th class="py-3 px-4 text-right">Rate (₱/kg)</th>
                            <th class="py-3 px-4 text-right">Cost Share</th>
                        </tr>
                    </thead>
                    <tbody id="plan-table-body" class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <tr><td colspan="9" class="py-6 text-center text-slate-500 dark:text-slate-400 italic text-xs">No plan generated yet.</td></tr>
                    </tbody>
                </table>
            </div>

            {{-- Notes + Proposal Submission Trigger --}}
            <div class="flex flex-wrap items-end gap-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                <div class="flex-1 min-w-[220px]">
                    <label for="plan-notes" class="block text-xs font-bold text-slate-400 dark:text-slate-600 uppercase tracking-widest mb-2">Instructions / Notes (optional)</label>
                    <input id="plan-notes" type="text" maxlength="500"
                           placeholder="e.g., Deliver to port before 12:00 PM, secure tarpaulin"
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl px-4 py-3 text-sm focus:border-brand focus:ring-4 focus:ring-brand/10 transition outline-none">
                </div>
                <x-button id="btn-confirm-plan" size="lg">
                    <span><x-icon name="inbox" class="w-4 h-4" /></span> Create Delivery Proposal
                </x-button>
            </div>

            {{-- Confirm feedback --}}
            <div id="confirm-feedback" class="hidden mt-4 p-4 rounded-xl text-sm font-bold animate-pulse"></div>
        </div>

        {{-- Multi-Truck Plan Panel (overflow -> extra truck) --}}
        <div id="plan-all-panel" class="hidden mt-8 space-y-5">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 heading-font"><x-icon name="calculator" class="w-5 h-5" /> Multi-Truck Route Plan</h2>
                <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-md bg-brand/10 text-brand dark:bg-brand/10 dark:text-brand-light border border-brand/20 dark:border-brand/15"><span id="plan-all-count">0</span> routes</span>
            </div>

            {{-- Amber warning: some farms could not be loaded (no more trucks) --}}
            <div id="plan-all-unassigned-banner" class="hidden p-4 rounded-xl text-sm font-bold bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border border-[var(--color-warning-border)] flex items-start gap-2">
                <span><svg class="w-5 h-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></span>
                <div id="plan-all-unassigned-text"></div>
            </div>

            <div id="plan-all-cards" class="space-y-5"></div>

            <div class="flex flex-wrap items-end gap-4 pt-4 bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-5">
                <div class="flex-1 min-w-[220px]">
                    <label for="plan-all-notes" class="block text-xs font-bold text-slate-400 dark:text-slate-600 uppercase tracking-widest mb-2">Instructions / Notes (optional)</label>
                    <input id="plan-all-notes" type="text" maxlength="500"
                           placeholder="e.g., Deliver to port before 12:00 PM, secure tarpaulin"
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl px-4 py-3 text-sm focus:border-brand focus:ring-4 focus:ring-brand/10 transition outline-none">
                </div>
                <x-button id="btn-confirm-all" size="lg">
                    <span><x-icon name="inbox" class="w-4 h-4" /></span> Confirm All Routes
                </x-button>
            </div>

            {{-- Confirm feedback --}}
            <div id="confirm-all-feedback" class="hidden mt-4 p-4 rounded-xl text-sm font-bold animate-pulse"></div>
        </div>

        {{-- ─── Open Haul Requests (list-first primary) + My Routes ─── --}}
        <div class="space-y-6 mt-8">

            {{-- Done Deals (completed negotiations ready for routing) --}}
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-5">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 heading-font flex items-center gap-2">
                        <span class="text-brand"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg></span>
                        Done Deals
                    </h3>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-brand dark:text-brand-light bg-brand/10 dark:bg-brand/10 border border-brand/20 dark:border-brand/15 px-2.5 py-1 rounded-md" id="open-haul-count">{{ $doneDealFarms->count() }}</span>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-4 leading-relaxed">Finalized B2B deals ready for pickup routing. Click Show in Map to focus on a deal's pickup and drop-off points.</p>
                <div id="open-haul-requests" class="space-y-3 max-h-[480px] overflow-y-auto pr-1 custom-scroll">
                    @forelse($doneDealFarms as $doneFarm)
                        @php
                            $doneHarvest = collect($doneFarm['harvests'] ?? [])->first(fn($h) => !empty($h['completed_negotiation']));
                            $dealTotalKg = collect($doneFarm['harvests'] ?? [])->sum(fn($h) => (float) ($h['quantity'] ?? 0));
                            $deal = $doneHarvest['completed_negotiation'] ?? null;
                        @endphp
                        <div class="bg-slate-50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-700/60  rounded-xl p-3.5">
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-200 heading-font truncate">{{ $doneFarm['name'] }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                                @if($doneHarvest)<span class="font-semibold">{{ $doneHarvest['crop'] }}</span> &bull; @endif
                                {{ number_format($dealTotalKg) }} kg
                                @if($deal) &bull; <span class="font-semibold text-brand dark:text-brand-light">&#8369;{{ number_format($deal['price'], 2) }}/kg</span> @endif
                            </p>
                            @if($deal)
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">Buyer: {{ $deal['buyer'] }}</p>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">Drop-off: {{ $deal['dropoff'] ?? $doneFarm['destination_address'] ?? '—' }}</p>
                            @endif
                            <div class="flex items-center gap-2 mt-2.5">
                                <button onclick="focusDealOnMap('{{ addslashes($doneFarm['name']) }}')" class="flex-1 text-[11px] font-bold text-brand dark:text-brand-light bg-brand/10 dark:bg-brand/10 border border-brand/20 dark:border-brand/15 hover:bg-brand/20 dark:hover:bg-brand/20 rounded-xl px-3 py-1.5 transition flex items-center justify-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Show in Map
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-slate-500 dark:text-slate-400 mt-8 italic text-xs">No done deals yet. Deals appear here after buyers finalize negotiations.</div>
                    @endforelse
                </div>
            </div>

            {{-- My Routes (pooling jobs) --}}
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-5">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 heading-font flex items-center gap-2">
                        <span class="text-brand"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg></span>
                        My Routes
                    </h3>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 px-2.5 py-1 rounded-md">{{ $myRoutes->count() }}</span>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-4 leading-relaxed">Recent pooling routes for this logistics partner. Select View Map to recall the saved route on the map.</p>

                @forelse($myRoutes as $route)
                    <div class="flex items-center justify-between gap-3 bg-slate-50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-700/60  rounded-xl p-3.5 mb-3">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-200 heading-font truncate">
                                <a href="{{ route('pooling.show', $route['id']) }}" class="hover:underline">Job #{{ $route['id'] }}</a>
                            </p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                                @if($route['crops'])
                                    <span class="font-semibold">{{ $route['crops'] }}</span> &bull;
                                @endif
                                {{ $route['farm_count'] }} farm{{ $route['farm_count'] == 1 ? '' : 's' }} &bull;
                                {{ number_format($route['total_kg']) }} kg &bull;
                                {{ $route['driver'] ?: 'No driver' }}
                            </p>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                                {{ $route['truck'] }} &bull; {{ number_format($route['planned_distance_km'], 1) }} km &bull; {{ $route['created_at'] }}
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-1.5 shrink-0">
                            <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md
                                {{ $route['status'] === 'in_progress' ? 'bg-[var(--color-info-bg)] text-[var(--color-info-text)] border border-[var(--color-info-border)]' : '' }}
                                {{ $route['status'] === 'confirmed' ? 'bg-brand text-white dark:bg-brand-light dark:text-brand border border-brand dark:border-brand-light' : '' }}
                                {{ $route['status'] === 'awaiting_confirmation' ? 'bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border border-[var(--color-warning-border)]' : '' }}
                                {{ !in_array($route['status'], ['in_progress', 'confirmed', 'awaiting_confirmation']) ? 'bg-slate-100 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700' : '' }}">
                                {{ ucfirst(str_replace('_', ' ', $route['status'])) }}
                            </span>
                            <button onclick="viewRouteMap({{ $route['id'] }})"
                                class="text-[11px] font-bold text-brand dark:text-brand-light bg-brand/10 dark:bg-brand/10 border border-brand/20 dark:border-brand/15 hover:bg-brand/20 dark:hover:bg-brand/20 rounded-md px-3 py-1.5 transition flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                View Map
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-slate-500 dark:text-slate-400 mt-8 italic text-xs">No routes planned yet.</div>
                @endforelse
            </div>
        </div>

    </div>

    @push('scripts')
    @php $logisticsHasLocation = !is_null(Auth::user()->logisticsProfile?->latitude); @endphp
    <script>
        const _escHtml = (str) => String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
        document.addEventListener('DOMContentLoaded', function () {
            const isDark = document.documentElement.classList.contains('dark');
            // Initialize the Leaflet map centered on Southern Mindanao (GenSan coordinates)
            const map = L.map('routing-map').setView([6.1164, 125.1716], 11);
            
            // Add standard OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

            // The map section is hidden on load (list-first flow); reveal it and
            // re-measure the Leaflet container once it becomes visible.
            // Generate stays locked until the logistics user actually opens the
            // map to review pickups (Layout #1). Map can render routes in the
            // background; a cue banner offers "View on map".
            let mapRevealed = false;
            function refreshGenerateState() {
                const selectedOpt = truckSelect.options[truckSelect.selectedIndex];
                const validDriver = selectedOpt && selectedOpt.value &&
                                    selectedOpt.dataset.driver !== 'No driver assigned' &&
                                    selectedOpt.dataset.driver.trim() !== '';
                btnGenerate.disabled = !(validDriver && mapRevealed && baseRouteGeoJSON && startMarker && endMarker);
            }
            function revealMap() {
                const section = document.getElementById('map-section');
                if (section) section.classList.remove('hidden');
                map.invalidateSize();
                setTimeout(function () { map.invalidateSize(); }, 300);
                document.getElementById('routing-map').scrollIntoView({ behavior: 'smooth', block: 'center' });
                document.getElementById('btn-show-map').classList.add('hidden');
                document.getElementById('btn-hide-map').classList.remove('hidden');
                mapRevealed = true;
                document.getElementById('route-ready-cue').classList.add('hidden');
                refreshGenerateState();
            }
            function hideMap() {
                const section = document.getElementById('map-section');
                if (section) section.classList.add('hidden');
                document.getElementById('btn-show-map').classList.remove('hidden');
                document.getElementById('btn-hide-map').classList.add('hidden');
                document.getElementById('btn-show-map').scrollIntoView({ behavior: 'smooth', block: 'center' });
                mapRevealed = false;
                refreshGenerateState();
            }
            document.getElementById('btn-show-map').addEventListener('click', function () {
                revealMap();
            });
            document.getElementById('btn-hide-map').addEventListener('click', function () {
                hideMap();
            });
            document.getElementById('btn-open-map-cue').addEventListener('click', function () {
                revealMap();
            });

            // ─── Collapsible Options toggle ─────────────────────────────
            const btnOptions   = document.getElementById('btn-toggle-options');
            const optionsPanel = document.getElementById('routing-options');
            if (btnOptions && optionsPanel) {
                btnOptions.addEventListener('click', function () {
                    const isOpen = !optionsPanel.classList.contains('hidden');
                    optionsPanel.classList.toggle('hidden');
                    this.setAttribute('aria-expanded', String(!isOpen));
                });
            }

            // Farmers coordinate data passed from the controller
            const farms = @json($farmersData);
            const hubLat = @json($hubLat);
            const hubLng = @json($hubLng);

            // Map and routing variables
            let baseRouteGeoJSON     = null;  // Original straight-line route path
            let farmMarkers          = [];    // Array holding all plotted farmer pins
            window.farmMarkers = farmMarkers;
            let destinationMarkers   = [];    // Array holding all harvest destination markers
            let startMarker          = null;  // Depot / start pin
            let endMarker            = null;  // Market / end destination pin
            let routePolyline        = null;  // Line drawn on Leaflet map representing active path
            let currentRouteGeoJSON  = null;  // Current active route geometry (e.g. including detours)
            let currentRouteDistanceKm = null; // Exact road distance (km) reported by the routing engine
            let lastNearbyFarms      = [];    // Farms matched inside the selected radius
            let selectedFarmIds     = new Set(); // Farms ticked into this load (manual split)
            let toggledFarmIds     = new Set(); // Farms the user explicitly ticked/unticked (stick across redraws)
            let currentPlan          = null;  // Final calculated cost and weight allocations
            let currentPlans         = [];    // Multi-route plans from planAll
            let currentExcluded      = [];    // Excluded farms from planAll
            let currentFarmDistances = {};    // Per-farm road distances from OSRM (km)
            let rateTouched = false;            // User typed a hauling rate — stop auto-filling
            let suggestedPrefilled = false;     // Suggested rate auto-filled once per page

            /**
             * Total road distance (km) of the drawn OSRM geometry.
             * Approximate haversine over the route nodes — good enough for a
             * rate suggestion and never used for billing.
             */
            function polylineKm(geojson) {
                var c = geojson && geojson.coordinates ? geojson.coordinates : [];
                var km = 0;
                for (var i = 1; i < c.length; i++) {
                    var a = c[i - 1], b = c[i];
                    if (!a || !b) continue;
                    var R = 6371, dLat = (b[1] - a[1]) * Math.PI / 180, dLng = (b[0] - a[0]) * Math.PI / 180;
                    var h = Math.sin(dLat / 2) ** 2 + Math.cos(a[1] * Math.PI / 180) * Math.cos(b[1] * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
                    km += 2 * R * Math.asin(Math.sqrt(h));
                }
                return km;
            }
            function currentRouteKm() {
                if (currentRouteDistanceKm != null && currentRouteDistanceKm > 0) return currentRouteDistanceKm;
                return currentRouteGeoJSON ? polylineKm(currentRouteGeoJSON) : null;
            }
            var haulingRateInput = document.getElementById('hauling-rate');
            if (haulingRateInput) { haulingRateInput.addEventListener('input', function () { rateTouched = true; }); }

            // Saved pooling routes (for the "My Routes" panel) + dedicated layer for route recall
            const myRoutes    = @json($myRoutes);
            const historyLayer = L.layerGroup().addTo(map);

            // Colors for custom markers
            const defaultIcon   = L.icon({ iconUrl: '{{ asset('vendor/leaflet/markers/marker-icon-blue.png') }}',  iconSize: [25, 41], iconAnchor: [12, 41] });
            const highlightIcon = L.icon({ iconUrl: '{{ asset('vendor/leaflet/markers/marker-icon-green.png') }}', iconSize: [25, 41], iconAnchor: [12, 41] });

            // High contrast red marker mapping for drop-off terminals / buyers
            const destinationMarkerIcon = L.icon({ iconUrl: '{{ asset('vendor/leaflet/markers/marker-icon-red.png') }}', iconSize: [25, 41], iconAnchor: [12, 41] });

            const truckSelect   = document.getElementById('truck-select');
            const truckInfo     = document.getElementById('truck-info');
            const btnGenerate   = document.getElementById('btn-generate-plan');

            // Auto-trigger change event if a truck was pre-selected (auto-recommended)
            if (truckSelect.value) {
                truckSelect.dispatchEvent(new Event('change'));
            }

            // ─── Truck Selector & Driver Validation Guard ───────────────────────
            // Checks if the chosen truck has a valid driver assigned before allowing routing.
            // Displays feedback if a driver is required.
            truckSelect.addEventListener('change', function () {
                const opt = this.options[this.selectedIndex];
                if (!this.value) {
                    truckInfo.classList.add('hidden');
                    btnGenerate.disabled = true;
                    return;
                }

                const driverValue = opt.dataset.driver ? opt.dataset.driver.trim() : '';
                const isDriverAssigned = driverValue !== '' && driverValue !== 'No driver assigned';

                if (!isDriverAssigned) {
                    document.getElementById('truck-info-driver').textContent   = driverValue;
                    document.getElementById('truck-info-capacity').textContent = 'Requires driver assignment.';
                    truckInfo.className = 'text-xs text-[var(--color-warning-text)] bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] rounded-xl px-4 py-3.5 font-bold flex items-center gap-2';
                    truckInfo.classList.remove('hidden');
                    btnGenerate.disabled = true;
                } else {
                    document.getElementById('truck-info-driver').innerHTML     = '<svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg> Driver: <b>' + _escHtml(driverValue) + '</b>';
                    var capText = Number(opt.dataset.capacity).toLocaleString() + ' kg limit';
                    if (opt.dataset.volume) capText += ' · ' + Number(opt.dataset.volume).toLocaleString() + ' m³ limit';
                    document.getElementById('truck-info-capacity').textContent = capText;
                    truckInfo.className = 'text-xs text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3.5 font-semibold flex items-center gap-2';
                    truckInfo.classList.remove('hidden');

                    refreshGenerateState();
                }

                if (lastNearbyFarms.length > 0) {
                    renderPickupQueue(lastNearbyFarms);
                }
            });

            // ─── Driver Selector: manual override for driver assignment ───
            const driverSelect = document.getElementById('driver-select');
            driverSelect.addEventListener('change', async function () {
                const driverId = this.value;
                if (!driverId) return;

                const truckId = truckSelect.value;
                if (!truckId) return;

                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";

                swalConfirm(function () {
                    return fetch("/route-optimization/assign-driver", {
                        method: "POST",
                        headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": csrf },
                        body: JSON.stringify({ truck_id: Number(truckId), driver_id: Number(driverId) }),
                    }).then(function (res) { return res.json(); }).then(function (data) {
                        if (data.success) {
                            const selectedOpt = truckSelect.options[truckSelect.selectedIndex];
                            if (selectedOpt) selectedOpt.dataset.driver = data.driver_name;
                            truckSelect.dispatchEvent(new Event('change'));
                        }
                    }).catch(function (err) { console.error("Manual driver assignment failed:", err); });
                }, {
                    title: 'Assign This Driver?',
                    text: 'Assign ' + (driverSelect.options[driverSelect.selectedIndex] ? driverSelect.options[driverSelect.selectedIndex].text : 'this driver') + ' to this truck?',
                    icon: 'question',
                    confirmText: 'Yes, assign',
                    cancelText: 'Cancel',
                    confirmColor: '#16283C'
                });
            });

            // ─── Map Click Handlers ───────────────────────────────────────
            map.on('click', function (e) {
                if (!startMarker) {
                    startMarker = L.marker(e.latlng).addTo(map).bindPopup('<b>Start Point:</b> Hub Depot').openPopup();
                } else if (!endMarker) {
                    endMarker = L.marker(e.latlng).addTo(map).bindPopup('<b>End Point:</b> Delivery Wholesaler').openPopup();
                    generateBaseRoute(startMarker.getLatLng(), endMarker.getLatLng());
                    fetchWeather(e.latlng.lat, e.latlng.lng);
                }
            });

            // ─── OSRM Base Route Calculations & Callback Protections ──────
            async function generateBaseRoute(start, end) {
                const osrmUrl = buildOsrmUrl([start, end]);
                try {
                    const res  = await fetch(osrmUrl);
                    const data = await res.json();
                    if (!data.routes?.length) return;

                    currentRouteGeoJSON = data.routes[0].geometry;
                    baseRouteGeoJSON    = currentRouteGeoJSON;
                    currentRouteDistanceKm = (data.routes[0].distance || 0) / 1000; // meters → km

                    const nearbyFarms = await findFarmsAlongRoute();
                    lastNearbyFarms   = nearbyFarms;

                    if (nearbyFarms.length > 0) {
                        await generateDetourRoute(start, nearbyFarms, end);
                    } else {
                        drawRoute(currentRouteGeoJSON);
                    }

                    renderPickupQueue(nearbyFarms);
                    document.getElementById('reset-map').classList.remove('hidden');

                    // Route drew while the map stayed hidden → surface the cue.
                    if (!mapRevealed) {
                        document.getElementById('route-ready-cue').classList.remove('hidden');
                    }
                    refreshGenerateState();
                } catch (err) {
                    console.error('Routing Error:', err);
                    Swal.fire({ icon: 'error', title: 'Routing engine unreachable', text: 'Failed to connect to the routing engine. Check your connection and try again.', confirmButtonColor: '#16283C', background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff', color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b', customClass: { popup: 'rounded-xl' } });
                }
            }

            async function generateDetourRoute(start, nearbyFarms, end) {
                const coords = [start, ...nearbyFarms.map(f => ({
                    lat: f.data.farmer_profile.latitude,
                    lng: f.data.farmer_profile.longitude,
                }))];

                // Phase B: include each distinct market/destination so the drawn
                // route matches what the backend prices (sequenceDropoffs).
                const destCoords = [];
                const seenDest = {};
                nearbyFarms.forEach(f => {
                    (f.data.harvests || []).forEach(h => {
                        const dlat = h.destination_latitude, dlng = h.destination_longitude;
                        if (dlat == null || dlng == null) return;
                        const key = Math.round(parseFloat(dlat) * 1000) + ',' + Math.round(parseFloat(dlng) * 1000);
                        if (!seenDest[key]) {
                            seenDest[key] = true;
                            destCoords.push({ lat: parseFloat(dlat), lng: parseFloat(dlng) });
                        }
                    });
                });
                coords.push(...destCoords, end);

                const points = coords.map(p => `${p.lng},${p.lat}`).join(';');
                const tripUrl = `https://router.project-osrm.org/trip/v1/driving/${points}?source=first&destination=last&roundtrip=false&overview=full&geometries=geojson`;

                try {
                    const res  = await fetch(tripUrl);
                    const data = await res.json();
                    if (!data.trips?.length) {
                        console.warn('TSP optimization returned no trips, falling back to sequential route.');
                        generateDetourRouteFallback(start, nearbyFarms, end);
                        return;
                    }

                    currentRouteGeoJSON = data.trips[0].geometry;
                    currentRouteDistanceKm = (data.trips[0].distance || 0) / 1000; // meters → km
                    drawRoute(currentRouteGeoJSON);

                    // Sort waypoints by OSRM optimized sequence. Farms occupy
                    // waypoint_index 1..N; destinations come after them.
                    const wps = Array.isArray(data.waypoints) ? data.waypoints : [];
                    const optimizedWps = wps
                        .filter(wp => wp.waypoint_index >= 1 && wp.waypoint_index <= nearbyFarms.length)
                        .sort((a, b) => a.trips_index - b.trips_index);

                    const optimizedFarms = optimizedWps
                        .map(wp => nearbyFarms[wp.waypoint_index - 1])
                        .filter(f => f != null);

                    lastNearbyFarms = optimizedFarms.length > 0 ? optimizedFarms : nearbyFarms;

                    // Collect per-farm road distances from OSRM legs
                    currentFarmDistances = {};
                    const tripLegs = data.trips[0].legs || [];
                    let cumulativeKm = 0;
                    // legs[0] = depot→first farm, legs[1] = first→second, etc.
                    for (let i = 0; i < optimizedFarms.length && i < tripLegs.length; i++) {
                        cumulativeKm += (tripLegs[i].distance || 0) / 1000; // meters→km
                        const farmId = optimizedFarms[i].data?.id;
                        if (farmId) currentFarmDistances[farmId] = Math.round(cumulativeKm * 100) / 100;
                    }

                    renderPickupQueue(lastNearbyFarms);
                } catch (err) {
                    console.error('Detour OSRM Trip TSP error:', err);
                    generateDetourRouteFallback(start, nearbyFarms, end);
                }
            }

            async function generateDetourRouteFallback(start, nearbyFarms, end) {
                const waypoints = [start, ...nearbyFarms.map(f => ({
                    lat: f.data.farmer_profile.latitude,
                    lng: f.data.farmer_profile.longitude,
                })), end];
                const osrmUrl = buildOsrmUrl(waypoints);
                try {
                    const res  = await fetch(osrmUrl);
                    const data = await res.json();
                    if (!data.routes?.length) return;
                    currentRouteGeoJSON = data.routes[0].geometry;
                    currentRouteDistanceKm = (data.routes[0].distance || 0) / 1000; // meters → km
                    drawRoute(currentRouteGeoJSON);
                } catch (err) {
                    console.error('Detour routing fallback error:', err);
                }
            }

            function buildOsrmUrl(points) {
                const coords = points.map(p => `${p.lng},${p.lat}`).join(';');
                return `https://router.project-osrm.org/route/v1/driving/${coords}?overview=full&geometries=geojson`;
            }

            function drawRoute(geojson) {
                if (routePolyline) map.removeLayer(routePolyline);
                routePolyline = L.geoJSON(geojson, { style: { color: '#16283C', weight: 5 } }).addTo(map);
            }

            window.plotFarmRoute = function(farmLat, farmLng, destLat, destLng) {
                if (startMarker)   map.removeLayer(startMarker);
                if (endMarker)     map.removeLayer(endMarker);
                if (routePolyline) map.removeLayer(routePolyline);

                startMarker = null; endMarker = null;
                baseRouteGeoJSON = null; currentRouteGeoJSON = null; currentRouteDistanceKm = null;
                farmMarkers.forEach(item => { item.marker.setIcon(defaultIcon); item.marker.setOpacity(1); });
                destinationMarkers.forEach(function (dm) { dm.marker.setOpacity(1); });

                const startLatLng = L.latLng(farmLat, farmLng);
                const endLatLng   = L.latLng(destLat, destLng);

                startMarker = L.marker(startLatLng).addTo(map).bindPopup('<b>Start:</b> Farm Pickup').openPopup();
                endMarker   = L.marker(endLatLng).addTo(map).bindPopup('<b>End:</b> Delivery Destination').openPopup();

                map.fitBounds([startLatLng, endLatLng], { padding: [60, 60] });
                generateBaseRoute(startLatLng, endLatLng);
                document.getElementById('reset-map').classList.remove('hidden');
            }

            // ─── Plot all farm markers + Red Destinations on load ──────
            farms.forEach((farm) => {
                if (farm.farmer_profile && farm.farmer_profile.latitude) {
                    // Plot standard pickup pin
                    const marker = L.marker([farm.farmer_profile.latitude, farm.farmer_profile.longitude], { icon: defaultIcon }).addTo(map);

                    const harvestList = farm.harvests.length
                        ? farm.harvests.map(h => `<li><svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22V8m0 0c-2-4-6-4-6-4s1 4 6 4m0 0c2-4 6-4 6-4s-1 4-6 4M8 18c-2-4-6-4-6-4s1 4 6 4m8 0c2-4 6-4 6-4s-1 4-6 4"/></svg> ${h.crop} — ${h.quantity} kg</li>`).join('')
                        : '<li class="text-slate-400">No active posts</li>';

                    const destinationHtml = farm.destination
                        ? `<div style="margin-top:8px;padding-top:8px;border-top:1px solid ${isDark ? '#14202D' : '#E7EAE4'};">
                            <b style="font-size:11px;color:${isDark ? '#94A3B4' : '#5A6573'};letter-spacing:0.05em;text-transform:uppercase;">Destination</b>
                            <p style="margin:4px 0 0;font-size:12px;font-weight:700;color:${isDark ? '#e2e8f0' : '#1e293b'};"><svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg> ${farm.destination.name}</p>
                            <p style="margin:2px 0 0;font-size:11px;color:${isDark ? '#94A3B4' : '#5A6573'};">${farm.destination.address}</p>
                           </div>`
                        : farm.destination_address
                            ? `<div style="margin-top:8px;padding-top:8px;border-top:1px solid ${isDark ? '#14202D' : '#E7EAE4'};">
                                <b style="font-size:11px;color:${isDark ? '#94A3B4' : '#5A6573'};letter-spacing:0.05em;text-transform:uppercase;">Destination</b>
                                <p style="margin:4px 0 0;font-size:12px;font-weight:700;color:${isDark ? '#e2e8f0' : '#1e293b'};"><svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg> ${farm.destination_address}</p>
                               </div>`
                            : `<div style="margin-top:8px;padding-top:8px;border-top:1px solid ${isDark ? '#14202D' : '#E7EAE4'};">
                                <p style="font-size:11px;color:${isDark ? '#cbd5e1' : '#94a3b8'};">No destination set.</p>
                               </div>`;

                    const hasDestination = farm.destination_latitude && farm.destination_longitude;
                    const plotButtonHtml = hasDestination
                        ? `<button onclick="plotFarmRoute(${farm.farmer_profile.latitude},${farm.farmer_profile.longitude},${farm.destination_latitude},${farm.destination_longitude})"
                            style="margin-top:10px;width:100%;background:#16283C;color:white;border:none;border-radius:8px;padding:8px 0;font-size:12px;font-weight:700;cursor:pointer;box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                            <svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg> Plot Route
                           </button>`
                        : `<button disabled style="margin-top:10px;width:100%;background:${isDark ? '#14202D' : '#F5F6F2'};color:${isDark ? '#5A6573' : '#94A3B4'};border:none;border-radius:8px;padding:8px 0;font-size:12px;font-weight:700;cursor:not-allowed;">
                            No destination set
                           </button>`;

                    const dealHtml = farm.harvests && farm.harvests.some(h => h.completed_negotiation)
                        ? (() => {
                            const deal = farm.harvests.find(h => h.completed_negotiation).completed_negotiation;
                            return `<div style="margin-top:8px;padding-top:8px;border-top:1px solid ${isDark ? '#14202D' : '#E7EAE4'};">
                                <b style="font-size:11px;color:${isDark ? '#94A3B4' : '#5A6573'};letter-spacing:0.05em;text-transform:uppercase;">Done Deal</b>
                                <p style="margin:4px 0 0;font-size:11px;color:${isDark ? '#E9EEF4' : '#17202B'};">&#8369;${Number(deal.price).toLocaleString(undefined, {minimumFractionDigits:2})}/kg &bull; ${Number(deal.volume).toLocaleString()} kg</p>
                                <p style="margin:2px 0 0;font-size:11px;color:${isDark ? '#94A3B4' : '#5A6573'};">Buyer: ${deal.buyer}</p>
                                <p style="margin:2px 0 0;font-size:11px;color:${isDark ? '#94A3B4' : '#5A6573'};">Drop-off: ${deal.dropoff || '—'}</p>
                            </div>`;
                        })()
                        : '';

                    marker.bindPopup(`
                        <div style="min-width:200px;font-family:'DM Sans',sans-serif;">
                            <b style="font-size:14px;color:${isDark ? '#e2e8f0' : '#0f172a'};">${farm.name}</b>
                            <br><span style="color:${isDark ? '#94A3B4' : '#5A6573'};font-size:12px;"><svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg> ${farm.farmer_profile.farm_location}</span>
                            <hr style="margin:8px 0;border:0;border-top:1px solid ${isDark ? '#14202D' : '#F5F6F2'};">
                            <b style="font-size:11px;color:${isDark ? '#94A3B4' : '#5A6573'};letter-spacing:0.05em;text-transform:uppercase;">Active Harvests</b>
                            <ul style="margin:4px 0 0;padding-left:14px;font-size:12px;color:${isDark ? '#E9EEF4' : '#17202B'};list-style-type:square;">${harvestList}</ul>
                            ${destinationHtml}
                            ${plotButtonHtml}
                            ${dealHtml}
                        </div>
                    `, { maxWidth: 260 });

                    farmMarkers.push({ marker, data: farm });
                }
            });

            // ─── Plot ALL harvest-level destination markers ──────
            farms.forEach(function (farm) {
                if (!farm.harvests) return;
                farm.harvests.forEach(function (h) {
                    var dlat = h.destination_latitude, dlng = h.destination_longitude;
                    if (dlat === null || dlng === null || dlat === undefined || dlng === undefined) return;
                    dlat = parseFloat(dlat); dlng = parseFloat(dlng);
                    if (isNaN(dlat) || isNaN(dlng)) return;
                    var label = h.destination ? h.destination.name : (h.destination_address || 'B2B Terminal');
                    var marker = L.marker([dlat, dlng], { icon: destinationMarkerIcon })
                        .addTo(map)
                        .bindPopup(
                            '<div style="font-family:\'DM Sans\',sans-serif;font-size:12px;">' +
                            '<b><svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg> Drop-off Terminal</b><br>' +
                            '<span style="color:gray;">' + farm.name + ' → ' + label + '</span>' +
                            '</div>'
                        );
                    destinationMarkers.push({ marker: marker, farmName: farm.name, harvestId: h.id });
                });
            });

            // Auto-populate start/end markers from cooperative hub + first deal destination
            if (hubLat && hubLng) {
                var autoEndFarm = null;
                for (var i = 0; i < farms.length; i++) {
                    var f = farms[i];
                    if (f.destination_latitude && f.destination_longitude) {
                        autoEndFarm = f;
                        break;
                    }
                }

                if (autoEndFarm) {
                    var startLatLng = L.latLng(hubLat, hubLng);
                    var endLatLng = L.latLng(autoEndFarm.destination_latitude, autoEndFarm.destination_longitude);

                    startMarker = L.marker(startLatLng).addTo(map)
                        .bindPopup('<b>Start:</b> Coop Hub').openPopup();
                    endMarker = L.marker(endLatLng).addTo(map)
                        .bindPopup('<b>End:</b> Delivery Destination');

                    map.fitBounds([startLatLng, endLatLng], { padding: [60, 60] });
                    generateBaseRoute(startLatLng, endLatLng);
                    document.getElementById('reset-map').classList.remove('hidden');
                }
            }

            /**
             * Finds all farms along the route within the selected radius.
             * Uses TurfJS for initial proximity filter, then OSRM for actual driving
             * distance from each farm to its nearest point on the route.
             */
            async function findFarmsAlongRoute() {
                if (!baseRouteGeoJSON) return [];
                const currentRadius = parseFloat(document.getElementById('radius-select').value);
                const routeLine = turf.lineString(baseRouteGeoJSON.coordinates);
                const selectedFarmNames = [];

                // Pre-filter with generous straight-line buffer (3x radius) to avoid missing
                // farms that are close by road but far by air
                const preFilterRadius = currentRadius * 3;
                const candidates = [];

                farmMarkers.forEach(item => {
                    const farmPt = turf.point([item.data.farmer_profile.longitude, item.data.farmer_profile.latitude]);
                    const airDistance = turf.pointToLineDistance(farmPt, routeLine, { units: 'kilometers' });

                    if (airDistance <= preFilterRadius) {
                        const snapped = turf.nearestPointOnLine(routeLine, farmPt);
                        candidates.push({ item, farmPt, snapped, airDistance });
                    } else {
                        item.marker.setIcon(defaultIcon);
                    }
                });

                // Get actual driving distances from OSRM for each candidate.
                // Run in small batches (4 at a time) so the public router isn't
                // throttled by a 15-call parallel burst (was silently falling
                // back to straight-line on 429s).
                const distanceFor = async (c) => {
                    const farmCoords = `${c.item.data.farmer_profile.longitude},${c.item.data.farmer_profile.latitude}`;
                    const snapCoords = `${c.snapped.geometry.coordinates[0]},${c.snapped.geometry.coordinates[1]}`;
                    try {
                        const res = await fetch(`https://router.project-osrm.org/route/v1/driving/${farmCoords};${snapCoords}?overview=false`);
                        const data = await res.json();
                        if (data.routes && data.routes.length > 0) return data.routes[0].distance / 1000; // meters to km
                        return c.airDistance; // fallback to straight-line
                    } catch (e) { return c.airDistance; } // fallback on error
                };

                const drivingDistances = [];
                for (let i = 0; i < candidates.length; i += 4) {
                    const chunk = candidates.slice(i, i + 4);
                    const chunkResults = await Promise.all(chunk.map(distanceFor));
                    drivingDistances.push(...chunkResults);
                }

                const found = [];
                candidates.forEach((c, i) => {
                    const distance = drivingDistances[i];
                    if (distance <= currentRadius) {
                        c.item.marker.setIcon(highlightIcon);
                        selectedFarmNames.push(c.item.data.name);
                        found.push({ ...c.item, distance, routePosition: c.snapped.properties.location });
                    } else {
                        c.item.marker.setIcon(defaultIcon);
                    }
                });

                // Toggle destination marker opacity to match selection state
                destinationMarkers.forEach(function (dm) {
                    if (selectedFarmNames.indexOf(dm.farmName) !== -1) {
                        dm.marker.setOpacity(1);
                    } else {
                        dm.marker.setOpacity(0.4);
                    }
                });

                // Excluded farms must stay faded even after a radius change
                // re-highlights the in-range farms above.
                dimExcludedMarkers();

                // Sort farmers sequentially from Start depot towards End terminal
                found.sort((a, b) => a.routePosition - b.routePosition);
                return found;
            }

            function renderPickupQueue(nearbyFarms) {
                const currentRadius  = parseFloat(document.getElementById('radius-select').value);
                const queueContainer = document.getElementById('pickup-queue');
                queueContainer.innerHTML = '';

                if (nearbyFarms.length === 0) {
                    queueContainer.innerHTML = `<div class="text-center text-slate-400 mt-10">No farms detected within ${currentRadius}km of this route.</div>`;
                    return;
                }

                const selectedOpt = truckSelect.options[truckSelect.selectedIndex];
                const truckCapacity = selectedOpt && selectedOpt.value ? parseFloat(selectedOpt.dataset.capacity) : Infinity;

                nearbyFarms.forEach(item => {
                    const totalKg = item.data.harvests.reduce((sum, h) => sum + parseFloat(h.quantity || 0), 0);
                    const exceedsCapacity = totalKg > truckCapacity;

                    // Over-limit farms can't ride in this truck → force-deselect.
                    if (exceedsCapacity) selectedFarmIds.delete(item.data.id);
                    // Default-tick only farms the user hasn't explicitly toggled;
                    // manual unticks must survive the redraw.
                    if (!exceedsCapacity && !toggledFarmIds.has(item.data.id)) selectedFarmIds.add(item.data.id);

                    const included = selectedFarmIds.has(item.data.id);

                    const cardClass = exceedsCapacity
                        ? 'bg-slate-50 dark:bg-slate-900/40 p-4 rounded-xl border border-rose-300/60 dark:border-rose-900/40 opacity-60 filter grayscale relative overflow-hidden'
                        : (included
                            ? 'bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-100/70 dark:border-slate-700/80  shadow-sm hover:shadow-md transition-shadow relative overflow-hidden'
                            : 'bg-slate-50 dark:bg-slate-900/40 p-4 rounded-xl border border-slate-200/70 dark:border-slate-700/60 opacity-60 filter grayscale relative overflow-hidden');

                    const capacityBadge = exceedsCapacity
                        ? `<span class="inline-block mt-2 text-[9px] font-bold uppercase tracking-wider bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400 border border-rose-300/50 dark:border-rose-900/30 px-2 py-0.5 rounded-md"><svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg> Over Limit</span>`
                        : (included ? ''
                            : `<span class="inline-block mt-2 text-[9px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 px-2 py-0.5 rounded-md">Not in this load</span>`);

                    queueContainer.innerHTML += `
                        <div class="${cardClass}">
                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <input type="checkbox" data-farm-id="${item.data.id}" ${included ? 'checked' : ''} ${exceedsCapacity ? 'disabled' : ''}
                                       class="mt-0.5 w-4 h-4 accent-[#16283C] cursor-pointer">
                                <span class="flex-1 min-w-0">
                                    <strong class="text-sm block ${exceedsCapacity || !included ? 'text-slate-500 dark:text-slate-600' : 'text-slate-800 dark:text-slate-200 heading-font'}">${item.data.name}</strong>
                                </span>
                            </label>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 flex items-center gap-1"><span><svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span> ${item.data.farmer_profile.farm_location}</p>
                            <p class="text-xs text-slate-404 dark:text-slate-500 mt-1"><svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 17h.01M16 17h.01M3 11l1.5-5A2 2 0 016.4 4h11.2a2 2 0 011.9 1.4L21 11M3 11h18M3 11v6a1 1 0 001 1h1a1 1 0 001-1v-1h12v1a1 1 0 001 1h1a1 1 0 001-1v-6"/></svg> ${item.distance.toFixed(2)} km off-route</p>
                            <p class="text-xs mt-1.5 ${exceedsCapacity ? 'text-rose-600 dark:text-rose-450 font-bold' : 'text-slate-700 dark:text-slate-400 font-semibold'}"><svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg> ${totalKg.toLocaleString()} kg payload</p>
                            ${capacityBadge}
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 italic">Tick to include in this load. Untick deals for a separate split load.</p>
                        </div>
                    `;
                });

                // Manual split: ticking anywhere re-renders the queue + re-gates Generate.
                queueContainer.querySelectorAll('input[type="checkbox"][data-farm-id]').forEach(chk => {
                    chk.addEventListener('change', function () {
                        const fid = Number(this.dataset.farmId);
                        toggledFarmIds.add(fid);
                        if (this.checked) selectedFarmIds.add(fid); else selectedFarmIds.delete(fid);
                        renderPickupQueue(nearbyFarms);
                        refreshGenerateState();
                    });
                });

                refreshGenerateState();
            }

            // ─── Done Deals (always shows ALL completed deals, independent of route state) ──
            function renderOpenHaulRequests() {
                const container = document.getElementById('open-haul-requests');
                if (!container) return;
                const counter = document.getElementById('open-haul-count');

                const allDone = (farms || []).filter(f =>
                    f.harvests && f.harvests.some(h => h.completed_negotiation)
                );
                if (counter) counter.textContent = allDone.length;
                container.innerHTML = '';

                if (allDone.length === 0) {
                    container.innerHTML = '<div class="text-center text-slate-500 dark:text-slate-400 mt-8 italic text-xs">No done deals yet. Deals appear here after buyers finalize negotiations.</div>';
                    return;
                }

                allDone.forEach(farm => {
                    const deal = farm.harvests.find(h => h.completed_negotiation).completed_negotiation;
                    const totalKg = farm.harvests.reduce((s, h) => s + parseFloat(h.quantity || 0), 0);
                    const dropoff = deal.dropoff || farm.destination_address || '—';

                    container.innerHTML += `
                        <div class="bg-slate-50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-700/60  rounded-xl p-3.5">
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-200 heading-font truncate">${farm.name}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                                ${farm.harvests[0] ? '<span class="font-semibold">' + farm.harvests[0].crop + '</span> &bull; ' : ''}${Number(totalKg || 0).toLocaleString()} kg &bull; <span class="font-semibold text-brand dark:text-brand-light">&#8369;${Number(deal.price).toLocaleString(undefined, {minimumFractionDigits:2})}/kg</span>
                            </p>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">Buyer: ${deal.buyer}</p>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">Drop-off: ${dropoff}</p>
                            <div class="flex items-center gap-2 mt-2.5">
                                <button onclick="focusDealOnMap('${farm.name.replace(/\\/g, '\\\\').replace(/'/g, "\\'")}')" class="flex-1 text-[11px] font-bold text-brand dark:text-brand-light bg-brand/10 dark:bg-brand/10 border border-brand/20 dark:border-brand/15 hover:bg-brand/20 dark:hover:bg-brand/20 rounded-xl px-3 py-1.5 transition flex items-center justify-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Show in Map
                                </button>
                            </div>
                        </div>
                    `;
                });
            }

            // ─── Focus a done deal on the map and auto-plot route from pickup to delivery ──
            window.focusDealOnMap = function(farmName) {
                revealMap();
                const item = farmMarkers.find(m => m.data.name === farmName);
                if (!item || !item.marker) return;

                const farm = item.data;
                const farmLatLng = item.marker.getLatLng();

                const destItem = destinationMarkers.find(d => d.farmName === farm.name);

                if (destItem) {
                    const destLatLng = destItem.marker.getLatLng();
                    plotFarmRoute(farmLatLng.lat, farmLatLng.lng, destLatLng.lat, destLatLng.lng);
                } else {
                    map.setView(farmLatLng, 14);
                    item.marker.openPopup();
                }
            }

            // ─── Recall a saved pooling route on the map from My Routes ───
            function viewRouteMap(jobId) {
                revealMap();
                const job = myRoutes.find(j => j.id === jobId);
                if (!job) return;

                historyLayer.clearLayers();

                let coords = [];
                const geom = job.route_geometry;
                if (Array.isArray(geom) && geom.length) {
                    if (Array.isArray(geom[0])) {
                        coords = geom.map(p => [p[1], p[0]]);            // [[lng, lat], ...]
                    } else if (geom[0] && typeof geom[0] === 'object') {
                        coords = geom.map(p => [p.lat, p.lng]);          // [{lat, lng}, ...]
                    }
                }
                if (coords.length < 2 && Array.isArray(job.start) && Array.isArray(job.end)) {
                    coords = [job.start, job.end];                        // fallback straight line
                }

                if (coords.length >= 2) {
                    L.polyline(coords, { color: '#16283C', weight: 4, opacity: 0.85 }).addTo(historyLayer);
                }
                if (Array.isArray(job.start) && job.start.length >= 2) {
                    L.marker(job.start).addTo(historyLayer).bindPopup('<b>Start:</b> Hub Depot');
                }
                if (Array.isArray(job.end) && job.end.length >= 2) {
                    L.marker(job.end, { icon: destinationMarkerIcon }).addTo(historyLayer).bindPopup('<b>End:</b> Delivery Terminal');
                }

                if (coords.length >= 2) {
                    map.fitBounds(L.latLngBounds(coords), { padding: [40, 40] });
                } else if (coords.length === 1) {
                    map.setView(coords[0], 14);
                }
                map.invalidateSize();
                document.getElementById('routing-map').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            // ─── Weather Fetch on Route Generation ────────────────
            async function fetchWeather(lat, lng) {
                const widget = document.getElementById('weather-widget');
                try {
                    const res = await fetch(`https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lng}&units=metric&appid={{ config('services.openweather.key', '') }}`);
                    if (!res.ok) { widget.classList.add('hidden'); return; }
                    const data = await res.json();
                    const condition = data.weather?.[0]?.main ?? 'Unknown';
                    const desc = data.weather?.[0]?.description ?? '';
                    const icon = data.weather?.[0]?.icon ?? '01d';
                    const temp = data.main?.temp ?? 0;
                    const wind = data.wind?.speed ?? 0;
                    const advisory = [];
                    if (['Thunderstorm','Tornado'].includes(condition)) advisory.push('Severe storm. Delay if possible.');
                    if (wind > 25) advisory.push(`Strong winds (${wind} km/h).`);
                    if (temp > 40) advisory.push('Extreme heat. Ensure crop refrigeration.');
                    if (!advisory.length) advisory.push('Conditions favorable.');
                    document.getElementById('weather-icon').innerHTML = icon.includes('n') ? '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>' : '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>';
                    document.getElementById('weather-condition').textContent = `${condition} — ${desc}`;
                    document.getElementById('weather-temp').textContent = `${Math.round(temp)}C  Wind ${wind} km/h`;
                    document.getElementById('weather-advisory').textContent = advisory.join(' ');
                    widget.classList.remove('hidden');
                } catch (e) {
                    widget.classList.add('hidden');
                }
            }

            btnGenerate.addEventListener('click', function () {
                // Only ticked farms ride in this load (manual split per load).
                const chosenFarms = lastNearbyFarms.filter(f => selectedFarmIds.has(f.data.id));
                if (chosenFarms.length === 0) {
                    Swal.fire({ icon: 'warning', title: 'No farms selected', text: 'Tick at least one farm in the Route Pickups list, then generate.', confirmButtonColor: '#16283C', background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff', color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b', customClass: { popup: 'rounded-xl' } });
                    return;
                }
                const harvestIds = chosenFarms.flatMap(f => f.data.harvests.map(h => h.id));

                // Per-harvest off-route road distance so the backend validates
                // radius the same way the map does (Phase D).
                const farmDistances = {};
                chosenFarms.forEach(f => {
                    f.data.harvests.forEach(h => { farmDistances[h.id] = Math.round(f.distance * 100) / 100; });
                });

                swalConfirm(async function () {
                    btnGenerate.disabled = true;
                    btnGenerate.textContent = 'Generating...';
                    try {
                        const res = await fetch('{{ route("pooling.planAll") }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({
                                harvest_ids: harvestIds,
                                start_lat:   startMarker.getLatLng().lat,
                                start_lng:   startMarker.getLatLng().lng,
                                end_lat:     endMarker.getLatLng().lat,
                                end_lng:     endMarker.getLatLng().lng,
                                radius_km:   parseFloat(document.getElementById('radius-select').value),
                                hauling_rate_per_kg: (function(){ var el = document.getElementById('hauling-rate'); return el ? parseFloat(el.value) : null; })(),
                                route_distance_km: currentRouteKm(),
                                terrain: (function () { var el = document.getElementById('terrain-select'); return el ? el.value : 'flat'; })(),
                                farm_distances: farmDistances,
                            }),
                        });

                        const data = await res.json();
                        if (!res.ok || data.error || !data.plans || data.plans.length === 0) {
                            Swal.fire({ icon: 'error', title: 'Could not build routes', text: (data && (data.error || data.message)) || 'No available trucks with active drivers. Adjust your selection and try again.', confirmButtonColor: '#16283C', background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff', color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b', customClass: { popup: 'rounded-xl' } });
                            btnGenerate.textContent = 'Generate Route Plan'; refreshGenerateState(); return;
                        }
                        renderPlanAllPanel(data);
                    } catch (err) {
                        console.error(err);
                    } finally {
                        btnGenerate.textContent = 'Generate Route Plan';
                        refreshGenerateState();
                    }
                }, {
                    title: 'Generate Route Plan?',
                    text: 'Build a delivery route for the selected farms?',
                    icon: 'question',
                    confirmText: 'Yes, generate',
                    cancelText: 'Cancel',
                    confirmColor: '#16283C'
                });
            });

            /**
             * Dim map pins for farms whose EVERY harvest was excluded by planAll.
             * A dimmed pin means "this farm is on no route." If even one of the
             * farm's harvests is routed, the pin stays normal.
             */
            window.dimExcludedMarkers = function dimExcludedMarkers(excluded) {
                var excludedIds = new Set((excluded || currentExcluded || []).map(e => e.harvest_id));
                if (excludedIds.size === 0) {
                    farmMarkers.forEach(item => item.marker.setOpacity(1));
                    return;
                }
                farmMarkers.forEach(item => {
                    var farmIds = (item.data.harvests || []).map(h => h.id);
                    var anyIncluded = farmIds.length === 0 || farmIds.some(id => !excludedIds.has(id));
                    item.marker.setOpacity(anyIncluded ? 1 : 0.35);
                });
            }

            function renderPlanAllPanel(data) {
                var plans = data.plans || [];
                if (plans.length === 0) return;

                if (plans.length === 1) {
                    var plan = plans[0];
                    currentPlan = plan;
                    currentExcluded = data.excluded || [];
                    dimExcludedMarkers();
                    document.getElementById('plan-all-panel').classList.add('hidden');
                    document.getElementById('plan-panel').classList.remove('hidden');
                    renderPlanPanel(plan);
                    if (plan.truck_name) {
                        document.getElementById('plan-truck-label').textContent = plan.truck_name + ' (' + Number(plan.truck_capacity_kg || 0).toLocaleString() + ' kg)';
                    }
                    var banner = document.getElementById('plan-capacity-banner');
                    var bannerHtml = '';
                    if ((data.unassigned || 0) > 0) {
                        bannerHtml = _escHtml(data.message || (data.unassigned + ' farm(s) could not be loaded - no more available trucks.'));
                    }
                    if ((currentExcluded || []).length > 0) {
                        if (bannerHtml) bannerHtml += '<br>';
                        bannerHtml += currentExcluded.map(function (e) {
                            return '<br>- ' + _escHtml(e.farm_name || 'Farm') + ': ' + _escHtml(e.reason || 'Excluded');
                        }).join('');
                    }
                    if (bannerHtml) {
                        document.getElementById('plan-capacity-banner-text').innerHTML = bannerHtml;
                        banner.classList.remove('hidden');
                    } else {
                        banner.classList.add('hidden');
                    }
                    return;
                }

                currentPlans = plans;
                currentExcluded = data.excluded || [];
                dimExcludedMarkers();
                document.getElementById('plan-panel').classList.add('hidden');
                var panel = document.getElementById('plan-all-panel');
                panel.classList.remove('hidden');
                panel.scrollIntoView({ behavior: 'smooth', block: 'start' });

                document.getElementById('plan-all-count').textContent = plans.length;
                var banner = document.getElementById('plan-all-unassigned-banner');
                var bannerText = document.getElementById('plan-all-unassigned-text');
                var unassignedCount = data.unassigned || 0;
                var excludedList = currentExcluded;
                if (unassignedCount > 0 || excludedList.length > 0) {
                    var html = '';
                    if (unassignedCount > 0) {
                        html += _escHtml(data.message || (unassignedCount + ' farm(s) could not be loaded - no more available trucks.'));
                    }
                    if (excludedList.length > 0) {
                        if (html) html += '<br>';
                        excludedList.forEach(function (e) {
                            html += '<br>- ' + _escHtml(e.farm_name || 'Farm') + ': ' + _escHtml(e.reason || 'Excluded');
                        });
                    }
                    bannerText.innerHTML = html;
                    banner.classList.remove('hidden');
                } else {
                    banner.classList.add('hidden');
                }

                var container = document.getElementById('plan-all-cards');
                container.innerHTML = '';
                plans.forEach(function (plan, idx) {
                    var distKm = plan._distanceKm != null && plan._distanceKm > 0
                        ? plan._distanceKm
                        : ((plan.road_distance_km != null && Number(plan.road_distance_km) > 0) ? Number(plan.road_distance_km) : (plan.total_distance_km || 0));
                    var stopsHtml = (plan.selected_harvests || []).map(function (h) {
                        return '<div class="flex items-center justify-between gap-2 py-1.5 border-b border-slate-100 dark:border-slate-700/40 last:border-0">'
                            + '<span class="text-xs text-slate-600 dark:text-slate-300"><span class="font-mono text-slate-400 dark:text-slate-600">#' + h.harvest_id + '</span> ' + _escHtml(h.farm_name || '-') + ' <span class="text-slate-400"> - ' + _escHtml(h.crop || '-') + '</span></span>'
                            + '<span class="text-xs font-bold text-slate-700 dark:text-slate-300">' + Number(h.quantity_kg).toLocaleString() + ' kg</span></div>';
                    }).join('');

                    container.innerHTML +=
                    '<div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-5">'
                        + '<div class="flex items-center justify-between mb-4">'
                        + '<h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 heading-font flex items-center gap-2"><span class="flex h-6 w-6 items-center justify-center rounded-md bg-brand/10 text-brand dark:bg-brand/10 dark:text-brand-light text-xs font-black">' + (idx + 1) + '</span> Route ' + (idx + 1) + '</h3>'
                        + '<span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-md bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border border-[var(--color-warning-border)]">Truck auto-assigned</span>'
                        + '</div>'
                        + '<dl class="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-2 text-sm mb-4">'
                        + '<div class="flex justify-between"><dt class="text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider text-[10px]">Truck</dt><dd class="font-bold text-slate-700 dark:text-slate-300 text-xs text-right">' + _escHtml(plan.truck_name || 'Truck #' + plan.truck_id) + '</dd></div>'
                        + '<div class="flex justify-between"><dt class="text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider text-[10px]">Capacity</dt><dd class="font-bold text-slate-700 dark:text-slate-300 text-xs text-right">' + Number(plan.truck_capacity_kg || 0).toLocaleString() + ' kg</dd></div>'
                        + '<div class="flex justify-between"><dt class="text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider text-[10px]">Load</dt><dd class="font-bold text-brand dark:text-brand-light text-xs text-right">' + Number(plan.total_kg || 0).toLocaleString() + ' kg (' + Number(plan.load_percentage || 0).toFixed(1) + '%)</dd></div>'
                        + '<div class="flex justify-between"><dt class="text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider text-[10px]">Farms</dt><dd class="font-bold text-slate-700 dark:text-slate-300 text-xs text-right">' + (plan.farm_count || 0) + '</dd></div>'
                        + '<div class="flex justify-between"><dt class="text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider text-[10px]">Distance</dt><dd class="font-bold text-slate-700 dark:text-slate-300 text-xs text-right">' + distKm.toFixed(2) + ' km</dd></div>'
                        + '<div class="flex justify-between"><dt class="text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider text-[10px]">Total Haul Cost</dt><dd class="font-bold text-gold-700 dark:text-gold-light text-xs text-right">₱' + Number(plan.price_reference || 0).toLocaleString(undefined, {minimumFractionDigits: 2}) + '</dd></div>'
                        + '</dl>'
                        + '<p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-1">Pickup Stops</p>' + stopsHtml
                    + '</div>';
                });

                if (plans.length > 1) {
                    (async function () {
                        for (var i = 0; i < plans.length; i++) {
                            var p = plans[i];
                            try {
                                var coords = [startMarker.getLatLng()];
                                (p.stops || []).forEach(function (s) { coords.push({ lat: s.latitude, lng: s.longitude }); });
                                coords.push(endMarker.getLatLng());
                                var osrmUrl = buildOsrmUrl(coords);
                                var res = await fetch(osrmUrl);
                                var osrmData = await res.json();
                                if (osrmData.routes && osrmData.routes.length) {
                                    p._geometry = osrmData.routes[0].geometry;
                                    p._distanceKm = (osrmData.routes[0].distance || 0) / 1000;
                                }
                            } catch (err) {
                                console.error('Per-truck OSRM error for route ' + (i + 1) + ':', err);
                            }
                        }
                        container.innerHTML = '';
                        plans.forEach(function (plan, idx) {
                            var distKm = plan._distanceKm != null && plan._distanceKm > 0
                                ? plan._distanceKm
                                : ((plan.road_distance_km != null && Number(plan.road_distance_km) > 0) ? Number(plan.road_distance_km) : (plan.total_distance_km || 0));
                            var stopsHtml = (plan.selected_harvests || []).map(function (h) {
                                return '<div class="flex items-center justify-between gap-2 py-1.5 border-b border-slate-100 dark:border-slate-700/40 last:border-0">'
                                    + '<span class="text-xs text-slate-600 dark:text-slate-300"><span class="font-mono text-slate-400 dark:text-slate-600">#' + h.harvest_id + '</span> ' + _escHtml(h.farm_name || '-') + ' <span class="text-slate-400"> - ' + _escHtml(h.crop || '-') + '</span></span>'
                                    + '<span class="text-xs font-bold text-slate-700 dark:text-slate-300">' + Number(h.quantity_kg).toLocaleString() + ' kg</span></div>';
                            }).join('');
                            container.innerHTML +=
                            '<div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-5">'
                                + '<div class="flex items-center justify-between mb-4">'
                                + '<h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 heading-font flex items-center gap-2"><span class="flex h-6 w-6 items-center justify-center rounded-md bg-brand/10 text-brand dark:bg-brand/10 dark:text-brand-light text-xs font-black">' + (idx + 1) + '</span> Route ' + (idx + 1) + '</h3>'
                                + '<span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-md bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border border-[var(--color-warning-border)]">Truck auto-assigned</span>'
                                + '</div>'
                                + '<dl class="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-2 text-sm mb-4">'
                                + '<div class="flex justify-between"><dt class="text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider text-[10px]">Truck</dt><dd class="font-bold text-slate-700 dark:text-slate-300 text-xs text-right">' + _escHtml(plan.truck_name || 'Truck #' + plan.truck_id) + '</dd></div>'
                                + '<div class="flex justify-between"><dt class="text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider text-[10px]">Capacity</dt><dd class="font-bold text-slate-700 dark:text-slate-300 text-xs text-right">' + Number(plan.truck_capacity_kg || 0).toLocaleString() + ' kg</dd></div>'
                                + '<div class="flex justify-between"><dt class="text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider text-[10px]">Load</dt><dd class="font-bold text-brand dark:text-brand-light text-xs text-right">' + Number(plan.total_kg || 0).toLocaleString() + ' kg (' + Number(plan.load_percentage || 0).toFixed(1) + '%)</dd></div>'
                                + '<div class="flex justify-between"><dt class="text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider text-[10px]">Farms</dt><dd class="font-bold text-slate-700 dark:text-slate-300 text-xs text-right">' + (plan.farm_count || 0) + '</dd></div>'
                                + '<div class="flex justify-between"><dt class="text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider text-[10px]">Distance</dt><dd class="font-bold text-slate-700 dark:text-slate-300 text-xs text-right">' + distKm.toFixed(2) + ' km</dd></div>'
                                + '<div class="flex justify-between"><dt class="text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider text-[10px]">Total Haul Cost</dt><dd class="font-bold text-gold-700 dark:text-gold-light text-xs text-right">₱' + Number(plan.price_reference || 0).toLocaleString(undefined, {minimumFractionDigits: 2}) + '</dd></div>'
                                + '</dl>'
                                + '<p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-1">Pickup Stops</p>' + stopsHtml
                            + '</div>';
                        });
                    })();
                }
            }

            function renderPlanPanel(plan) {
                const panel = document.getElementById('plan-panel');
                panel.classList.remove('hidden');
                panel.scrollIntoView({ behavior: 'smooth', block: 'start' });

                // Capacity honesty: backend knapsack dropped some of the checked deals.
                const asked = plan.requested_count ?? 0;
                const fit   = plan.selected_count ?? (plan.selected_harvests || []).length;
                const banner = document.getElementById('plan-capacity-banner');
                if (fit < asked) {
                    banner.classList.remove('hidden');
                    document.getElementById('plan-capacity-banner-text').textContent =
                        'Only ' + fit + ' of ' + asked + ' checked deals fit this truck. Pick a bigger truck, or tick the rest into a separate load and generate again.';
                } else {
                    banner.classList.add('hidden');
                }

                document.getElementById('plan-farm-count').textContent = plan.farm_count ?? '—';
                document.getElementById('plan-total-kg').textContent   = (plan.total_kg ?? 0).toLocaleString() + ' kg';
                document.getElementById('plan-load-pct').textContent   = (plan.load_percentage ?? 0).toFixed(1) + '%';
                document.getElementById('plan-truck-label').textContent = truckSelect.options[truckSelect.selectedIndex].text;
                var distKm = (plan.road_distance_km != null && Number(plan.road_distance_km) > 0)
                        ? Number(plan.road_distance_km)
                        : (plan.total_distance_km ?? 0);
                document.getElementById('plan-distance').textContent = distKm.toFixed(2) + ' km';
                document.getElementById('plan-price-ref').textContent  = '₱' + Number(plan.price_reference ?? 0).toLocaleString(undefined, {minimumFractionDigits: 2});
                document.getElementById('plan-rate').textContent       = @json($isCoop)
                    ? 'Sum of farmers\' agreed rates'
                    : '₱' + Number(plan.hauling_rate_per_kg ?? 0).toFixed(2) + '/kg × ' + Number(plan.total_kg ?? 0).toLocaleString() + ' kg';

                // Road-distance rate suggestion + sanity banner
                (function () {
                    var sug = plan.suggested_rate_per_kg;
                    var sugRow = document.getElementById('plan-rate-suggestion');
                    if (sug != null && sug > 0) {
                        sugRow.classList.remove('hidden');
                        document.getElementById('plan-suggested-rate').textContent = '₱' + Number(sug).toFixed(2) + '/kg';
                        var basis = (plan.road_distance_km != null ? plan.road_distance_km + ' km · ' : '')
                            + (plan.terrain || 'flat') + ' terrain';
                        var sources = plan.suggestion_sources || [];
                        if (sources.length) {
                            basis += ' · <span class="font-bold">why this price</span>: '
                                + sources.map(function (s) {
                                    return '<a class="underline hover:opacity-80" href="' + _escHtml(s.url) + '" target="_blank" rel="noopener">' + _escHtml(s.label) + '</a>';
                                }).join(' · ');
                        } else if (plan.suggested_basis) {
                            basis += ' · ' + plan.suggested_basis;
                        }
                        document.getElementById('plan-suggested-basis').innerHTML = basis;
                        // Pre-fill the rate box once — never overwrite a rate the user explicitly typed.
                        var rateInput = document.getElementById('hauling-rate');
                        if (rateInput && !rateTouched && !suggestedPrefilled) {
                            rateInput.value = Number(sug).toFixed(2);
                            suggestedPrefilled = true;
                        }
                    } else {
                        sugRow.classList.add('hidden');
                    }
                    var sanity = plan.rate_sanity;
                    var sb = document.getElementById('plan-rate-sanity-banner');
                    if (sanity && sanity.level === 'warning') {
                        sb.classList.remove('hidden');
                        document.getElementById('plan-rate-sanity-text').textContent = sanity.message;
                    } else {
                        sb.classList.add('hidden');
                    }
                })();

                const tbody = document.getElementById('plan-table-body');
                tbody.innerHTML = '';

                (plan.selected_harvests || []).forEach((h, i) => {
                    var windowText = '—';
                    if (h.pickup_window_start || h.pickup_window_end) {
                        var parts = [];
                        if (h.pickup_window_start) parts.push(h.pickup_window_start);
                        if (h.pickup_window_end) parts.push(h.pickup_window_end);
                        windowText = '<span class="text-[var(--color-warning-text)] font-bold">' + parts.join(' — ') + '</span>';
                    }
                    tbody.innerHTML += `
                        <tr class="border-b border-slate-100 dark:border-slate-700/40 hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition-colors">
                            <td class="py-3.5 px-4 font-mono text-xs text-slate-400 dark:text-slate-600">#${i + 1}</td>
                            <td class="py-3.5 px-4 font-bold text-slate-700 dark:text-slate-300">${h.farm_name ?? '—'}</td>
                            <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 text-xs">${h.farm_location ?? '—'}</td>
                            <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 text-xs font-semibold">${h.crop ?? '—'}</td>
                            <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 text-xs">
                                <span class="font-semibold text-slate-700 dark:text-slate-300">${h.dropoff ?? '—'}</span>
                                <span class="block text-[10px] text-slate-400 dark:text-slate-500">Buyer: ${h.buyer ?? '—'}</span>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-800 dark:text-slate-200">${Number(h.quantity_kg).toLocaleString()} kg</td>
                            <td class="py-3.5 px-4 text-xs">${windowText}</td>
                            <td class="py-3.5 px-4 font-bold text-slate-700 dark:text-slate-300 text-right">${h.split_rate != null ? '₱' + Number(h.split_rate).toFixed(2) : '—'}/kg</td>
                            <td class="py-3.5 px-4 font-extrabold text-brand dark:text-brand-light text-right">
                                ₱${Number(h.split_cost ?? 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                            </td>
                        </tr>
                    `;
                });
            }

            document.getElementById('btn-confirm-plan').addEventListener('click', function () {
                var btn = this;
                swalConfirm(async function () {
                    btn.disabled = true; btn.textContent = 'Creating Proposal...';
                    const harvestIds = currentPlan.selected_harvests.map(h => h.harvest_id);

                    try {
                        const res = await fetch('{{ route("pooling.confirm") }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({
                                truck_id:       currentPlan.truck_id ? parseInt(currentPlan.truck_id) : parseInt(truckSelect.value),
                                harvest_ids:    harvestIds,
                                stop_order:     harvestIds,
                                total_kg:       currentPlan.total_kg,
                                start_lat:      startMarker.getLatLng().lat,
                                start_lng:      startMarker.getLatLng().lng,
                                end_lat:        endMarker.getLatLng().lat,
                                end_lng:        endMarker.getLatLng().lng,
                                radius_km:      parseFloat(document.getElementById('radius-select').value),
                                hauling_rate_per_kg: (function(){ var el = document.getElementById('hauling-rate'); return el ? parseFloat(el.value) : null; })(),
                                notes:          document.getElementById('plan-notes').value,
                                route_distance_km: currentRouteKm(),
                                terrain: (function () { var el = document.getElementById('terrain-select'); return el ? el.value : 'flat'; })(),
                                route_geometry: currentRouteGeoJSON ? currentRouteGeoJSON.coordinates : [],
                                farm_distances: currentFarmDistances,
                            }),
                        });

                        const result = await res.json();
                        const feedback = document.getElementById('confirm-feedback');
                        feedback.classList.remove('hidden');

                        if (res.ok && result.success) {
                            document.getElementById('plan-status-badge').textContent = 'Proposal Created';
                            feedback.className = 'mt-4 p-4 rounded-xl bg-brand/10 dark:bg-brand/10 text-brand-dark dark:text-brand-light border border-brand/20 dark:border-brand/15 font-bold flex items-center gap-2';
                            feedback.innerHTML = '<span><svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></span> Proposal pipeline open. Room linked to Job #' + result.pooling_job_id;
                            btn.innerHTML = '<span><svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></span> Proposal Sent';
                            window.__nextSteps = {
                                title: 'Proposal created',
                                message: 'Your pooling proposal has been created and sent to farmers.',
                                steps: [
                                    'Farmers are now notified to review their cost share and accept or decline.',
                                    'Wait for all farmers to accept before the route is confirmed.',
                                    'Once confirmed, assign a driver so the trip can begin.',
                                    'Monitor progress under your Pooling Proposals page.'
                                ],
                                cta: { label: 'View Proposals', url: '{{ route("pooling.index") }}' }
                            };
                            showNextSteps();
                        } else {
                            feedback.className = 'mt-4 p-4 rounded-xl bg-rose-50 dark:bg-rose-950/20 text-rose-800 dark:text-rose-455 border border-rose-200/60 dark:border-rose-900/30 font-bold flex items-center gap-2';
                            feedback.innerHTML = '<span><svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></span> ' + result.error;
                            btn.disabled = false; btn.innerHTML = '<span><svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></span> Create Delivery Proposal';
                        }
                    } catch (err) { console.error(err); }
                }, {
                    title: 'Create Delivery Proposal?',
                    text: 'Open a pooling proposal and notify the selected farmers?',
                    icon: 'question',
                    confirmText: 'Yes, create proposal',
                    cancelText: 'Cancel',
                    confirmColor: '#16283C'
                });
            });

            document.getElementById('btn-confirm-all').addEventListener('click', function () {
                var btn = this;
                swalConfirm(async function () {
                    btn.disabled = true;
                    btn.textContent = 'Creating Proposals...';
                    var plansPayload = (currentPlans || []).map(function (plan) {
                        var harvestIds = plan.selected_harvests.map(function (h) { return h.harvest_id; });
                        return {
                            truck_id:             parseInt(plan.truck_id),
                            harvest_ids:          harvestIds,
                            stop_order:           harvestIds,
                            total_kg:             plan.total_kg,
                            start_lat:            startMarker.getLatLng().lat,
                            start_lng:            startMarker.getLatLng().lng,
                            end_lat:              endMarker.getLatLng().lat,
                            end_lng:              endMarker.getLatLng().lng,
                            radius_km:            parseFloat(document.getElementById('radius-select').value),
                            hauling_rate_per_kg:  (function(){ var el = document.getElementById('hauling-rate'); return el ? parseFloat(el.value) : null; })(),
                            notes:                document.getElementById('plan-all-notes').value,
                            route_distance_km:    plan._distanceKm ?? currentRouteKm(),
                            terrain:              (function () { var el = document.getElementById('terrain-select'); return el ? el.value : 'flat'; })(),
                            route_geometry:       (plan._geometry?.coordinates) ?? (currentRouteGeoJSON ? currentRouteGeoJSON.coordinates : []),
                            farm_distances:       currentFarmDistances,
                        };
                    });

                    try {
                        const res = await fetch('{{ route("pooling.confirmBatch") }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ plans: plansPayload, excluded: currentExcluded ?? [] }),
                        });
                        const result = await res.json();
                        var feedback = document.getElementById('confirm-all-feedback');
                        feedback.classList.remove('hidden');
                        if (res.ok && result.success) {
                            feedback.className = 'mt-4 p-4 rounded-xl bg-brand/10 dark:bg-brand/10 text-brand-dark dark:text-brand-light border border-brand/20 dark:border-brand/15 font-bold flex items-center gap-2';
                            feedback.textContent = result.message + ' Farmers are now notified to review and accept their cost share.';
                            window.__nextSteps = {
                                title: 'Routes created',
                                message: 'Each route is now an open pooling proposal sent to its farmers.',
                                steps: [
                                    'Farmers review their cost share and accept or decline per route.',
                                    "When all of a route's farmers accept, that route is confirmed.",
                                    'Assign a driver to each confirmed route so the trip can begin.',
                                    'Monitor every route under your Pooling Proposals page.'
                                ],
                                cta: { label: 'View Proposals', url: '{{ route("pooling.index") }}' }
                            };
                            showNextSteps();
                        } else {
                            feedback.className = 'mt-4 p-4 rounded-xl bg-rose-50 dark:bg-rose-950/20 text-rose-800 dark:text-rose-400 border border-rose-200/60 dark:border-rose-900/30 font-bold flex items-center gap-2';
                            feedback.textContent = result.error || 'Route confirmation failed. Please try again.';
                            btn.disabled = false;
                            btn.innerHTML = '<span><svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></span> Confirm All Routes';
                        }
                    } catch (err) {
                        console.error(err);
                        var feedback = document.getElementById('confirm-all-feedback');
                        feedback.classList.remove('hidden');
                        feedback.className = 'mt-4 p-4 rounded-xl bg-rose-50 dark:bg-rose-950/20 text-rose-800 dark:text-rose-400 border border-rose-200/60 dark:border-rose-900/30 font-bold flex items-center gap-2';
                        feedback.textContent = 'Route confirmation failed. Please try again.';
                        btn.disabled = false;
                        btn.innerHTML = '<span><svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></span> Confirm All Routes';
                    }
                }, {
                    title: 'Confirm all routes?',
                    text: 'Create a pooling proposal for every route shown above? Farmers will be notified to review their cost share.',
                    icon: 'question',
                    confirmText: 'Yes, confirm all',
                    cancelText: 'Cancel',
                    confirmColor: '#16283C'
                });
            });

            document.getElementById('radius-select').addEventListener('change', async function () {
                if (!baseRouteGeoJSON) return;
                const nearbyFarms = await findFarmsAlongRoute();
                lastNearbyFarms = nearbyFarms;
                renderPickupQueue(nearbyFarms);
                if (nearbyFarms.length > 0) generateDetourRoute(startMarker.getLatLng(), nearbyFarms, endMarker.getLatLng());
                else drawRoute(baseRouteGeoJSON);
            });

            document.getElementById('reset-map').addEventListener('click', function () {
                if (startMarker) map.removeLayer(startMarker); if (endMarker) map.removeLayer(endMarker); if (routePolyline) map.removeLayer(routePolyline);
                startMarker = null; endMarker = null; currentRouteGeoJSON = null; baseRouteGeoJSON = null; currentRouteDistanceKm = null; lastNearbyFarms = []; selectedFarmIds.clear(); toggledFarmIds.clear(); currentFarmDistances = {};
                suggestedPrefilled = false;
                farmMarkers.forEach(item => { item.marker.setIcon(defaultIcon); item.marker.setOpacity(1); });
                destinationMarkers.forEach(function (dm) { dm.marker.setOpacity(1); });
                document.getElementById('pickup-queue').innerHTML = '<div class="text-center text-slate-400 mt-10 italic">Awaiting route coordinates...</div>';
                document.getElementById('route-ready-cue').classList.add('hidden');
                renderOpenHaulRequests();
                historyLayer.clearLayers();
                document.getElementById('plan-panel').classList.add('hidden');
                document.getElementById('plan-all-panel').classList.add('hidden');
                currentPlans = [];
                currentExcluded = [];
                btnGenerate.disabled = true; this.classList.add('hidden');
            });
                });

        var logisticsHasLocation = {{ $logisticsHasLocation ? 'true' : 'false' }};

        
    </script>
    @endpush

    <x-location-picker-modal />

</x-layout>
