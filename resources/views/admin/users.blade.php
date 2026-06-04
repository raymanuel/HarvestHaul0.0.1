<x-layout>
<div class="w-full max-w-7xl mx-auto">

    <!-- Nice Admin Page Header -->
    <header class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white heading-font tracking-tight">User Management</h1>
                <p class="text-sm text-slate-400 dark:text-slate-500 mt-1 font-semibold">View and manage all registered platform accounts</p>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-widest text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 px-3 py-1.5 rounded-lg border border-emerald-500/10 dark:border-emerald-500/20 self-start">{{ $users->count() }} Total Users</span>
        </div>
    </header>

    @if (session('success'))
        <div class="mb-6 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 rounded-xl px-5 py-4 text-sm font-semibold flex items-center gap-2">
            <span>✅</span> {{ session('success') }}
        </div>
    @endif

    <!-- Nice Admin Card Table -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left" style="min-width: 700px;">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/40">
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Name</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Email</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Role</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Joined</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/40">
                    @foreach($users as $user)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-slate-100 to-slate-50 dark:from-slate-700 dark:to-slate-800 border border-slate-200 dark:border-slate-750 flex items-center justify-center text-[10px] font-extrabold text-slate-600 dark:text-slate-300 uppercase">{{ substr($user->name, 0, 2) }}</div>
                                <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400 text-xs font-medium">{{ $user->email }}</td>
                        <td class="px-6 py-4">
                            <span class="bg-slate-100 dark:bg-slate-900/50 text-slate-700 dark:text-slate-300 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide">
                                {{ str_replace('_', ' ', $user->role) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($user->status === 'active')
                                <span class="bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide">Active</span>
                            @else
                                <span class="bg-red-50 dark:bg-red-950/20 text-red-600 dark:text-red-400 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-slate-400 dark:text-slate-500 text-xs font-semibold">{{ $user->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4">
                            @if($user->role !== 'admin')
                                @if($user->status === 'active')
                                    @if($user->role === 'farmer')
                                        <button type="button"
                                            onclick="checkAndArchive({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                            class="text-red-500 hover:text-red-700 font-bold text-xs hover:underline transition">
                                            Archive
                                        </button>
                                    @else
                                        <form method="POST" action="{{ route('admin.users.status', $user->id) }}">
                                            @csrf
                                            <button type="submit"
                                                onclick="return confirm('Archive {{ addslashes($user->name) }}?')"
                                                class="text-red-500 hover:text-red-700 font-bold text-xs hover:underline transition">
                                                Archive
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <form method="POST" action="{{ route('admin.users.status', $user->id) }}">
                                        @csrf
                                        <button type="submit"
                                            onclick="return confirm('Reactivate {{ addslashes($user->name) }}?')"
                                            class="text-emerald-600 hover:text-emerald-800 font-bold text-xs hover:underline transition">
                                            Reactivate
                                        </button>
                                    </form>
                                @endif
                            @else
                                <span class="text-slate-300 dark:text-slate-700 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Warning Modal --}}
<div id="archiveModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md p-7 border border-slate-100 dark:border-slate-700">
        <h2 class="text-lg font-extrabold text-slate-800 dark:text-white heading-font mb-3 flex items-center gap-2"><span>⚠️</span> Active Listings Detected</h2>
        <p id="modalMessage" class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed mb-3"></p>
        <p class="text-xs text-red-500 dark:text-red-400 font-bold mb-6">
            Proceeding will cancel all active harvest listings and remove this farmer's pins from the logistics map.
        </p>
        <div class="flex gap-3 justify-end">
            <button onclick="closeModal()"
                class="px-5 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                Cancel
            </button>
            <form id="forceArchiveForm" method="POST">
                @csrf
                <input type="hidden" name="force" value="1">
                <button type="submit"
                    class="px-5 py-2.5 bg-red-600 text-white rounded-xl text-sm font-bold hover:bg-red-700 shadow transition">
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
                document.getElementById('archiveModal').classList.remove('hidden');
            } else if (data.success) {
                window.location.reload();
            }
        });
    }

    function closeModal() {
        document.getElementById('archiveModal').classList.add('hidden');
    }

    document.getElementById('archiveModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
</script>
</x-layout>
