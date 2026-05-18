<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>HarvestHaul — B2B Crop Distribution & Logistics Management System</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] text-[#1b1b18] antialiased min-h-screen font-['Instrument_Sans',sans-serif]">

        <header class="sticky top-0 z-50 bg-[#FDFDFC]/90 backdrop-blur-md border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex justify-between items-center">

                <a href="/" class="flex items-center gap-2 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="#2D8A37" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8a13 13 0 0 1-10 10Z"/>
                        <path d="M9 21s-4.5-3-4.5-7"/>
                        <path d="M7 20s-4-3.5-4-9"/>
                    </svg>
                    <span class="text-2xl font-bold tracking-tight text-[#2D8A37]">HarvestHaul</span>
                </a>

                <nav class="hidden md:flex items-center gap-8 bg-gray-100/80 px-6 py-2 rounded-full border border-gray-200/50">
                    <a href="#features" class="text-sm font-medium text-gray-600 hover:text-[#2D8A37] transition">Features</a>
                    <a href="#how-it-works" class="text-sm font-medium text-gray-600 hover:text-[#2D8A37] transition">How It Works</a>
                    <a href="#why-us" class="text-sm font-medium text-gray-600 hover:text-[#2D8A37] transition">Why Us</a>
                </nav>

                <div class="hidden md:flex items-center gap-4">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-medium text-gray-700 hover:text-[#2D8A37] transition px-3 py-2">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="px-5 py-2.5 bg-[#2D8A37] text-white rounded-lg text-sm font-medium hover:bg-[#246e2c] transition shadow-sm">
                                    Register
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>

                <div class="flex md:hidden">
                    <button type="button" onclick="toggleMobileMenu()" class="text-gray-600 hover:text-gray-900 focus:outline-none" aria-label="Toggle Menu">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <div id="mobileMenu" class="hidden md:hidden border-b border-gray-200 bg-[#FDFDFC] px-4 pt-2 pb-6 space-y-3">
                <a href="#features" onclick="toggleMobileMenu()" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-50 hover:text-[#2D8A37]">Features</a>
                <a href="#how-it-works" onclick="toggleMobileMenu()" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-50 hover:text-[#2D8A37]">How It Works</a>
                <a href="#why-us" onclick="toggleMobileMenu()" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-50 hover:text-[#2D8A37]">Why Us</a>
                <div class="pt-4 border-t border-gray-200 flex flex-col gap-2">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="w-full text-center px-4 py-2.5 border border-gray-300 rounded-md text-base font-medium text-gray-700">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="w-full text-center px-4 py-2.5 border border-gray-200 rounded-md text-base font-medium text-gray-700">Log in</a>
                        <a href="{{ route('register') }}" class="w-full text-center px-4 py-2.5 bg-[#2D8A37] text-white rounded-md text-base font-medium hover:bg-[#246e2c]">Register</a>
                    @endauth
                </div>
            </div>
        </header>

        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex-1">

            <section class="py-12 lg:py-20 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <div class="lg:col-span-7 flex flex-col items-start space-y-6">
                    <span class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold tracking-wide uppercase border border-emerald-600/30 bg-emerald-50 text-[#2D8A37]">
                        B2B Crop Distribution & Logistics Platform
                    </span>
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-gray-900 leading-[1.1]">
                        Streamline Your Crops Logistics with <span class="text-[#2D8A37]">Real-Time Monitoring</span>
                    </h1>
                    <p class="text-lg text-gray-600 max-w-xl leading-relaxed">
                        A digital platform built to improve coordination, reduce unnecessary trips, and support resource pooling for agricultural transport across regional hubs.
                    </p>
                    <div class="flex flex-wrap gap-4 pt-2">
                        @guest
                            <a href="{{ route('register') }}" class="px-6 py-3.5 bg-[#2D8A37] text-white rounded-xl font-medium hover:bg-[#246e2c] transition shadow-md shadow-emerald-700/10">
                                Join the Network
                            </a>
                            <a href="#how-it-works" class="px-6 py-3.5 border border-gray-300 rounded-xl font-medium text-gray-700 hover:bg-gray-50 transition">
                                Learn More
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" class="px-6 py-3.5 bg-[#2D8A37] text-white rounded-xl font-medium hover:bg-[#246e2c] transition shadow-md shadow-emerald-700/10">
                                Go to Dashboard →
                            </a>
                        @endguest
                    </div>
                </div>
                <div class="lg:col-span-5 h-[400px] sm:h-[500px] rounded-3xl overflow-hidden shadow-xl border border-gray-100">
                    <img src="https://images.unsplash.com/photo-1559827291-72ee739d0d9a?auto=format&fit=crop&q=80&w=800" alt="Philippine Agricultural Field Landscape" class="w-full h-full object-cover transform hover:scale-105 transition duration-700">
                </div>
            </section>

            <hr class="border-gray-100 my-12">

            <section id="features" class="py-12 scroll-mt-24">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">Powerful Features For Modern Agriculture Logistics</h2>
                    <p class="mt-4 text-base text-gray-500">Practical tools designed to organize, execute, and document crop transport workflows systematically.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-white border border-gray-100 p-8 rounded-2xl shadow-sm hover:shadow-md transition flex flex-col justify-between">
                        <div>
                            <p class="text-gray-600 mb-6 font-normal leading-relaxed">Register delivery vehicles, assign drivers to specific active trips, and manage current asset availability states directly through an organized fleet dashboard.</p>
                        </div>
                        <div class="flex items-center gap-4 pt-4 border-t border-gray-50">
                            <div class="p-3 bg-emerald-50 rounded-xl text-[#2D8A37]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" /></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 text-sm">Fleet Assignment</h4>
                                <p class="text-xs text-gray-400">Truck & Driver Matching</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-100 p-8 rounded-2xl shadow-sm hover:shadow-md transition flex flex-col justify-between">
                        <div>
                            <p class="text-gray-600 mb-6 font-normal leading-relaxed">Monitor delivery progress along routes driven entirely by device-supported browser geolocation tracking and manual, user-generated milestone updates.</p>
                        </div>
                        <div class="flex items-center gap-4 pt-4 border-t border-gray-50">
                            <div class="p-3 bg-emerald-50 rounded-xl text-[#2D8A37]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 text-sm">Real-Time Tracking</h4>
                                <p class="text-xs text-gray-400">Browser GPS Visibility</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-100 p-8 rounded-2xl shadow-sm hover:shadow-md transition flex flex-col justify-between">
                        <div>
                            <p class="text-gray-600 mb-6 font-normal leading-relaxed">Identify multiple farm pickup locations situated sequentially along an active delivery path to maximize truck spatial utilization and minimize empty runs.</p>
                        </div>
                        <div class="flex items-center gap-4 pt-4 border-t border-gray-50">
                            <div class="p-3 bg-emerald-50 rounded-xl text-[#2D8A37]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 text-sm">Resource Pooling</h4>
                                <p class="text-xs text-gray-400">Route-Based Consolidation</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <hr class="border-gray-100 my-12">

            <section id="why-us" class="py-12 scroll-mt-24">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">Why Choose HarvestHaul?</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-12">
                    <div class="flex gap-4 border-b border-gray-100 pb-6">
                        <div class="shrink-0 text-[#2D8A37] font-semibold text-lg pt-1">✓</div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">Minimize Repeated Travel</h3>
                            <p class="text-sm text-gray-500 leading-relaxed">Consolidate crop distribution requests by scheduling high-capacity vehicle runs across neighboring farming communities sequentially.</p>
                        </div>
                    </div>

                    <div class="flex gap-4 border-b border-gray-100 pb-6">
                        <div class="shrink-0 text-[#2D8A37] font-semibold text-lg pt-1">✓</div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">Replace Fragmented Communication</h3>
                            <p class="text-sm text-gray-500 leading-relaxed">Eliminate scheduling delays caused by manual coordination, scattered mobile chat updates, and traditional phone call channels.</p>
                        </div>
                    </div>

                    <div class="flex gap-4 border-b border-gray-100 pb-6">
                        <div class="shrink-0 text-[#2D8A37] font-semibold text-lg pt-1">✓</div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">Centralized Operational History</h3>
                            <p class="text-sm text-gray-500 leading-relaxed">Securely maintain transaction history logs, drop-off confirmation statuses, and operational reports exportable in clean CSV or PDF formats.</p>
                        </div>
                    </div>

                    <div class="flex gap-4 border-b border-gray-100 pb-6">
                        <div class="shrink-0 text-[#2D8A37] font-semibold text-lg pt-1">✓</div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">Grounded Local Optimization</h3>
                            <p class="text-sm text-gray-500 leading-relaxed">Architecture custom-tailored around regional validation insights gathered directly from verified local cooperative networks.</p>
                        </div>
                    </div>
                </div>
            </section>

            <hr class="border-gray-100 my-12">

            <section id="how-it-works" class="py-12 scroll-mt-24">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">How It Works</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-gray-50/70 border border-gray-100 p-8 rounded-2xl">
                        <span class="text-xs font-bold uppercase text-[#2D8A37] tracking-wider bg-emerald-50 px-2.5 py-1 rounded-md">Step 01</span>
                        <h3 class="text-xl font-bold text-gray-900 mt-4 mb-2">Account Registration</h3>
                        <p class="text-sm text-gray-600 leading-relaxed">Users authenticate and onboard into roles specifically designated by the system: Administrator, Logistics Coordinator, Driver, or Farmer.</p>
                    </div>

                    <div class="bg-gray-50/70 border border-gray-100 p-8 rounded-2xl">
                        <span class="text-xs font-bold uppercase text-[#2D8A37] tracking-wider bg-emerald-50 px-2.5 py-1 rounded-md">Step 02</span>
                        <h3 class="text-xl font-bold text-gray-900 mt-4 mb-2">Route & Load Planning</h3>
                        <p class="text-sm text-gray-600 leading-relaxed">Farmers post seasonal harvest quantities, allowing Logistics Coordinators to organize sequential route groups and assign available fleet vehicles.</p>
                    </div>

                    <div class="bg-gray-50/70 border border-gray-100 p-8 rounded-2xl">
                        <span class="text-xs font-bold uppercase text-[#2D8A37] tracking-wider bg-emerald-50 px-2.5 py-1 rounded-md">Step 03</span>
                        <h3 class="text-xl font-bold text-gray-900 mt-4 mb-2">Track & Execute</h3>
                        <p class="text-sm text-gray-600 leading-relaxed">Drivers track assignments through mobile-optimized portals, updating active pickup status logs as shipments move toward market targets.</p>
                    </div>
                </div>
            </section>

            <section class="my-16 bg-gradient-to-br from-emerald-800 to-emerald-950 rounded-3xl p-8 sm:p-12 text-center text-white shadow-xl relative overflow-hidden">
                <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
                <div class="relative z-10 max-w-2xl mx-auto space-y-6">
                    <h2 class="text-3xl sm:text-4xl font-bold tracking-tight">Ready to Optimize Agricultural Distribution?</h2>
                    <p class="text-emerald-100 text-sm sm:text-base leading-relaxed">
                        Bring structural transparency and coordinated resource pooling directly to your transport networks across General Santos City, Polomolok, and Tupi.
                    </p>
                    <div class="pt-2">
                        @guest
                            <a href="{{ route('register') }}" class="inline-block px-8 py-3.5 bg-white text-[#2D8A37] font-semibold rounded-xl hover:bg-gray-100 transition shadow-md">
                                Get Started Now
                            </a>
                        @else
                            <a href="{{ url('/dashboard') }}" class="inline-block px-8 py-3.5 bg-white text-[#2D8A37] font-semibold rounded-xl hover:bg-gray-100 transition shadow-md">
                                Access Management Dashboard
                            </a>
                        @endguest
                    </div>
                </div>
            </section>

        </main>

        <footer class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 border-t border-gray-100 pt-10 pb-12 flex flex-col sm:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="#2D8A37" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8a13 13 0 0 1-10 10Z"/>
                    </svg>
                    <span class="font-bold text-gray-900 text-sm">HarvestHaul</span>
                </div>
                <div class="hidden sm:flex gap-4 text-xs text-gray-500">
                    <a href="#features" class="hover:text-[#2D8A37]">Features</a>
                    <a href="#how-it-works" class="hover:text-[#2D8A37]">How It Works</a>
                    <a href="#why-us" class="hover:text-[#2D8A37]">Why Us</a>
                </div>
            </div>

            <div class="flex items-center gap-6 text-xs text-gray-400">
                <p>&copy; {{ date('Y') }} HarvestHaul. All rights reserved.</p>
            </div>
        </footer>

        <script>
            function toggleMobileMenu() {
                var menu = document.getElementById('mobileMenu');
                if (menu.classList.contains('hidden')) {
                    menu.classList.remove('hidden');
                } else {
                    menu.classList.add('hidden');
                }
            }
        </script>
    </body>
</html>
