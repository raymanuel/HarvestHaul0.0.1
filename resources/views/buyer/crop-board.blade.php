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
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">Available Posts</h1>
                </div>
            </div>
        </header>

        @if($posts->isEmpty())
            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl p-12 text-center">
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
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
                @foreach($posts as $post)
                    @php
                        $isNegotiating = in_array($post->id, $allNegotiatingIds);
                        $isMyNegotiation = in_array($post->id, $negotiatingHarvestIds);
                    @endphp
                    <x-crop-card
                        :post="$post"
                        :href="route('buyer.crop-board.show', $post->id)"
                        :negotiating="$isNegotiating"
                        :my-negotiation="$isMyNegotiation"
                        :negotiation-url="isset($negotiationRoomMap[$post->id]) ? route('negotiations.room', $negotiationRoomMap[$post->id]) : null"
                    />
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
