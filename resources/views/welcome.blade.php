<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="32x32" href="/favicon-32x32.png">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#16283C">
    <meta name="description" content="HarvestHaul — B2B crop distribution and logistics platform connecting farmers, buyers, logistics partners, and drivers in General Santos City and Polomolok. Streamline your agribusiness operations.">
    <meta property="og:title" content="HarvestHaul — Coordinated Crop Logistics for Mindanao">
    <meta property="og:description" content="B2B crop distribution and logistics platform connecting farmers, buyers, logistics partners, and drivers in General Santos City and Polomolok.">
    <meta property="og:image" content="{{ asset('images/hero-bg.webp') }}">
    <meta property="og:type" content="website">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').then(function(reg) {
                    console.log('Service Worker registered: ', reg.scope);
                }).catch(function(err) {
                    console.error('Service Worker registration failed: ', err);
                });
            });
        }
    </script>
    <title>HarvestHaul — Coordinated Crop Logistics for Mindanao</title>

    <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preload" as="image" href="{{ asset('images/hero-bg.webp') }}">

    <style>
        :root {
            --soil: #17202B;
            --leaf: #16283C;
            --leaf-dark: #0E1620;
            --wheat: #F26B5E;
        }

        body { font-family: 'DM Sans', sans-serif; }
        .font-display { font-family: 'Schibsted Grotesk', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }

        .faq-content {
            display: grid;
            grid-template-rows: 0fr;
            overflow: hidden;
            transition: grid-template-rows 0.3s ease-out;
        }
        .faq-content.open {
            grid-template-rows: 1fr;
        }
        .faq-content > * {
            min-height: 0;
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

        #main-header.scrolled {
            background: rgba(22, 40, 60, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

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
<body class="bg-[#FAFAFA] text-[#17202B] antialiased overflow-x-hidden">

    <!-- Header -->
    <header id="main-header" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex justify-between items-center">
            <a href="/" class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-lg bg-[#16283C] flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round" style="filter: drop-shadow(0.6px 1px 0.8px rgba(14, 22, 32,0.45));">
                        <defs>
                            <linearGradient id="hh-logo-wheat" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#FFFFFF"/>
                                <stop offset="55%" stop-color="#D7BC7A"/>
                                <stop offset="100%" stop-color="#BFA05A"/>
                            </linearGradient>
                        </defs>
                        <path d="M2 22 16 8" stroke="#FFFFFF" stroke-width="2"/>
                        <path d="M3.47 12.53 5 11l1.53 1.53a3.5 3.5 0 0 1 0 4.94L5 19l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z" stroke="#FFFFFF" stroke-width="2"/>
                        <path d="M7.47 8.53 9 7l1.53 1.53a3.5 3.5 0 0 1 0 4.94L9 15l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z" stroke="#FFFFFF" stroke-width="2"/>
                        <path d="M11.47 4.53 13 3l1.53 1.53a3.5 3.5 0 0 1 0 4.94L13 11l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z" stroke="#FFFFFF" stroke-width="2"/>
                        <path d="M20 2h2v2a4 4 0 0 1-4 4h-2V6a4 4 0 0 1 4-4Z" stroke="url(#hh-logo-wheat)" stroke-width="2"/>
                        <path d="M11.47 17.47 13 19l-1.53 1.53a3.5 3.5 0 0 1-4.94 0L5 19l1.53-1.53a3.5 3.5 0 0 1 4.94 0Z" stroke="url(#hh-logo-wheat)" stroke-width="2"/>
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
                        <a href="{{ url('/dashboard') }}" class="px-4 py-2 bg-[#F26B5E] text-[#17202B] rounded-lg text-sm font-bold hover:bg-[#F26B5E]/90 transition">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-white/70 hover:text-white transition px-3 py-2">
                            Log in
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ url('/register') }}" class="px-4 py-2 bg-[#F26B5E] text-[#17202B] rounded-lg text-sm font-bold hover:bg-[#F26B5E]/90 transition">
                                Get Started
                            </a>
                        @endif
                    @endauth
                @endif
            </div>

            <button onclick="toggleMobileMenu()" class="md:hidden w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center text-white" aria-label="Menu">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>

        <div id="mobileMenu" class="hidden md:hidden bg-[#0E1620]/95 backdrop-blur-xl border-t border-white/10 px-6 py-4 space-y-3">
            <a href="#about" onclick="toggleMobileMenu()" class="block text-sm font-medium text-white/70 hover:text-white py-2">About</a>
            <a href="#services" onclick="toggleMobileMenu()" class="block text-sm font-medium text-white/70 hover:text-white py-2">Services</a>
            <a href="#roles" onclick="toggleMobileMenu()" class="block text-sm font-medium text-white/70 hover:text-white py-2">Portals</a>
            <a href="#faq" onclick="toggleMobileMenu()" class="block text-sm font-medium text-white/70 hover:text-white py-2">FAQ</a>
            <div class="pt-3 border-t border-white/10 space-y-2">
                @guest
                    <a href="{{ url('/login') }}" class="block w-full text-center px-4 py-2.5 bg-white/10 text-white rounded-lg font-bold text-sm">Log in</a>
                    <a href="{{ url('/register') }}" class="block w-full text-center px-4 py-2.5 bg-[#F26B5E] text-[#17202B] rounded-lg font-bold text-sm">Get Started</a>
                @else
                    <a href="{{ url('/dashboard') }}" class="block w-full text-center px-4 py-2.5 bg-[#F26B5E] text-[#17202B] rounded-lg font-bold text-sm">Dashboard</a>
                @endguest
            </div>
        </div>
    </header>

    <main>
        <!-- Hero -->
        <section class="relative min-h-[90vh] flex items-center overflow-hidden">
        <div class="absolute inset-0">
            <img src="{{ asset('images/hero-bg.webp') }}" alt="Lush farmlands of Mindanao" width="1024" height="1024" fetchpriority="high" decoding="async" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-r from-[#0E1620]/85 via-[#0E1620]/60 to-[#0E1620]/40"></div>
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
                        <a href="{{ url('/register') }}" class="px-6 py-3.5 bg-[#F26B5E] text-[#17202B] rounded-xl font-bold text-sm hover:bg-[#F26B5E]/90 transition shadow-lg">
                            Get Started
                        </a>
                        <a href="#about" class="px-6 py-3.5 border border-white/20 text-white rounded-xl font-semibold text-sm hover:bg-white/5 transition">
                            Learn More
                        </a>
                    @else
                        <a href="{{ url('/dashboard') }}" class="px-6 py-3.5 bg-[#F26B5E] text-[#17202B] rounded-xl font-bold text-sm hover:bg-[#F26B5E]/90 transition shadow-lg">
                            Open Dashboard
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </section>

    <!-- Sneak Peek -->
    <section id="preview" class="py-24 bg-[#0E1620] scroll-mt-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12 scroll-reveal">
                <h2 class="text-3xl sm:text-4xl font-display text-white">A peek inside the portal</h2>
                <p class="text-white/60 mt-4 text-lg">A glance at the farmer dashboard, as it looks in the live app. Sample data shown for illustration only.</p>
            </div>

            <div class="scroll-reveal">
                <div class="bg-white rounded-3xl overflow-hidden border border-white/10 shadow-2xl">
                    <div class="flex">
                        <div class="hidden sm:flex w-16 flex-col items-center py-6 gap-4 bg-[#16283C]">
                            <span class="text-[10px] font-extrabold tracking-tight text-[#D7BC7A] font-display">HH</span>
                            <span class="w-8 h-8 rounded-lg bg-white/5 text-white/70 text-xs font-bold flex items-center justify-center border border-white/10">D</span>
                            <span class="w-8 h-8 rounded-lg bg-white/5 text-white/70 text-xs font-bold flex items-center justify-center border border-white/10">G</span>
                            <span class="w-8 h-8 rounded-lg bg-white/5 text-white/70 text-xs font-bold flex items-center justify-center border border-white/10">S</span>
                            <span class="w-8 h-8 rounded-lg bg-white/5 text-white/70 text-xs font-bold flex items-center justify-center border border-white/10">T</span>
                            <span class="w-8 h-8 rounded-lg bg-[#BFA05A]/25 text-[#D7BC7A] text-xs font-bold flex items-center justify-center border border-[#BFA05A]/40">M</span>
                        </div>
                        <div class="flex-1 bg-[#FAFAFA] p-5 sm:p-7">
                            <div class="flex items-center justify-between mb-5">
                                <p class="text-sm font-extrabold text-[#17202B]">Farmer Dashboard</p>
                                <span class="text-[10px] font-extrabold uppercase tracking-widest text-[#7C6527] bg-[#BFA05A]/15 border border-[#BFA05A]/30 px-2 py-1 rounded">Sample</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="border-l-4 border-l-[#16283C] bg-white rounded-2xl p-4 shadow-sm">
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Active Harvests</p>
                                    <p class="text-2xl font-extrabold text-[#16283C] heading-font mt-1">3</p>
                                    <div class="pt-2 mt-2 border-t border-slate-100 flex flex-wrap gap-1.5">
                                        <span class="text-[9px] font-semibold text-slate-600 bg-slate-50 border border-slate-200 px-2 py-0.5 rounded">240 kg rice</span>
                                        <span class="text-[9px] font-semibold text-slate-600 bg-slate-50 border border-slate-200 px-2 py-0.5 rounded">310 kg banana</span>
                                    </div>
                                </div>
                                <div class="border-l-4 border-l-white/25 bg-[#16283C] rounded-2xl p-4 text-white shadow-sm">
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-white/70">Season Budget</p>
                                    <p class="text-2xl font-extrabold text-white heading-font mt-1">₱38,500 <span class="text-xs font-semibold text-white/70">per kg haul rate</span></p>
                                    <div class="pt-2 mt-2 border-t border-white/15 flex flex-wrap gap-1.5">
                                        <span class="text-[9px] font-semibold text-white/80 bg-white/10 border border-white/15 px-2 py-0.5 rounded">₱2.50/kg agreed</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 bg-white rounded-2xl border border-slate-200/60 overflow-hidden">
                                <div class="flex items-center justify-between px-4 py-2.5 border-b border-slate-100 bg-slate-50/50">
                                    <p class="text-[10px] font-extrabold uppercase tracking-widest text-[#16283C]">DA RFO12 — Market Prices</p>
                                    <span class="text-[9px] font-bold text-slate-500">Today</span>
                                </div>
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="border-b border-slate-100">
                                            <th class="px-4 py-2 text-[9px] font-extrabold uppercase tracking-widest text-slate-500">Category</th>
                                            <th class="px-3 py-2 text-[9px] font-extrabold uppercase tracking-widest text-slate-500">Commodity</th>
                                            <th class="px-3 py-2 text-[9px] font-extrabold uppercase tracking-widest text-slate-500 text-right">Low</th>
                                            <th class="px-3 py-2 text-[9px] font-extrabold uppercase tracking-widest text-slate-500 text-right">High</th>
                                            <th class="px-3 py-2 text-[9px] font-extrabold uppercase tracking-widest text-slate-500 text-right">Common</th>
                                            <th class="px-4 py-2 text-[9px] font-extrabold uppercase tracking-widest text-slate-500 text-right">DPI</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <tr>
                                            <td class="px-4 py-2 text-[10px] font-bold text-slate-500">Fruit &amp; Veg</td>
                                            <td class="px-3 py-2 text-[11px] font-bold text-slate-800">Banana (Lakatan)</td>
                                            <td class="px-3 py-2 text-[11px] text-slate-700 text-right">25</td>
                                            <td class="px-3 py-2 text-[11px] text-slate-700 text-right">35</td>
                                            <td class="px-3 py-2 text-[11px] text-slate-700 text-right">32</td>
                                            <td class="px-4 py-2 text-[11px] font-bold text-[#16283C] text-right">30</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-2 text-[10px] font-bold text-slate-500">Fruit &amp; Veg</td>
                                            <td class="px-3 py-2 text-[11px] font-bold text-slate-800">Banana (Saba)</td>
                                            <td class="px-3 py-2 text-[11px] text-slate-700 text-right">20</td>
                                            <td class="px-3 py-2 text-[11px] text-slate-700 text-right">28</td>
                                            <td class="px-3 py-2 text-[11px] text-slate-700 text-right">25</td>
                                            <td class="px-4 py-2 text-[11px] font-bold text-[#16283C] text-right">23</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4 bg-white rounded-2xl border border-slate-200/60 px-4 py-3 flex flex-wrap items-center gap-4">
                                <span class="text-[9px] font-extrabold uppercase tracking-widest text-slate-500">Route</span>
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#BFA05A]"></span>
                                    <span class="text-[10px] font-semibold text-slate-600">Farm A</span>
                                    <span class="w-14 border-t-2 border-dashed border-[#16283C]/30"></span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#BFA05A]"></span>
                                    <span class="text-[10px] font-semibold text-slate-600">Farm B</span>
                                    <span class="w-14 border-t-2 border-dashed border-[#16283C]/30"></span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#16283C]"></span>
                                    <span class="text-[10px] font-bold text-slate-800">Hub</span>
                                </div>
                                <span class="text-[9px] font-semibold text-slate-500 bg-slate-50 border border-slate-200 px-2 py-0.5 rounded">2 stops</span>
                                <span class="text-[9px] font-semibold text-slate-500 bg-slate-50 border border-slate-200 px-2 py-0.5 rounded">38 km</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap justify-center gap-2.5">
                    <span class="px-3 py-1.5 rounded-full bg-white/5 border border-white/10 text-white/70 text-xs font-semibold">Live GPS</span>
                    <span class="px-3 py-1.5 rounded-full bg-white/5 border border-white/10 text-white/70 text-xs font-semibold">Knapsack Route Pooling</span>
                    <span class="px-3 py-1.5 rounded-full bg-white/5 border border-white/10 text-white/70 text-xs font-semibold">DA RFO12 Prices</span>
                    <span class="px-3 py-1.5 rounded-full bg-white/5 border border-white/10 text-white/70 text-xs font-semibold">Cost Split + Invoice</span>
                </div>
            </div>
        </div>
    </section>

    <!-- About -->
    <section id="about" class="py-24 bg-[#FAFAFA] scroll-mt-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl scroll-reveal">
                <h2 class="text-3xl sm:text-4xl font-display text-[#17202B] leading-tight">
                    A localized digital solution for crop distribution and logistics coordination
                </h2>
                <p class="text-[#64748B] mt-6 text-lg leading-relaxed">
                    HarvestHaul addresses the challenges of fragmented communication, underutilized vehicles, and limited delivery visibility in agricultural transport. By integrating mapping, route planning, tracking, and reporting into one web-based platform, we aim to support a more organized and efficient transport process for registered stakeholders in General Santos City and Polomolok.
                </p>
            </div>
        </div>
    </section>

    <!-- Services -->
    <section id="services" class="py-24 bg-[#FAFAFA] scroll-mt-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16 scroll-reveal">
                <h2 class="text-3xl sm:text-4xl font-display text-[#17202B]">Our Services</h2>
            </div>

            <div class="space-y-0 divide-y divide-[#17202B]/5">
                <!-- Service 01 -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 py-10 items-start scroll-reveal">
                    <div class="lg:col-span-1">
                        <span class="font-mono text-sm font-bold text-[#C23A2E]">01</span>
                    </div>
                    <div class="lg:col-span-4">
                        <h3 class="text-xl font-display text-[#17202B]">Route-Pooling Logistics</h3>
                    </div>
                    <div class="lg:col-span-4">
                        <p class="text-[#64748B] leading-relaxed">
                            We aggregate crop dimensions and pickup dates from posted harvests and partner cooperatives. The routing engine sequences farm locations along a single path, matching them to high-capacity freight trucks.
                        </p>
                    </div>
                    <div class="lg:col-span-3">
                        <div class="w-full h-32 rounded-2xl bg-[#0E1620]/5 flex items-center justify-center">
                            <svg class="w-10 h-10" viewBox="0 0 48 48" fill="none" stroke-linecap="round" stroke-linejoin="round" style="filter: drop-shadow(1px 2px 2px rgba(14, 22, 32,0.35));">
                                <defs>
                                    <linearGradient id="s1-line" x1="6" y1="38" x2="42" y2="12" gradientUnits="userSpaceOnUse">
                                        <stop offset="0%" stop-color="#D7BC7A"/>
                                        <stop offset="100%" stop-color="#0E1620"/>
                                    </linearGradient>
                                    <radialGradient id="s1-g" cx="35%" cy="30%" r="80%">
                                        <stop offset="0%" stop-color="#bfd6c9"/>
                                        <stop offset="100%" stop-color="#0E1620"/>
                                    </radialGradient>
                                    <radialGradient id="s1-a" cx="35%" cy="30%" r="80%">
                                        <stop offset="0%" stop-color="#F98B80"/>
                                        <stop offset="100%" stop-color="#C23A2E"/>
                                    </radialGradient>
                                </defs>
                                <path d="M6 38 L18 22 L30 28 L42 12" stroke="url(#s1-line)" stroke-width="4"/>
                                <g stroke="#0E1620" stroke-width="1">
                                    <circle cx="6" cy="38" r="4.5" fill="url(#s1-g)"/>
                                    <circle cx="18" cy="22" r="4.5" fill="url(#s1-a)"/>
                                    <circle cx="30" cy="28" r="4.5" fill="url(#s1-a)"/>
                                    <circle cx="42" cy="12" r="4.5" fill="url(#s1-g)"/>
                                </g>
                                <circle cx="5" cy="36.8" r="1.3" fill="#FFFFFF" opacity="0.7"/>
                                <circle cx="17" cy="20.8" r="1.3" fill="#FFFFFF" opacity="0.7"/>
                                <circle cx="29" cy="26.8" r="1.3" fill="#FFFFFF" opacity="0.7"/>
                                <circle cx="41" cy="10.8" r="1.3" fill="#FFFFFF" opacity="0.7"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Service 02 -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 py-10 items-start scroll-reveal">
                    <div class="lg:col-span-1">
                        <span class="font-mono text-sm font-bold text-[#C23A2E]">02</span>
                    </div>
                    <div class="lg:col-span-4">
                        <h3 class="text-xl font-display text-[#17202B]">Real-Time GPS Tracking</h3>
                    </div>
                    <div class="lg:col-span-4">
                        <p class="text-[#64748B] leading-relaxed">
                            Drivers broadcast GPS location live. You see your crop move from farm to hub to buyer in real time, with delay detection and weather-aware ETA predictions.
                        </p>
                    </div>
                    <div class="lg:col-span-3">
                        <div class="w-full h-32 rounded-2xl bg-[#0E1620]/5 flex items-center justify-center">
                            <svg class="w-10 h-10" viewBox="0 0 48 48" fill="none" stroke-linecap="round" stroke-linejoin="round" style="filter: drop-shadow(1px 2px 2px rgba(14, 22, 32,0.35));">
                                <defs>
                                    <linearGradient id="s2-pin" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#D7BC7A"/>
                                        <stop offset="100%" stop-color="#0E1620"/>
                                    </linearGradient>
                                    <radialGradient id="s2-center" cx="35%" cy="30%" r="80%">
                                        <stop offset="0%" stop-color="#F98B80"/>
                                        <stop offset="100%" stop-color="#C23A2E"/>
                                    </radialGradient>
                                    <radialGradient id="s2-dot" cx="35%" cy="30%" r="80%">
                                        <stop offset="0%" stop-color="#bfd6c9"/>
                                        <stop offset="100%" stop-color="#0E1620"/>
                                    </radialGradient>
                                </defs>
                                <circle cx="24" cy="20" r="14" stroke="#16283C" stroke-opacity="0.35" stroke-width="2"/>
                                <circle cx="24" cy="20" r="8" stroke="url(#s2-pin)" stroke-width="2.5"/>
                                <path d="M24 6V2M24 38v-4M10 20H6M42 20h-4" stroke="url(#s2-pin)" stroke-width="2.5"/>
                                <circle cx="24" cy="20" r="4" fill="url(#s2-center)"/>
                                <circle cx="23" cy="19" r="1.1" fill="#FFFFFF" opacity="0.7"/>
                                <path d="M24 34 L18 44 L24 40 L30 44 Z" fill="url(#s2-pin)"/>
                                <path d="M24 37 L21.5 42 L24 40.2 L26.5 42 Z" fill="#FFFFFF" opacity="0.3"/>
                                <circle cx="8" cy="8" r="1.6" fill="url(#s2-dot)" stroke="#0E1620" stroke-width="0.8"/>
                                <circle cx="40" cy="34" r="1.6" fill="url(#s2-dot)" stroke="#0E1620" stroke-width="0.8"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Service 03 -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 py-10 items-start scroll-reveal">
                    <div class="lg:col-span-1">
                        <span class="font-mono text-sm font-bold text-[#C23A2E]">03</span>
                    </div>
                    <div class="lg:col-span-4">
                        <h3 class="text-xl font-display text-[#17202B]">Cost-Split Management</h3>
                    </div>
                    <div class="lg:col-span-4">
                        <p class="text-[#64748B] leading-relaxed">
                            Per-farmer cost allocation based on the hauling rate agreed in each negotiation chat, applied to each farmer's crop weight. Automated invoicing and payment tracking between all parties.
                        </p>
                    </div>
                    <div class="lg:col-span-3">
                        <div class="w-full h-32 rounded-2xl bg-[#0E1620]/5 flex items-center justify-center">
                            <svg class="w-10 h-10" viewBox="0 0 48 48" fill="none" stroke-linecap="round" stroke-linejoin="round" style="filter: drop-shadow(1px 2px 2px rgba(14, 22, 32,0.35));">
                                <defs>
                                    <linearGradient id="s3-grid" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#D7BC7A"/>
                                        <stop offset="100%" stop-color="#0E1620"/>
                                    </linearGradient>
                                    <radialGradient id="s3-coin" cx="35%" cy="30%" r="80%">
                                        <stop offset="0%" stop-color="#F98B80"/>
                                        <stop offset="100%" stop-color="#C23A2E"/>
                                    </radialGradient>
                                </defs>
                                <g stroke="#0E1620" stroke-width="1.5">
                                    <rect x="8" y="8" width="32" height="32" rx="5" fill="url(#s3-grid)"/>
                                </g>
                                <rect x="9" y="9" width="30" height="30" rx="4" fill="none" stroke="#FFFFFF" stroke-opacity="0.28" stroke-width="1.2"/>
                                <path d="M8 18h32M18 8v32" stroke="#FFFFFF" stroke-opacity="0.5" stroke-width="1.4"/>
                                <circle cx="30" cy="30" r="6.5" fill="url(#s3-coin)" stroke="#C23A2E" stroke-width="1"/>
                                <path d="M28 30h4M30 28v4" stroke="#FFFFFF" stroke-width="2.2" stroke-linecap="round"/>
                                <circle cx="28.4" cy="28.4" r="1.4" fill="#FFFFFF" opacity="0.6"/>
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
            <div class="text-center max-w-2xl mx-auto mb-12 scroll-reveal">
                <h2 class="text-3xl sm:text-4xl font-display text-[#17202B]">Built for each seat at the table</h2>
            </div>

            <!-- Role tabs -->
            <div class="flex justify-center gap-2 mb-10" role="tablist" aria-label="Role portals">
                <button onclick="setRole('farmer')" id="role-btn-farmer" role="tab" aria-selected="true" aria-controls="role-farmer" class="role-tab px-5 py-2.5 rounded-xl text-sm font-semibold bg-[#16283C] text-white transition">
                    Farmers
                </button>
                <button onclick="setRole('logistics')" id="role-btn-logistics" role="tab" aria-selected="false" aria-controls="role-logistics" class="role-tab px-5 py-2.5 rounded-xl text-sm font-semibold bg-white text-[#17202B] border border-[#17202B]/10 hover:border-[#16283C]/30 transition">
                    Logistics
                </button>
                <button onclick="setRole('driver')" id="role-btn-driver" role="tab" aria-selected="false" aria-controls="role-driver" class="role-tab px-5 py-2.5 rounded-xl text-sm font-semibold bg-white text-[#17202B] border border-[#17202B]/10 hover:border-[#16283C]/30 transition">
                    Drivers
                </button>
                <button onclick="setRole('buyer')" id="role-btn-buyer" role="tab" aria-selected="false" aria-controls="role-buyer" class="role-tab px-5 py-2.5 rounded-xl text-sm font-semibold bg-white text-[#17202B] border border-[#17202B]/10 hover:border-[#16283C]/30 transition">
                    Buyers
                </button>
            </div>

            <!-- Farmer Panel -->
            <div id="role-farmer" role="tabpanel" aria-labelledby="role-btn-farmer" class="role-panel bg-[#FAFAFA] rounded-3xl border border-[#17202B]/5 overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2">
                    <div class="p-8 sm:p-12 flex flex-col justify-center">
                        <h3 class="text-2xl font-display text-[#17202B] mb-4">Ship full loads without meeting minimums</h3>
                        <p class="text-[#64748B] leading-relaxed mb-6">
                            Submit your harvest. Our engine finds nearby farms within your pickup radius and groups compatible crops into a single optimized truckload. You pay only for the space you use.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <span class="px-3 py-1.5 rounded-full bg-[#BFA05A]/15 text-[#7C6527] text-xs font-semibold">Radius Matching</span>
                            <span class="px-3 py-1.5 rounded-full bg-[#16283C]/10 text-[#16283C] text-xs font-semibold">Proportional Cost Split</span>
                            <span class="px-3 py-1.5 rounded-full bg-[#16283C]/10 text-[#16283C] text-xs font-semibold">Quick Crop Posting</span>
                        </div>
                    </div>
                    <div class="bg-[#0E1620] flex items-center justify-center p-8 sm:p-12">
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto mb-4" viewBox="0 0 64 64" fill="none" stroke-linecap="round" stroke-linejoin="round" style="filter: drop-shadow(1px 3px 3px rgba(0,0,0,0.35));">
                                <defs>
                                    <radialGradient id="rf-drop" cx="38%" cy="28%" r="85%">
                                        <stop offset="0%" stop-color="#F98B80"/>
                                        <stop offset="55%" stop-color="#F26B5E"/>
                                        <stop offset="100%" stop-color="#C23A2E"/>
                                    </radialGradient>
                                    <radialGradient id="rf-core" cx="38%" cy="28%" r="85%">
                                        <stop offset="0%" stop-color="#D7BC7A"/>
                                        <stop offset="100%" stop-color="#0E1620"/>
                                    </radialGradient>
                                    <linearGradient id="rf-base" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#16283C"/>
                                        <stop offset="100%" stop-color="#0E1620"/>
                                    </linearGradient>
                                </defs>
                                <path d="M32 8 C20 8 12 16 12 28 C12 40 32 56 32 56 C32 56 52 40 52 28 C52 16 44 8 32 8Z" fill="url(#rf-drop)"/>
                                <ellipse cx="25" cy="18" rx="7" ry="12" fill="#FFFFFF" opacity="0.28" transform="rotate(-28 25 18)"/>
                                <circle cx="32" cy="26" r="8" fill="url(#rf-core)"/>
                                <circle cx="29.4" cy="23.4" r="2" fill="#FFFFFF" opacity="0.55"/>
                                <path d="M24 40 L32 34 L40 40" stroke="url(#rf-base)" stroke-width="3.5"/>
                            </svg>
                            <p class="text-white/60 text-sm">Post harvest, get matched, ship full</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logistics Panel -->
            <div id="role-logistics" role="tabpanel" aria-labelledby="role-btn-logistics" class="role-panel hidden bg-[#FAFAFA] rounded-3xl border border-[#17202B]/5 overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2">
                    <div class="p-8 sm:p-12 flex flex-col justify-center">
                        <h3 class="text-2xl font-display text-[#17202B] mb-4">Build optimized multi-stop routes in one screen</h3>
                        <p class="text-[#64748B] leading-relaxed mb-6">
                            View regional farm posts, select compatible harvests, and generate sequential pickup routes. Track truck capacity and assign drivers instantly.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <span class="px-3 py-1.5 rounded-full bg-[#F26B5E]/10 text-[#E14B3D] text-xs font-semibold">Sequential Routing</span>
                            <span class="px-3 py-1.5 rounded-full bg-[#F26B5E]/10 text-[#E14B3D] text-xs font-semibold">Fleet Monitoring</span>
                            <span class="px-3 py-1.5 rounded-full bg-[#F26B5E]/10 text-[#E14B3D] text-xs font-semibold">Knapsack Optimization</span>
                        </div>
                    </div>
                    <div class="bg-[#0E1620] flex items-center justify-center p-8 sm:p-12">
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto mb-4" viewBox="0 0 64 64" fill="none" stroke-linecap="round" stroke-linejoin="round" style="filter: drop-shadow(1px 3px 3px rgba(0,0,0,0.35));">
                                <defs>
                                    <radialGradient id="rl-g" cx="35%" cy="30%" r="80%">
                                        <stop offset="0%" stop-color="#D7BC7A"/>
                                        <stop offset="100%" stop-color="#0E1620"/>
                                    </radialGradient>
                                    <radialGradient id="rl-a" cx="35%" cy="30%" r="80%">
                                        <stop offset="0%" stop-color="#F98B80"/>
                                        <stop offset="100%" stop-color="#C23A2E"/>
                                    </radialGradient>
                                    <linearGradient id="rl-truck" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#F26B5E"/>
                                        <stop offset="100%" stop-color="#E14B3D"/>
                                    </linearGradient>
                                    <linearGradient id="rl-road" x1="0" y1="0" x2="1" y2="0">
                                        <stop offset="0%" stop-color="#F26B5E"/>
                                        <stop offset="100%" stop-color="#C23A2E"/>
                                    </linearGradient>
                                </defs>
                                <path d="M8 48 Q20 32 32 36 T56 28" stroke="url(#rl-road)" stroke-width="4" stroke-dasharray="6 5"/>
                                <circle cx="8" cy="48" r="6" fill="url(#rl-g)"/>
                                <circle cx="32" cy="36" r="5" fill="url(#rl-a)"/>
                                <circle cx="56" cy="28" r="6" fill="url(#rl-a)"/>
                                <circle cx="8" cy="48" r="6" fill="none" stroke="#FFFFFF" stroke-opacity="0.5" stroke-width="1.6"/>
                                <circle cx="56" cy="28" r="6" fill="none" stroke="#FFFFFF" stroke-opacity="0.5" stroke-width="1.6"/>
                                <circle cx="5.8" cy="46.6" r="1.5" fill="#FFFFFF" opacity="0.7"/>
                                <circle cx="53.8" cy="26.6" r="1.5" fill="#FFFFFF" opacity="0.7"/>
                                <g stroke="#0E1620" stroke-width="1.5">
                                    <rect x="20" y="18" width="24" height="14" rx="3" fill="url(#rl-truck)"/>
                                </g>
                                <rect x="22" y="20" width="20" height="10" rx="2" fill="#FFFFFF" opacity="0.15"/>
                                <path d="M26 18 L32 10 L38 18 Z" fill="url(#rl-truck)"/>
                                <path d="M28 16 L32 11.5 L36 16 Z" fill="#FFFFFF" opacity="0.3"/>
                            </svg>
                            <p class="text-white/60 text-sm">Plan routes, assign trucks, deploy</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Driver Panel -->
            <div id="role-driver" role="tabpanel" aria-labelledby="role-btn-driver" class="role-panel hidden bg-[#FAFAFA] rounded-3xl border border-[#17202B]/5 overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2">
                    <div class="p-8 sm:p-12 flex flex-col justify-center">
                        <h3 class="text-2xl font-display text-[#17202B] mb-4">Mobile-first dispatch with live GPS</h3>
                        <p class="text-[#64748B] leading-relaxed mb-6">
                            Drivers use a lightweight PWA. View stops, tap milestones as you load, and broadcast GPS — no app install needed. Works offline in low-signal areas.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <span class="px-3 py-1.5 rounded-full bg-[#BFA05A]/15 text-[#7C6527] text-xs font-semibold">Mobile PWA</span>
                            <span class="px-3 py-1.5 rounded-full bg-[#16283C]/10 text-[#16283C] text-xs font-semibold">Live Telemetry</span>
                            <span class="px-3 py-1.5 rounded-full bg-[#16283C]/10 text-[#16283C] text-xs font-semibold">Offline Support</span>
                        </div>
                    </div>
                    <div class="bg-[#0E1620] flex items-center justify-center p-8 sm:p-12">
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto mb-4" viewBox="0 0 64 64" fill="none" stroke-linecap="round" stroke-linejoin="round" style="filter: drop-shadow(1px 3px 3px rgba(0,0,0,0.35));">
                                <defs>
                                    <linearGradient id="rd-body" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0%" stop-color="#F26B5E"/>
                                        <stop offset="100%" stop-color="#C23A2E"/>
                                    </linearGradient>
                                    <linearGradient id="rd-screen" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#16233B"/>
                                        <stop offset="100%" stop-color="#0E1620"/>
                                    </linearGradient>
                                    <linearGradient id="rd-signal" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#D7BC7A"/>
                                        <stop offset="100%" stop-color="#0E1620"/>
                                    </linearGradient>
                                </defs>
                                <rect x="17" y="6" width="30" height="52" rx="7" fill="url(#rd-body)"/>
                                <rect x="18.5" y="7.5" width="6" height="50" rx="3" fill="#FFFFFF" opacity="0.25"/>
                                <rect x="22" y="14" width="20" height="32" rx="3" fill="url(#rd-screen)"/>
                                <rect x="23.5" y="15.5" width="17" height="29" rx="2.5" fill="#FFFFFF" opacity="0.06"/>
                                <path d="M28 22 L32 18 L36 22" stroke="#F26B5E" stroke-width="2.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M26 34 L30 30 L34 34 L38 28" stroke="url(#rd-signal)" stroke-width="2.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="30" cy="40" r="2.4" fill="#F26B5E"/>
                                <circle cx="32" cy="52" r="2.6" fill="#16233B"/>
                                <rect x="28.5" y="48.5" width="7" height="3" rx="1.5" fill="#FFFFFF" opacity="0.35"/>
                            </svg>
                            <p class="text-white/60 text-sm">View stops, track GPS, mark delivered</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Buyer Panel -->
            <div id="role-buyer" role="tabpanel" aria-labelledby="role-btn-buyer" class="role-panel hidden bg-[#FAFAFA] rounded-3xl border border-[#17202B]/5 overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2">
                    <div class="p-8 sm:p-12 flex flex-col justify-center">
                        <h3 class="text-2xl font-display text-[#17202B] mb-4">Buy crops with price data on your side</h3>
                        <p class="text-[#64748B] leading-relaxed mb-6">
                            Browse farmer postings on the crop board, compare them against DA RFO12 government price benchmarks, and negotiate deals in a structured negotiation room. Track every purchase from farm to delivery.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <span class="px-3 py-1.5 rounded-full bg-[#BFA05A]/15 text-[#7C6527] text-xs font-semibold">Crop Board</span>
                            <span class="px-3 py-1.5 rounded-full bg-[#16283C]/10 text-[#16283C] text-xs font-semibold">DA RFO12 Benchmarks</span>
                            <span class="px-3 py-1.5 rounded-full bg-[#16283C]/10 text-[#16283C] text-xs font-semibold">Live Delivery Tracking</span>
                        </div>
                    </div>
                    <div class="bg-[#0E1620] flex items-center justify-center p-8 sm:p-12">
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto mb-4" viewBox="0 0 64 64" fill="none" stroke-linecap="round" stroke-linejoin="round" style="filter: drop-shadow(1px 3px 3px rgba(0,0,0,0.35));">
                                <defs>
                                    <linearGradient id="rb-board" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#D7BC7A"/>
                                        <stop offset="100%" stop-color="#0E1620"/>
                                    </linearGradient>
                                    <radialGradient id="rb-bar" cx="35%" cy="30%" r="80%">
                                        <stop offset="0%" stop-color="#bfd6c9"/>
                                        <stop offset="100%" stop-color="#16283C"/>
                                    </radialGradient>
                                    <radialGradient id="rb-tag" cx="35%" cy="30%" r="80%">
                                        <stop offset="0%" stop-color="#F98B80"/>
                                        <stop offset="100%" stop-color="#C23A2E"/>
                                    </radialGradient>
                                </defs>
                                <g stroke="#0E1620" stroke-width="1.5">
                                    <rect x="8" y="14" width="34" height="36" rx="5" fill="url(#rb-board)"/>
                                </g>
                                <rect x="9.5" y="15.5" width="31" height="33" rx="3.5" fill="none" stroke="#FFFFFF" stroke-opacity="0.25" stroke-width="1.2"/>
                                <rect x="14" y="34" width="5" height="10" rx="1.5" fill="url(#rb-bar)" opacity="0.85"/>
                                <rect x="22" y="28" width="5" height="16" rx="1.5" fill="url(#rb-bar)" opacity="0.92"/>
                                <rect x="30" y="22" width="5" height="22" rx="1.5" fill="url(#rb-bar)"/>
                                <circle cx="16.5" cy="21.5" r="2.2" fill="#FFFFFF" opacity="0.55"/>
                                <path d="M40 40 L54 26 L58 30 L44 44 L38 46 Z" fill="url(#rb-tag)"/>
                                <path d="M42.5 41.5 L52 32" stroke="#FFFFFF" stroke-opacity="0.45" stroke-width="1.4"/>
                                <circle cx="53" cy="27" r="1.6" fill="#FFFFFF" opacity="0.75"/>
                            </svg>
                            <p class="text-white/60 text-sm">Compare prices, negotiate, receive</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<!-- FAQ -->
    <section id="faq" class="py-24 bg-[#FAFAFA] scroll-mt-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl mx-auto mb-12">
                <h2 class="text-3xl sm:text-4xl font-display text-[#17202B]">Your Questions, Answered</h2>
            </div>

            <div class="max-w-2xl mx-auto space-y-3 scroll-reveal">
                <div class="bg-white rounded-2xl border border-[#17202B]/5 overflow-hidden">
                    <button onclick="toggleFaq(0)" aria-expanded="false" class="w-full px-6 py-5 flex items-center justify-between text-left">
                        <span class="text-sm font-bold text-[#17202B]">How does route-pooling work?</span>
                        <span id="faq-icon-0" class="text-[#C23A2E] font-bold text-lg transition-transform duration-300">+</span>
                    </button>
                    <div id="faq-0" class="faq-content px-6 pb-5">
                        <p class="text-sm text-[#64748B] leading-relaxed">We aggregate crop dimensions and pickup dates from posted harvests and partner cooperatives. The routing engine sequences these farm locations along a single path, matching them to a high-capacity freight truck.</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-[#17202B]/5 overflow-hidden">
                    <button onclick="toggleFaq(1)" aria-expanded="false" class="w-full px-6 py-5 flex items-center justify-between text-left">
                        <span class="text-sm font-bold text-[#17202B]">Is GPS tracking secure?</span>
                        <span id="faq-icon-1" class="text-[#C23A2E] font-bold text-lg transition-transform duration-300">+</span>
                    </button>
                    <div id="faq-1" class="faq-content px-6 pb-5">
                        <p class="text-sm text-[#64748B] leading-relaxed">Yes. Drivers broadcast location only while their route is active. Broadcasters disconnect automatically upon route completion.</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-[#17202B]/5 overflow-hidden">
                    <button onclick="toggleFaq(2)" aria-expanded="false" class="w-full px-6 py-5 flex items-center justify-between text-left">
                        <span class="text-sm font-bold text-[#17202B]">Is there a registration fee?</span>
                        <span id="faq-icon-2" class="text-[#C23A2E] font-bold text-lg transition-transform duration-300">+</span>
                    </button>
                    <div id="faq-2" class="faq-content px-6 pb-5">
                        <p class="text-sm text-[#64748B] leading-relaxed">No — registering is free. Accounts are open to farmers, buyers, and freight operators in General Santos City, Polomolok, and nearby areas, subject to admin verification.</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-[#17202B]/5 overflow-hidden">
                    <button onclick="toggleFaq(3)" aria-expanded="false" class="w-full px-6 py-5 flex items-center justify-between text-left">
                        <span class="text-sm font-bold text-[#17202B]">How are fuel costs split?</span>
                        <span id="faq-icon-3" class="text-[#C23A2E] font-bold text-lg transition-transform duration-300">+</span>
                    </button>
                    <div id="faq-3" class="faq-content px-6 pb-5">
                        <p class="text-sm text-[#64748B] leading-relaxed">HarvestHaul calculates each farmer's share from the hauling rate (₱/kg) agreed in their negotiation chat, applied to the crop weight registered for that farmer's route.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Value Outcomes -->
    <section class="py-24 bg-[#FAFAFA]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-14 scroll-reveal">
                <h2 class="text-3xl sm:text-4xl font-display text-[#17202B]">Outcomes from HarvestHaul</h2>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-2xl p-6 sm:p-8 border border-[#17202B]/5 flex items-start gap-5 scroll-reveal">
                    <div class="w-1.5 h-14 rounded-full bg-[#16283C] flex-shrink-0 mt-0.5"></div>
                    <div>
                        <h3 class="text-lg font-bold text-[#17202B]">Farmers</h3>
                        <p class="text-sm text-[#64748B] mt-1 leading-relaxed max-w-2xl">
                            Track profit per harvest. The Profit & Expense report shows revenue, costs, and net profit per crop. Know what you earned — per crop, per season.
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 sm:p-8 border border-[#17202B]/5 flex items-start gap-5 scroll-reveal">
                    <div class="w-1.5 h-14 rounded-full bg-[#F26B5E] flex-shrink-0 mt-0.5"></div>
                    <div>
                        <h3 class="text-lg font-bold text-[#17202B]">Logistics (Cooperative)</h3>
                        <p class="text-sm text-[#64748B] mt-1 leading-relaxed max-w-2xl">
                            Fleet-wide visibility. The Analytics Hub shows trips completed, fuel efficiency (KPL), and refuel expenditure per truck. The Fleet Capacity page shows how many trucks your active harvests require.
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 sm:p-8 border border-[#17202B]/5 flex items-start gap-5 scroll-reveal">
                    <div class="w-1.5 h-14 rounded-full bg-[#334155] flex-shrink-0 mt-0.5"></div>
                    <div>
                        <h3 class="text-lg font-bold text-[#17202B]">Logistics (Commercial)</h3>
                        <p class="text-sm text-[#64748B] mt-1 leading-relaxed max-w-2xl">
                            Operational reporting. Trip reports break down completed trips, fuel costs, KPL, and revenue per truck.
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 sm:p-8 border border-[#17202B]/5 flex items-start gap-5 scroll-reveal">
                    <div class="w-1.5 h-14 rounded-full bg-[#16283C] flex-shrink-0 mt-0.5"></div>
                    <div>
                        <h3 class="text-lg font-bold text-[#17202B]">Buyers</h3>
                        <p class="text-sm text-[#64748B] mt-1 leading-relaxed max-w-2xl">
                            Buy smarter with real market data. Browse DA RFO12 government price benchmarks alongside farmer listings. Track deals and deliveries with live GPS. Every purchase backed by market intelligence.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-24 bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center scroll-reveal">
                <div>
                    <h2 class="text-3xl sm:text-4xl font-display text-[#17202B] leading-tight">Get Started with HarvestHaul</h2>
                    <p class="text-[#64748B] mt-6 text-lg leading-relaxed">
                        Register your cooperative or freight operator account to start coordinating crop pickups and deliveries through a centralized platform.
                    </p>

                    <div class="mt-10 space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-[#16283C]/10 flex items-center justify-center">
                                <svg class="w-4 h-4 text-[#16283C]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <span class="text-sm text-[#64748B]">General Santos City, Mindanao</span>
                        </div>
                    </div>

                    <div class="mt-10 flex flex-wrap gap-4">
                        @guest
                            <a href="{{ url('/register') }}" class="px-8 py-4 bg-[#F26B5E] text-[#17202B] rounded-xl font-bold hover:bg-[#F26B5E]/90 transition shadow-lg">
                                Register Your Organization
                            </a>
                            <a href="{{ route('login') }}" class="px-8 py-4 bg-[#0E1620] text-white rounded-xl font-semibold hover:bg-[#0E1620]/90 transition">
                                Access Portal
                            </a>
                        @else
                            <a href="{{ url('/dashboard') }}" class="px-8 py-4 bg-[#F26B5E] text-[#17202B] rounded-xl font-bold hover:bg-[#F26B5E]/90 transition shadow-lg">
                                Open Dashboard
                            </a>
                        @endguest
                    </div>
                </div>

                <div class="relative">
                    <div class="bg-[#FAFAFA] rounded-3xl p-8 border border-[#17202B]/5">
                        <div class="space-y-6">
                            <div class="flex items-start gap-4">
                                <span class="font-mono text-sm font-bold text-[#C23A2E] mt-0.5">01</span>
                                <div>
                                    <p class="text-sm font-bold text-[#17202B]">Register your organization</p>
                                    <p class="text-xs text-[#64748B] mt-0.5 leading-relaxed">Create a farmer, buyer, or freight operator account with your details.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <span class="font-mono text-sm font-bold text-[#C23A2E] mt-0.5">02</span>
                                <div>
                                    <p class="text-sm font-bold text-[#17202B]">Get verified</p>
                                    <p class="text-xs text-[#64748B] mt-0.5 leading-relaxed">An administrator reviews and verifies your account before you can post, bid, or haul.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <span class="font-mono text-sm font-bold text-[#C23A2E] mt-0.5">03</span>
                                <div>
                                    <p class="text-sm font-bold text-[#17202B]">Start coordinating</p>
                                    <p class="text-xs text-[#64748B] mt-0.5 leading-relaxed">Post harvests, build pooled routes, negotiate deals, and track deliveries end to end.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    </main>

    <!-- Footer -->
    <footer class="bg-[#0E1620] text-white/60 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-md bg-[#16283C] flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 22 16 8"/>
                        <path d="M3.47 12.53 5 11l1.53 1.53a3.5 3.5 0 0 1 0 4.94L5 19l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/>
                        <path d="M7.47 8.53 9 7l1.53 1.53a3.5 3.5 0 0 1 0 4.94L9 15l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/>
                        <path d="M11.47 4.53 13 3l1.53 1.53a3.5 3.5 0 0 1 0 4.94L13 11l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/>
                        <path d="M20 2h2v2a4 4 0 0 1-4 4h-2V6a4 4 0 0 1 4-4Z"/>
                        <path d="M11.47 17.47 13 19l-1.53 1.53a3.5 3.5 0 0 1-4.94 0L5 19l1.53-1.53a3.5 3.5 0 0 1 4.94 0Z"/>
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
            document.getElementById('main-header').classList.toggle('scrolled', window.scrollY > 50);
        }, { passive: true });

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
            const roles = ['farmer', 'logistics', 'driver', 'buyer'];
            roles.forEach(r => {
                const btn = document.getElementById(`role-btn-${r}`);
                const panel = document.getElementById(`role-${r}`);
                if (r === role) {
                    btn.className = 'role-tab px-5 py-2.5 rounded-xl text-sm font-semibold bg-[#16283C] text-white transition';
                    btn.setAttribute('aria-selected', 'true');
                    panel.classList.remove('hidden');
                } else {
                    btn.className = 'role-tab px-5 py-2.5 rounded-xl text-sm font-semibold bg-white text-[#17202B] border border-[#17202B]/10 hover:border-[#16283C]/30 transition';
                    btn.setAttribute('aria-selected', 'false');
                    panel.classList.add('hidden');
                }
            });
        }

        // Arrow-key navigation for role tabs
        (function () {
            const tabs = Array.from(document.querySelectorAll('[role="tablist"] .role-tab'));
            tabs.forEach(function (tab, i) {
                tab.addEventListener('keydown', function (e) {
                    const dir = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
                    if (!dir) return;
                    e.preventDefault();
                    const next = tabs[(i + dir + tabs.length) % tabs.length];
                    next.focus();
                    next.click();
                });
            });
        })();

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
