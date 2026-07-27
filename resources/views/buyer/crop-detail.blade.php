<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">

    <header class="mb-8 pt-6">
        <div class="flex items-center gap-2 mb-4">
            <a href="{{ route('buyer.crop-board') }}" class="text-xs font-bold text-harvest dark:text-harvest hover:underline flex items-center gap-1">
                ← Back to Crop Board
            </a>
        </div>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
        <div class="lg:col-span-3 space-y-6">
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl overflow-hidden">
                <div class="h-64 sm:h-80 relative flex items-center justify-center @if(!empty($harvest->crop_photos)) bg-slate-100 dark:bg-slate-900 @else bg-gradient-to-br from-harvest/20 via-brand/10 to-brand/10 dark:from-harvest/20 dark:via-brand/10 dark:to-brand/10 @endif">
                    @if(!empty($harvest->crop_photos))
                        <img src="{{ asset('storage/' . $harvest->crop_photos[0]) }}" alt="{{ $harvest->crop->name ?? $harvest->crop_type }}" class="w-full h-full object-cover">
                    @else
                        <svg class="w-24 h-24 text-harvest/30 dark:text-harvest/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12M6 12h12M3 6h3M18 6h3M3 18h3M18 18h3M6 3v3M6 18v3M18 3v3M18 18v3" />
                        </svg>
                    @endif
                    <span class="absolute top-4 left-4 text-[10px] font-extrabold uppercase tracking-widest text-harvest dark:text-harvest bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm px-3 py-1 rounded-full border border-harvest/20">PRODUCT #{{ $harvest->id }}</span>
                    @if($harvest->status === 'active')
                        <span class="absolute top-4 right-4 text-[10px] font-extrabold uppercase tracking-widest text-[#3A7D44] dark:text-[#3A7D44] bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 backdrop-blur-sm px-3 py-1 rounded-full border border-[#3A7D44]/20">Available</span>
                    @endif
                </div>
                @if(!empty($harvest->crop_photos) && count($harvest->crop_photos) > 1)
                    <div class="flex gap-2 p-2 overflow-x-auto">
                        @foreach($harvest->crop_photos as $photo)
                            <div class="w-16 h-16 shrink-0 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900">
                                <img src="{{ asset('storage/' . $photo) }}" alt="" class="w-full h-full object-cover">
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white heading-font">{{ $harvest->crop->name ?? $harvest->crop_type }}</h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-semibold">{{ $harvest->cropVariety->name ?? $harvest->variety ?? 'Standard Variety' }}</p>
                    </div>
                    <span class="text-2xl font-extrabold text-[#3A7D44] dark:text-[#3A7D44] font-mono shrink-0">{{ number_format($harvest->quantity_kg) }} <span class="text-sm font-bold text-[#3A7D44]/70">kg</span></span>
                </div>

                <div class="flex flex-wrap items-center gap-2 mb-6">
                    @if($harvest->harvest_date)
                        @php $daysAgo = $harvest->harvest_date->diffInDays(now()); @endphp
                        @if($daysAgo <= 7)
                            <span class="text-[10px] font-bold text-rose-500 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/30 px-2.5 py-1 rounded border border-rose-200/50 dark:border-rose-700/30">FRESH</span>
                        @endif
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded border border-slate-200 dark:border-slate-700">Harvested {{ $harvest->harvest_date->format('M d, Y') }}</span>
                    @endif
                </div>

                @if($harvest->cropCategory)
                    <div class="mb-3">
                        <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">Category</span>
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $harvest->cropCategory->name }}</p>
                    </div>
                @endif

                @if($harvest->notes)
                    <div class="mb-3">
                        <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">Description</span>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">{{ $harvest->notes }}</p>
                    </div>
                @endif

                @if($harvest->cropVariety && $harvest->cropVariety->price_per_kg)
                    <div class="mt-4 p-4 bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 border border-[#3A7D44]/20 dark:border-[#3A7D44]/20 rounded-2xl">
                        <span class="text-xs font-semibold text-[#3A7D44] dark:text-[#3A7D44]">Reference Price</span>
                        <p class="text-lg font-extrabold text-[#3A7D44] dark:text-[#3A7D44]/60 font-mono">₱{{ number_format($harvest->cropVariety->price_per_kg, 2) }} <span class="text-sm font-bold text-[#3A7D44]/70">/ kg</span></p>
                    </div>
                @endif

                @if($harvest->suggested_price_per_kg)
                    <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200/50 dark:border-green-700/30 rounded-2xl">
                        <span class="text-xs font-semibold text-green-700 dark:text-green-400">Farmer's Suggested Price</span>
                        <p class="text-lg font-extrabold text-green-700 dark:text-green-400 font-mono">₱{{ number_format($harvest->suggested_price_per_kg, 2) }} <span class="text-sm font-bold text-green-600/70 dark:text-green-400/70">/ kg</span></p>
                    </div>
                @else
                    <div class="mt-4 p-4 bg-slate-50 dark:bg-slate-700/50 border border-slate-200/50 dark:border-slate-600/30 rounded-2xl">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Farmer's Price</span>
                        <p class="text-sm font-bold text-slate-500 dark:text-slate-400">Open to Negotiation</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6">
                <h2 class="text-sm font-extrabold text-slate-900 dark:text-white heading-font mb-4">Farmer</h2>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-harvest/10 dark:bg-harvest/50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-harvest dark:text-harvest" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ $harvest->farmer->name ?? 'Unknown Farmer' }}</p>
                        @if($harvest->farmer && $harvest->farmer->farmerProfile)
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $harvest->farmer->farmerProfile->farm_location ?? 'Location not set' }}</p>
                        @endif
                    </div>
                </div>
                @if($harvest->farmer && $harvest->farmer->farmerProfile && $harvest->farmer->farmerProfile->is_verified)
                    <div class="flex items-center gap-1.5 text-xs font-bold text-[#3A7D44] dark:text-[#3A7D44] bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 px-3 py-1.5 rounded-lg border border-[#3A7D44]/20 dark:border-[#3A7D44]/20 w-fit">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Verified Farmer
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6">
                <h2 class="text-sm font-extrabold text-slate-900 dark:text-white heading-font mb-4">Pickup Location</h2>
                @if($harvest->farmer && $harvest->farmer->farmerProfile && $harvest->farmer->farmerProfile->farm_location)
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-slate-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ $harvest->farmer->farmerProfile->farm_location }}</p>
                    </div>
                @else
                    <p class="text-sm text-slate-500 dark:text-slate-400">Location not specified</p>
                @endif
            </div>

            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6">
                @if($negotiation)
                    <a href="{{ route('negotiations.room', $negotiation->id) }}" class="w-full flex items-center justify-center gap-2 py-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200/50 dark:border-amber-700/30 rounded-2xl text-sm font-bold text-amber-700 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        Open Negotiation
                    </a>
                @else
                    <form action="{{ route('negotiations.start') }}" method="POST">
                        @csrf
                        <input type="hidden" name="harvest_id" value="{{ $harvest->id }}">
                        <button type="submit" class="w-full flex items-center justify-center gap-2 py-3 bg-harvest hover:bg-harvest-dark dark:bg-harvest dark:hover:bg-harvest-dark text-white font-bold rounded-2xl text-sm transition-colors shadow-sm shadow-harvest/10 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12M6 12h12" />
                            </svg>
                            Initiate Negotiation
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

</div>
</x-layout>
