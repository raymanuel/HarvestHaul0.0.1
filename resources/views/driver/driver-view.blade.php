<x-driver-layout title="HarvestHaul — Driver Portal">

    <!-- Top Header Panel -->
    <header class="sticky top-0 z-35 bg-white/80 dark:bg-slate-950/80 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800/60 px-4 py-4 transition-colors duration-300">
        <div class="flex items-center justify-between max-w-lg mx-auto">
            <div class="flex items-center gap-3">
                <div>
                    <p class="text-[9px] text-[#3A7D44] dark:text-[#3A7D44] font-bold uppercase tracking-widest leading-none">Driver Portal</p>
                    <h1 class="text-base font-extrabold heading-font text-slate-800 dark:text-white mt-1.5 leading-none">{{ Auth::user()->name }}</h1>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <!-- Notifications Dropdown -->
                <x-notification-dropdown />

                <!-- Theme Toggle Button -->
                <button onclick="toggleDarkMode()" class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/50 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white flex items-center justify-center transition-all duration-200 active:scale-[0.97]" title="Toggle Theme">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                <form method="POST" action="{{ route('logout') }}" id="driver-logout-form">
                    @csrf
                    <button type="button" onclick="swalConfirm(document.getElementById('driver-logout-form'), {title:'Sign Out', text:'Are you sure you want to sign out?', icon:'question', confirmText:'Yes, sign out', cancelText:'Cancel', confirmColor:'#ef4444'})" class="cursor-pointer flex items-center gap-1.5 text-xs font-bold text-slate-600 dark:text-slate-350 hover:text-slate-800 dark:hover:text-white bg-slate-100 dark:bg-slate-800/80 hover:bg-slate-200 dark:hover:bg-slate-700/80 border border-slate-200 dark:border-slate-700/50 rounded-xl px-3.5 py-2 transition-all duration-200 active:scale-[0.97]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Exit</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 py-6 relative">

        <!-- Summary Metrics Cards -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="glass-card rounded-3xl p-5 flex items-center gap-3.5 relative overflow-hidden group hover:border-[#3A7D44]/30 transition-all duration-300 shadow-sm">
                <div class="absolute -right-3 -bottom-3 w-16 h-16 bg-[#3A7D44]/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                <div class="w-10 h-10 rounded-xl bg-[#3A7D44]/10 border border-[#3A7D44]/20 flex items-center justify-center text-[#3A7D44] dark:text-[#3A7D44] shrink-0">
                    <x-icon name="map" class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider leading-none">Active Runs</p>
                    <p class="text-2xl font-black text-[#3A7D44] dark:text-[#3A7D44] heading-font mt-1.5 leading-none">{{ $jobs->count() }}</p>
                </div>
            </div>

            <div class="glass-card rounded-3xl p-5 flex items-center gap-3.5 relative overflow-hidden group hover:border-[#2E6336]/20 transition-all duration-300 shadow-sm">
                <div class="absolute -right-3 -bottom-3 w-16 h-16 bg-[#2E6336]/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                <div class="w-10 h-10 rounded-xl bg-[#2E6336]/10 border border-[#2E6336]/20 flex items-center justify-center text-[#2E6336] dark:text-[#2E6336] shrink-0">
                    <x-icon name="check" class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider leading-none">Completed</p>
                    <p class="text-2xl font-black text-[#2E6336] dark:text-[#2E6336] heading-font mt-1.5 leading-none">{{ $completedJobs }}</p>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        <x-flash-success />
        <x-flash-error />

        <!-- Section Label -->
        <div class="flex items-center justify-between mb-4 px-1">
            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Active Runs Assigned</p>
        </div>

        <!-- Job Cards -->
        @forelse($jobs as $job)
            <div class="glass-card rounded-3xl mb-6 overflow-hidden shadow-md border border-slate-200/60 dark:border-slate-800/80 hover:border-slate-350 dark:hover:border-slate-700/80 transition-all duration-300">

                <!-- Card Header -->
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-900/40 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-black text-slate-800 dark:text-white heading-font">Job ID #{{ $job->id }}</p>
                        <p class="text-[10px] text-slate-450 mt-1">
                            Active Delivery Run
                        </p>
                    </div>
                    @php
                        $badge = match($job->status) {
                            'confirmed'   => ['bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20', 'Ready'],
                            'in_progress' => ['bg-[#1F4D25]/10 text-[#1F4D25] dark:text-[#1F4D25] border-[#1F4D25]/20', 'In Transit'],
                            default       => ['bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-500/20', ucfirst($job->status)],
                        };
                    @endphp
                    <span class="text-[9px] font-extrabold uppercase tracking-widest px-2.5 py-1 rounded-lg border {{ $badge[0] }} shadow-sm">
                        {{ $badge[1] }}
                    </span>
                </div>

                <!-- Delivery Details -->
                <div class="grid grid-cols-2 divide-x divide-slate-100 dark:divide-slate-800/40 border-b border-slate-100 dark:border-slate-800/50 bg-slate-50/20 dark:bg-slate-900/10">
                    <div class="px-5 py-4 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-brand/10 border border-brand/20 flex items-center justify-center text-brand dark:text-brand-light shrink-0">
                            <x-icon name="pin" class="w-4 h-4" />
                        </div>
                        <div>
                            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider leading-none">Stops</p>
                            <p class="text-xs font-extrabold text-slate-800 dark:text-white mt-1 heading-font">{{ $job->farm_count }} {{ Str::plural('Farm Stop', $job->farm_count) }}</p>
                        </div>
                    </div>

                    <div class="px-5 py-4 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-harvest/10 border border-harvest/20 flex items-center justify-center text-harvest dark:text-harvest shrink-0">
                            <x-icon name="gauge" class="w-4 h-4" />
                        </div>
                        <div>
                            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider leading-none">Payload</p>
                            <p class="text-xs font-extrabold text-slate-800 dark:text-white mt-1 heading-font">{{ number_format($job->total_kg, 1) }} kg</p>
                        </div>
                    </div>
                </div>

                <!-- Truck Row -->
                <div class="px-5 py-3 text-xs border-b border-slate-100 dark:border-slate-800/50 bg-slate-50/10 dark:bg-slate-900/5 flex items-center justify-between">
                    <div class="flex items-center gap-2 text-slate-400 font-semibold text-[10px] uppercase tracking-wide">
                        <x-icon name="pin" class="w-3.5 h-3.5 text-[#3A7D44] dark:text-[#3A7D44]" />
                        <span>Assigned Truck</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-[9px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-[#3A7D44] border border-slate-200 dark:border-slate-700/60 rounded px-2.5 py-0.5">
                            {{ $job->truck->plate_number ?? '—' }}
                        </span>
                        @if($job->truck->vehicle_type ?? false)
                            <span class="text-slate-300 dark:text-slate-650">&middot;</span>
                            <span class="text-slate-500 dark:text-slate-400 text-[9px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800/60 px-2 py-0.5 rounded border border-slate-200/50 dark:border-slate-700/30">{{ $job->truck->vehicle_type }}</span>
                        @endif
                    </div>
                </div>

                <!-- Notes Preview -->
                @if($job->notes)
                    <div class="px-5 py-4 bg-amber-500/5 border-b border-slate-100 dark:border-slate-800/50">
                        <p class="text-[9px] text-amber-600 dark:text-amber-500 font-bold uppercase tracking-widest">Dispatcher Instructions</p>
                        <p class="text-xs text-amber-900/80 dark:text-amber-200/80 italic leading-relaxed mt-1 bg-amber-50/50 dark:bg-slate-950/40 p-3 rounded-xl border border-amber-200 dark:border-amber-500/10">
                            "{{ Str::limit($job->notes, 90) }}"
                        </p>
                    </div>
                @endif

                <!-- Action Buttons -->
                <div class="flex gap-3 px-5 py-4 bg-slate-50/50 dark:bg-slate-900/40 border-t border-slate-100 dark:border-slate-800/10">
                    <a href="{{ route('driver.jobs.show', $job) }}"
                       class="flex-1 text-center text-xs font-bold text-slate-650 dark:text-slate-350 hover:text-slate-800 dark:hover:text-white bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700/80 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl py-3.5 transition duration-200 shadow-sm active:scale-[0.98]">
                        View Details
                    </a>
                    @if($job->status === 'confirmed')
                        <form method="POST" action="{{ route('driver.jobs.status', $job) }}" class="flex-1">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="w-full text-xs font-extrabold text-white bg-gradient-to-r from-[#3A7D44] to-[#2E6336] hover:from-[#3A7D44] hover:to-[#2E6336] rounded-2xl py-3.5 shadow-lg shadow-[#3A7D44]/15 hover:shadow-[#3A7D44]/25 transition-all duration-200 active:scale-[0.98] flex items-center justify-center gap-1.5">
                                <span>Start Run</span>
                            </button>
                        </form>
                    @endif
                </div>

            </div>
        @empty
            <div class="text-center py-16 px-6 glass-card rounded-3xl shadow-xl relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-b from-slate-200/10 dark:from-slate-950/10 to-transparent pointer-events-none"></div>
                <div class="relative w-24 h-24 mx-auto mb-6 flex items-center justify-center">
                    <div class="w-14 h-14 rounded-full bg-[#3A7D44]/10 border border-[#3A7D44]/20 flex items-center justify-center text-[#3A7D44] dark:text-[#3A7D44] shadow-md font-bold text-xl">—</div>
                </div>
                <h3 class="text-base font-extrabold text-slate-850 dark:text-white heading-font tracking-tight">No Active Routes Assigned</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 max-w-xs mx-auto leading-relaxed">
                    No runs assigned yet. You'll be notified when a dispatch is ready.
                </p>
                <div class="mt-6 inline-flex items-center gap-2 bg-[#3A7D44]/10 border border-[#3A7D44]/20 px-3 py-1.5 rounded-full">
                    <span class="w-2 h-2 rounded-full bg-[#3A7D44]/100 animate-pulse"></span>
                    <span class="text-[9px] text-[#3A7D44] dark:text-[#3A7D44] font-bold uppercase tracking-wider">Waiting for dispatch...</span>
                </div>
            </div>
        @endforelse

    </main>

</x-driver-layout>
