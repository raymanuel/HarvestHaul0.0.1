<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">

    <div class="relative z-10">
        <header class="mb-8 pt-8">
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('dashboard') }}" class="text-xs font-bold text-harvest-dark dark:text-harvest-light hover:underline flex items-center gap-1">
                    ← Back to Dashboard
                </a>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight heading-font">Available Posts</h1>
                </div>
            </div>
        </header>

        @if($posts->isEmpty())
            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-12 text-center">
                <h3 class="text-lg font-bold text-slate-800 dark:text-white heading-font">No Posts Available</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto">There are currently no active crop products posted by verified independent farmers on the marketplace.</p>
            </div>
        @else
            <div id="new-posts-banner" class="hidden mb-6 bg-[#16283C]/10 dark:bg-[#D7BC7A]/10 border border-[#16283C]/20 dark:border-[#D7BC7A]/20 rounded-xl px-5 py-3 flex items-center justify-between">
                <p class="text-xs font-bold text-[#16283C] dark:text-[#D7BC7A]">New posts available</p>
                <button onclick="window.location.reload()" class="text-[10px] font-bold text-[#16283C] dark:text-[#D7BC7A] underline">Refresh</button>
            </div>
            <div id="crop-board-freshness" class="mb-4 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                Updated just now
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                @foreach($posts as $post)
                    @php
                        $isNegotiating = in_array($post->id, $allNegotiatingIds);
                        $isMyNegotiation = in_array($post->id, $negotiatingHarvestIds);
                    @endphp
                    <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-3xl {{ $isNegotiating && !$isMyNegotiation ? 'opacity-60 grayscale hover:none pointer-events-none' : 'hover:-translate-y-1.5 hover:shadow-xl hover:shadow-harvest/5 hover:border-harvest/30 dark:hover:border-harvest/30' }} transition-all duration-300 group flex flex-col relative overflow-hidden">
                        <div class="h-28 relative overflow-hidden flex items-center justify-center @if(!empty($post->crop_photos)) bg-slate-100 dark:bg-slate-900 @else bg-gradient-to-br from-harvest/20 to-brand/10 dark:from-harvest/20 dark:to-brand/10 @endif">
                            @if(!empty($post->crop_photos))
                                <img src="{{ asset('storage/' . $post->crop_photos[0]) }}" alt="{{ $post->crop->name ?? $post->crop_type }}" class="w-full h-full object-cover">
                            @else
                                <x-icon name="folder" class="w-12 h-12 text-harvest/40 dark:text-harvest/30" />
                            @endif
                            <span class="absolute top-3 left-3 text-[9px] font-extrabold uppercase tracking-widest text-harvest-dark dark:text-harvest-light bg-white/70 dark:bg-slate-900/70 backdrop-blur-sm px-2 py-0.5 rounded border border-harvest/20">Post #{{ $post->id }}</span>
                        </div>
                        <div class="p-5 flex flex-col flex-1">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <h3 class="text-base font-extrabold text-slate-900 dark:text-white heading-font leading-snug">{{ $post->crop->name ?? $post->crop_type }}</h3>
                                <div class="text-right shrink-0">
                                    <span class="text-base font-extrabold text-[#16283C] dark:text-[#D7BC7A] font-mono">{{ number_format($post->quantity_kg) }} <span class="text-[10px] font-bold text-[#16283C]/70 dark:text-[#D7BC7A]/70">kg</span></span>
                                    @if($post->remaining_quantity_kg && (float)$post->remaining_quantity_kg < (float)$post->quantity_kg)
                                        <span class="text-[9px] font-bold text-[var(--color-warning-text)] block">{{ number_format($post->remaining_quantity_kg, 0) }} kg left</span>
                                    @endif
                                </div>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-semibold">{{ $post->cropVariety->name ?? $post->variety ?? 'Standard Variety' }}</p>

                            @if($post->suggested_price_per_kg)
                                <div class="mt-2 flex items-center gap-1.5">
                                    <span class="text-sm font-extrabold text-[#16283C] dark:text-[#D7BC7A] font-mono">₱{{ number_format($post->suggested_price_per_kg, 2) }}</span>
                                    <span class="text-[9px] font-bold text-[#16283C]/70 dark:text-[#D7BC7A]/70">/kg suggested</span>
                                </div>
                            @else
                                <div class="mt-2">
                                    <span class="text-[9px] font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded">Negotiable</span>
                                </div>
                            @endif

                            <div class="mt-3 flex items-center gap-2 flex-wrap">
                                @if($isNegotiating && !$isMyNegotiation)
                                    <span class="text-[9px] font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-600">UNDER NEGOTIATION</span>
                                @elseif($post->status->value === 'partially_sold')
                                    <span class="text-[9px] font-bold text-[var(--color-warning-text)] bg-[var(--color-warning-bg)] px-2 py-0.5 rounded border border-[var(--color-warning-border)]">PARTIAL SALE</span>
                                @endif
                                @if($post->harvest_date)
                                    @php $daysAgo = $post->harvest_date->diffInDays(now()); @endphp
                                    @if($daysAgo <= 7)
                                        <span class="text-[9px] font-bold text-rose-500 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/30 px-2 py-0.5 rounded border border-rose-200/50 dark:border-rose-700/30">FRESH</span>
                                    @endif
                                @endif
                            </div>

                            @if($post->status->value === 'partially_sold' && $post->sale_progress !== null)
                                <div class="mt-2 w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-[var(--color-warning-text)] h-full rounded-full" style="width: {{ $post->sale_progress }}%"></div>
                                </div>
                                <p class="text-[9px] text-slate-500 dark:text-slate-400 mt-1 font-semibold">{{ $post->sale_progress }}% sold</p>
                            @endif

                            <div class="mt-auto pt-4 border-t border-slate-100 dark:border-slate-700/50">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">{{ $post->farmer->name ?? 'Farmer' }}</span>
                                </div>
                                @if($post->notes)
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed mb-3 line-clamp-2">"{{ Str::limit($post->notes, 80) }}"</p>
                                @endif
                                <a href="{{ route('buyer.crop-board.show', $post->id) }}" class="w-full flex items-center justify-center gap-2 py-2 mb-2 bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                                    <x-icon name="search" class="w-3.5 h-3.5" />
                                    View Details
                                </a>
                                @if($isMyNegotiation)
                                    <a href="{{ route('negotiations.room', $negotiationRoomMap[$post->id]) }}" class="w-full flex items-center justify-center gap-2 py-2.5 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] rounded-xl text-xs font-bold text-[var(--color-warning-text)] hover:opacity-80 transition-colors">
                                        <x-icon name="chat" class="w-3.5 h-3.5" />
                                        Continue Negotiation
                                    </a>
                                @elseif($isNegotiating)
                                    <div class="w-full flex items-center justify-center gap-2 py-2.5 bg-slate-100 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-xs font-bold text-slate-500 dark:text-slate-400 cursor-not-allowed">
                                        <x-icon name="document" class="w-3.5 h-3.5" />
                                        Under Negotiation
                                    </div>
                                @else
                                    <form action="{{ route('negotiations.start') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="harvest_id" value="{{ $post->id }}">
                                        <button type="button" onclick="swalConfirm(this.closest('form'), {title:'Start Negotiation?', text:'Open a crop negotiation with this farmer?', icon:'question', confirmText:'Yes, start', cancelText:'Cancel', confirmColor:'#16283C'})" class="w-full flex items-center justify-center gap-2 py-2.5 bg-harvest hover:bg-harvest-dark dark:bg-harvest dark:hover:bg-harvest-dark text-[#17202B] font-bold rounded-xl text-xs transition-colors shadow-sm shadow-harvest/10 cursor-pointer">
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

<script>
    (function () {
        var jsonUrl = '{{ route("buyer.crop-board.json") }}';
        var lastCount = {{ $posts->total() }};
        var freshnessEl = document.getElementById('crop-board-freshness');
        var lastChecked = Date.now();

        function updateFreshness() {
            if (!freshnessEl) return;
            var secs = Math.floor((Date.now() - lastChecked) / 1000);
            if (secs < 60) {
                freshnessEl.textContent = 'Updated ' + secs + 's ago';
            } else {
                freshnessEl.textContent = 'Updated ' + Math.floor(secs / 60) + 'm ago';
            }
        }

        setInterval(updateFreshness, 10000);

        setInterval(function () {
            fetch(jsonUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (!data) return;
                    lastChecked = Date.now();
                    updateFreshness();
                    if (data.count !== lastCount) {
                        // Instead of window.location.reload(), show a banner
                        var banner = document.getElementById('new-posts-banner');
                        if (banner) {
                            banner.classList.remove('hidden');
                        }
                    }
                })
                .catch(function () {});
        }, 30000);
    })();
</script>
</x-layout>
