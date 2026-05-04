<x-layout title="Driver Accounts">
<div class="w-full">
    <header class="pt-8 mb-8">
        <a href="{{ route('dashboard') }}" class="text-sm text-gray-400 hover:text-gray-600 mb-4 inline-block">← Back to Dashboard</a>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Driver Accounts</h1>
        <p class="text-gray-500">All registered drivers across logistics partners.</p>
    </header>

    @if (session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 rounded-xl px-5 py-4 text-sm font-medium">
            ✅ {{ session('success') }}
        </div>
    @endif

    <div class="table-responsive">
        <table class="w-full text-sm text-left" style="min-width:600px;">
            <thead class="bg-slate-50 text-gray-500 uppercase text-xs tracking-wider">
                <tr>
                    <th class="px-6 py-4">Name</th>
                    <th class="px-6 py-4">Email</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4">Joined</th>
                    <th class="px-6 py-4">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($drivers as $driver)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-6 py-4 font-semibold text-gray-800">{{ $driver->name }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $driver->email }}</td>
                    <td class="px-6 py-4">
                        @if($driver->status === 'active')
                            <span class="bg-green-100 text-green-700 text-xs font-bold px-3 py-1 rounded-full uppercase">Active</span>
                        @else
                            <span class="bg-red-100 text-red-700 text-xs font-bold px-3 py-1 rounded-full uppercase">Inactive</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-400 text-xs">{{ $driver->created_at->format('M d, Y') }}</td>
                    <td class="px-6 py-4">
                        <form method="POST" action="{{ route('admin.users.status', $driver->id) }}">
                            @csrf
                            <button type="submit"
                                onclick="return confirm('{{ $driver->status === 'active' ? 'Archive' : 'Reactivate' }} {{ addslashes($driver->name) }}?')"
                                class="{{ $driver->status === 'active' ? 'text-red-500 hover:text-red-700' : 'text-green-500 hover:text-green-700' }} font-semibold text-xs">
                                {{ $driver->status === 'active' ? 'Archive' : 'Reactivate' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-10 text-center text-gray-400 text-sm">No drivers registered yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-layout>
