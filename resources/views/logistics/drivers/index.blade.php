<x-layout title="Manage Drivers">

    <div class="w-full max-w-4xl mx-auto pb-12">

        <header class="pt-8 mb-6 border-b border-slate-200/80 dark:border-slate-700/80 pb-5">
            <a href="{{ route('dashboard') }}" class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 mb-4 inline-block font-semibold transition">
                ← Back to Dashboard
            </a>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-harvest dark:text-harvest bg-harvest/10 dark:bg-harvest/20 px-3 py-1.5 rounded-lg border border-harvest/10 dark:border-harvest/20 inline-block mb-2">Driver Fleet</span>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">Manage Driver Accounts</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium">Create driver credentials and monitor vehicle profiles within your fleet.</p>
                </div>
                <div>
                    <a href="{{ route('logistics.drivers.create') }}" 
                        class="inline-flex items-center gap-2 bg-gradient-to-tr from-[#3A7D44] to-[#2E6336] hover:from-[#3A7D44] hover:to-[#2E6336] text-white text-sm font-bold px-5 py-3 rounded-xl shadow-md shadow-[#3A7D44]/15 dark:shadow-[#3A7D44]/30 hover:shadow-lg transition duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Driver Account
                    </a>
                </div>
            </div>
        </header>

        {{-- Flash Messages --}}
        <x-flash-success />
        <x-flash-error />

        {{-- Drivers List --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6">
            <h2 class="text-base font-bold text-slate-800 dark:text-slate-200 mb-5 heading-font">Registered Drivers</h2>

            @if($drivers->isEmpty())
                <div class="bg-slate-50 dark:bg-slate-900/40 border border-dashed border-slate-300 dark:border-slate-700/80 rounded-xl p-12 text-center">
                    <p class="text-4xl mb-4 font-bold text-slate-300 dark:text-slate-600">—</p>
                    <p class="text-slate-800 dark:text-slate-200 font-bold text-base mb-1 heading-font">No Drivers Registered</p>
                    <p class="text-slate-400 dark:text-slate-500 font-medium text-xs max-w-sm mx-auto">
                        Register a new driver account to allow them to stream geolocation telemetry and coordinate multi-stop shipments.
                    </p>
                    <a href="{{ route('logistics.drivers.create') }}" class="mt-5 inline-block text-xs font-bold text-[#3A7D44] dark:text-[#3A7D44] hover:underline transition">
                        Create first driver account <span>→</span>
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-700/60">
                                <th class="pb-3 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Driver Details</th>
                                <th class="pb-3 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">License Number</th>
                                <th class="pb-3 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Identity</th>
                                <th class="pb-3 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Vehicle Profile</th>
                                <th class="pb-3 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            @foreach($drivers as $driver)
                                @php
                                    $statusColor = match($driver->status) {
                                        'active' => 'text-[#3A7D44] dark:text-[#3A7D44] bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 border-[#3A7D44]/10 dark:border-[#3A7D44]/20',
                                        'suspended' => 'text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/20 border-amber-500/10 dark:border-amber-500/20',
                                        'resigned' => 'text-slate-650 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700',
                                        default => 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700',
                                    };
                                @endphp
                                <tr>
                                    <td class="py-4 pr-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-harvest/10 dark:bg-harvest/20 border border-harvest/20 dark:border-harvest/20 flex items-center justify-center text-harvest dark:text-harvest font-extrabold uppercase text-sm select-none">
                                                {{ substr($driver->user->name, 0, 2) }}
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ $driver->user->name }}</p>
                                                <p class="text-xs text-slate-400 dark:text-slate-500 font-medium mt-0.5">{{ $driver->user->email }}</p>
                                                <p class="text-[10px] text-slate-400 dark:text-slate-400 font-mono mt-0.5">{{ $driver->phone }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-3 font-mono text-sm font-semibold text-slate-700 dark:text-slate-300">
                                        {{ $driver->license_number }}
                                    </td>
                                    <td class="py-4 px-3">
                                        @if($driver->identity_verified)
                                            <span class="bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 text-[#3A7D44] dark:text-[#3A7D44] text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide">Verified</span>
                                        @elseif($driver->id_photo_path)
                                            <span class="bg-amber-50 dark:bg-amber-950/20 text-amber-600 dark:text-amber-400 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide">Pending</span>
                                        @else
                                            <span class="bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide">—</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-3">
                                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            {{ $driver->vehicle_type ?: 'Not Assigned' }}
                                        </span>
                                    </td>
                                    <td class="py-4 pl-3">
                                        <span class="text-[10px] font-bold uppercase tracking-wider {{ $statusColor }} border px-2.5 py-1 rounded-lg">
                                            {{ $driver->status }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>

</x-layout>
