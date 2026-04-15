<x-layout>
<div class="w-full">
    <header class="pt-8 mb-8">
        <a href="{{ route('dashboard') }}" class="text-sm text-gray-400 hover:text-gray-600 mb-4 inline-block">← Back to Dashboard</a>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Farmer Verification</h1>
        <p class="text-gray-500">Approve or reject farmer accounts.</p>
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
                    <th class="px-6 py-4">Farm Location</th>
                    <th class="px-6 py-4">Verified</th>
                    <th class="px-6 py-4">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($farmers as $farmer)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-6 py-4 font-semibold text-gray-800">{{ $farmer->name }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $farmer->email }}</td>
                    <td class="px-6 py-4 text-gray-500">{{ $farmer->farmerProfile->farm_location ?? '—' }}</td>
                    <td class="px-6 py-4">
                        @if($farmer->farmerProfile?->is_verified)
                            <span class="bg-green-100 text-green-700 text-xs font-bold px-3 py-1 rounded-full uppercase">Verified</span>
                        @else
                            <span class="bg-yellow-100 text-yellow-700 text-xs font-bold px-3 py-1 rounded-full uppercase">Pending</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 flex gap-2">
                        @if(!$farmer->farmerProfile?->is_verified)
                            <form method="POST" action="{{ route('admin.farmers.verify', $farmer->id) }}">
                                @csrf
                                <button type="submit" class="text-green-600 hover:text-green-800 font-semibold text-xs">Approve</button>
                            </form>
                        @endif
                        @if($farmer->farmerProfile?->is_verified)
                            <form method="POST" action="{{ route('admin.farmers.reject', $farmer->id) }}">
                                @csrf
                                <button type="submit"
                                    onclick="return confirm('Reject {{ $farmer->name }}?')"
                                    class="text-red-500 hover:text-red-700 font-semibold text-xs">Reject</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
</x-layout>
