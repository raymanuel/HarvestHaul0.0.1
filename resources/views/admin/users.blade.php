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
                            <form method="POST" action="{{ route('admin.users.status', $user->id) }}">
                                @csrf
                                <button type="submit"
                                    onclick="return confirm('Change status of {{ $user->name }}?')"
                                    class="{{ $user->status === 'active' ? 'text-red-500 hover:text-red-700' : 'text-green-500 hover:text-green-700' }} font-semibold text-xs">
                                    {{ $user->status === 'active' ? 'Archive' : 'Reactivate' }}
                                </button>
                            </form>
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
</x-layout>
