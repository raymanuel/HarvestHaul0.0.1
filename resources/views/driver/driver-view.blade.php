<x-driver-layout title="HarvestHaul — Driver Portal">

    <header class="sticky top-0 z-30 bg-white/80 dark:bg-slate-950/80 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800/60 px-4 py-4 transition-colors duration-300">
        <div class="flex items-center justify-between max-w-lg mx-auto">
            <div class="flex items-center gap-3">
                <div>
                    <h1 class="text-base font-bold heading-font text-slate-800 dark:text-white mt-1.5 leading-none">{{ Auth::user()->name }}</h1>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <x-notification-dropdown />

                <button onclick="toggleDarkMode()" class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700/50 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white flex items-center justify-center transition-all duration-200" aria-label="Toggle dark mode">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                <form method="POST" action="{{ route('logout') }}" id="driver-logout-form">
                    @csrf
                    <button type="button" onclick="swalConfirm(document.getElementById('driver-logout-form'), {title:'Sign Out', text:'Are you sure you want to sign out?', icon:'question', confirmText:'Yes, sign out', cancelText:'Cancel', confirmColor:'#ef4444'})" class="cursor-pointer flex items-center gap-1.5 text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700/80 border border-slate-200 dark:border-slate-700/50 rounded-xl px-3.5 py-2 transition-all duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Exit</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 py-6 relative">

        <div class="grid grid-cols-2 gap-4 mb-6">
            <x-stat-card
                title="Active Runs"
                :value="$jobs->count()"
                :href="route('driver.dashboard')"
                linkText="View Runs"
            />

            <x-stat-card
                title="Completed"
                :value="$completedToday"
                unit="total runs"
            />
        </div>

        <div class="mb-6">
            <x-stat-card
                title="Fuel This Week"
                value="{{ number_format($fuelThisWeekLiters, 1) }}"
                unit="liters"
            >
                <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400">{{ $fuelThisWeekCost > 0 ? '&#8369;' . number_format($fuelThisWeekCost, 2) : 'No logs yet' }}</span>
            </x-stat-card>
        </div>

        <x-flash-success />
        <x-flash-error />

        @forelse($jobs as $job)
            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl mb-6 overflow-hidden shadow-md border border-slate-200/60 dark:border-slate-800/80 hover:border-slate-400 dark:hover:border-slate-700/80 transition-all duration-300">

                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-900/40 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-black text-slate-800 dark:text-white heading-font">Job ID #{{ $job->id }}</p>
                    </div>
                    @php
                        $badge = match($job->status->value) {
                            'confirmed'   => ['bg-warning-bg text-warning-text border-warning-border', 'Ready'],
                            'in_progress' => ['bg-brand-dark/10 text-brand-dark dark:text-text-dark border-brand-dark/20', 'In Transit'],
                            default       => ['bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-500/20', $job->status->label()],
                        };
                    @endphp
                    <span class="text-[10px] font-extrabold uppercase tracking-widest px-2.5 py-1 rounded-md border {{ $badge[0] }} shadow-sm">
                        {{ $badge[1] }}
                    </span>
                </div>

                <div class="grid grid-cols-2 divide-x divide-slate-100 dark:divide-slate-800/40 border-b border-slate-100 dark:border-slate-800/50 bg-slate-50/20 dark:bg-slate-900/10">
                    <div class="px-5 py-4 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-md bg-brand/10 border border-brand/20 flex items-center justify-center text-brand dark:text-brand-light shrink-0">
                            <x-icon name="pin" class="w-4 h-4" />
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider leading-none">Stops</p>
                            @if($job->leg_type === 'outbound')
                                <p class="text-xs font-extrabold text-slate-800 dark:text-white mt-1 heading-font">Customer Delivery</p>
                            @else
                                <p class="text-xs font-extrabold text-slate-800 dark:text-white mt-1 heading-font">{{ $job->farm_count }} {{ Str::plural('Farm Stop', $job->farm_count) }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="px-5 py-4 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-md bg-gold/10 border border-gold/20 flex items-center justify-center text-gold-700 dark:text-gold-light shrink-0">
                            <x-icon name="gauge" class="w-4 h-4" />
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider leading-none">Payload</p>
                            <p class="text-xs font-extrabold text-slate-800 dark:text-white mt-1 heading-font">{{ number_format($job->total_kg, 1) }} kg</p>
                        </div>
                    </div>
                </div>

                @if($job->weather)
                    @php
                        $wx = $job->weather;
                        $wxSevere = (bool) ($wx->is_severe ?? false);
                    @endphp
                    <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-800/50 bg-slate-50/10 dark:bg-slate-900/5 flex items-center justify-between">
                        <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-wide">
                            <x-icon name="cloud-sun" class="w-3.5 h-3.5 text-brand dark:text-brand-light" />
                            <span class="text-slate-500">
                                {{ ucfirst($wx->condition ?? 'Weather') }}
                                @if($wx->temperature !== null)
                                    <span class="text-slate-400">&middot;</span> {{ round($wx->temperature) }}&deg;C
                                @endif
                            </span>
                        </div>
                        @if($wxSevere)
                            <span class="text-[10px] font-bold text-warning-text bg-warning-bg border border-warning-border px-2 py-0.5 rounded">
                                Severe Weather
                            </span>
                        @else
                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded border border-slate-200/50 dark:border-slate-700/30">
                                {{ $wx->checked_at ? \Carbon\Carbon::parse($wx->checked_at)->diffForHumans() : 'Recent' }}
                            </span>
                        @endif
                    </div>
                @endif

                <div class="px-5 py-3 text-xs border-b border-slate-100 dark:border-slate-800/50 bg-slate-50/10 dark:bg-slate-900/5 flex items-center justify-between">
                    <div class="flex items-center gap-2 text-slate-500 font-semibold text-[10px] uppercase tracking-wide">
                        <x-icon name="pin" class="w-3.5 h-3.5 text-brand dark:text-brand-light" />
                        <span>Assigned Truck</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-brand-light border border-slate-200 dark:border-slate-700/60 rounded px-2.5 py-0.5">
                            {{ $job->truck->plate_number ?? '—' }}
                        </span>
                        @if($job->truck->vehicle_type ?? false)
                            <span class="text-slate-300 dark:text-slate-700">&middot;</span>
                            <span class="text-slate-500 dark:text-slate-400 text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800/60 px-2 py-0.5 rounded border border-slate-200/50 dark:border-slate-700/30">{{ $job->truck->vehicle_type }}</span>
                        @endif
                    </div>
                </div>

                @if($job->notes)
                    <div class="px-5 py-4 bg-warning-bg border-b border-slate-100 dark:border-dark-border">
                        <p class="text-[10px] text-warning-text font-bold uppercase tracking-widest">Dispatcher Instructions</p>
                        <p class="text-xs text-warning-text italic leading-relaxed mt-1 bg-warning-bg p-3 rounded-xl border border-warning-border">
                            "{{ Str::limit($job->notes, 90) }}"
                        </p>
                    </div>
                @endif

                <div class="flex gap-3 px-5 py-4 bg-slate-50/50 dark:bg-slate-900/40 border-t border-slate-100 dark:border-slate-800/10">
                    <a href="{{ route('driver.jobs.show', $job) }}"
                       class="flex-1 text-center text-xs font-bold text-slate-700 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700/80 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl py-3.5 transition duration-200 shadow-sm">
                        View Details
                    </a>
                    @if($job->status->value === 'confirmed')
                        @if(!$job->accepted_at)
                            <form method="POST" action="{{ route('driver.jobs.accept', $job) }}" class="flex-1">
                                @csrf
                                <button type="button"
                                    onclick="swalConfirm(this.closest('form'), {title: 'Accept Job?', text: 'Accept Route #{{ $job->id }} and prepare to depart.', confirmText: 'Yes, accept', icon: 'question', confirmColor: '#16283C'})"
                                    class="w-full text-center text-xs font-bold text-white bg-[#16283C] hover:bg-[#1e3a56] dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] py-3.5 rounded-2xl transition duration-200 shadow-sm">
                                    Accept Job
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('driver.jobs.status', $job) }}" class="flex-1">
                                @csrf @method('PATCH')
                                <x-button type="submit" size="lg" full class="rounded-2xl shadow-lg">
                                    <span>Start Run</span>
                                </x-button>
                            </form>
                        @endif
                    @endif
                </div>

            </div>
        @empty
            <div class="text-center py-16 px-6 bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl relative overflow-hidden">
                <div class="absolute inset-0 bg-slate-50 dark:bg-slate-800 pointer-events-none"></div>
                <div class="relative w-16 h-16 mx-auto mb-6 flex items-center justify-center">
                    <div class="w-10 h-10 rounded-full bg-brand/10 border border-brand/20 flex items-center justify-center text-brand dark:text-brand-light shadow-md font-bold text-xl" aria-hidden="true">—</div>
                </div>
                <h2 class="text-base font-bold text-slate-800 dark:text-white heading-font tracking-tight">No Active Routes Assigned</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 max-w-xs mx-auto leading-relaxed">
                    You'll be notified when a dispatch is ready.
                </p>
                <div class="mt-6 inline-flex items-center gap-2 bg-brand/10 border border-brand/20 px-3 py-1.5 rounded-md">
                    <span class="w-2 h-2 rounded-full bg-brand animate-pulse"></span>
                    <span class="text-[10px] text-brand dark:text-brand-light font-bold uppercase tracking-wider">Waiting for dispatch...</span>
                </div>
            </div>
        @endforelse

        {{-- Identity Verification Section --}}
        @php
            $driverProfile = Auth::user()->driverProfile;
            $needsUpload = $driverProfile && !$driverProfile->id_photo_path;
        @endphp

        @if($needsUpload)
            <div class="bg-white dark:bg-slate-800 border border-amber-200 dark:border-amber-800 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 flex items-center justify-center">
                        <x-icon name="document" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-800 dark:text-white heading-font">Identity Verification Required</h2>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Upload your ID and a selfie to get verified.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('driver.identity.upload') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="space-y-3">
                        <div>
                            <label class="text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">ID Photo</label>
                            <input type="file" name="id_photo" accept="image/*" required
                                   class="mt-1 w-full text-xs text-slate-600 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-brand/10 file:text-[#16283C] dark:file:bg-gold-light/10 dark:file:text-[#D7BC7A] hover:file:bg-brand/20">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Selfie</label>
                            <input type="file" name="selfie" accept="image/*" required
                                   class="mt-1 w-full text-xs text-slate-600 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-brand/10 file:text-[#16283C] dark:file:bg-gold-light/10 dark:file:text-[#D7BC7A] hover:file:bg-brand/20">
                        </div>
                    </div>
                    <button type="submit" class="mt-4 w-full bg-[#16283C] hover:bg-[#1e3a56] dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] text-white text-xs font-bold py-3 rounded-xl transition ">
                        Upload Documents
                    </button>
                </form>
            </div>
        @endif

    </main>

</x-driver-layout>
