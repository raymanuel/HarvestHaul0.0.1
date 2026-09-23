<x-layout title="Drivers — Cooperative">
    <x-page-header title="Drivers" :showDate="true">
        <a href="{{ route('coop.drivers.create') }}" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-900 text-white dark:bg-white dark:text-slate-900 hover:opacity-90 transition">Add Driver</a>
    </x-page-header>

    <x-card>
        <x-section-label title="Delivery Personnel" />

        @if($drivers->isEmpty())
            <x-empty-state
                type="first-use"
                title="No drivers yet"
                description="Add a driver to assign them to trips and trucks."
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Driver</th>
                            <th class="px-4 py-3 hidden md:table-cell">Phone</th>
                            <th class="px-4 py-3 hidden md:table-cell">Employment Status</th>
                            <th class="px-4 py-3">Added</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($drivers as $driver)
                            <tr class="align-top">
                                <td class="px-4 py-3">
                                    <span class="font-semibold text-slate-900 dark:text-white">{{ $driver->name }}</span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $driver->email }}</span>
                                </td>
                                <td class="px-4 py-3 hidden md:table-cell text-slate-600 dark:text-slate-300">{{ $driver->phone ?? '—' }}</td>
                                <td class="px-4 py-3 hidden md:table-cell text-slate-600 dark:text-slate-300 capitalize">{{ $driver->driverProfile?->employment_status ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $driver->created_at?->diffForHumans() ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-layout>
