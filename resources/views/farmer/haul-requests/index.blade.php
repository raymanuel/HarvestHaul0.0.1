<x-layout title="My Pickup Requests — HarvestHaul">
    <x-page-header title="My Pickup Requests" :showDate="true">
        @if($cooperative?->coopAdminUser)
            <x-button tag="a" variant="secondary" href="{{ route('messages.show', $cooperative->coopAdminUser) }}">Message Cooperative</x-button>
        @endif
        <x-button tag="a" href="{{ route('farmer.haul-requests.create') }}">Request Pickup</x-button>
    </x-page-header>

    @if(! $cooperative)
        <x-card>
            <x-section-label title="Cooperative Membership" />
            <x-empty-state
                type="first-use"
                title="Join a cooperative first"
                description="Farmers request pickups through their cooperative. Once your cooperative approves your membership, you can submit pickup requests here."
            />
        </x-card>
    @elseif($requests->isEmpty())
        <x-card>
            <x-section-label title="Your Requests" />
            <x-empty-state
                type="first-use"
                title="No pickup requests yet"
                description="When you have a harvest ready, request a pickup so your cooperative can schedule a truck for you."
            />
        </x-card>
    @else
        <x-card>
            <x-section-label title="Your Requests" />
            <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
                @foreach($requests as $request)
                    <li class="py-4 flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-100">
                                {{ $request->crop?->name ?? 'Crop' }}@if($request->cropVariety) · {{ $request->cropVariety->name }}@endif
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                @if($request->estimated_sacks){{ $request->estimated_sacks }} sacks · @endif{{ number_format((float) $request->estimated_weight_kg, 2) }} kg
                                @if($request->packagingType) · {{ $request->packagingType->name }}@endif
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                Pickup: {{ $request->preferred_pickup_date?->format('M d, Y') ?? 'TBD' }}
                                @if($request->pickup_window_start)
                                    · {{ $request->pickup_window_start->format('g:i A') }} – {{ $request->pickup_window_end->format('g:i A') }}
                                @endif
                            </p>
                            @if($request->pickup_location)
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Location: {{ $request->pickup_location }}</p>
                            @endif
                        </div>
                        <div class="flex flex-col items-end gap-2 shrink-0">
                            <x-badge :status="$request->status" dot />
                            @if($request->status === \App\Models\HaulRequest::STATUS_PENDING)
                                <form method="POST" action="{{ route('farmer.haul-requests.cancel', $request) }}">
                                    @csrf
                                    <button
                                        type="button"
                                        onclick="swalConfirm(this.closest('form'), {title:'Cancel Pickup Request', text:'Your cooperative will stop reviewing this request. You can file a new one later.', icon:'warning', confirmText:'Yes, cancel it', cancelText:'Keep request', confirmColor:'#ef4444'})"
                                        class="text-xs font-bold text-[var(--color-error-text)] hover:underline">
                                        Cancel request
                                    </button>
                                </form>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
</x-layout>