<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HarvestHaul — Coordinated Crop Logistics for Mindanao</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

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
            --terracotta: #C1694F;
            --dust: #E8DCC8;
            --dust-dark: #D4C8B0;
        }

        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-display { font-family: 'Outfit', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }

        .route-path {
            stroke-dasharray: 8 4;
            animation: dash 2s linear infinite;
        }
        @keyframes dash {
            to { stroke-dashoffset: -24; }
        }

        .stat-counter {
            transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .role-card {
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .role-card:hover {
            transform: translateY(-4px);
        }
        .role-card.active {
            border-color: var(--wheat);
            box-shadow: 0 0 0 1px var(--wheat), 0 20px 40px -12px rgba(212, 165, 32, 0.15);
        }

        .faq-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .faq-content.open {
            max-height: 200px;
        }

        .hero-gradient {
            background: linear-gradient(160deg, #1A2E1A 0%, #243524 40%, #1F4D25 100%);
        }

        .gold-glow {
            box-shadow: 0 0 60px -12px rgba(212, 165, 32, 0.3);
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

        .topo-lines {
            opacity: 0.06;
        }

        @keyframes topo-drift {
            0%, 100% { transform: translateX(0) translateY(0); }
            50% { transform: translateX(8px) translateY(-4px); }
        }
        .topo-lines { animation: topo-drift 20s ease-in-out infinite; }

        @keyframes hero-fade-up {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .hero-animate { animation: hero-fade-up 0.8s cubic-bezier(0.16, 1, 0.3, 1) both; }
        .hero-animate-delay-1 { animation-delay: 0.1s; }
        .hero-animate-delay-2 { animation-delay: 0.2s; }
        .hero-animate-delay-3 { animation-delay: 0.3s; }
        .hero-animate-delay-4 { animation-delay: 0.4s; }

        .role-panel { transition: opacity 0.3s ease, transform 0.3s ease; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
            .scroll-reveal { opacity: 1; transform: none; }
            .hero-animate { opacity: 1; transform: none; }
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
                <a href="#problem" class="text-sm font-medium text-white/70 hover:text-white transition">Problem</a>
                <a href="#solution" class="text-sm font-medium text-white/70 hover:text-white transition">How It Works</a>
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
            <a href="#problem" onclick="toggleMobileMenu()" class="block text-sm font-medium text-white/70 hover:text-white py-2">Problem</a>
            <a href="#solution" onclick="toggleMobileMenu()" class="block text-sm font-medium text-white/70 hover:text-white py-2">How It Works</a>
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
    <section class="hero-gradient relative min-h-screen flex items-center overflow-hidden">
        <!-- Topographic contour lines -->
        <svg class="topo-lines absolute inset-0 w-full h-full" viewBox="0 0 1200 800" preserveAspectRatio="none" fill="none" stroke="white" stroke-width="1.2">
            <path d="M0 620 Q 150 580 300 600 T 600 560 T 900 590 T 1200 550"/>
            <path d="M0 560 Q 200 520 400 540 T 800 500 T 1200 520"/>
            <path d="M0 500 Q 180 460 360 480 T 720 440 T 1080 460 T 1200 450"/>
            <path d="M0 440 Q 220 400 440 420 T 880 380 T 1200 400"/>
            <path d="M0 380 Q 160 340 320 360 T 640 320 T 960 340 T 1200 330"/>
            <path d="M0 320 Q 200 280 400 300 T 800 260 T 1200 280"/>
            <path d="M0 260 Q 180 220 360 240 T 720 200 T 1080 220 T 1200 210"/>
            <path d="M0 200 Q 220 160 440 180 T 880 140 T 1200 160"/>
            <path d="M0 140 Q 160 100 320 120 T 640 80 T 960 100 T 1200 90"/>
            <path d="M0 80 Q 200 40 400 60 T 800 20 T 1200 40"/>
        </svg>

        <!-- Animated route overlay -->
        <svg class="absolute inset-0 w-full h-full opacity-[0.08]" viewBox="0 0 1200 800" preserveAspectRatio="none">
            <path d="M-100 600 Q 200 400 400 500 T 800 350 T 1300 450" fill="none" stroke="#C8A415" stroke-width="2" class="route-path"/>
            <path d="M-50 700 Q 300 500 500 550 T 900 400 T 1350 500" fill="none" stroke="#3A7D44" stroke-width="1.5" class="route-path" style="animation-delay: 0.5s"/>
        </svg>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 py-32 lg:py-40">
            <div class="max-w-3xl">
                <div class="hero-animate hero-animate-delay-1 inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#C8A415]/10 border border-[#C8A415]/20 mb-6">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#C8A415] animate-pulse"></span>
                    <span class="text-xs font-semibold text-[#C8A415] uppercase tracking-wider">Southern Mindanao Logistics Network</span>
                </div>

                <h1 class="hero-animate hero-animate-delay-2 text-4xl sm:text-5xl lg:text-6xl font-display text-white leading-[1.1] mb-6">
                    Your harvest deserves a <span class="text-[#C8A415]">coordinated</span> route to market
                </h1>

                <p class="hero-animate hero-animate-delay-3 text-lg text-white/60 leading-relaxed max-w-xl mb-10">
                    HarvestHaul connects small farms in Tupi, Polomolok, and General Santos with shared logistics — so you ship full loads, split costs, and arrive on time.
                </p>

                <div class="hero-animate hero-animate-delay-4 flex flex-wrap gap-4">
                    @guest
                        <a href="{{ route('login') }}" class="px-6 py-3.5 bg-[#C8A415] text-[#1A2E1A] rounded-xl font-bold text-sm hover:bg-[#C8A415]/90 transition shadow-lg gold-glow">
                            Post Your Harvest
                        </a>
                        <a href="#solution" class="px-6 py-3.5 border border-white/20 text-white rounded-xl font-semibold text-sm hover:bg-white/5 transition">
                            See How It Works
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="px-6 py-3.5 bg-[#C8A415] text-[#1A2E1A] rounded-xl font-bold text-sm hover:bg-[#C8A415]/90 transition shadow-lg gold-glow">
                            Open Dashboard
                        </a>
                    @endguest
                </div>
            </div>

            <!-- Stats strip -->
            <div class="mt-16 grid grid-cols-2 sm:grid-cols-4 gap-6 max-w-2xl">
                <div>
                    <p class="font-mono text-2xl font-bold text-[#C8A415]">3</p>
                    <p class="text-xs text-white/40 mt-1">Hub Corridors</p>
                </div>
                <div>
                    <p class="font-mono text-2xl font-bold text-[#C8A415]">92%</p>
                    <p class="text-xs text-white/40 mt-1">Space Utilization</p>
                </div>
                <div>
                    <p class="font-mono text-2xl font-bold text-[#C8A415]">₱3.4k</p>
                    <p class="text-xs text-white/40 mt-1">Avg. Fuel Saved</p>
                </div>
                <div>
                    <p class="font-mono text-2xl font-bold text-[#C8A415]">48h</p>
                    <p class="text-xs text-white/40 mt-1">Max Delivery</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Logos -->
    <section class="py-8 bg-[#1F4D25] border-t border-white/5">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-6">
            <span class="text-[10px] font-bold uppercase tracking-widest text-white/30">Trusted by cooperatives across Mindanao</span>
            <div class="flex flex-wrap items-center justify-center gap-x-8 gap-y-3">
                <span class="text-xs font-semibold text-white/40 hover:text-white/60 transition">Tupi Agri-Coop</span>
                <span class="text-xs font-semibold text-white/40 hover:text-white/60 transition">GenSan Freight Alliance</span>
                <span class="text-xs font-semibold text-white/40 hover:text-white/60 transition">Polomolok Growers</span>
                <span class="text-xs font-semibold text-white/40 hover:text-white/60 transition">Matutum Transit</span>
            </div>
        </div>
    </section>

    <!-- Problem Statement -->
    <section id="problem" class="py-24 bg-[#FAFAF5] scroll-mt-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <span class="text-xs font-bold uppercase tracking-widest text-[#C1694F]">The Problem</span>
                <h2 class="text-3xl sm:text-4xl font-display text-[#1A2E1A] mt-4 leading-tight">
                    Small farms lose money because logistics companies demand massive minimums
                </h2>
                <p class="text-[#1A2E1A]/60 mt-6 text-lg leading-relaxed">
                    A farmer with 4 tons of pineapples can't fill a 10-ton truck. So they either pay for empty space or wait until they have enough — and their harvest spoils. Meanwhile, trucks run half-empty between farm hubs.
                </p>
            </div>

            <div class="mt-16 grid grid-cols-1 sm:grid-cols-3 gap-8">
                <div class="p-6 rounded-2xl bg-white border border-[#C1694F]/10">
                    <p class="font-mono text-3xl font-bold text-[#C1694F]">40%</p>
                    <p class="text-sm text-[#1A2E1A]/50 mt-2">of small farm transport runs half-empty</p>
                </div>
                <div class="p-6 rounded-2xl bg-white border border-[#C1694F]/10">
                    <p class="font-mono text-3xl font-bold text-[#C1694F]">₱8.5k</p>
                    <p class="text-sm text-[#1A2E1A]/50 mt-2">average wasted fuel per cooperative per month</p>
                </div>
                <div class="p-6 rounded-2xl bg-white border border-[#C1694F]/10">
                    <p class="font-mono text-3xl font-bold text-[#C1694F]">3-5 days</p>
                    <p class="text-sm text-[#1A2E1A]/50 mt-2">lost coordinating between farms and freight</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Hero Image Break -->
    <section class="relative h-72 sm:h-96 overflow-hidden">
        <div class="absolute inset-0">
            <img src="{{ asset('images/hero-bg.png') }}" alt="Farm road through lush Mindanao countryside" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-r from-[#1A2E1A]/80 via-[#1A2E1A]/40 to-[#1A2E1A]/80"></div>
        </div>
        <div class="relative z-10 h-full flex items-center justify-center text-center px-4">
            <div class="max-w-xl">
                <p class="text-xs font-bold uppercase tracking-widest text-[#C8A415] mb-3">From Soil to Sale</p>
                <h2 class="text-2xl sm:text-3xl font-display text-white leading-snug">
                    Every road connects a farmer's field to a buyer's table
                </h2>
            </div>
        </div>
    </section>

    <!-- Solution: How It Works -->
    <section id="solution" class="py-24 bg-[#1F4D25] scroll-mt-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="text-xs font-bold uppercase tracking-widest text-[#C8A415]">The Solution</span>
                <h2 class="text-3xl sm:text-4xl font-display text-white mt-4">Three steps to coordinated shipping</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
                <!-- Connector line -->
                <div class="hidden md:block absolute top-12 left-[16%] right-[16%] h-px bg-gradient-to-r from-transparent via-[#C8A415]/30 to-transparent"></div>

                <!-- Step 1 -->
                <div class="relative z-10 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-[#C8A415]/10 border border-[#C8A415]/20 flex items-center justify-center mx-auto mb-6">
                        <span class="font-mono text-lg font-bold text-[#C8A415]">01</span>
                    </div>
                    <h3 class="text-lg font-bold text-white font-display">Post Your Harvest</h3>
                    <p class="text-sm text-white/50 mt-3 leading-relaxed max-w-xs mx-auto">
                        Farmers submit crop type, volume, and pickup location. Takes 2 minutes.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="relative z-10 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-[#C8A415]/10 border border-[#C8A415]/20 flex items-center justify-center mx-auto mb-6">
                        <span class="font-mono text-lg font-bold text-[#C8A415]">02</span>
                    </div>
                    <h3 class="text-lg font-bold text-white font-display">Route Gets Built</h3>
                    <p class="text-sm text-white/50 mt-3 leading-relaxed max-w-xs mx-auto">
                        Our system groups your harvest with nearby farms along the same corridor into one full truckload.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="relative z-10 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-[#C8A415]/10 border border-[#C8A415]/20 flex items-center justify-center mx-auto mb-6">
                        <span class="font-mono text-lg font-bold text-[#C8A415]">03</span>
                    </div>
                    <h3 class="text-lg font-bold text-white font-display">Track & Save</h3>
                    <p class="text-sm text-white/50 mt-3 leading-relaxed max-w-xs mx-auto">
                        Drivers broadcast GPS live. You see your crop move from farm to hub to buyer in real time.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Role Portals -->
    <section id="roles" class="py-24 bg-[#FAFAF5] scroll-mt-20">
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
            <div id="role-farmer" role="tabpanel" aria-labelledby="role-btn-farmer" class="role-panel bg-white rounded-3xl border border-[#3A7D44]/10 p-8 sm:p-12 shadow-sm">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div class="space-y-6">
                        <h3 class="text-2xl font-display text-[#1A2E1A]">Ship full loads without meeting minimums</h3>
                        <p class="text-[#1A2E1A]/60 leading-relaxed">
                            Submit your harvest. Our engine finds other farms along your highway corridor and groups your crops into a single optimized truckload. You pay only for the space you use.
                        </p>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 rounded-xl bg-[#FAFAF5] border border-[#3A7D44]/5">
                                <p class="text-sm font-bold text-[#1A2E1A]">Corridor Matching</p>
                                <p class="text-xs text-[#1A2E1A]/40 mt-1">Grouped with nearby farms</p>
                            </div>
                            <div class="p-4 rounded-xl bg-[#FAFAF5] border border-[#3A7D44]/5">
                                <p class="text-sm font-bold text-[#1A2E1A]">Fair Pricing</p>
                                <p class="text-xs text-[#1A2E1A]/40 mt-1">Pay only for your space</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-[#FAFAF5] p-6 rounded-2xl border border-[#3A7D44]/5">
                        <div class="bg-white border border-[#1A2E1A]/5 rounded-xl p-5 shadow-sm space-y-4">
                            <div class="flex justify-between items-center pb-3 border-b border-[#1A2E1A]/5">
                                <p class="text-sm font-bold text-[#1A2E1A]">New Harvest Posting</p>
                                <span class="px-2 py-0.5 text-[10px] bg-[#3A7D44]/10 text-[#3A7D44] rounded font-bold uppercase">Ready</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <p class="text-[#1A2E1A]/40">Crop</p>
                                    <p class="font-bold text-[#1A2E1A] mt-0.5">Pineapples</p>
                                </div>
                                <div>
                                    <p class="text-[#1A2E1A]/40">Volume</p>
                                    <p class="font-bold text-[#1A2E1A] mt-0.5">4.8 Metric Tons</p>
                                </div>
                                <div>
                                    <p class="text-[#1A2E1A]/40">Pickup</p>
                                    <p class="font-bold text-[#1A2E1A] mt-0.5">Tupi Highway Hub</p>
                                </div>
                                <div>
                                    <p class="text-[#1A2E1A]/40">Dispatch</p>
                                    <p class="font-bold text-[#1A2E1A] mt-0.5">May 30, 2026</p>
                                </div>
                            </div>
                            <button class="w-full bg-[#3A7D44] text-white rounded-lg py-2.5 text-xs font-bold hover:bg-[#2E6336] transition">
                                Publish to Logistics Board
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logistics Panel -->
            <div id="role-logistics" role="tabpanel" aria-labelledby="role-btn-logistics" class="role-panel hidden bg-white rounded-3xl border border-[#3A7D44]/10 p-8 sm:p-12 shadow-sm">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div class="space-y-6">
                        <h3 class="text-2xl font-display text-[#1A2E1A]">Build optimized multi-stop routes in one screen</h3>
                        <p class="text-[#1A2E1A]/60 leading-relaxed">
                            View regional farm posts, select compatible harvests, and generate sequential pickup routes. Track truck capacity and assign drivers instantly.
                        </p>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 rounded-xl bg-[#FAFAF5] border border-[#3A7D44]/5">
                                <p class="text-sm font-bold text-[#1A2E1A]">Sequential Routing</p>
                                <p class="text-xs text-[#1A2E1A]/40 mt-1">Optimal multi-stop lines</p>
                            </div>
                            <div class="p-4 rounded-xl bg-[#FAFAF5] border border-[#3A7D44]/5">
                                <p class="text-sm font-bold text-[#1A2E1A]">Fleet Monitoring</p>
                                <p class="text-xs text-[#1A2E1A]/40 mt-1">Track truck space & drivers</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-[#FAFAF5] p-6 rounded-2xl border border-[#3A7D44]/5">
                        <div class="bg-white border border-[#1A2E1A]/5 rounded-xl p-5 shadow-sm space-y-3">
                            <div class="flex justify-between items-center pb-3 border-b border-[#1A2E1A]/5">
                                <p class="text-sm font-bold text-[#1A2E1A]">Dispatch Optimizer</p>
                                <span class="px-2 py-0.5 text-[10px] bg-[#C8A415]/10 text-[#9A7D10] rounded font-bold uppercase">Route Ready</span>
                            </div>
                            <div class="space-y-2">
                                <div class="p-3 bg-[#FAFAF5] rounded-lg flex justify-between items-center text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-[#C8A415]"></span>
                                        <span class="font-semibold text-[#1A2E1A]">Stop 1: Tupi Co-op</span>
                                    </div>
                                    <span class="text-[#1A2E1A]/40">4.8T</span>
                                </div>
                                <div class="p-3 bg-[#FAFAF5] rounded-lg flex justify-between items-center text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-[#3A7D44]"></span>
                                        <span class="font-semibold text-[#1A2E1A]">Stop 2: Polomolok</span>
                                    </div>
                                    <span class="text-[#1A2E1A]/40">3.2T</span>
                                </div>
                                <div class="p-3 bg-[#1F4D25] text-white rounded-lg flex justify-between items-center text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-[#C8A415]"></span>
                                        <span class="font-semibold">GenSan Terminal</span>
                                    </div>
                                    <span class="text-[#C8A415] font-bold">8.0T / 10T</span>
                                </div>
                            </div>
                            <button class="w-full bg-[#3A7D44] text-white rounded-lg py-2.5 text-xs font-bold hover:bg-[#2E6336] transition">
                                Deploy Route
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Driver Panel -->
            <div id="role-driver" role="tabpanel" aria-labelledby="role-btn-driver" class="role-panel hidden bg-white rounded-3xl border border-[#3A7D44]/10 p-8 sm:p-12 shadow-sm">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div class="space-y-6">
                        <h3 class="text-2xl font-display text-[#1A2E1A]">Mobile-first dispatch with live GPS</h3>
                        <p class="text-[#1A2E1A]/60 leading-relaxed">
                            Drivers use a lightweight PWA. View stops, tap milestones as you load, and broadcast GPS — no app install needed.
                        </p>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 rounded-xl bg-[#FAFAF5] border border-[#3A7D44]/5">
                                <p class="text-sm font-bold text-[#1A2E1A]">Mobile PWA</p>
                                <p class="text-xs text-[#1A2E1A]/40 mt-1">Works in low signal</p>
                            </div>
                            <div class="p-4 rounded-xl bg-[#FAFAF5] border border-[#3A7D44]/5">
                                <p class="text-sm font-bold text-[#1A2E1A]">Live Telemetry</p>
                                <p class="text-xs text-[#1A2E1A]/40 mt-1">Browser GPS broadcast</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-center">
                        <div class="w-56 bg-[#1A2E1A] border-[5px] border-[#3D2517] rounded-[32px] p-4 text-white aspect-[9/18] shadow-2xl">
                            <div class="absolute top-2 left-1/2 -translate-x-1/2 w-16 h-3.5 rounded-full bg-[#3D2517]"></div>
                            <div class="pt-4 space-y-3">
                                <div class="text-center">
                                    <p class="text-[9px] text-white/40 font-bold uppercase tracking-wider">Active Route</p>
                                    <p class="text-xs font-bold text-white mt-0.5">#HH-409</p>
                                </div>
                                <div class="space-y-2 text-left">
                                    <div class="p-2.5 rounded-xl bg-[#3A7D44]/15 border border-[#3A7D44]/20 flex gap-2">
                                        <span class="text-[#C8A415] font-bold text-[10px]">✓</span>
                                        <div>
                                            <p class="text-[10px] font-bold text-white">Tupi Hub</p>
                                            <p class="text-[8px] text-[#C8A415] font-semibold">4.8T loaded</p>
                                        </div>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-white/5 border border-white/10 flex gap-2">
                                        <span class="w-3 h-3 rounded-full bg-white/10 flex items-center justify-center text-[8px] text-white/40">2</span>
                                        <div class="flex-1">
                                            <p class="text-[10px] font-bold text-white">Polomolok</p>
                                            <p class="text-[8px] text-white/40">3.2T pending</p>
                                            <button class="w-full mt-1.5 bg-[#C8A415] text-[#1A2E1A] rounded py-1 text-[9px] font-bold">Mark Loaded</button>
                                        </div>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-white/5 border border-white/10 opacity-50 flex gap-2">
                                        <span class="w-3 h-3 rounded-full bg-white/10 flex items-center justify-center text-[8px] text-white/40">3</span>
                                        <div>
                                            <p class="text-[10px] font-bold text-white/60">GenSan</p>
                                            <p class="text-[8px] text-white/30">Final drop-off</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white/5 p-2 rounded-xl border border-white/10 flex items-center justify-between">
                                    <div>
                                        <p class="text-[8px] text-white/40 uppercase font-bold">GPS</p>
                                        <p class="text-[9px] text-[#C8A415] font-bold">Active</p>
                                    </div>
                                    <div class="w-7 h-3.5 rounded-full bg-[#3A7D44] p-0.5 flex justify-end">
                                        <div class="w-2.5 h-2.5 rounded-full bg-white"></div>
                                    </div>
                                </div>
                            </div>
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
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 divide-x divide-white/5">
                    <div class="text-center md:text-left px-4 first:pl-0">
                        <p class="font-mono text-3xl font-bold text-[#C8A415]">₱3,450</p>
                        <p class="text-xs text-white/40 mt-2 uppercase tracking-wider font-semibold">Avg. Fuel Saved Per Trip</p>
                    </div>
                    <div class="text-center md:text-left px-4">
                        <p class="font-mono text-3xl font-bold text-[#C8A415]">92.6%</p>
                        <p class="text-xs text-white/40 mt-2 uppercase tracking-wider font-semibold">Truck Space Utilized</p>
                    </div>
                    <div class="text-center md:text-left px-4">
                        <p class="font-mono text-3xl font-bold text-[#C8A415]">48h</p>
                        <p class="text-xs text-white/40 mt-2 uppercase tracking-wider font-semibold">Farm to Terminal Max</p>
                    </div>
                    <div class="text-center md:text-left px-4">
                        <p class="font-mono text-3xl font-bold text-[#C8A415]">2 min</p>
                        <p class="text-xs text-white/40 mt-2 uppercase tracking-wider font-semibold">Harvest Posting Time</p>
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
                <h2 class="text-3xl sm:text-4xl font-display text-[#1A2E1A] mt-4">Common questions</h2>
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
                        <p class="text-sm text-[#1A2E1A]/50 leading-relaxed">HARVEST calculates proportional costs based on crop weight (tons) and pickup-to-hub distance (km) registered during scheduling.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-24 bg-[#2E6336]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-5xl font-display text-white leading-tight">Ready to coordinate your shipments?</h2>
            <p class="text-white/60 mt-6 text-lg max-w-xl mx-auto">
                Join cooperatives across Tupi, Polomolok, and General Santos who are already saving on logistics.
            </p>
            <div class="mt-10 flex flex-wrap justify-center gap-4">
                @guest
                    <a href="{{ route('login') }}" class="px-8 py-4 bg-[#C8A415] text-[#1A2E1A] rounded-xl font-bold hover:bg-[#C8A415]/90 transition shadow-lg gold-glow">
                        Register Your Organization
                    </a>
                    <a href="{{ route('login') }}" class="px-8 py-4 bg-white/10 text-white rounded-xl font-semibold hover:bg-white/15 border border-white/20 transition">
                        Access Portal
                    </a>
                @else
                    <a href="{{ url('/dashboard') }}" class="px-8 py-4 bg-[#C8A415] text-[#1A2E1A] rounded-xl font-bold hover:bg-[#C8A415]/90 transition shadow-lg gold-glow">
                        Open Dashboard
                    </a>
                @endguest
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
                <a href="#problem" class="hover:text-white transition">Problem</a>
                <a href="#solution" class="hover:text-white transition">How It Works</a>
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