<x-layout title="Scheduling Calendar — Cooperative">
    <x-page-header title="Scheduling Calendar" :showDate="true">
        <a href="{{ route('coop.pickups.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold border-2 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:border-brand-700/40 transition">Back to Trips</a>
    </x-page-header>

    <div class="flex items-center gap-2 mb-6">
        @foreach(['list' => 'List', 'day' => 'Day', 'week' => 'Week'] as $mode => $label)
            <a href="{{ route('coop.pickups.calendar', ['view' => $mode, 'date' => $anchor->toDateString()]) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition
                      {{ $view === $mode ? 'bg-brand-700 text-white dark:bg-[#D7BC7A] dark:text-[#17202B]' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                {{ $label }}
            </a>
        @endforeach

        @if($view !== 'list')
            <form method="GET" action="{{ route('coop.pickups.calendar') }}" class="ml-4">
                <input type="hidden" name="view" value="{{ $view }}">
                <input type="date" name="date" value="{{ $anchor->toDateString() }}" onchange="this.form.submit()"
                    class="border border-slate-200 dark:border-slate-600 rounded-lg px-3 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-800 dark:text-white">
            </form>
        @endif
    </div>

    @php
        $dates = collect();
        $cursor = $rangeStart->copy();
        while ($cursor->lte($rangeEnd)) {
            $dates->push($cursor->toDateString());
            $cursor->addDay();
        }
    @endphp

    <div class="space-y-6">
        @forelse($dates as $dateStr)
            @php
                $dayRequests = $requests->get($dateStr, collect());
                $dayTrips = $trips->get($dateStr, collect());
            @endphp
            @if($dayRequests->isNotEmpty() || $dayTrips->isNotEmpty() || $view !== 'list')
                <x-card>
                    <div class="flex items-center justify-between flex-wrap gap-3 mb-3">
                        <x-section-label :title="\Carbon\Carbon::parse($dateStr)->format('l, M d, Y')" width="w-32" />
                        <a href="{{ route('coop.pickups.create', ['date' => $dateStr]) }}" class="text-xs font-bold text-brand-700 dark:text-gold-light hover:underline">Plan trip for this date →</a>
                    </div>

                    @if($dayRequests->isEmpty() && $dayTrips->isEmpty())
                        <p class="text-sm text-slate-400">Nothing scheduled.</p>
                    @endif

                    @if($dayRequests->isNotEmpty())
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400 mb-2">{{ $dayRequests->count() }} approved request(s) waiting to be planned</p>
                        <ul class="text-sm space-y-1 mb-3">
                            @foreach($dayRequests as $req)
                                <li class="text-slate-700 dark:text-slate-300">{{ $req->farmer?->name ?? 'Farmer' }} — {{ $req->crop?->name ?? 'Crop' }} ({{ number_format((float) $req->estimated_weight_kg, 2) }} kg)</li>
                            @endforeach
                        </ul>
                    @endif

                    @if($dayTrips->isNotEmpty())
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400 mb-2">{{ $dayTrips->count() }} trip(s) scheduled</p>
                        <ul class="text-sm space-y-1">
                            @foreach($dayTrips as $trip)
                                <li>
                                    <a href="{{ route('coop.pickups.show', $trip) }}" class="text-brand-700 dark:text-gold-light hover:underline">Trip #{{ $trip->id }}</a>
                                    — {{ $trip->truck?->plate_number ?? 'No truck' }}, {{ $trip->deliveryPersonnel?->name ?? 'No driver' }}
                                    <x-badge :status="$trip->status" />
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            @endif
        @empty
            <x-empty-state type="first-use" title="Nothing in this range" />
        @endforelse
    </div>
</x-layout>
