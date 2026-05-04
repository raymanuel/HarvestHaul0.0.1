<x-layout>
<div class="w-full">
    <header class="pt-8 mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Welcome, Admin</h1>
        <p class="text-gray-500">System Orchestrator — Manage users, verifications, and platform activity.</p>
    </header>

    @if (session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 rounded-xl px-5 py-4 text-sm font-medium">
            ✅ {{ session('success') }}
        </div>
    @endif

    {{-- ── PLATFORM OVERVIEW ── --}}
    <p style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:#94a3b8; margin-bottom:1rem;">Platform Overview</p>

    <div class="report-grid" style="margin-top:0;">

        <div class="report-widget">
            <span class="text-3xl mb-3 block">👥</span>
            <h3 class="font-bold text-gray-400 text-xs uppercase tracking-wider">Total Users</h3>
            <p class="text-4xl font-black text-gray-900 mt-1">{{ $totalUsers }}</p>
            <div style="display:flex; gap:1rem; margin-top:0.75rem;">
                <span style="font-size:0.75rem; color:#64748b;">🌾 {{ $totalFarmers }} Farmers</span>
                <span style="font-size:0.75rem; color:#64748b;">🚛 {{ $totalLogistics }} Logistics</span>
                <span style="font-size:0.75rem; color:#64748b;">🧑‍✈️ {{ $totalDrivers }} Drivers</span>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <a href="{{ route('admin.users') }}" class="text-blue-600 font-bold text-sm hover:underline">Manage users →</a>
            </div>
        </div>

        <div class="report-widget">
            <span class="text-3xl mb-3 block">🌾</span>
            <h3 class="font-bold text-gray-400 text-xs uppercase tracking-wider">Active Harvests</h3>
            <p class="text-4xl font-black text-gray-900 mt-1">{{ $activeHarvests }}</p>
            <div style="margin-top:0.75rem;">
                <span style="font-size:0.75rem; color:#64748b;">⏳ {{ $pendingHarvests }} pending listings</span>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <a href="{{ route('admin.harvests') }}" class="text-green-600 font-bold text-sm hover:underline">View all listings →</a>
            </div>
        </div>

        <div class="report-widget" style="{{ ($pendingFarmers > 0 || $pendingLogistics > 0) ? 'border-color:rgba(234,179,8,0.4);' : '' }}">
            <span class="text-3xl mb-3 block">⏳</span>
            <h3 class="font-bold text-gray-400 text-xs uppercase tracking-wider">Pending Verifications</h3>
            <p class="text-4xl font-black text-gray-900 mt-1">{{ $pendingFarmers + $pendingLogistics }}</p>
            <div style="display:flex; gap:1rem; margin-top:0.75rem;">
                <span style="font-size:0.75rem; color:#64748b;">🌾 {{ $pendingFarmers }} Farmers</span>
                <span style="font-size:0.75rem; color:#64748b;">🚛 {{ $pendingLogistics }} Logistics</span>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100" style="display:flex; gap:1rem;">
                <a href="{{ route('admin.farmers') }}" class="text-indigo-600 font-bold text-sm hover:underline">Farmers →</a>
                <a href="{{ route('admin.logistics') }}" class="text-orange-600 font-bold text-sm hover:underline">Logistics →</a>
            </div>
        </div>

    </div>

    {{-- ── PENDING DOCUMENT REVIEWS ── --}}
    @php
        $pendingFarmerDocs = \App\Models\FarmerDocument::where('status', 'pending')->count();
        $pendingLogisticsDocs = \App\Models\LogisticsDocument::where('status', 'pending')->count();
        $totalPendingDocs = $pendingFarmerDocs + $pendingLogisticsDocs;
    @endphp

    @if($totalPendingDocs > 0)
    <div style="margin-top:2rem; background:#fefce8; border:1px solid #fde68a; border-radius:1.25rem; padding:1.25rem 1.5rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
        <div>
            <p style="font-weight:700; color:#854d0e; font-size:0.9rem; margin:0 0 0.25rem;">📋 {{ $totalPendingDocs }} Document{{ $totalPendingDocs > 1 ? 's' : '' }} Awaiting Review</p>
            <p style="font-size:0.8rem; color:#92400e; margin:0;">
                {{ $pendingFarmerDocs }} farmer {{ Str::plural('document', $pendingFarmerDocs) }}
                &nbsp;·&nbsp;
                {{ $pendingLogisticsDocs }} logistics {{ Str::plural('document', $pendingLogisticsDocs) }}
            </p>
        </div>
        <div style="display:flex; gap:0.75rem;">
            @if($pendingFarmerDocs > 0)
                <a href="{{ route('admin.farmer-documents') }}"
                    style="background:#2D8A37; color:white; padding:0.5rem 1rem; border-radius:0.6rem; font-size:0.8rem; font-weight:600; text-decoration:none;">
                    Review Farmer Docs
                </a>
            @endif
            @if($pendingLogisticsDocs > 0)
                <a href="{{ route('admin.logistics-documents') }}"
                    style="background:#0f172a; color:white; padding:0.5rem 1rem; border-radius:0.6rem; font-size:0.8rem; font-weight:600; text-decoration:none;">
                    Review Logistics Docs
                </a>
            @endif
        </div>
    </div>
    @endif

    {{-- ── QUICK ACTIONS ── --}}
    <p style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:#94a3b8; margin-top:2.5rem; margin-bottom:1rem;">Quick Actions</p>

    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(160px, 1fr)); gap:1rem;">

        <a href="{{ route('admin.farmer-documents') }}"
            style="background:white; border:1px solid rgba(0,0,0,0.07); border-radius:1rem; padding:1.25rem; text-decoration:none; transition:all 0.2s; display:block;"
            onmouseover="this.style.borderColor='rgba(45,138,55,0.3)'"
            onmouseout="this.style.borderColor='rgba(0,0,0,0.07)'">
            <span style="font-size:1.5rem; display:block; margin-bottom:0.5rem;">📋</span>
            <p style="font-size:0.8rem; font-weight:700; color:#0f172a; margin:0;">Farmer Documents</p>
            @if($pendingFarmerDocs > 0)
                <p style="font-size:0.72rem; color:#854d0e; margin:0.25rem 0 0;">{{ $pendingFarmerDocs }} pending</p>
            @else
                <p style="font-size:0.72rem; color:#94a3b8; margin:0.25rem 0 0;">All reviewed</p>
            @endif
        </a>

        <a href="{{ route('admin.logistics-documents') }}"
            style="background:white; border:1px solid rgba(0,0,0,0.07); border-radius:1rem; padding:1.25rem; text-decoration:none; transition:all 0.2s; display:block;"
            onmouseover="this.style.borderColor='rgba(45,138,55,0.3)'"
            onmouseout="this.style.borderColor='rgba(0,0,0,0.07)'">
            <span style="font-size:1.5rem; display:block; margin-bottom:0.5rem;">📦</span>
            <p style="font-size:0.8rem; font-weight:700; color:#0f172a; margin:0;">Logistics Documents</p>
            @if($pendingLogisticsDocs > 0)
                <p style="font-size:0.72rem; color:#854d0e; margin:0.25rem 0 0;">{{ $pendingLogisticsDocs }} pending</p>
            @else
                <p style="font-size:0.72rem; color:#94a3b8; margin:0.25rem 0 0;">All reviewed</p>
            @endif
        </a>

        <a href="{{ route('admin.farmers') }}"
            style="background:white; border:1px solid rgba(0,0,0,0.07); border-radius:1rem; padding:1.25rem; text-decoration:none; transition:all 0.2s; display:block;"
            onmouseover="this.style.borderColor='rgba(45,138,55,0.3)'"
            onmouseout="this.style.borderColor='rgba(0,0,0,0.07)'">
            <span style="font-size:1.5rem; display:block; margin-bottom:0.5rem;">🌾</span>
            <p style="font-size:0.8rem; font-weight:700; color:#0f172a; margin:0;">Farmer Verification</p>
            @if($pendingFarmers > 0)
                <p style="font-size:0.72rem; color:#854d0e; margin:0.25rem 0 0;">{{ $pendingFarmers }} pending</p>
            @else
                <p style="font-size:0.72rem; color:#94a3b8; margin:0.25rem 0 0;">All verified</p>
            @endif
        </a>

        <a href="{{ route('admin.logistics') }}"
            style="background:white; border:1px solid rgba(0,0,0,0.07); border-radius:1rem; padding:1.25rem; text-decoration:none; transition:all 0.2s; display:block;"
            onmouseover="this.style.borderColor='rgba(45,138,55,0.3)'"
            onmouseout="this.style.borderColor='rgba(0,0,0,0.07)'">
            <span style="font-size:1.5rem; display:block; margin-bottom:0.5rem;">🚛</span>
            <p style="font-size:0.8rem; font-weight:700; color:#0f172a; margin:0;">Logistics Verification</p>
            @if($pendingLogistics > 0)
                <p style="font-size:0.72rem; color:#854d0e; margin:0.25rem 0 0;">{{ $pendingLogistics }} pending</p>
            @else
                <p style="font-size:0.72rem; color:#94a3b8; margin:0.25rem 0 0;">All verified</p>
            @endif
        </a>

        <a href="{{ route('admin.harvests') }}"
            style="background:white; border:1px solid rgba(0,0,0,0.07); border-radius:1rem; padding:1.25rem; text-decoration:none; transition:all 0.2s; display:block;"
            onmouseover="this.style.borderColor='rgba(45,138,55,0.3)'"
            onmouseout="this.style.borderColor='rgba(0,0,0,0.07)'">
            <span style="font-size:1.5rem; display:block; margin-bottom:0.5rem;">📦</span>
            <p style="font-size:0.8rem; font-weight:700; color:#0f172a; margin:0;">Harvest Listings</p>
            <p style="font-size:0.72rem; color:#94a3b8; margin:0.25rem 0 0;">{{ $activeHarvests }} active</p>
        </a>

        <a href="{{ route('admin.crops.index') }}"
            style="background:white; border:1px solid rgba(0,0,0,0.07); border-radius:1rem; padding:1.25rem; text-decoration:none; transition:all 0.2s; display:block;"
            onmouseover="this.style.borderColor='rgba(45,138,55,0.3)'"
            onmouseout="this.style.borderColor='rgba(0,0,0,0.07)'">
            <span style="font-size:1.5rem; display:block; margin-bottom:0.5rem;">🌱</span>
            <p style="font-size:0.8rem; font-weight:700; color:#0f172a; margin:0;">Crop Registry</p>
            <p style="font-size:0.72rem; color:#94a3b8; margin:0.25rem 0 0;">Manage crops</p>
        </a>

        <a href="{{ route('admin.drivers') }}"
            style="background:white; border:1px solid rgba(0,0,0,0.07); border-radius:1rem; padding:1.25rem; text-decoration:none; transition:all 0.2s; display:block;"
            onmouseover="this.style.borderColor='rgba(45,138,55,0.3)'"
            onmouseout="this.style.borderColor='rgba(0,0,0,0.07)'">
            <span style="font-size:1.5rem; display:block; margin-bottom:0.5rem;">🧑‍✈️</span>
            <p style="font-size:0.8rem; font-weight:700; color:#0f172a; margin:0;">Driver Accounts</p>
            <p style="font-size:0.72rem; color:#94a3b8; margin:0.25rem 0 0;">{{ $totalDrivers }} registered</p>
        </a>

        <a href="{{ route('admin.audit-logs') }}"
            style="background:white; border:1px solid rgba(0,0,0,0.07); border-radius:1rem; padding:1.25rem; text-decoration:none; transition:all 0.2s; display:block;"
            onmouseover="this.style.borderColor='rgba(45,138,55,0.3)'"
            onmouseout="this.style.borderColor='rgba(0,0,0,0.07)'">
            <span style="font-size:1.5rem; display:block; margin-bottom:0.5rem;">🔍</span>
            <p style="font-size:0.8rem; font-weight:700; color:#0f172a; margin:0;">Audit Logs</p>
            <p style="font-size:0.72rem; color:#94a3b8; margin:0.25rem 0 0;">Full activity trail</p>
        </a>

    </div>

    {{-- ── RECENT ACTIVITY ── --}}
    <div class="mt-10">
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
                <table class="w-full text-sm text-left" style="min-width:600px;">
                    <thead class="bg-slate-50 text-gray-500 uppercase text-xs tracking-wider">
                        <tr>
                            <th class="px-6 py-3">Action</th>
                            <th class="px-6 py-3">Target</th>
                            <th class="px-6 py-3">Notes</th>
                            <th class="px-6 py-3">By</th>
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
                            <td class="px-6 py-4 text-gray-500">
                                <span class="text-xs font-mono bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                                    {{ str_replace('_', ' ', $log->target_type) }} #{{ $log->target_id }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-xs">{{ $log->notes ?? '—' }}</td>
                            <td class="px-6 py-4 font-semibold text-gray-800">{{ $log->admin->name ?? '—' }}</td>
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
