<x-layout title="Field & Receiving Staff — Cooperative">
    <x-page-header title="Field & Receiving Staff" :showDate="true">
        <a href="{{ route('coop.staff.create') }}" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-900 text-white dark:bg-white dark:text-slate-900 hover:opacity-90 transition">Add Staff</a>
    </x-page-header>

    <x-card>
        <x-section-label title="Field / Receiving Personnel" />

        @if($staff->isEmpty())
            <x-empty-state
                type="first-use"
                title="No field staff yet"
                description="Add a field/receiving staff account so they can record weight, grade, and pricing at pickup."
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Staff</th>
                            <th class="px-4 py-3 hidden md:table-cell">Phone</th>
                            <th class="px-4 py-3">Added</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($staff as $member)
                            <tr class="align-top">
                                <td class="px-4 py-3">
                                    <span class="font-semibold text-slate-900 dark:text-white">{{ $member->name }}</span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $member->email }}</span>
                                </td>
                                <td class="px-4 py-3 hidden md:table-cell text-slate-600 dark:text-slate-300">{{ $member->phone ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $member->created_at?->diffForHumans() ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-layout>