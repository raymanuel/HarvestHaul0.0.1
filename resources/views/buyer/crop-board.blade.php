<x-layout>
<meta http-equiv="refresh" content="30">
<div class="w-full max-w-7xl mx-auto pb-12">

    <div class="relative z-10">
        <header class="mb-8 pt-6">
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('dashboard') }}" class="text-xs font-bold text-harvest dark:text-harvest hover:underline flex items-center gap-1">
                    ← Dashboard
                </a>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-harvest dark:text-harvest bg-harvest/10 dark:bg-harvest/10 px-3 py-1 rounded-full border border-harvest/20">Crop Board</span>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight heading-font mt-3">Available Posts</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Browse farmer posts and start a negotiation.</p>
                </div>
            </div>
        </header>

        @if($posts->isEmpty())
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-12 text-center">
                <h3 class="text-lg font-bold text-slate-800 dark:text-white heading-font">No Posts Available</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto">There are currently no active crop products posted by verified independent farmers on the marketplace.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                @foreach($posts as $post)
                    @php
                        $isNegotiating = in_array($post->id, $allNegotiatingIds);
                        $isMyNegotiation = in_array($post->id, $negotiatingHarvestIds);
                    @endphp
                    <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl {{ $isNegotiating && !$isMyNegotiation ? 'opacity-60 grayscale hover:none pointer-events-none' : 'hover:-translate-y-1.5 hover:shadow-xl hover:shadow-harvest/5 hover:border-harvest/30 dark:hover:border-harvest/30' }} transition-all duration-300 group flex flex-col relative overflow-hidden">
                        <div class="h-28 relative overflow-hidden flex items-center justify-center @if(!empty($post->crop_photos)) bg-slate-100 dark:bg-slate-900 @else bg-gradient-to-br from-harvest/20 to-brand/10 dark:from-harvest/20 dark:to-brand/10 @endif">
                            @if(!empty($post->crop_photos))
                                <img src="{{ asset('storage/' . $post->crop_photos[0]) }}" alt="{{ $post->crop->name ?? $post->crop_type }}" class="w-full h-full object-cover">
                            @else
                                <x-icon name="folder" class="w-12 h-12 text-harvest/40 dark:text-harvest/30" />
                            @endif
                            <span class="absolute top-3 left-3 text-[9px] font-extrabold uppercase tracking-widest text-harvest dark:text-harvest bg-white/70 dark:bg-slate-900/70 backdrop-blur-sm px-2 py-0.5 rounded border border-harvest/20">Post #{{ $post->id }}</span>
                        </div>
                        <div class="p-5 flex flex-col flex-1">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <h3 class="text-base font-extrabold text-slate-900 dark:text-white heading-font leading-snug">{{ $post->crop->name ?? $post->crop_type }}</h3>
                                <div class="text-right shrink-0">
                                    <span class="text-base font-extrabold text-[#3A7D44] dark:text-[#3A7D44] font-mono">{{ number_format($post->quantity_kg) }} <span class="text-[10px] font-bold text-[#3A7D44]/70 dark:text-[#3A7D44]/70">kg</span></span>
                                    @if($post->remaining_quantity_kg && (float)$post->remaining_quantity_kg < (float)$post->quantity_kg)
                                        <span class="text-[9px] font-bold text-amber-600 dark:text-amber-400 block">{{ number_format($post->remaining_quantity_kg, 0) }} kg left</span>
                                    @endif
                                </div>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-semibold">{{ $post->cropVariety->name ?? $post->variety ?? 'Standard Variety' }}</p>

                            @if($post->suggested_price_per_kg)
                                <div class="mt-2 flex items-center gap-1.5">
                                    <span class="text-sm font-extrabold text-[#2D8A37] dark:text-[#3A7D44] font-mono">₱{{ number_format($post->suggested_price_per_kg, 2) }}</span>
                                    <span class="text-[9px] font-bold text-[#3A7D44]/70 dark:text-[#3A7D44]/70">/kg suggested</span>
                                </div>
                            @else
                                <div class="mt-2">
                                    <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded">Negotiable</span>
                                </div>
                            @endif

                            <div class="mt-3 flex items-center gap-2 flex-wrap">
                                @if($isNegotiating && !$isMyNegotiation)
                                    <span class="text-[9px] font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-600">UNDER NEGOTIATION</span>
                                @elseif($post->status === 'partially_sold')
                                    <span class="text-[9px] font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 rounded border border-amber-200/50 dark:border-amber-700/30">PARTIAL SALE</span>
                                @endif
                                @if($post->harvest_date)
                                    @php $daysAgo = $post->harvest_date->diffInDays(now()); @endphp
                                    @if($daysAgo <= 7)
                                        <span class="text-[9px] font-bold text-rose-500 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/30 px-2 py-0.5 rounded border border-rose-200/50 dark:border-rose-700/30">FRESH</span>
                                    @endif
                                @endif
                            </div>

                            @if($post->status === 'partially_sold' && $post->sale_progress !== null)
                                <div class="mt-2 w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-amber-500 h-full rounded-full" style="width: {{ $post->sale_progress }}%"></div>
                                </div>
                                <p class="text-[9px] text-slate-400 dark:text-slate-500 mt-1 font-semibold">{{ $post->sale_progress }}% sold</p>
                            @endif

                            <div class="mt-auto pt-4 border-t border-slate-100 dark:border-slate-700/50">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">{{ $post->farmer->name ?? 'Farmer' }}</span>
                                </div>
                                @if($post->notes)
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 leading-relaxed mb-3 line-clamp-2">"{{ Str::limit($post->notes, 80) }}"</p>
                                @endif
                                <a href="{{ route('buyer.crop-board.show', $post->id) }}" class="w-full flex items-center justify-center gap-2 py-2 mb-2 bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                                    <x-icon name="search" class="w-3.5 h-3.5" />
                                    View Details
                                </a>
                                @if($isMyNegotiation)
                                    <a href="{{ route('negotiations.room', $negotiationRoomMap[$post->id]) }}" class="w-full flex items-center justify-center gap-2 py-2.5 bg-amber-50 dark:bg-amber-900/20 border border-amber-200/50 dark:border-amber-700/30 rounded-xl text-xs font-bold text-amber-700 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors">
                                        <x-icon name="chat" class="w-3.5 h-3.5" />
                                        Continue Negotiation
                                    </a>
                                @elseif($isNegotiating)
                                    <div class="w-full flex items-center justify-center gap-2 py-2.5 bg-slate-100 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-xs font-bold text-slate-400 dark:text-slate-500 cursor-not-allowed">
                                        <x-icon name="document" class="w-3.5 h-3.5" />
                                        Under Negotiation
                                    </div>
                                @else
                                    <form action="{{ route('negotiations.start') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="harvest_id" value="{{ $post->id }}">
                                        <button type="submit" class="w-full flex items-center justify-center gap-2 py-2.5 bg-harvest hover:bg-harvest-dark dark:bg-harvest dark:hover:bg-harvest-dark text-white font-bold rounded-xl text-xs transition-colors shadow-sm shadow-harvest/10 cursor-pointer">
                                            <x-icon name="plus" class="w-3.5 h-3.5" />
                                            Initiate Negotiation
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $posts->links() }}
            </div>
        @endif
    </div>

</div>
</x-layout>
