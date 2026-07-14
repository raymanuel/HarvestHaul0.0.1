<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">

    <!-- Ambient glow decoration -->
    <div class="absolute top-0 right-1/4 w-96 h-96 rounded-full bg-violet-500/5 blur-[120px] pointer-events-none z-0"></div>
    <div class="absolute top-1/3 left-1/3 w-[500px] h-[500px] rounded-full bg-indigo-500/5 blur-[150px] pointer-events-none z-0"></div>

    <div class="relative z-10">
        <!-- Page Header -->
        <header class="mb-8 pt-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-violet-600 dark:text-violet-400 bg-violet-500/10 dark:bg-violet-400/10 px-3 py-1 rounded-full border border-violet-500/20">Buyer Portal</span>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight heading-font mt-3">Welcome, {{ Auth::user()->name }}</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Browse crop posts, negotiate with farmers, and manage your purchase deals.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold font-mono text-slate-400 dark:text-slate-500">{{ now()->format('l, M d, Y') }}</span>
                </div>
            </div>
        </header>

        @if (session('error'))
            <div class="mb-6 bg-red-500/10 border border-red-500/20 text-red-650 dark:text-red-400 rounded-2xl p-5 text-sm font-semibold flex items-center gap-3 shadow-sm">
                <span class="w-6 h-6 rounded-full bg-red-500/20 flex items-center justify-center text-red-500 shrink-0 text-xs">⚠️</span>
                {{ session('error') }}
            </div>
        @endif

        {{-- ── PLATFORM OVERVIEW STATS ── --}}
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Buyer Console Dashboard</h2>
            <span class="w-20 h-px bg-slate-200 dark:bg-slate-700/80"></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">

            {{-- Card 1: Active Negotiations --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:-translate-y-1 hover:shadow-xl hover:shadow-violet-500/5 hover:border-violet-500/30 dark:hover:border-violet-500/30 transition-all duration-300 group flex flex-col justify-between h-52 relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-violet-500/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                <div>
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-violet-500/10 border border-violet-500/15 flex items-center justify-center text-violet-600 dark:text-violet-400 shrink-0 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-violet-600 dark:text-violet-400 bg-violet-500/10 px-2 py-0.5 rounded border border-violet-500/10">Active</span>
                    </div>
                    <h3 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none">Active Negotiations</h3>
                    <p class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight heading-font mt-2.5">
                        {{ $activeNegotiations->count() }} <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">open deals</span>
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                    <a href="{{ route('buyer.negotiations') }}" class="text-violet-600 dark:text-violet-400 font-bold text-xs hover:underline transition inline-flex items-center gap-1.5 group-hover:translate-x-1 duration-200">
                        View Negotiations <span>→</span>
                    </a>
                </div>
            </div>

            {{-- Card 2: Completed Deals --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:-translate-y-1 hover:shadow-xl hover:shadow-[#3A7D44]/5 hover:border-[#3A7D44]/30 dark:hover:border-[#3A7D44]/30 transition-all duration-300 group flex flex-col justify-between h-52 relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-[#3A7D44]/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                <div>
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-[#3A7D44]/10 border border-[#3A7D44]/15 flex items-center justify-center text-[#3A7D44] dark:text-[#3A7D44] shrink-0 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-[#3A7D44] dark:text-[#3A7D44] bg-[#3A7D44]/10 px-2 py-0.5 rounded border border-[#3A7D44]/10">Closed</span>
                    </div>
                    <h3 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none">Completed Deals</h3>
                    <p class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight heading-font mt-2.5">
                        {{ $completedDeals }} <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">total purchases</span>
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                    <a href="{{ route('buyer.negotiations') }}" class="text-[#3A7D44] dark:text-[#3A7D44] font-bold text-xs hover:underline transition inline-flex items-center gap-1.5 group-hover:translate-x-1 duration-200">
                        View History <span>→</span>
                    </a>
                </div>
            </div>

            {{-- Card 3: Browse Crop Board --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:-translate-y-1 hover:shadow-xl hover:shadow-amber-500/5 hover:border-amber-500/30 dark:hover:border-amber-500/30 transition-all duration-300 group flex flex-col justify-between h-52 relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-amber-500/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                <div>
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/15 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-amber-700 dark:text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/10">Marketplace</span>
                    </div>
                    <h3 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none">Crop Board</h3>
                    <p class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight heading-font mt-2.5">
                        {{ $recentPosts->count() }} <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">available lots</span>
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                    <a href="{{ route('buyer.crop-board') }}" class="text-amber-650 dark:text-amber-450 font-bold text-xs hover:underline transition inline-flex items-center gap-1.5 group-hover:translate-x-1 duration-200">
                        Browse All Posts <span>→</span>
                    </a>
                </div>
            </div>

        </div>

        {{-- ── LIVE WORKSPACE DATA ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">

            {{-- COLUMN 1 & 2: Active Negotiations (left 2/3 on desktop) --}}
            <div class="lg:col-span-2 space-y-8">

                {{-- Active Negotiations Table --}}
                <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-slate-800 dark:text-white heading-font">Active Negotiations</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Current purchase deals with independent farmers.</p>
                        </div>
                        <a href="{{ route('buyer.crop-board') }}" class="bg-violet-600 hover:bg-violet-700 dark:bg-violet-500 dark:hover:bg-violet-600 text-white text-xs font-bold px-4 py-2 rounded-xl transition duration-200 shadow-sm flex items-center gap-1.5">
                            <span>+</span> Find Crops
                        </a>
                    </div>

                    @if($activeNegotiations->isEmpty())
                        <div class="p-8 text-center border border-dashed border-slate-200 dark:border-slate-700 rounded-2xl">
                            
                            <p class="text-slate-455 dark:text-slate-400 text-sm font-semibold">No active negotiations</p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Browse the Crop Board to find posts and start negotiating with farmers.</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($activeNegotiations as $negotiation)
                                <div class="bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200/50 dark:border-slate-700 rounded-2xl p-5 hover:border-violet-500/30 dark:hover:border-violet-500/20 transition-all group">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-[9px] font-extrabold uppercase tracking-widest
                                            {{ $negotiation->status === 'AGREED' ? 'text-[#3A7D44] dark:text-[#3A7D44] bg-[#3A7D44]/10 border-[#3A7D44]/10' : 'text-violet-700 dark:text-violet-400 bg-violet-500/10 border-violet-500/10' }}
                                            px-2 py-0.5 rounded border">{{ $negotiation->status }}</span>
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">{{ $negotiation->created_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ $negotiation->harvest->crop->name ?? 'Crop' }} — {{ $negotiation->harvest->cropVariety->name ?? 'Standard' }}</h4>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Farmer: {{ $negotiation->farmer->name ?? 'Unknown' }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs text-slate-400 dark:text-slate-500">Offered</p>
                                            <p class="text-lg font-extrabold text-slate-800 dark:text-white font-mono">₱{{ number_format($negotiation->offered_price, 2) }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>

            {{-- COLUMN 3: Recent Posts (right 1/3 on desktop) --}}
            <div class="space-y-8">

                <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm h-full flex flex-col">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white heading-font">Fresh Posts</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Latest crop lots available from verified farmers.</p>
                    </div>

                    @if($recentPosts->isEmpty())
                        <div class="p-8 text-center border border-dashed border-slate-200 dark:border-slate-700 rounded-2xl my-auto">
                            
                            <p class="text-slate-455 dark:text-slate-400 text-sm font-semibold">No posts available</p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">When farmers publish new crop lots, they will appear here.</p>
                        </div>
                    @else
                        <div class="space-y-4 flex-1">
                            @foreach($recentPosts as $post)
                                <div class="bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200/50 dark:border-slate-700 rounded-2xl p-4 hover:border-amber-500/30 dark:hover:border-amber-500/20 transition-all duration-300">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-amber-700 dark:text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/10">Available</span>
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 font-mono">{{ number_format($post->quantity_kg) }} kg</span>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ $post->crop->name ?? $post->crop_type }}</h4>
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">{{ $post->cropVariety->name ?? $post->variety ?? 'Standard Variety' }}</p>
                                    <div class="mt-3 pt-3 border-t border-slate-200/50 dark:border-slate-700/50 flex justify-between items-center">
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">{{ $post->farmer->name ?? 'Farmer' }}</span>
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">{{ $post->harvest_date ? $post->harvest_date->format('M d') : '—' }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <a href="{{ route('buyer.crop-board') }}" class="mt-4 block text-center text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline">
                            View All Posts →
                        </a>
                    @endif
                </div>

            </div>

        </div>
    </div>

</div>
</x-layout>
