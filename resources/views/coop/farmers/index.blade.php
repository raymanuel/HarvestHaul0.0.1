<x-layout title="Farmer Membership — Cooperative">
    <x-page-header title="Farmer Membership" :showDate="true">
        <a href="{{ route('coop.farmers.create') }}" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-900 text-white dark:bg-white dark:text-slate-900 hover:opacity-90 transition">Add Farmer</a>
    </x-page-header>

    @if($pendingFarmers->isNotEmpty())
        <div class="relative mb-8">
            <div class="relative overflow-hidden rounded-2xl border border-amber-500/40 dark:border-amber-400/30 bg-amber-50 dark:bg-amber-900/20">
                <div class="relative z-10 px-6 py-9 sm:px-8 text-center">
                    <p class="badge-inline">Pending decisions</p>
                    <h3 class="mt-3 text-2xl font-bold text-slate-900 dark:text-white">{{ $pendingFarmers->count() }}</h3>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300 max-w-md mx-auto">
                        Farmers requested membership into your cooperative. Approve to let them submit haul requests, or reject with a reason.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <x-card>
        <x-section-label title="Awaiting Approval" />

        @if($pendingFarmers->isEmpty())
            <x-empty-state
                type="farmers"
                title="No pending requests"
                description="When a farmer requests membership into your cooperative, they'll appear here for you to approve or reject."
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Farmer</th>
                            <th class="px-4 py-3 hidden md:table-cell">Phone</th>
                            <th class="px-4 py-3 hidden lg:table-cell">Location</th>
                            <th class="px-4 py-3">Requested</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($pendingFarmers as $farmer)
                            <tr class="align-top">
                                <td class="px-4 py-3">
                                    <span class="font-semibold text-slate-900 dark:text-white">{{ $farmer->user?->name ?? 'Unnamed farmer' }}</span>
                                </td>
                                <td class="px-4 py-3 hidden md:table-cell text-slate-600 dark:text-slate-300">{{ $farmer->user?->phone ?? '—' }}</td>
                                <td class="px-4 py-3 hidden lg:table-cell text-slate-600 dark:text-slate-300">{{ $farmer->farm_location ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">
                                    {{ $farmer->membership_requested_at?->diffForHumans() ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <form method="POST" action="{{ route('coop.farmers.approve', $farmer->user) }}">
                                            @csrf
                                            <button class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-900 text-white dark:bg-white dark:text-slate-900 hover:opacity-90 transition">Approve</button>
                                        </form>

                                        <x-modal triggerLabel="Reject">
                                            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Reject {{ $farmer->user?->name }}'s membership</h2>
                                            <form method="POST" action="{{ route('coop.farmers.reject', $farmer->user) }}" class="space-y-4">
                                                @csrf
                                                <div>
                                                    <label for="reason" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Reason</label>
                                                    <textarea name="reason" id="reason" rows="3" required maxlength="500" class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-slate-900 dark:focus:border-white focus:ring-0" placeholder="Tell the farmer why their request was declined."></textarea>
                                                    @error('reason')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                                                </div>
                                                <div class="pt-1 flex justify-end gap-2">
                                                    <button type="button" data-modal-close class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition">Cancel</button>
                                                    <button class="px-4 py-2 rounded-lg text-sm font-bold text-white bg-red-600 hover:bg-red-700 transition">Reject</button>
                                                </div>
                                            </form>
                                        </x-modal>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <x-card class="mt-8">
        <x-section-label title="Active Members" />

        @if($approvedFarmers->isEmpty())
            <x-empty-state
                type="farmers"
                title="No members yet"
                description="Approved farmers appear here once they're part of your cooperative."
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Farmer</th>
                            <th class="px-4 py-3 hidden md:table-cell">Phone</th>
                            <th class="px-4 py-3">Approved</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($approvedFarmers as $farmer)
                            <tr class="align-top">
                                <td class="px-4 py-3">
                                    <span class="font-semibold text-slate-900 dark:text-white">{{ $farmer->user?->name ?? 'Unnamed farmer' }}</span>
                                </td>
                                <td class="px-4 py-3 hidden md:table-cell text-slate-600 dark:text-slate-300">{{ $farmer->user?->phone ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">
                                    {{ $farmer->membership_decided_at?->diffForHumans() ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('coop.farmers.show', $farmer->user) }}" class="px-3 py-1.5 rounded-lg text-xs font-bold border-2 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:border-brand-700/40 transition">View</a>
                                        <x-modal triggerLabel="Remove">
                                            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Remove {{ $farmer->user?->name }}?</h2>
                                            <p class="text-sm text-slate-600 dark:text-slate-300 mb-4">Removing ends their cooperative membership. They can request to rejoin at any time.</p>
                                            <form method="POST" action="{{ route('coop.farmers.remove', $farmer->user) }}" class="flex justify-end gap-2">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" data-modal-close class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition">Cancel</button>
                                                <button class="px-4 py-2 rounded-lg text-sm font-bold text-white bg-red-600 hover:bg-red-700 transition">Remove</button>
                                            </form>
                                        </x-modal>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-layout>
