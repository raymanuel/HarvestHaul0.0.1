<x-layout>
    <div class="w-full py-8">
        <header class="mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">💬 Proposal Inbox</h1>
            <p class="text-gray-500 text-lg">Manage negotiable delivery options and track farmer consensus channels.</p>
        </header>

        @if($proposals->isEmpty())
            <div class="bg-white border border-gray-200 rounded-xl p-12 text-center">
                <p class="text-gray-400 italic">No active delivery proposals open. Generate route pools from the Dispatch Console to open negotiation rooms.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($proposals as $proposal)
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200 px-2.5 py-1 rounded-full font-mono">Job #{{ $proposal->id }}</span>
                                <span class="text-xs font-medium text-amber-700 bg-amber-50 border border-amber-100 px-2 py-0.5 rounded">Pending Negotiation</span>
                            </div>

                            <h3 class="text-base font-bold text-gray-800 mb-1">🚛 {{ $proposal->truck->truck_name ?? 'Fleet Hauler' }}</h3>
                            <p class="text-xs text-gray-500 font-mono mb-4">💳 Base reference: ₱{{ number_format($proposal->price_reference, 2) }}</p>

                            <div class="border-t border-gray-100 pt-3 space-y-2">
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-wide">Pooled Harvest Manifest</p>
                                <ul class="text-xs text-gray-600 space-y-1">
                                    @foreach($proposal->harvests as $harvest)
                                        <li class="flex items-center justify-between bg-gray-50 px-2 py-1 rounded">
                                            <span class="font-medium truncate max-w-[150px]">{{ $harvest->farmer->name ?? 'Farmer' }}</span>
                                            <span class="font-bold text-green-700 font-mono">{{ number_format($harvest->pivot->quantity_kg) }} kg</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <button class="w-full mt-5 bg-green-600 text-white text-xs font-bold py-2 rounded-lg hover:bg-green-700 transition">
                            💬 Open Chat Room Threads
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
