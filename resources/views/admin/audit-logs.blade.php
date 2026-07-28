<x-layout>
<div class="w-full max-w-7xl mx-auto">

    <header class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white heading-font tracking-tight">System Audit Logs</h1>
                <p class="text-sm text-slate-400 dark:text-slate-500 mt-1 font-semibold">Full history of admin actions performed on the platform</p>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-900/50 px-3 py-1.5 rounded-lg border border-slate-200/50 dark:border-slate-700 self-start">Activity Log</span>
        </div>
    </header>

    <x-data-table empty-message="No audit logs recorded yet">
        <x-slot:header>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Action</th>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Performed By</th>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Target</th>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Notes</th>
            <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Date & Time</th>
        </x-slot:header>

        @forelse($logs as $log)
        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
            <td class="px-4 py-3">
                <x-badge status="active" :label="str_replace('_', ' ', $log->action)" />
            </td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                    <div class="w-7 h-7 rounded-lg bg-gradient-to-tr from-slate-100 to-slate-50 dark:from-slate-700 dark:to-slate-800 border border-slate-200/50 dark:border-slate-700 flex items-center justify-center text-[9px] font-extrabold text-slate-600 dark:text-slate-300 uppercase">{{ substr($log->admin->name ?? '—', 0, 2) }}</div>
                    <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ $log->admin->name ?? '—' }}</span>
                </div>
            </td>
            <td class="px-4 py-3">
                <span class="text-[10px] font-mono bg-slate-100/80 dark:bg-slate-900/50 text-slate-600 dark:text-slate-300 px-2 py-0.5 rounded-md border border-transparent dark:border-slate-700">
                    {{ str_replace('_', ' ', $log->target_type) }} #{{ $log->target_id }}
                </span>
            </td>
            <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs font-medium max-w-[200px] truncate">{{ $log->notes ?? '—' }}</td>
            <td class="px-4 py-3 text-slate-400 dark:text-slate-550 text-xs font-semibold whitespace-nowrap">{{ $log->created_at->format('M d, Y h:i A') }}</td>
        </tr>
        @empty
        @endforelse
    </x-data-table>

    @if($logs->hasPages())
        <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-700/60 bg-slate-50/30 dark:bg-slate-900/20 mt-0">
            {{ $logs->links() }}
        </div>
    @endif
</div>
</x-layout>
