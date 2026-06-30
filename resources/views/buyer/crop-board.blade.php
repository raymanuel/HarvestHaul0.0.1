<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">

    <!-- Ambient glow decoration -->
    <div class="absolute top-0 right-1/4 w-96 h-96 rounded-full bg-violet-500/5 blur-[120px] pointer-events-none z-0"></div>
    <div class="absolute top-1/3 left-1/3 w-[500px] h-[500px] rounded-full bg-indigo-500/5 blur-[150px] pointer-events-none z-0"></div>

    <div class="relative z-10">
        <!-- Page Header -->
        <header class="mb-8 pt-6">
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('dashboard') }}" class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1">
                    ← Dashboard
                </a>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-violet-600 dark:text-violet-400 bg-violet-500/10 dark:bg-violet-400/10 px-3 py-1 rounded-full border border-violet-500/20">B2B Crop Board</span>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight heading-font mt-3">Available Harvest Listings</h1>
                    <p class="text-sm text-slate-505 dark:text-slate-400 mt-1 font-medium">Browse verified independent farmer listings and initiate direct B2B negotiations.</p>
                </div>
            </div>
        </header>

        <!-- Listings Grid -->
        @if($listings->isEmpty())
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-12 text-center">
                <div class="w-16 h-16 rounded-full bg-slate-50 dark:bg-slate-900 flex items-center justify-center mx-auto mb-4 text-slate-400 text-2xl">🌾</div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white heading-font">No Harvest Listings Available</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto">There are currently no active crop lots posted by verified independent farmers on the marketplace.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                @foreach($listings as $listing)
                    <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:-translate-y-1.5 hover:shadow-xl hover:shadow-violet-500/5 hover:border-violet-500/30 dark:hover:border-violet-500/30 transition-all duration-300 group flex flex-col justify-between relative overflow-hidden">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-[9px] font-extrabold uppercase tracking-widest text-violet-700 dark:text-violet-400 bg-violet-500/10 px-2 py-0.5 rounded border border-violet-500/10">LOT #{{ $listing->id }}</span>
                                <span class="text-xs font-bold text-slate-800 dark:text-white font-mono bg-slate-100 dark:bg-slate-700 px-2.5 py-1 rounded-lg">{{ number_format($listing->quantity_kg) }} kg</span>
                            </div>

                            <h3 class="text-lg font-extrabold text-slate-800 dark:text-white heading-font leading-snug">{{ $listing->crop->name ?? $listing->crop_type }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-semibold">{{ $listing->cropVariety->name ?? $listing->variety ?? 'Standard Variety' }}</p>

                            <!-- Attributes list -->
                            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700/50 space-y-2.5 text-xs">
                                <div class="flex justify-between items-center font-medium">
                                    <span class="text-slate-400 dark:text-slate-500">Farmer:</span>
                                    <span class="text-slate-700 dark:text-slate-300 font-bold">{{ $listing->farmer->name ?? 'Farmer' }}</span>
                                </div>
                                <div class="flex justify-between items-center font-medium">
                                    <span class="text-slate-400 dark:text-slate-500">Grade / Quality:</span>
                                    <span class="text-slate-750 dark:text-slate-350 bg-slate-100 dark:bg-slate-750 px-2 py-0.5 rounded font-bold uppercase tracking-wider text-[10px]">{{ $listing->quality_grade ?? 'Standard' }}</span>
                                </div>
                                <div class="flex justify-between items-center font-medium">
                                    <span class="text-slate-400 dark:text-slate-500">Harvest Date:</span>
                                    <span class="text-slate-700 dark:text-slate-300 font-mono">{{ $listing->harvest_date ? $listing->harvest_date->format('M d, Y') : '—' }}</span>
                                </div>
                                @if($listing->notes)
                                    <div class="mt-2.5 p-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 rounded-xl">
                                        <p class="text-[11px] text-slate-500 dark:text-slate-450 leading-relaxed italic">"{{ Str::limit($listing->notes, 80) }}"</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-700/50">
                            <form action="{{ route('negotiations.start') }}" method="POST">
                                @csrf
                                <input type="hidden" name="harvest_id" value="{{ $listing->id }}">
                                <button type="submit" class="w-full py-2.5 bg-violet-600 hover:bg-violet-700 dark:bg-violet-500 dark:hover:bg-violet-600 text-white font-bold rounded-xl text-xs transition duration-205 shadow-sm shadow-violet-500/10 cursor-pointer">
                                    Initiate Negotiation
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination Links -->
            <div class="mt-8">
                {{ $listings->links() }}
            </div>
        @endif
    </div>

</div>
</x-layout>
