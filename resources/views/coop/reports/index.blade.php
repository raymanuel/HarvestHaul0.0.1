<x-layout title="Reports — HarvestHaul">
    <x-page-header title="Reports" :showDate="true" />

    <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 max-w-2xl">
        Historical summaries over a date range. Defaults to the current month — adjust the dates on each report page.
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <x-card>
            <x-section-label title="Procurement" width="w-20" />
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Confirmed purchases from farmers — volume and spend.</p>
            <x-button tag="a" href="{{ route('coop.reports.procurement', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">View Report</x-button>
        </x-card>
        <x-card>
            <x-section-label title="Sales" width="w-16" />
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Booked buyer orders — volume and revenue.</p>
            <x-button tag="a" href="{{ route('coop.reports.sales', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">View Report</x-button>
        </x-card>
        <x-card>
            <x-section-label title="Farmer Payouts" width="w-24" />
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Payments recorded to farmers.</p>
            <x-button tag="a" href="{{ route('coop.reports.payouts', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">View Report</x-button>
        </x-card>
    </div>
</x-layout>
