<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="theme-color" content="#020617" />
    <title>HarvestHaul — Driver Portal</title>

    <!-- Theme Initializer -->
    <script>
        (function() {
            var theme = localStorage.getItem('theme');
            var isDark = theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        slate: {
                            950: '#020617',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at 50% 0%, #F8FAFC 0%, #EEF2F6 100%);
            transition: background 0.3s ease, color 0.3s ease;
        }
        html.dark body {
            background: radial-gradient(circle at 50% 0%, #0c1524 0%, #020617 100%);
        }
        .heading-font {
            font-family: 'Outfit', sans-serif;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(0, 0, 0, 0.06);
            transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }
        html.dark .glass-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .glass-card-accent {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(20, 184, 166, 0.05) 100%);
            border: 1px solid rgba(16, 185, 129, 0.15);
        }
    </style>
</head>
<body class="text-slate-800 dark:text-slate-100 antialiased min-h-screen pb-12">

    <!-- Top Header Panel (Premium Sticky Glassmorphism) -->
    <header class="sticky top-0 z-30 bg-white/80 dark:bg-slate-950/80 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800/60 px-4 py-4 transition-colors duration-300">
        <div class="flex items-center justify-between max-w-lg mx-auto">
            <div class="flex items-center gap-3">
                <div class="relative flex items-center justify-center">
                    <span class="absolute inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-ping opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-600 dark:bg-emerald-500"></span>
                </div>
                <div>
                    <p class="text-[9px] text-emerald-600 dark:text-emerald-400 font-bold uppercase tracking-widest leading-none">Driver Portal &middot; Live</p>
                    <h1 class="text-base font-extrabold heading-font text-slate-800 dark:text-white mt-1.5 leading-none">{{ Auth::user()->name }}</h1>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <!-- Theme Toggle Button -->
                <button onclick="toggleDarkMode()" class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/50 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white flex items-center justify-center transition-all duration-200 active:scale-[0.97]" title="Toggle Theme">
                    <!-- Sun Icon (shown in dark mode) -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <!-- Moon Icon (shown in light mode) -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-2 text-xs font-bold text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white bg-slate-100 dark:bg-slate-800/80 hover:bg-slate-200 dark:hover:bg-slate-700/80 border border-slate-200 dark:border-slate-700/50 rounded-xl px-3.5 py-2 transition-all duration-200 active:scale-[0.97]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Exit</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 py-6">

        <!-- Summary Metrics Cards -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="glass-card rounded-2xl p-4 flex items-center gap-3.5 relative overflow-hidden group hover:border-emerald-500/30 transition-all duration-300">
                <div class="absolute -right-3 -bottom-3 w-16 h-16 bg-emerald-500/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider leading-none">Active Jobs</p>
                    <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 heading-font mt-1.5 leading-none">{{ $jobs->count() }}</p>
                </div>
            </div>

            <div class="glass-card rounded-2xl p-4 flex items-center gap-3.5 relative overflow-hidden group hover:border-teal-500/30 transition-all duration-300">
                <div class="absolute -right-3 -bottom-3 w-16 h-16 bg-teal-500/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                <div class="w-10 h-10 rounded-xl bg-teal-500/10 border border-teal-500/20 flex items-center justify-center text-teal-600 dark:text-teal-400 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider leading-none">Completed</p>
                    <p class="text-2xl font-black text-teal-600 dark:text-teal-400 heading-font mt-1.5 leading-none">{{ $completedJobs }}</p>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="mb-5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-semibold heading-font rounded-xl p-4 flex items-center gap-3 animate-fadeIn">
                <div class="w-5 h-5 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-500 dark:text-emerald-300 shrink-0">✓</div>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-5 bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-semibold heading-font rounded-xl p-4 flex items-center gap-3 animate-fadeIn">
                <div class="w-5 h-5 rounded-full bg-rose-500/20 flex items-center justify-center text-rose-550 dark:text-rose-300 shrink-0">!</div>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Section Label -->
        <div class="flex items-center justify-between mb-4 px-1">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Active Runs assigned</p>
            <span class="w-1.5 h-1.5 rounded-full bg-slate-400 dark:bg-slate-600"></span>
        </div>

        <!-- Job Cards -->
        @forelse($jobs as $job)
            <div class="glass-card rounded-3xl mb-6 overflow-hidden shadow-lg border border-slate-200 dark:border-slate-800/80 hover:border-slate-300 dark:hover:border-slate-700/80 transition-all duration-300 hover:shadow-emerald-950/5">

                <!-- Card Header -->
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-900/40 flex items-center justify-between transition-colors duration-300">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <p class="text-sm font-black text-slate-800 dark:text-white heading-font">Job ID #{{ $job->id }}</p>
                        </div>
                        <p class="text-[10px] text-slate-400 font-medium mt-1">
                            Dispatched Shipping Container Run
                        </p>
                    </div>
                    @php
                        $badge = match($job->status) {
                            'confirmed'   => ['bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20 dark:border-amber-500/20', 'Ready'],
                            'in_progress' => ['bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/20 dark:border-sky-500/20', 'In Transit'],
                            default       => ['bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-500/20 dark:border-slate-500/20', ucfirst($job->status)],
                        };
                    @endphp
                    <span class="text-[9px] font-extrabold uppercase tracking-widest px-2.5 py-1 rounded-lg border {{ $badge[0] }} shadow-sm">
                        {{ $badge[1] }}
                    </span>
                </div>

                <!-- Logistics Payload & Stops Details -->
                <div class="grid grid-cols-2 divide-x divide-slate-100 dark:divide-slate-800/50 border-b border-slate-100 dark:border-slate-800/60 bg-slate-50/30 dark:bg-slate-900/20 transition-colors duration-300">
                    <div class="px-5 py-4 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider leading-none">Stops</p>
                            <p class="text-xs font-extrabold text-slate-800 dark:text-white mt-1 heading-font">{{ $job->farm_count }} {{ Str::plural('Farm Stop', $job->farm_count) }}</p>
                        </div>
                    </div>

                    <div class="px-5 py-4 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-pink-500/10 border border-pink-500/20 flex items-center justify-center text-pink-600 dark:text-pink-400 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider leading-none">Payload</p>
                            <p class="text-xs font-extrabold text-slate-800 dark:text-white mt-1 heading-font">{{ number_format($job->total_kg, 1) }} kg</p>
                        </div>
                    </div>
                </div>

                <!-- Truck Row -->
                <div class="px-5 py-3.5 text-xs border-b border-slate-100 dark:border-slate-800/60 bg-slate-50/10 dark:bg-slate-900/10 flex items-center justify-between transition-colors duration-300">
                    <div class="flex items-center gap-2 text-slate-400 font-semibold text-[11px]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-550 dark:text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1" />
                        </svg>
                        <span>Assigned Fleet Truck</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-emerald-400 border border-slate-200 dark:border-slate-700/60 rounded px-2.5 py-0.5 shadow-inner">
                            {{ $job->truck->plate_number ?? '—' }}
                        </span>
                        @if($job->truck->vehicle_type ?? false)
                            <span class="text-slate-300 dark:text-slate-600 text-[10px]">&middot;</span>
                            <span class="text-slate-500 dark:text-slate-400 text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800/60 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700/30">{{ $job->truck->vehicle_type }}</span>
                        @endif
                    </div>
                </div>

                <!-- Notes Preview -->
                @if($job->notes)
                    <div class="px-5 py-4 bg-amber-500/5 border-b border-slate-100 dark:border-slate-800/60 transition-colors duration-300">
                        <p class="text-[9px] text-amber-600 dark:text-amber-500 font-bold uppercase tracking-widest">Dispatcher Instructions</p>
                        <p class="text-xs text-amber-900/80 dark:text-amber-200/80 italic leading-relaxed mt-1 bg-amber-50/50 dark:bg-slate-950/40 p-3 rounded-xl border border-amber-200 dark:border-amber-500/10">
                            "{{ Str::limit($job->notes, 90) }}"
                        </p>
                    </div>
                @endif

                <!-- Action Buttons -->
                <div class="flex gap-3 px-5 py-4 bg-slate-50/50 dark:bg-slate-900/40 border-t border-slate-100 dark:border-slate-800/20 transition-colors duration-300">
                    <a href="{{ route('driver.jobs.show', $job) }}"
                       class="flex-1 text-center text-xs font-bold text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700/80 border border-slate-200 dark:border-slate-700/60 rounded-2xl py-3.5 transition duration-200 shadow-sm active:scale-[0.98]">
                        View Details
                    </a>
                    @if($job->status === 'confirmed')
                        <form method="POST" action="{{ route('driver.jobs.status', $job) }}" class="flex-1">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="w-full text-xs font-extrabold text-white bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 rounded-2xl py-3.5 shadow-lg shadow-emerald-500/10 hover:shadow-emerald-500/20 transition-all duration-200 active:scale-[0.98] flex items-center justify-center gap-1.5">
                                <span>Start Run</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </form>
                    @endif
                </div>

            </div>
        @empty
            <!-- Empty State (Beautiful Futuristic Interface) -->
            <div class="text-center py-16 px-6 glass-card rounded-3xl border border-slate-200 dark:border-slate-800/80 shadow-2xl relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-b from-slate-200/10 dark:from-slate-950/20 to-transparent pointer-events-none"></div>
                
                <!-- Animated locator graphics -->
                <div class="relative w-24 h-24 mx-auto mb-6 flex items-center justify-center">
                    <div class="absolute inset-0 rounded-full bg-slate-200 dark:bg-slate-800/50 animate-ping opacity-25"></div>
                    <div class="absolute inset-2 rounded-full bg-emerald-500/5 animate-pulse"></div>
                    <div class="w-14 h-14 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-4xl text-emerald-650 dark:text-emerald-400 shadow-lg shadow-emerald-500/5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1" />
                        </svg>
                    </div>
                </div>

                <h3 class="text-base font-extrabold text-slate-800 dark:text-white heading-font tracking-tight">No Active Routes Assigned</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 max-w-xs mx-auto leading-relaxed">
                    Logistic dispatchers have not pooled a run for you yet. Standing by for real-time schedule assignments.
                </p>
                <div class="mt-6 inline-flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/25 px-3 py-1.5 rounded-full">
                    <span class="w-2 h-2 rounded-full bg-emerald-550 dark:bg-emerald-400 animate-pulse"></span>
                    <span class="text-[10px] text-emerald-600 dark:text-emerald-300 font-bold uppercase tracking-wider">Listening for dispatch...</span>
                </div>
            </div>
        @endforelse

    </main>

    <!-- Toggle scripts -->
    <script>
        function toggleDarkMode() {
            var isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }
    </script>

</body>
</html>
