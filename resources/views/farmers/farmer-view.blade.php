<x-layout>
@push('head')
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&display=swap" rel="stylesheet" />
    <style>
        .heading-font { font-family: 'DM Serif Display', serif !important; }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }
    </style>
@endpush
<div class="w-full max-w-7xl mx-auto pb-12">

    <!-- Ambient glow decoration -->
    <div class="absolute top-0 right-1/4 w-96 h-96 rounded-full bg-[#3A7D44]/5 blur-[120px] pointer-events-none z-0"></div>
    <div class="absolute top-1/3 left-1/3 w-[500px] h-[500px] rounded-full bg-[#2E6336]/5 blur-[150px] pointer-events-none z-0"></div>

    <div class="relative z-10">
        <!-- Page Header -->
        <header class="mb-8 pt-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-[#3A7D44] dark:text-[#3A7D44] bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 px-3 py-1 rounded-full border border-[#3A7D44]/20">Farmer Portal</span>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight heading-font mt-3">Welcome, {{ Auth::user()->name }}</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Manage harvests, coordinate B2B highway pooling, and monitor active shipments.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold font-mono text-slate-400 dark:text-slate-500">{{ now()->format('l, M d, Y') }}</span>
                </div>
            </div>
        </header>

        {{-- PENDING VERIFICATION BANNER --}}
        @if (!Auth::user()->farmerProfile?->is_verified)
            <div class="mb-8 bg-gradient-to-r from-[#D4A520]/10 via-[#D4A520]/5 to-transparent border border-[#D4A520]/20 rounded-3xl p-6 shadow-sm flex items-start gap-4 relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-32 h-32 bg-[#D4A520]/5 rounded-full blur-2xl group-hover:scale-150 transition-all duration-700"></div>
                <div class="w-12 h-12 rounded-2xl bg-[#D4A520]/15 border border-[#D4A520]/30 flex items-center justify-center text-[#D4A520] dark:text-[#D4A520] shrink-0 text-xl shadow-inner select-none">⏳</div>
                <div>
                    <h3 class="text-base font-bold text-[#B8911A] dark:text-[#D4A520] heading-font">Account Pending Board Verification</h3>
                    <p class="text-xs text-[#B8911A]/95 dark:text-[#D4A520]/90 mt-1.5 leading-relaxed max-w-3xl font-medium">
                        Your submitted credentials and cooperative licensing records are currently undergoing verification audit. 
                        Full route pooling capabilities and crop posts will unlock once verified.
                    </p>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 bg-red-500/10 border border-red-500/20 text-red-650 dark:text-red-400 rounded-2xl p-5 text-sm font-semibold flex items-center gap-3 shadow-sm">
                <span class="w-6 h-6 rounded-full bg-red-500/20 flex items-center justify-center text-red-500 shrink-0 text-xs">⚠️</span>
                {{ session('error') }}
            </div>
        @endif

        {{-- ── PLATFORM OVERVIEW STATS ── --}}
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Farmer Console Dashboard</h2>
            <span class="w-20 h-px bg-slate-200 dark:bg-slate-700/80"></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">

            {{-- Card 1: Active Harvests --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:-translate-y-1 hover:shadow-xl hover:shadow-[#3A7D44]/5 hover:border-[#3A7D44]/30 dark:hover:border-[#3A7D44]/30 transition-all duration-300 group flex flex-col justify-between h-52 relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-[#3A7D44]/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                <div>
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-[#3A7D44]/10 border border-[#3A7D44]/15 flex items-center justify-center text-[#3A7D44] dark:text-[#3A7D44] shrink-0 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.271.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.271.477-4.5 1.253" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-[#3A7D44] dark:text-[#3A7D44] bg-[#3A7D44]/10 px-2 py-0.5 rounded border border-[#3A7D44]/10">In Stock</span>
                    </div>
                    <h3 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none">Active Harvest Posts</h3>
                    <p class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight heading-font mt-2.5">
                        {{ $activeHarvestsCount ?? 0 }} <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">active posts</span>
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                    <a href="{{ route('harvests.index') }}" class="text-[#3A7D44] dark:text-[#3A7D44] font-bold text-xs hover:underline transition inline-flex items-center gap-1.5 group-hover:translate-x-1 duration-200">
                        Manage Harvests <span>→</span>
                    </a>
                </div>
            </div>

            {{-- Card 2: Track Shipments --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:-translate-y-1 hover:shadow-xl hover:shadow-[#1F4D25]/5 hover:border-[#1F4D25]/30 dark:hover:border-[#1F4D25]/30 transition-all duration-300 group flex flex-col justify-between h-52 relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-[#1F4D25]/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                <div>
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-[#1F4D25]/10 border border-[#1F4D25]/15 flex items-center justify-center text-[#1F4D25] dark:text-[#3A7D44] shrink-0 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-[#1F4D25] dark:text-[#3A7D44] bg-[#1F4D25]/10 px-2 py-0.5 rounded border border-[#1F4D25]/10">In Transit</span>
                    </div>
                    <h3 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none">Shipments En Route</h3>
                    <p class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight heading-font mt-2.5">
                        {{ $activeShipmentsCount ?? 0 }} <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">active runs</span>
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                    <a href="{{ route('tracking.index') }}" class="text-[#1F4D25] dark:text-[#3A7D44] font-bold text-xs hover:underline transition inline-flex items-center gap-1.5 group-hover:translate-x-1 duration-200">
                        View Live Map <span>→</span>
                    </a>
                </div>
            </div>

            {{-- CARD 3: B2B Pool Proposals --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:-translate-y-1 hover:shadow-xl hover:shadow-[#D4A520]/5 hover:border-[#D4A520]/30 dark:hover:border-[#D4A520]/30 transition-all duration-300 group flex flex-col justify-between h-52 relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-[#D4A520]/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                <div>
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-[#D4A520]/10 border border-[#D4A520]/15 flex items-center justify-center text-[#D4A520] dark:text-[#D4A520] shrink-0 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-[#B8911A] dark:text-[#D4A520] bg-[#D4A520]/10 px-2 py-0.5 rounded border border-[#D4A520]/10">Action Required</span>
                    </div>
                    <h3 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none">Pooling Proposals</h3>
                    <p class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight heading-font mt-2.5">
                        {{ $pendingProposalsCount ?? 0 }} <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">pending deals</span>
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                    <a href="{{ route('farmer.proposals') }}" class="text-[#B8911A] dark:text-[#D4A520] font-bold text-xs hover:underline transition inline-flex items-center gap-1.5 group-hover:translate-x-1 duration-200">
                        Review Cost Splits <span>→</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- ── LIVE WORKSPACE DATA ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">

            {{-- COLUMN 1 & 2: Crop Inventory & Proposals (left 2/3 on desktop) --}}
            <div class="lg:col-span-2 space-y-8">
                
                {{-- CARD 1: My Active Crop Inventory --}}
                <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-slate-800 dark:text-white heading-font">Active Posts</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Harvests currently listed on the B2B highway marketplace.</p>
                        </div>
                        @if (Auth::user()->farmerProfile?->is_verified)
                            <a href="{{ route('harvests.create') }}" class="bg-[#3A7D44] hover:bg-[#2E6336] dark:bg-[#3A7D44] dark:hover:bg-[#2E6336] text-white text-xs font-bold px-4 py-2 rounded-xl transition duration-200 shadow-sm flex items-center gap-1.5">
                                <span>+</span> Post Harvest
                            </a>
                        @endif
                    </div>

                    @if($activeHarvests->isEmpty())
                        <div class="p-8 text-center border border-dashed border-slate-200 dark:border-slate-700 rounded-2xl">
                            
                            <p class="text-slate-455 dark:text-slate-400 text-sm font-semibold">No active harvests listed</p>
                            @if (!Auth::user()->farmerProfile?->is_verified)
                                <p class="text-[10px] text-[#D4A520] dark:text-[#D4A520] mt-1 font-bold">Verify account to publish harvest posts</p>
                            @else
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Get started by creating your first crop product</p>
                            @endif
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead>
                                    <tr class="border-b border-slate-150 dark:border-slate-700/50">
                                        <th class="pb-3 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Crop / Variety</th>
                                        <th class="pb-3 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest text-right">Quantity</th>
                                        <th class="pb-3 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Destination</th>
                                        <th class="pb-3 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Harvested</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100/50 dark:divide-slate-700/30">
                                    @foreach($activeHarvests as $harvest)
                                        <tr class="group hover:bg-slate-50/30 dark:hover:bg-slate-900/20 transition duration-150">
                                            <td class="py-3.5 pr-4 whitespace-nowrap">
                                                <div class="font-bold text-slate-800 dark:text-slate-200 text-xs">
                                                    {{ $harvest->crop->name ?? $harvest->crop_type }}
                                                </div>
                                                <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">
                                                    {{ $harvest->cropVariety->name ?? $harvest->variety ?? 'Standard Variety' }}
                                                </div>
                                            </td>
                                            <td class="py-3.5 pr-4 text-right font-mono font-extrabold text-slate-700 dark:text-slate-350 text-xs">
                                                {{ number_format($harvest->quantity_kg) }} kg
                                                @if($harvest->remaining_quantity_kg && (float)$harvest->remaining_quantity_kg < (float)$harvest->quantity_kg)
                                                    <span class="text-[9px] text-amber-600 dark:text-amber-400 block">{{ number_format($harvest->remaining_quantity_kg, 0) }} remaining</span>
                                                @endif
                                            </td>
                                            <td class="py-3.5 pr-4 text-slate-500 dark:text-slate-400 text-xs font-semibold max-w-[150px] truncate">
                                                {{ $harvest->destination_label }}
                                            </td>
                                            <td class="py-3.5 text-slate-400 dark:text-slate-500 text-xs font-bold">
                                                {{ $harvest->harvest_date ? $harvest->harvest_date->format('M d, Y') : '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- CARD 2: Active Pooling Proposals --}}
                <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white heading-font mb-2">Pending Pooling Proposals</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">Logistics offers to combine your harvest with highway neighbors and split freight costs.</p>

                    @if($pendingProposals->isEmpty())
                        <div class="p-8 text-center border border-dashed border-slate-200 dark:border-slate-700 rounded-2xl">
                            
                            <p class="text-slate-455 dark:text-slate-400 text-sm font-semibold">No pending proposals</p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">When logistics partners build routes matching your location, offers will appear here.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($pendingProposals as $proposal)
                                @php
                                    $farmerHarvests = $proposal->harvests->where('user_id', Auth::id());
                                    $myCostShare = $farmerHarvests->sum('pivot.cost_share');
                                    $myQuantity = $farmerHarvests->sum('pivot.quantity_kg');
                                    
                                    // Calculate estimated savings compared to solo run
                                    $savings = $proposal->price_reference > 0 ? round((($proposal->price_reference - $proposal->negotiated_price) / $proposal->price_reference) * 100) : 25;
                                    if($savings <= 0) $savings = 30;
                                @endphp
                                <div class="bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200/50 dark:border-slate-700 rounded-2xl p-5 hover:border-[#D4A520]/30 dark:hover:border-[#D4A520]/20 transition-all group flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center justify-between mb-3.5">
                                            <span class="text-[9px] font-extrabold uppercase tracking-widest text-[#B8911A] dark:text-[#D4A520] bg-[#D4A520]/10 px-2 py-0.5 rounded border border-[#D4A520]/10">Cost Split Proposal</span>
                                            <span class="text-xs font-bold text-[#3A7D44] dark:text-[#3A7D44] font-mono">⚡ Save {{ $savings }}%</span>
                                        </div>
                                        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ $proposal->logisticsProfile->company_name ?? 'Logistics Partner' }}</h4>
                                        <div class="space-y-1.5 mt-3 text-xs text-slate-500 dark:text-slate-400">
                                            <div class="flex justify-between font-medium">
                                                <span>Your Cargo Volume:</span>
                                                <span class="font-bold text-slate-700 dark:text-slate-300">{{ number_format($myQuantity) }} kg</span>
                                            </div>
                                            <div class="flex justify-between font-medium">
                                                <span>Your Cost Share:</span>
                                                <span class="font-bold text-slate-950 dark:text-white font-mono">₱{{ number_format($myCostShare, 2) }}</span>
                                            </div>
                                            <div class="flex justify-between font-medium">
                                                <span>Assigned Truck:</span>
                                                <span class="font-mono text-slate-700 dark:text-slate-300">{{ $proposal->truck->truck_name ?? 'Truck' }} ({{ $proposal->truck->plate_number }})</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="pt-4 mt-4 border-t border-slate-200/60 dark:border-slate-800/80 flex items-center justify-between">
                                        <span class="text-[10px] text-slate-400 dark:text-slate-550 font-bold uppercase tracking-wider">Status: Pending negotiation</span>
                                        <a href="{{ route('farmer.proposals') }}" class="text-xs font-bold text-[#3A7D44] dark:text-[#3A7D44] hover:underline inline-flex items-center gap-1.5">
                                            Respond <span>→</span>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>

            {{-- COLUMN 3: Live Shipments / Tracking Tracker (right 1/3 on desktop) --}}
            <div class="space-y-8">
                
                {{-- CARD 3: Active Transport Runs --}}
                <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm h-full flex flex-col justify-between">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white heading-font">Live Shipments</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Crops currently en route to destination hubs.</p>
                    </div>

                    @if($activeShipments->isEmpty())
                        <div class="p-8 text-center border border-dashed border-slate-200 dark:border-slate-700 rounded-2xl my-auto">
                            <div class="w-12 h-12 rounded-full bg-slate-50 dark:bg-slate-900 flex items-center justify-center mx-auto mb-3 text-slate-400"><svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0z" /></svg></div>
                            <p class="text-slate-455 dark:text-slate-400 text-sm font-semibold">No active shipments</p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">When pooling deals are confirmed and dispatch begins, real-time GPS tracking will load here.</p>
                        </div>
                    @else
                        <div class="space-y-6 my-auto">
                            @foreach($activeShipments as $shipment)
                                <div class="bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200/50 dark:border-slate-700 rounded-2xl p-5 hover:border-[#3A7D44]/30 dark:hover:border-[#3A7D44]/20 transition-all duration-300">
                                    <div class="flex items-center justify-between mb-4">
                                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-[#1F4D25] dark:text-[#3A7D44] bg-[#1F4D25]/10 px-2 py-0.5 rounded border border-[#1F4D25]/10">In Transit</span>
                                        <a href="{{ route('tracking.index') }}" class="text-[10px] font-bold text-[#1F4D25] dark:text-[#3A7D44] hover:underline flex items-center gap-1">
                                            Track GPS
                                        </a>
                                    </div>
                                    
                                    {{-- Mini visual progress bar --}}
                                    <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden mb-4">
                                        <div class="bg-[#3A7D44] h-full w-2/3 rounded-full animate-pulse"></div>
                                    </div>

                                    <div class="space-y-2 text-xs">
                                        <div class="flex justify-between font-medium">
                                            <span class="text-slate-400 dark:text-slate-550">Driver:</span>
                                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ $shipment->driver->name ?? 'Assigned Driver' }}</span>
                                        </div>
                                        <div class="flex justify-between font-medium">
                                            <span class="text-slate-400 dark:text-slate-550">Vehicle:</span>
                                            <span class="font-mono text-slate-700 dark:text-slate-300">{{ $shipment->truck->plate_number ?? 'Plate #' }}</span>
                                        </div>
                                        <div class="flex justify-between font-medium">
                                            <span class="text-slate-400 dark:text-slate-550">Cargo Weight:</span>
                                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ number_format($shipment->total_kg) }} kg</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>

        </div>

        {{-- ── MODULE 6: FARMER ANALYTICS & DECISION TOOLS ── --}}
        <div class="mb-6 mt-12 flex items-center justify-between">
            <h2 class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Analytics & Decision Intelligence</h2>
            <span class="w-32 h-px bg-slate-200 dark:bg-slate-700/80"></span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
            
            {{-- WIDGET 1: Net Profit Calculator (2/3 width) --}}
            <div class="lg:col-span-2 bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm flex flex-col justify-between" id="profit-calculator">
                <div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white heading-font">Interactive Net Profit Calculator</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">Estimate yield revenue, subtract production expenses, and discover your profit margin.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Inputs -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Crop Selection / Name</label>
                                <input type="text" id="calc-crop-name" value="General Crops"
                                    class="w-full border border-slate-200 dark:border-slate-700 dark:bg-slate-900 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-350 font-bold focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/10 focus:border-[#3A7D44]">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Sale Price (₱/kg)</label>
                                    <input type="number" id="calc-sale-price" value="45.00" step="0.5"
                                        class="w-full border border-slate-200 dark:border-slate-700 dark:bg-slate-900 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-350 font-bold focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/10 focus:border-[#3A7D44]">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Yield Volume (kg)</label>
                                    <input type="number" id="calc-yield" value="1500" step="50"
                                        class="w-full border border-slate-200 dark:border-slate-700 dark:bg-slate-900 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-350 font-bold focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/10 focus:border-[#3A7D44]">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Seeds & Inputs (₱)</label>
                                    <input type="number" id="calc-seeds" value="5000" step="100"
                                        class="w-full border border-slate-200 dark:border-slate-700 dark:bg-slate-900 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-350 font-bold focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/10 focus:border-[#3A7D44]">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Fertilizer (₱)</label>
                                    <input type="number" id="calc-fertilizer" value="8000" step="100"
                                        class="w-full border border-slate-200 dark:border-slate-700 dark:bg-slate-900 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-350 font-bold focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/10 focus:border-[#3A7D44]">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Labor & Farming (₱)</label>
                                    <input type="number" id="calc-labor" value="12000" step="100"
                                        class="w-full border border-slate-200 dark:border-slate-700 dark:bg-slate-900 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-350 font-bold focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/10 focus:border-[#3A7D44]">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Logistics & Haul (₱)</label>
                                    <input type="number" id="calc-logistics" value="6500" step="100"
                                        class="w-full border border-slate-200 dark:border-slate-700 dark:bg-slate-900 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-350 font-bold focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/10 focus:border-[#3A7D44]">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Visual Output -->
                            <div class="bg-slate-50 dark:bg-slate-900/40 border border-slate-150 dark:border-slate-700 rounded-2xl p-5 flex flex-col justify-between text-center relative overflow-hidden group">
                            <div class="absolute -right-12 -bottom-12 w-36 h-36 bg-[#3A7D44]/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                            
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
                                            <p class="text-base font-bold text-[#3A7D44] dark:text-[#3A7D44] mt-0.5" id="calc-profit">₱36,000.00</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-6 flex flex-col items-center justify-center">
                                <div class="relative inline-flex items-center justify-center">
                                    <!-- Simple styled margin gauge using tailwind & raw inline CSS -->
                                    <div class="w-24 h-24 rounded-full border-[6px] border-slate-200 dark:border-slate-700 flex flex-col items-center justify-center relative">
                                        <span class="text-lg font-extrabold text-[#3A7D44] dark:text-[#3A7D44]" id="calc-margin">53.3%</span>
                                        <span class="text-[8px] font-extrabold uppercase text-slate-400 tracking-wider">Margin</span>
                                    </div>
                                </div>
                                <p class="text-[10px] text-[#3A7D44] dark:text-[#3A7D44] mt-3 font-bold" id="calc-crop-label">Profitability status: Healthy margin</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- WIDGET 2: Pricing Guidance Hub (1/3 width) --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm flex flex-col justify-between" id="pricing-guidance">
                <div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white heading-font">B2B Pricing Guidance</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">Real-time local market benchmark vs direct B2B pricing model.</p>
                    
                    <div class="space-y-4">
                        @foreach($guidanceCrops as $gCrop)
                            <div class="bg-slate-50/50 dark:bg-slate-900/30 border border-slate-100 dark:border-slate-700/50 rounded-2xl p-3 flex flex-col justify-between hover:border-[#3A7D44]/20 dark:hover:border-[#3A7D44]/20 transition group">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-xs text-slate-800 dark:text-slate-200">{{ $gCrop['name'] }}</span>
                                            @php
                                                $trendClass = match($gCrop['trend_color']) {
                                                    'emerald' => 'bg-[#3A7D44]/10 text-[#3A7D44] dark:text-[#3A7D44] border-[#3A7D44]/25',
                                                    'amber' => 'bg-[#D4A520]/10 text-[#B8911A] dark:text-[#D4A520] border-[#D4A520]/25',
                                                    default => 'bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-500/25',
                                                };
                                            @endphp
                                            <span class="text-[8px] font-bold px-1.5 py-0.5 rounded border {{ $trendClass }}">{{ $gCrop['trend'] }}</span>
                                        </div>
                                        <div class="flex items-center gap-3 mt-1.5 text-[10px] text-slate-400">
                                            <span>Broker: ₱{{ $gCrop['broker'] }}/kg</span>
                                            <span>B2B: <strong class="text-[#3A7D44] dark:text-[#3A7D44]">₱{{ $gCrop['b2b'] }}/kg</strong></span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        @php
                                            $premium = round((($gCrop['b2b'] - $gCrop['broker']) / $gCrop['broker']) * 100);
                                        @endphp
                                        <span class="text-xs font-mono font-black text-[#3A7D44] dark:text-[#3A7D44]">+{{ $premium }}%</span>
                                        <p class="text-[8px] text-slate-400 font-semibold mt-0.5">Direct Gain</p>
                                    </div>
                                </div>
                                <div class="pt-2 mt-2 border-t border-slate-100 dark:border-slate-800/80 flex justify-end">
                                    <button type="button" 
                                            onclick="applyToCalculator('{{ $gCrop['name'] }}', {{ $gCrop['b2b'] }})"
                                            class="text-[9px] font-bold text-[#3A7D44] dark:text-[#3A7D44] hover:underline uppercase tracking-wide inline-flex items-center gap-0.5">
                                        ⚡ Use in calculator
                                    </button>
                                </div>
                            </div>
                        @endforeach
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

                // Format values
                document.getElementById('calc-gross').textContent = '₱' + grossRevenue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                document.getElementById('calc-expenses').textContent = '₱' + totalExpenses.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                const profitEl = document.getElementById('calc-profit');
                profitEl.textContent = '₱' + netProfit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                if (netProfit < 0) {
                    profitEl.className = 'text-base font-bold text-[#C1694F] mt-0.5';
                } else {
                    profitEl.className = 'text-base font-bold text-[#3A7D44] dark:text-[#3A7D44] mt-0.5';
                }

                const marginEl = document.getElementById('calc-margin');
                marginEl.textContent = margin.toFixed(1) + '%';
                if (margin < 0) {
                    marginEl.className = 'text-lg font-extrabold text-[#C1694F]';
                } else {
                    marginEl.className = 'text-lg font-extrabold text-[#3A7D44] dark:text-[#3A7D44]';
                }

                // Update profitability status label
                const labelEl = document.getElementById('calc-crop-label');
                if (netProfit < 0) {
                    labelEl.textContent = 'Profitability status: Net loss. Check costs!';
                    labelEl.className = 'text-[10px] text-[#C1694F] mt-3 font-bold';
                } else if (margin < 15) {
                    labelEl.textContent = 'Profitability status: Low margin. Try direct B2B pooling!';
                    labelEl.className = 'text-[10px] text-[#D4A520] mt-3 font-bold';
                } else {
                    labelEl.textContent = 'Profitability status: Healthy margin. Excellent B2B return.';
                    labelEl.className = 'text-[10px] text-[#3A7D44] dark:text-[#3A7D44] mt-3 font-bold';
                }
            }

            function applyToCalculator(cropName, b2bPrice) {
                document.getElementById('calc-crop-name').value = cropName;
                document.getElementById('calc-sale-price').value = b2bPrice;
                
                // Trigger animation effect
                const calculatorCard = document.getElementById('profit-calculator');
                calculatorCard.classList.add('ring-2', 'ring-[#3A7D44]/20');
                setTimeout(() => calculatorCard.classList.remove('ring-2', 'ring-[#3A7D44]/20'), 500);

                calculateProfit();
            }

            // Bind events
            document.querySelectorAll('#profit-calculator input').forEach(input => {
                input.addEventListener('input', calculateProfit);
            });

            // Initialize on load
            window.addEventListener('load', () => {
                calculateProfit();
            });
        </script>
    </div>
</div>
</x-layout>
