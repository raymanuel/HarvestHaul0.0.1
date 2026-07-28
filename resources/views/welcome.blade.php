<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HarvestHaul — Coordinated Crop Logistics for Mindanao</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&family=Instrument+Serif:ital@0;1&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --soil: #1A2E1A;
            --soil-light: #243524;
            --leaf: #3A7D44;
            --leaf-dark: #2E6336;
            --leaf-deep: #1F4D25;
            --wheat: #C8A415;
            --wheat-dim: #9A7D10;
            --parchment: #FAFAF5;
        }

        body { font-family: 'DM Sans', sans-serif; }
        .font-display { font-family: 'Instrument Serif', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }

        .faq-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .faq-content.open {
            max-height: 200px;
        }

        .scroll-reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .scroll-reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .role-panel { transition: opacity 0.3s ease, transform 0.3s ease; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
            .scroll-reveal { opacity: 1; transform: none; }
        }
    </style>
</head>
<body class="bg-[#FAFAF5] text-[#1A2E1A] antialiased">

    <!-- Header -->
    <header id="main-header" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex justify-between items-center">
            <a href="/" class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-lg bg-[#3A7D44] flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8a13 13 0 0 1-10 10Z"/>
                        <path d="M9 21s-4.5-3-4.5-7"/>
                    </svg>
                </div>
                <span class="text-lg font-bold tracking-tight font-display text-white">HarvestHaul</span>
            </a>

            <nav class="hidden md:flex items-center gap-6">
                <a href="#about" class="text-sm font-medium text-white/70 hover:text-white transition">About</a>
                <a href="#services" class="text-sm font-medium text-white/70 hover:text-white transition">Services</a>
                <a href="#roles" class="text-sm font-medium text-white/70 hover:text-white transition">Portals</a>
                <a href="#faq" class="text-sm font-medium text-white/70 hover:text-white transition">FAQ</a>
            </nav>

            <div class="hidden md:flex items-center gap-3">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="px-4 py-2 bg-[#C8A415] text-[#1A2E1A] rounded-lg text-sm font-bold hover:bg-[#C8A415]/90 transition">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-white/70 hover:text-white transition px-3 py-2">
                            Log in
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('login') }}" class="px-4 py-2 bg-[#C8A415] text-[#1A2E1A] rounded-lg text-sm font-bold hover:bg-[#C8A415]/90 transition">
                                Get Started
                            </a>
                        @endif
                    @endauth
                @endif
            </div>

            <button onclick="toggleMobileMenu()" class="md:hidden w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center text-white" aria-label="Menu">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>

        <div id="mobileMenu" class="hidden md:hidden bg-[#1F4D25]/95 backdrop-blur-xl border-t border-white/10 px-6 py-4 space-y-3">
            <a href="#about" onclick="toggleMobileMenu()" class="block text-sm font-medium text-white/70 hover:text-white py-2">About</a>
            <a href="#services" onclick="toggleMobileMenu()" class="block text-sm font-medium text-white/70 hover:text-white py-2">Services</a>
            <a href="#roles" onclick="toggleMobileMenu()" class="block text-sm font-medium text-white/70 hover:text-white py-2">Portals</a>
            <a href="#faq" onclick="toggleMobileMenu()" class="block text-sm font-medium text-white/70 hover:text-white py-2">FAQ</a>
            <div class="pt-3 border-t border-white/10">
                @guest
                    <a href="{{ route('login') }}" class="block w-full text-center px-4 py-2.5 bg-[#C8A415] text-[#1A2E1A] rounded-lg font-bold text-sm">Get Started</a>
                @else
                    <a href="{{ url('/dashboard') }}" class="block w-full text-center px-4 py-2.5 bg-[#C8A415] text-[#1A2E1A] rounded-lg font-bold text-sm">Dashboard</a>
                @endguest
            </div>
        </div>
    </header>

    <!-- Hero -->
    <section class="relative min-h-[90vh] flex items-center overflow-hidden">
        <div class="absolute inset-0">
            <img src="{{ asset('images/hero-bg.png') }}" alt="Lush farmlands of Mindanao" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-r from-[#1A2E1A]/85 via-[#1A2E1A]/60 to-[#1A2E1A]/40"></div>
        </div>

        <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-32 lg:py-40">
            <div class="max-w-2xl">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-display text-white leading-[1.1] mb-6">
                    B2B Crop Distribution & Logistics
                </h1>

                <p class="text-lg text-white/70 leading-relaxed max-w-xl mb-10">
                    A web-based platform connecting farmers, logistics coordinators, and drivers in General Santos City and Polomolok for route-planned pickup consolidation and real-time delivery tracking.
                </p>

                <div class="flex flex-wrap gap-4">
                    @guest
                        <a href="{{ route('login') }}" class="px-6 py-3.5 bg-[#C8A415] text-[#1A2E1A] rounded-xl font-bold text-sm hover:bg-[#C8A415]/90 transition shadow-lg">
                            Get Started
                        </a>
                        <a href="#about" class="px-6 py-3.5 border border-white/20 text-white rounded-xl font-semibold text-sm hover:bg-white/5 transition">
                            Learn More
                        </a>
                    @else
                        <a href="{{ url('/dashboard') }}" class="px-6 py-3.5 bg-[#C8A415] text-[#1A2E1A] rounded-xl font-bold text-sm hover:bg-[#C8A415]/90 transition shadow-lg">
                            Open Dashboard
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </section>

    <!-- About -->
    <section id="about" class="py-24 bg-[#FAFAF5] scroll-mt-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <span class="text-xs font-bold uppercase tracking-widest text-[#C8A415]">About</span>
                <h2 class="text-3xl sm:text-4xl font-display text-[#1A2E1A] mt-4 leading-tight">
                    A localized digital solution for crop distribution and logistics coordination
                </h2>
                <p class="text-[#1A2E1A]/60 mt-6 text-lg leading-relaxed">
                    HarvestHaul addresses the challenges of fragmented communication, underutilized vehicles, and limited delivery visibility in agricultural transport. By integrating mapping, route planning, tracking, and reporting into one web-based platform, we aim to support a more organized and efficient transport process for registered stakeholders in General Santos City and Polomolok.
                </p>
            </div>
        </div>
    </section>

    <!-- Services -->
    <section id="services" class="py-24 bg-[#FAFAF5] scroll-mt-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="text-xs font-bold uppercase tracking-widest text-[#C8A415]">What We Do</span>
                <h2 class="text-3xl sm:text-4xl font-display text-[#1A2E1A] mt-4">Our Services</h2>
            </div>

            <div class="space-y-0 divide-y divide-[#1A2E1A]/5">
                <!-- Service 01 -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 py-10 items-start">
                    <div class="lg:col-span-1">
                        <span class="font-mono text-sm font-bold text-[#C8A415]">01</span>
                    </div>
                    <div class="lg:col-span-4">
                        <h3 class="text-xl font-display text-[#1A2E1A]">Route-Pooling Logistics</h3>
                    </div>
                    <div class="lg:col-span-4">
                        <p class="text-[#1A2E1A]/60 leading-relaxed">
                            We aggregate crop dimensions and pickup dates from local cooperatives. The routing engine sequences farm locations along a single path, matching them to high-capacity freight trucks.
                        </p>
                    </div>
                    <div class="lg:col-span-3">
                        <div class="w-full h-32 rounded-2xl bg-[#1F4D25]/5 flex items-center justify-center">
                            <svg class="w-10 h-10 text-[#3A7D44]" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 38 L18 22 L30 28 L42 12"/>
                                <circle cx="6" cy="38" r="3" fill="#3A7D44"/>
                                <circle cx="18" cy="22" r="3" fill="#C8A415"/>
                                <circle cx="30" cy="28" r="3" fill="#C8A415"/>
                                <circle cx="42" cy="12" r="3" fill="#3A7D44"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Service 02 -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 py-10 items-start">
                    <div class="lg:col-span-1">
                        <span class="font-mono text-sm font-bold text-[#C8A415]">02</span>
                    </div>
                    <div class="lg:col-span-4">
                        <h3 class="text-xl font-display text-[#1A2E1A]">Real-Time GPS Tracking</h3>
                    </div>
                    <div class="lg:col-span-4">
                        <p class="text-[#1A2E1A]/60 leading-relaxed">
                            Drivers broadcast GPS location live. You see your crop move from farm to hub to buyer in real time, with delay detection and weather-aware ETA predictions.
                        </p>
                    </div>
                    <div class="lg:col-span-3">
                        <div class="w-full h-32 rounded-2xl bg-[#1F4D25]/5 flex items-center justify-center">
                            <svg class="w-10 h-10 text-[#3A7D44]" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="24" cy="20" r="14" opacity="0.3"/>
                                <circle cx="24" cy="20" r="8"/>
                                <path d="M24 6V2M24 38v-4M10 20H6M42 20h-4"/>
                                <circle cx="24" cy="20" r="3" fill="#C8A415"/>
                                <path d="M24 34 L18 44 L24 40 L30 44 Z" fill="#3A7D44"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Service 03 -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 py-10 items-start">
                    <div class="lg:col-span-1">
                        <span class="font-mono text-sm font-bold text-[#C8A415]">03</span>
                    </div>
                    <div class="lg:col-span-4">
                        <h3 class="text-xl font-display text-[#1A2E1A]">Cost-Split Management</h3>
                    </div>
                    <div class="lg:col-span-4">
                        <p class="text-[#1A2E1A]/60 leading-relaxed">
                            Proportional cost allocation based on crop weight and distance. Automated invoicing, receipt tracking, and payment verification between all parties.
                        </p>
                    </div>
                    <div class="lg:col-span-3">
                        <div class="w-full h-32 rounded-2xl bg-[#1F4D25]/5 flex items-center justify-center">
                            <svg class="w-10 h-10 text-[#3A7D44]" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="8" y="8" width="32" height="32" rx="4"/>
                                <path d="M8 18h32"/>
                                <path d="M18 8v32"/>
                                <circle cx="30" cy="30" r="6" fill="#C8A415" opacity="0.3" stroke="#C8A415"/>
                                <path d="M28 30h4M30 28v4" stroke="#C8A415" stroke-width="2"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Role Portals -->
    <section id="roles" class="py-24 bg-white scroll-mt-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <span class="text-xs font-bold uppercase tracking-widest text-[#3A7D44]">Role-Based Portals</span>
                <h2 class="text-3xl sm:text-4xl font-display text-[#1A2E1A] mt-4">Built for each seat at the table</h2>
            </div>

            <!-- Role tabs -->
            <div class="flex justify-center gap-2 mb-10" role="tablist" aria-label="Role portals">
                <button onclick="setRole('farmer')" id="role-btn-farmer" role="tab" aria-selected="true" aria-controls="role-farmer" class="role-tab px-5 py-2.5 rounded-xl text-sm font-semibold bg-[#3A7D44] text-white transition">
                    Farmers
                </button>
                <button onclick="setRole('logistics')" id="role-btn-logistics" role="tab" aria-selected="false" aria-controls="role-logistics" class="role-tab px-5 py-2.5 rounded-xl text-sm font-semibold bg-white text-[#1A2E1A] border border-[#1A2E1A]/10 hover:border-[#3A7D44]/30 transition">
                    Logistics
                </button>
                <button onclick="setRole('driver')" id="role-btn-driver" role="tab" aria-selected="false" aria-controls="role-driver" class="role-tab px-5 py-2.5 rounded-xl text-sm font-semibold bg-white text-[#1A2E1A] border border-[#1A2E1A]/10 hover:border-[#3A7D44]/30 transition">
                    Drivers
                </button>
            </div>

            <!-- Farmer Panel -->
            <div id="role-farmer" role="tabpanel" aria-labelledby="role-btn-farmer" class="role-panel bg-[#FAFAF5] rounded-3xl border border-[#1A2E1A]/5 overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2">
                    <div class="p-8 sm:p-12 flex flex-col justify-center">
                        <h3 class="text-2xl font-display text-[#1A2E1A] mb-4">Ship full loads without meeting minimums</h3>
                        <p class="text-[#1A2E1A]/60 leading-relaxed mb-6">
                            Submit your harvest. Our engine finds other farms along your highway corridor and groups your crops into a single optimized truckload. You pay only for the space you use.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <span class="px-3 py-1.5 rounded-full bg-[#3A7D44]/10 text-[#3A7D44] text-xs font-semibold">Corridor Matching</span>
                            <span class="px-3 py-1.5 rounded-full bg-[#3A7D44]/10 text-[#3A7D44] text-xs font-semibold">Proportional Cost Split</span>
                            <span class="px-3 py-1.5 rounded-full bg-[#3A7D44]/10 text-[#3A7D44] text-xs font-semibold">Quick Crop Posting</span>
                        </div>
                    </div>
                    <div class="bg-[#1F4D25] flex items-center justify-center p-8 sm:p-12">
                        <div class="text-center">
                            <svg class="w-16 h-16 text-[#C8A415] mx-auto mb-4" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M32 8 C20 8 12 16 12 28 C12 40 32 56 32 56 C32 56 52 40 52 28 C52 16 44 8 32 8Z" opacity="0.2" fill="#C8A415"/>
                                <path d="M32 8 C20 8 12 16 12 28 C12 40 32 56 32 56 C32 56 52 40 52 28 C52 16 44 8 32 8Z"/>
                                <circle cx="32" cy="26" r="8"/>
                                <path d="M24 40 L32 34 L40 40"/>
                            </svg>
                            <p class="text-white/60 text-sm">Post harvest, get matched, ship full</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logistics Panel -->
            <div id="role-logistics" role="tabpanel" aria-labelledby="role-btn-logistics" class="role-panel hidden bg-[#FAFAF5] rounded-3xl border border-[#1A2E1A]/5 overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2">
                    <div class="p-8 sm:p-12 flex flex-col justify-center">
                        <h3 class="text-2xl font-display text-[#1A2E1A] mb-4">Build optimized multi-stop routes in one screen</h3>
                        <p class="text-[#1A2E1A]/60 leading-relaxed mb-6">
                            View regional farm posts, select compatible harvests, and generate sequential pickup routes. Track truck capacity and assign drivers instantly.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <span class="px-3 py-1.5 rounded-full bg-[#C8A415]/10 text-[#9A7D10] text-xs font-semibold">Sequential Routing</span>
                            <span class="px-3 py-1.5 rounded-full bg-[#C8A415]/10 text-[#9A7D10] text-xs font-semibold">Fleet Monitoring</span>
                            <span class="px-3 py-1.5 rounded-full bg-[#C8A415]/10 text-[#9A7D10] text-xs font-semibold">Knapsack Optimization</span>
                        </div>
                    </div>
                    <div class="bg-[#1F4D25] flex items-center justify-center p-8 sm:p-12">
                        <div class="text-center">
                            <svg class="w-16 h-16 text-[#C8A415] mx-auto mb-4" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M8 48 Q20 32 32 36 T56 28" stroke-dasharray="4 3"/>
                                <circle cx="8" cy="48" r="5" fill="#3A7D44" stroke="white" stroke-width="2"/>
                                <circle cx="32" cy="36" r="4" fill="#C8A415" stroke="white" stroke-width="2"/>
                                <circle cx="56" cy="28" r="5" fill="#C8A415" stroke="white" stroke-width="2"/>
                                <rect x="20" y="18" width="24" height="14" rx="3" opacity="0.3" fill="#C8A415"/>
                                <path d="M26 18 L32 10 L38 18"/>
                            </svg>
                            <p class="text-white/60 text-sm">Plan routes, assign trucks, deploy</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Driver Panel -->
            <div id="role-driver" role="tabpanel" aria-labelledby="role-btn-driver" class="role-panel hidden bg-[#FAFAF5] rounded-3xl border border-[#1A2E1A]/5 overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2">
                    <div class="p-8 sm:p-12 flex flex-col justify-center">
                        <h3 class="text-2xl font-display text-[#1A2E1A] mb-4">Mobile-first dispatch with live GPS</h3>
                        <p class="text-[#1A2E1A]/60 leading-relaxed mb-6">
                            Drivers use a lightweight PWA. View stops, tap milestones as you load, and broadcast GPS — no app install needed. Works offline in low-signal areas.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <span class="px-3 py-1.5 rounded-full bg-[#3A7D44]/10 text-[#3A7D44] text-xs font-semibold">Mobile PWA</span>
                            <span class="px-3 py-1.5 rounded-full bg-[#3A7D44]/10 text-[#3A7D44] text-xs font-semibold">Live Telemetry</span>
                            <span class="px-3 py-1.5 rounded-full bg-[#3A7D44]/10 text-[#3A7D44] text-xs font-semibold">Offline Support</span>
                        </div>
                    </div>
                    <div class="bg-[#1F4D25] flex items-center justify-center p-8 sm:p-12">
                        <div class="text-center">
                            <svg class="w-16 h-16 text-[#C8A415] mx-auto mb-4" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="18" y="6" width="28" height="52" rx="6" stroke-width="2"/>
                                <rect x="22" y="14" width="20" height="32" rx="2" opacity="0.2" fill="#C8A415"/>
                                <circle cx="32" cy="52" r="2"/>
                                <path d="M28 22 L32 18 L36 22" stroke="#C8A415" stroke-width="2"/>
                                <path d="M26 34 L30 30 L34 34 L38 28" stroke="#3A7D44" stroke-width="2"/>
                                <circle cx="30" cy="40" r="2" fill="#C8A415"/>
                            </svg>
                            <p class="text-white/60 text-sm">View stops, track GPS, mark delivered</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <!-- Stats -->
    <section class="py-16 bg-[#FAFAF5]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-[#1F4D25] rounded-3xl p-8 sm:p-12 border border-white/5">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 divide-y md:divide-y-0 md:divide-x divide-white/5">
                    <div class="text-center px-6 pt-6 md:pt-0 first:pt-0">
                        <p class="font-mono text-3xl font-bold text-[#C8A415]">4</p>
                        <p class="text-xs text-white/40 mt-2 uppercase tracking-wider font-semibold">Role-Based Portals</p>
                    </div>
                    <div class="text-center px-6 pt-6 md:pt-0">
                        <p class="font-mono text-3xl font-bold text-[#C8A415]">GPS</p>
                        <p class="text-xs text-white/40 mt-2 uppercase tracking-wider font-semibold">Real-Time Tracking</p>
                    </div>
                    <div class="text-center px-6 pt-6 md:pt-0">
                        <p class="font-mono text-3xl font-bold text-[#C8A415]">PWA</p>
                        <p class="text-xs text-white/40 mt-2 uppercase tracking-wider font-semibold">Mobile-First Driver App</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section id="faq" class="py-24 bg-[#FAFAF5] scroll-mt-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl mx-auto mb-12">
                <span class="text-xs font-bold uppercase tracking-widest text-[#3A7D44]">FAQ</span>
                <h2 class="text-3xl sm:text-4xl font-display text-[#1A2E1A] mt-4">Your Questions, Answered</h2>
            </div>

            <div class="max-w-2xl mx-auto space-y-3">
                <div class="bg-white rounded-2xl border border-[#1A2E1A]/5 overflow-hidden">
                    <button onclick="toggleFaq(0)" aria-expanded="false" class="w-full px-6 py-5 flex items-center justify-between text-left">
                        <span class="text-sm font-bold text-[#1A2E1A]">How does route-pooling work?</span>
                        <span id="faq-icon-0" class="text-[#C8A415] font-bold text-lg transition-transform duration-300">+</span>
                    </button>
                    <div id="faq-0" class="faq-content px-6 pb-5">
                        <p class="text-sm text-[#1A2E1A]/50 leading-relaxed">We aggregate crop dimensions and pickup dates from local cooperatives. The routing engine sequences these farm locations along a single path, matching them to a high-capacity freight truck.</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-[#1A2E1A]/5 overflow-hidden">
                    <button onclick="toggleFaq(1)" aria-expanded="false" class="w-full px-6 py-5 flex items-center justify-between text-left">
                        <span class="text-sm font-bold text-[#1A2E1A]">Is GPS tracking secure?</span>
                        <span id="faq-icon-1" class="text-[#C8A415] font-bold text-lg transition-transform duration-300">+</span>
                    </button>
                    <div id="faq-1" class="faq-content px-6 pb-5">
                        <p class="text-sm text-[#1A2E1A]/50 leading-relaxed">Yes. Drivers broadcast location only while their route is active. Broadcasters disconnect automatically upon route completion.</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-[#1A2E1A]/5 overflow-hidden">
                    <button onclick="toggleFaq(2)" aria-expanded="false" class="w-full px-6 py-5 flex items-center justify-between text-left">
                        <span class="text-sm font-bold text-[#1A2E1A]">Is there a registration fee?</span>
                        <span id="faq-icon-2" class="text-[#C8A415] font-bold text-lg transition-transform duration-300">+</span>
                    </button>
                    <div id="faq-2" class="faq-content px-6 pb-5">
                        <p class="text-sm text-[#1A2E1A]/50 leading-relaxed">Onboarding is open for cooperatives and freight operators in Mindanao. Core coordination is accessible to support regional agriculture.</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-[#1A2E1A]/5 overflow-hidden">
                    <button onclick="toggleFaq(3)" aria-expanded="false" class="w-full px-6 py-5 flex items-center justify-between text-left">
                        <span class="text-sm font-bold text-[#1A2E1A]">How are fuel costs split?</span>
                        <span id="faq-icon-3" class="text-[#C8A415] font-bold text-lg transition-transform duration-300">+</span>
                    </button>
                    <div id="faq-3" class="faq-content px-6 pb-5">
                        <p class="text-sm text-[#1A2E1A]/50 leading-relaxed">HarvestHaul calculates proportional costs based on crop weight (tons) and pickup-to-hub distance (km) registered during scheduling.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-24 bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div>
                    <h2 class="text-3xl sm:text-4xl font-display text-[#1A2E1A] leading-tight">Get Started with HarvestHaul</h2>
                    <p class="text-[#1A2E1A]/60 mt-6 text-lg leading-relaxed">
                        Register your cooperative or freight operator account to start coordinating crop pickups and deliveries through a centralized platform.
                    </p>

                    <div class="mt-10 space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-[#3A7D44]/10 flex items-center justify-center">
                                <svg class="w-4 h-4 text-[#3A7D44]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <span class="text-sm text-[#1A2E1A]/60">hello@harvesthaul.ph</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-[#3A7D44]/10 flex items-center justify-center">
                                <svg class="w-4 h-4 text-[#3A7D44]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <span class="text-sm text-[#1A2E1A]/60">General Santos City, Mindanao</span>
                        </div>
                    </div>

                    <div class="mt-10 flex flex-wrap gap-4">
                        @guest
                            <a href="{{ route('login') }}" class="px-8 py-4 bg-[#C8A415] text-[#1A2E1A] rounded-xl font-bold hover:bg-[#C8A415]/90 transition shadow-lg">
                                Register Your Organization
                            </a>
                            <a href="{{ route('login') }}" class="px-8 py-4 bg-[#1A2E1A] text-white rounded-xl font-semibold hover:bg-[#1A2E1A]/90 transition">
                                Access Portal
                            </a>
                        @else
                            <a href="{{ url('/dashboard') }}" class="px-8 py-4 bg-[#C8A415] text-[#1A2E1A] rounded-xl font-bold hover:bg-[#C8A415]/90 transition shadow-lg">
                                Open Dashboard
                            </a>
                        @endguest
                    </div>
                </div>

                <div class="relative">
                    <div class="bg-[#FAFAF5] rounded-3xl p-8 border border-[#1A2E1A]/5">
                        <p class="text-xs font-bold uppercase tracking-widest text-[#C8A415] mb-6">Stay Updated</p>
                        <p class="text-sm text-[#1A2E1A]/60 mb-6 leading-relaxed">
                            Receive platform updates and delivery coordination tips.
                        </p>
                        <div class="flex gap-3">
                            <input type="email" placeholder="Email address" class="flex-1 px-4 py-3 rounded-xl bg-white border border-[#1A2E1A]/10 text-sm text-[#1A2E1A] placeholder:text-[#1A2E1A]/30 focus:outline-none focus:ring-2 focus:ring-[#C8A415]/30 focus:border-[#C8A415]">
                            <button class="px-6 py-3 bg-[#3A7D44] text-white rounded-xl text-sm font-bold hover:bg-[#2E6336] transition whitespace-nowrap">
                                Sign Up
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-[#1F4D25] text-white/40 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-md bg-[#3A7D44] flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8a13 13 0 0 1-10 10Z"/>
                    </svg>
                </div>
                <span class="text-sm font-bold text-white/60 font-display">HarvestHaul</span>
            </div>
            <div class="flex gap-6 text-xs">
                <a href="#about" class="hover:text-white transition">About</a>
                <a href="#services" class="hover:text-white transition">Services</a>
                <a href="#roles" class="hover:text-white transition">Portals</a>
                <a href="#faq" class="hover:text-white transition">FAQ</a>
            </div>
            <p class="text-xs">&copy; {{ date('Y') }} HarvestHaul. Mindanao.</p>
        </div>
    </footer>

    <script>
        // Header scroll
        window.addEventListener('scroll', function() {
            const header = document.getElementById('main-header');
            if (window.scrollY > 50) {
                header.className = 'fixed top-0 left-0 right-0 z-50 bg-[#1F4D25]/95 backdrop-blur-md border-b border-white/5 transition-all duration-300';
            } else {
                header.className = 'fixed top-0 left-0 right-0 z-50 transition-all duration-300';
            }
        });

        // Mobile menu
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
            if (!menu.classList.contains('hidden')) {
                document.addEventListener('click', closeMobileMenuOnBackdrop);
            }
        }
        function closeMobileMenuOnBackdrop(e) {
            const menu = document.getElementById('mobileMenu');
            const btn = document.querySelector('button[aria-label="Menu"]');
            if (!menu.contains(e.target) && !btn.contains(e.target)) {
                menu.classList.add('hidden');
                document.removeEventListener('click', closeMobileMenuOnBackdrop);
            }
        }

        // Role tabs
        function setRole(role) {
            const roles = ['farmer', 'logistics', 'driver'];
            roles.forEach(r => {
                const btn = document.getElementById(`role-btn-${r}`);
                const panel = document.getElementById(`role-${r}`);
                if (r === role) {
                    btn.className = 'role-tab px-5 py-2.5 rounded-xl text-sm font-semibold bg-[#3A7D44] text-white transition';
                    btn.setAttribute('aria-selected', 'true');
                    panel.classList.remove('hidden');
                } else {
                    btn.className = 'role-tab px-5 py-2.5 rounded-xl text-sm font-semibold bg-white text-[#1A2E1A] border border-[#1A2E1A]/10 hover:border-[#3A7D44]/30 transition';
                    btn.setAttribute('aria-selected', 'false');
                    panel.classList.add('hidden');
                }
            });
        }

        // FAQ accordion
        function toggleFaq(index) {
            const content = document.getElementById(`faq-${index}`);
            const icon = document.getElementById(`faq-icon-${index}`);
            const btn = icon.closest('button');
            if (content.classList.contains('open')) {
                content.classList.remove('open');
                icon.textContent = '+';
                icon.style.transform = 'rotate(0deg)';
                btn.setAttribute('aria-expanded', 'false');
            } else {
                content.classList.add('open');
                icon.textContent = '−';
                icon.style.transform = 'rotate(180deg)';
                btn.setAttribute('aria-expanded', 'true');
            }
        }

        // Scroll reveal
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.scroll-reveal').forEach(el => observer.observe(el));
    </script>
</body>
</html>
