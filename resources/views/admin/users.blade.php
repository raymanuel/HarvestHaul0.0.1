<x-layout>
<div class="w-full">
    <header class="pt-8 mb-8">
        <a href="{{ route('dashboard') }}" class="text-sm text-gray-400 hover:text-gray-600 mb-4 inline-block">← Back to Dashboard</a>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">User Management</h1>
        <p class="text-gray-500">View and manage all registered users.</p>
    </header>

    @if (session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 rounded-xl px-5 py-4 text-sm font-medium">
            ✅ {{ session('success') }}
        </div>
    @endif

    <div class="table-responsive">
        <table class="w-full text-sm text-left" style="min-width: 600px;">
            <thead class="bg-slate-50 text-gray-500 uppercase text-xs tracking-wider">
                <tr>
                    <th class="px-6 py-4">Name</th>
                    <th class="px-6 py-4">Email</th>
                    <th class="px-6 py-4">Role</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4">Joined</th>
                    <th class="px-6 py-4">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($users as $user)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-6 py-4 font-semibold text-gray-800">{{ $user->name }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $user->email }}</td>
                    <td class="px-6 py-4">
                        <span class="bg-slate-100 text-slate-700 text-xs font-bold px-3 py-1 rounded-full uppercase">
                            {{ str_replace('_', ' ', $user->role) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        @if($user->status === 'active')
                            <span class="bg-green-100 text-green-700 text-xs font-bold px-3 py-1 rounded-full uppercase">Active</span>
                        @else
                            <span class="bg-red-100 text-red-700 text-xs font-bold px-3 py-1 rounded-full uppercase">Inactive</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-400 text-xs">{{ $user->created_at->format('M d, Y') }}</td>
                    <td class="px-6 py-4">
                        @if($user->role !== 'admin')
                            @if($user->status === 'active')
                                {{-- Archive button — AJAX for farmers, normal POST for others --}}
                                @if($user->role === 'farmer')
                                    <button type="button"
                                        onclick="checkAndArchive({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                        class="text-red-500 hover:text-red-700 font-semibold text-xs">
                                        Archive
                                    </button>
                                @else
                                    <form method="POST" action="{{ route('admin.users.status', $user->id) }}">
                                        @csrf
                                        <button type="submit"
                                            onclick="return confirm('Archive {{ addslashes($user->name) }}?')"
                                            class="text-red-500 hover:text-red-700 font-semibold text-xs">
                                            Archive
                                        </button>
                                    </form>
                                @endif
                            @else
                                <form method="POST" action="{{ route('admin.users.status', $user->id) }}">
                                    @csrf
                                    <button type="submit"
                                        onclick="return confirm('Reactivate {{ addslashes($user->name) }}?')"
                                        class="text-green-500 hover:text-green-700 font-semibold text-xs">
                                        Reactivate
                                    </button>
                                </form>
                            @endif
                        @else
                            <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Warning Modal --}}
<div id="archiveModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:1.25rem; padding:2rem; max-width:440px; width:90%; box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h2 style="font-size:1.1rem; font-weight:800; color:#0f172a; margin:0 0 0.75rem;">⚠️ Active Listings Detected</h2>
        <p id="modalMessage" style="font-size:0.875rem; color:#475569; margin:0 0 1.25rem; line-height:1.6;"></p>
        <p style="font-size:0.8rem; color:#ef4444; font-weight:600; margin:0 0 1.5rem;">
            Proceeding will cancel all active harvest listings and remove this farmer's pins from the logistics map.
        </p>
        <div style="display:flex; gap:0.75rem; justify-content:flex-end;">
            <button onclick="closeModal()"
                style="padding:0.6rem 1.25rem; border:1px solid #e2e8f0; border-radius:0.75rem; font-size:0.875rem; font-weight:600; color:#475569; background:white; cursor:pointer;">
                Cancel
            </button>
            <form id="forceArchiveForm" method="POST">
                @csrf
                <input type="hidden" name="force" value="1">
                <button type="submit"
                    style="padding:0.6rem 1.25rem; border:none; border-radius:0.75rem; font-size:0.875rem; font-weight:600; color:white; background:#ef4444; cursor:pointer;">
                    Archive Anyway
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function checkAndArchive(userId, userName) {
        fetch(`/admin/users/${userId}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({})
        })
        .then(res => res.json())
        .then(data => {
            if (data.requires_confirmation) {
                const count = data.active_harvest_count;
                document.getElementById('modalMessage').textContent =
                    `${userName} currently has ${count} active harvest listing${count > 1 ? 's' : ''}. Archiving this account will cancel ${count > 1 ? 'all of them' : 'it'}.`;
                document.getElementById('forceArchiveForm').action = `/admin/users/${userId}/status`;
                document.getElementById('archiveModal').style.display = 'flex';
            } else if (data.success) {
                window.location.reload();
            }
        });
    }

    function closeModal() {
        document.getElementById('archiveModal').style.display = 'none';
    }

    // Close on backdrop click
    document.getElementById('archiveModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
</script>
</x-layout>
