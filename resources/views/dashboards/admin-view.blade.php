<x-layout>
<div class="w-full">
    <header class="pt-8 mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Welcome, Admin</h1>
        <p class="text-gray-500">System Orchestrator — Manage users, verifications, and audit logs.</p>
    </header>

    @if (session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 rounded-xl px-5 py-4 text-sm font-medium">
            ✅ {{ session('success') }}
        </div>
    @endif

    <div class="report-grid">

        <div class="report-widget">
            <span class="text-4xl mb-3 block">👥</span>
            <h3 class="font-bold text-gray-400 text-sm uppercase tracking-wider">Total Users</h3>
            <p class="text-3xl font-black text-gray-900 mt-2">{{ $totalUsers }}</p>
            <div class="mt-6 pt-4 border-t border-gray-100">
                <a href="{{ route('admin.users') }}" class="text-blue-600 font-bold text-sm hover:underline">Manage users →</a>
            </div>
        </div>

        <div class="report-widget">
            <span class="text-4xl mb-3 block">🌾</span>
            <h3 class="font-bold text-gray-400 text-sm uppercase tracking-wider">Pending Farmers</h3>
            <p class="text-3xl font-black text-gray-900 mt-2">{{ $pendingFarmers }}</p>
            <div class="mt-6 pt-4 border-t border-gray-100">
                <a href="{{ route('admin.farmers') }}" class="text-indigo-600 font-bold text-sm hover:underline">Verify now →</a>
            </div>
        </div>

        <div class="report-widget">
            <span class="text-4xl mb-3 block">🚛</span>
            <h3 class="font-bold text-gray-400 text-sm uppercase tracking-wider">Pending Logistics</h3>
            <p class="text-3xl font-black text-gray-900 mt-2">{{ $pendingLogistics }}</p>
            <div class="mt-6 pt-4 border-t border-gray-100">
                <a href="{{ route('admin.logistics') }}" class="text-orange-600 font-bold text-sm hover:underline">Verify now →</a>
            </div>
        </div>

    </div>

    {{-- Recent Audit Logs --}}
    <div class="mt-12">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-800">Recent Activity</h2>
            <a href="{{ route('admin.audit-logs') }}" class="text-sm text-blue-600 font-bold hover:underline">View all →</a>
        </div>

        @if($recentLogs->isEmpty())
            <div class="bg-slate-50 border border-dashed border-slate-300 rounded-xl p-8 text-center text-gray-400">
                No activity recorded yet.
            </div>
        @else
            <div class="table-responsive">
                <table class="w-full text-sm text-left" style="min-width: 600px;">
                    <thead class="bg-slate-50 text-gray-500 uppercase text-xs tracking-wider">
                        <tr>
                            <th class="px-6 py-3">Action</th>
                            <th class="px-6 py-3">Target User</th>
                            <th class="px-6 py-3">Notes</th>
                            <th class="px-6 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($recentLogs as $log)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <span class="bg-blue-100 text-blue-700 text-xs font-bold px-3 py-1 rounded-full uppercase">
                                    {{ str_replace('_', ' ', $log->action) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-800">{{ $log->target->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $log->notes ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-400 text-xs">{{ $log->created_at->format('M d, Y h:i A') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
</x-layout>
