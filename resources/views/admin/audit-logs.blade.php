<x-layout>
<div class="w-full max-w-7xl mx-auto">

    <header class="pt-8 mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white heading-font tracking-tight">System Audit Logs</h1>
            </div>
        </div>
    </header>

    <form method="GET" action="{{ route('admin.audit-logs') }}" class="flex flex-wrap items-end gap-3 mb-6">
        <div>
            <label for="action" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Action</label>
            <select name="action" id="action" class="border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white">
                <option value="">All Actions</option>
                @foreach($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ str_replace('_', ' ', $action) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="target_type" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Target Type</label>
            <select name="target_type" id="target_type" class="border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white">
                <option value="">All Targets</option>
                @foreach($targetTypes as $targetType)
                    <option value="{{ $targetType }}" @selected(request('target_type') === $targetType)>{{ str_replace('_', ' ', $targetType) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="from" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">From</label>
            <input type="date" name="from" id="from" value="{{ request('from') }}" class="border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white" />
        </div>
        <div>
            <label for="to" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">To</label>
            <input type="date" name="to" id="to" value="{{ request('to') }}" class="border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white" />
        </div>
        <x-button variant="secondary" size="sm">Filter</x-button>
        @if(request()->hasAny(['action', 'target_type', 'from', 'to']))
            <a href="{{ route('admin.audit-logs') }}" class="text-xs font-bold text-slate-500 dark:text-slate-400 hover:underline pb-2.5">Clear</a>
        @endif
    </form>

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
                    <div class="w-7 h-7 rounded-md bg-slate-100 dark:bg-slate-700 border border-slate-200/50 dark:border-slate-700 flex items-center justify-center text-[10px] font-extrabold text-slate-600 dark:text-slate-300 uppercase">{{ substr($log->admin->name ?? '—', 0, 2) }}</div>
                    <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ $log->admin->name ?? '—' }}</span>
                </div>
            </td>
            <td class="px-4 py-3">
                <span class="text-[10px] font-mono bg-slate-100/80 dark:bg-slate-900/50 text-slate-600 dark:text-slate-300 px-2 py-0.5 rounded-md border border-transparent dark:border-slate-700">
                    {{ str_replace('_', ' ', $log->target_type) }} #{{ $log->target_id }}
                </span>
            </td>
            <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs font-medium max-w-[200px] truncate">{{ $log->notes ?? '—' }}</td>
            <td class="px-4 py-3 text-slate-400 dark:text-slate-600 text-xs font-semibold whitespace-nowrap">{{ $log->created_at->format('M d, Y h:i A') }}</td>
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
