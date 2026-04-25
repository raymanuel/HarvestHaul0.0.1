<x-layout>
<div class="w-full">
    <header class="pt-8 mb-8">
        <a href="{{ route('dashboard') }}" class="text-sm text-gray-400 hover:text-gray-600 mb-4 inline-block">← Back to Dashboard</a>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">System Audit Logs</h1>
        <p class="text-gray-500">Full history of all admin actions performed on the platform.</p>
    </header>

    <div class="table-responsive">
        <table class="w-full text-sm text-left" style="min-width: 600px;">
            <thead class="bg-slate-50 text-gray-500 uppercase text-xs tracking-wider">
                <tr>
                    <th class="px-6 py-4">Action</th>
                    <th class="px-6 py-4">Performed By</th>
                    <th class="px-6 py-4">Target</th>
                    <th class="px-6 py-4">Notes</th>
                    <th class="px-6 py-4">Date & Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-6 py-4">
                        <span class="bg-blue-100 text-blue-700 text-xs font-bold px-3 py-1 rounded-full uppercase">
                            {{ str_replace('_', ' ', $log->action) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 font-semibold text-gray-800">{{ $log->admin->name ?? '—' }}</td>
                    <td class="px-6 py-4">
                        <span class="text-xs font-mono bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                            {{ str_replace('_', ' ', $log->target_type) }} #{{ $log->target_id }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-500">{{ $log->notes ?? '—' }}</td>
                    <td class="px-6 py-4 text-gray-400 text-xs">{{ $log->created_at->format('M d, Y h:i A') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">No audit logs recorded yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($logs->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
</x-layout>
