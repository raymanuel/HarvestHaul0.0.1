<x-layout>
<meta http-equiv="refresh" content="30">
<div class="w-full max-w-7xl mx-auto pb-12">

    <div class="relative z-10">
        <header class="mb-8 pt-6">
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('dashboard') }}" class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1">
                    ← Dashboard
                </a>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-violet-600 dark:text-violet-400 bg-violet-500/10 dark:bg-violet-400/10 px-3 py-1 rounded-full border border-violet-500/20">B2B Crop Board</span>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight heading-font mt-3">Available Posts</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Browse verified independent farmer posts and initiate direct B2B negotiations.</p>
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
                    <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl {{ $isNegotiating && !$isMyNegotiation ? 'opacity-60 grayscale hover:none pointer-events-none' : 'hover:-translate-y-1.5 hover:shadow-xl hover:shadow-violet-500/5 hover:border-violet-500/30 dark:hover:border-violet-500/30' }} transition-all duration-300 group flex flex-col relative overflow-hidden">
                        <div class="h-28 relative overflow-hidden flex items-center justify-center @if(!empty($post->crop_photos)) bg-slate-100 dark:bg-slate-900 @else bg-gradient-to-br from-violet-500/20 to-indigo-500/10 dark:from-violet-600/20 dark:to-indigo-600/10 @endif">
                            @if(!empty($post->crop_photos))
                                <img src="{{ asset('storage/' . $post->crop_photos[0]) }}" alt="{{ $post->crop->name ?? $post->crop_type }}" class="w-full h-full object-cover">
                            @else
                                <svg class="w-12 h-12 text-violet-400/40 dark:text-violet-500/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12M6 12h12M3 6h3M18 6h3M3 18h3M18 18h3M6 3v3M6 18v3M18 3v3M18 18v3" />
                                </svg>
                            @endif
                            <span class="absolute top-3 left-3 text-[9px] font-extrabold uppercase tracking-widest text-violet-600 dark:text-violet-400 bg-white/70 dark:bg-slate-900/70 backdrop-blur-sm px-2 py-0.5 rounded border border-violet-500/20">PRODUCT #{{ $post->id }}</span>
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

                            <div class="mt-3 flex items-center gap-2 flex-wrap">
                                @php
                                    $grade = $post->quality_grade ?? 'Standard';
                                    $gradeColors = ['Premium' => 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 border-amber-200/50 dark:border-amber-700/30', 'Standard' => 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700', 'Budget' => 'text-[#3A7D44] dark:text-[#3A7D44] bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 border-[#3A7D44]/20 dark:border-[#3A7D44]/20'];
                                    $colorClass = $gradeColors[$grade] ?? $gradeColors['Standard'];
                                @endphp
                                <span class="text-[9px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded border {{ $colorClass }}">{{ $grade }}</span>
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
                                    <div class="w-6 h-6 rounded-full bg-violet-100 dark:bg-violet-900/50 flex items-center justify-center">
                                        <svg class="w-3 h-3 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">{{ $post->farmer->name ?? 'Farmer' }}</span>
                                </div>
                                @if($post->notes)
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 leading-relaxed mb-3 line-clamp-2">"{{ Str::limit($post->notes, 80) }}"</p>
                                @endif
                                <a href="{{ route('buyer.crop-board.show', $post->id) }}" class="w-full flex items-center justify-center gap-2 py-2 mb-2 bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    View Details
                                </a>
                                @if($isMyNegotiation)
                                    <a href="{{ route('negotiations.room', $negotiationRoomMap[$post->id]) }}" class="w-full flex items-center justify-center gap-2 py-2.5 bg-amber-50 dark:bg-amber-900/20 border border-amber-200/50 dark:border-amber-700/30 rounded-xl text-xs font-bold text-amber-700 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        Continue Negotiation
                                    </a>
                                @elseif($isNegotiating)
                                    <div class="w-full flex items-center justify-center gap-2 py-2.5 bg-slate-100 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-xs font-bold text-slate-400 dark:text-slate-500 cursor-not-allowed">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Under Negotiation
                                    </div>
                                @else
                                    <form action="{{ route('negotiations.start') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="harvest_id" value="{{ $post->id }}">
                                        <button type="submit" class="w-full flex items-center justify-center gap-2 py-2.5 bg-violet-600 hover:bg-violet-700 dark:bg-violet-500 dark:hover:bg-violet-600 text-white font-bold rounded-xl text-xs transition-colors shadow-sm shadow-violet-500/10 cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12M6 12h12" />
                                            </svg>
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
