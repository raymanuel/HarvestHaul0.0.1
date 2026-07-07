<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>HarvestHaul — B2B Crop Distribution & Optimized Logistics Management</title>

        <!-- Google Fonts: Modern Premium Typography -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Spline 3D Viewer Script -->
        <script type="module" src="https://unpkg.com/@splinetool/viewer@1.9.54/build/spline-viewer.js"></script>

        <style>
            body {
                font-family: 'Plus Jakarta Sans', sans-serif;
            }
            .heading-font {
                font-family: 'Outfit', sans-serif;
            }
            /* SVG Wave separators styling */
            .wave-divider {
                position: relative;
                width: 100%;
                overflow: hidden;
                line-height: 0;
            }
            .wave-divider svg {
                position: relative;
                display: block;
                width: 100%;
                height: 40px;
            }
            /* Dash offset animation for the routing map path */
            @keyframes dash {
                to {
                    stroke-dashoffset: -40;
                }
            }
            .animated-route-path {
                stroke-dasharray: 8, 4;
                animation: dash 3s linear infinite;
            }
            /* Floating animations for badges */
            @keyframes float-y {
                0%, 100% { transform: translateY(0px); }
                50% { transform: translateY(-8px); }
            }
            .hover-float {
                animation: float-y 5s ease-in-out infinite;
            }
            .hover-float-delayed {
                animation: float-y 6s ease-in-out infinite;
                animation-delay: 1.5s;
            }
        </style>
    </head>
    <body class="bg-[#F8F6F1] text-[#1B1B18] antialiased min-h-screen relative overflow-x-hidden">

        <!-- Sticky Header with scroll transition -->
        <header id="main-header" class="fixed top-0 left-0 right-0 z-50 bg-transparent border-b border-transparent transition-all duration-300 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex justify-between items-center">

                <!-- Logo Section -->
                <a href="/" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#2D6A2F] to-[#5A8A3C] flex items-center justify-center shadow-md shadow-emerald-950/20 group-hover:scale-105 transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8a13 13 0 0 1-10 10Z"/>
                            <path d="M9 21s-4.5-3-4.5-7"/>
                        </svg>
                    </div>
                    <span id="logo-text" class="text-2xl font-bold tracking-tight heading-font text-white transition-colors duration-300">HarvestHaul</span>
                </a>

                <!-- Navigation Links -->
                <nav class="hidden md:flex items-center gap-1 bg-white/10 backdrop-blur-md p-1 rounded-full border border-white/10 shadow-sm transition-all">
                    <a href="#features" class="nav-link text-sm font-semibold text-white/80 hover:text-white hover:bg-white/10 px-4 py-2 rounded-full transition-all">Features</a>
                    <a href="#role-showcase" class="nav-link text-sm font-semibold text-white/80 hover:text-white hover:bg-white/10 px-4 py-2 rounded-full transition-all">Portal Modules</a>
                    <a href="#faq" class="nav-link text-sm font-semibold text-white/80 hover:text-white hover:bg-white/10 px-4 py-2 rounded-full transition-all">FAQ</a>
                    <a href="#how-it-works" class="nav-link text-sm font-semibold text-white/80 hover:text-white hover:bg-white/10 px-4 py-2 rounded-full transition-all">How It Works</a>
                </nav>

                <!-- Auth Buttons -->
                <div class="hidden md:flex items-center gap-4">
                    @if (Route::has('login'))
                        @auth
                            <a id="header-cta" href="{{ url('/dashboard') }}" class="px-5 py-2.5 bg-white text-[#2D6A2F] rounded-xl text-sm font-bold hover:bg-white/90 transition shadow-sm">
                                Open Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="nav-link text-sm font-semibold text-white/80 hover:text-white px-4 py-2 transition">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a id="header-cta" href="{{ route('register') }}" class="px-5 py-2.5 bg-white text-[#2D6A2F] rounded-xl text-sm font-bold hover:bg-white/90 transition shadow-sm">
                                    Get Started
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex md:hidden">
                    <button type="button" onclick="toggleMobileMenu()" class="w-10 h-10 rounded-lg flex items-center justify-center bg-white/10 border border-white/10 text-white hover:bg-white/20 focus:outline-none" aria-label="Toggle Menu">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Drawer Menu -->
            <div id="mobileMenu" class="hidden md:hidden border-t border-white/10 bg-slate-900/95 backdrop-blur-xl px-6 py-6 space-y-4 shadow-inner text-white">
                <a href="#features" onclick="toggleMobileMenu()" class="block text-base font-semibold text-white/80 hover:text-white py-2 border-b border-white/5">Features</a>
                <a href="#role-showcase" onclick="toggleMobileMenu()" class="block text-base font-semibold text-white/80 hover:text-white py-2 border-b border-white/5">Portal Modules</a>
                <a href="#faq" onclick="toggleMobileMenu()" class="block text-base font-semibold text-white/80 hover:text-white py-2 border-b border-white/5">FAQ</a>
                <a href="#how-it-works" onclick="toggleMobileMenu()" class="block text-base font-semibold text-white/80 hover:text-white py-2 border-b border-white/5">How It Works</a>
                <div class="pt-4 flex flex-col gap-3">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="w-full text-center px-4 py-3 bg-[#2D6A2F] text-white rounded-xl font-bold">Open Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="w-full text-center px-4 py-3 border border-white/20 text-white rounded-xl font-semibold hover:bg-white/10">Log in</a>
                        <a href="{{ route('register') }}" class="w-full text-center px-4 py-3 bg-[#2D6A2F] text-white rounded-xl font-bold">Register</a>
                    @endauth
                </div>
            </div>
        </header>

        <!-- Hero Section Wrapper with Full-Bleed Nature Image & Dark Overlay Scrim -->
        <div class="relative w-full min-h-screen bg-slate-950 overflow-hidden flex items-center pt-20">
            <!-- Full-bleed background image -->
            <div class="absolute inset-0 bg-cover bg-center bg-no-repeat" style="background-image: url('/images/hero-bg.png');"></div>
            <!-- Left-to-right gradient overlay scrim for readability -->
            <div class="absolute inset-0 bg-gradient-to-r from-slate-950/90 via-slate-950/65 to-transparent pointer-events-none"></div>

            <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 py-12 lg:py-24 grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
                <!-- Left text column -->
                <div class="lg:col-span-6 flex flex-col items-start space-y-8 text-white">
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold tracking-wide uppercase border border-[#2D6A2F]/35 bg-[#2D6A2F]/20 text-[#6EC95A] shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-[#6EC95A] animate-pulse"></span>
                        B2B Crop Routing & Logistics Engine
                    </span>
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-[1.08] heading-font">
                        Coordinated Crop Shipments with <span class="bg-gradient-to-r from-[#6EC95A] to-teal-400 bg-clip-text text-transparent">Optimized Routing</span>
                    </h1>
                    <p class="text-base sm:text-lg text-white/80 leading-relaxed max-w-xl">
                        Designed to assist agricultural logistics in Southern Mindanao. HarvestHaul helps coordinate vehicle sharing, maps sequential pickups, and works to connect cooperative transport lanes from Tupi and Polomolok to General Santos.
                    </p>

                    <!-- Stats badging floating inside hero as glassmorphic card -->
                    <div class="grid grid-cols-3 gap-6 w-full max-w-lg p-5 rounded-2xl bg-white/5 backdrop-blur-md border border-white/10 shadow-2xl">
                        <div>
                            <p class="text-base sm:text-lg font-bold uppercase tracking-wider heading-font text-[#6EC95A]">Multiple</p>
                            <p class="text-[10px] font-bold text-white/60 uppercase tracking-wide mt-1">Hub Locations</p>
                        </div>
                        <div class="border-l border-white/10 pl-6">
                            <p class="text-base sm:text-lg font-bold uppercase tracking-wider heading-font text-[#6EC95A]">Optimized</p>
                            <p class="text-[10px] font-bold text-white/60 uppercase tracking-wide mt-1">Space Util.</p>
                        </div>
                        <div class="border-l border-white/10 pl-6">
                            <p class="text-base sm:text-lg font-bold uppercase tracking-wider heading-font text-teal-300">Potential</p>
                            <p class="text-[10px] font-bold text-white/60 uppercase tracking-wide mt-1">Fuel Savings</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-4 pt-2">
                        @guest
                            <a href="{{ route('register') }}" class="px-8 py-4 bg-[#2D6A2F] text-white rounded-2xl font-bold hover:shadow-lg hover:shadow-[#2D6A2F]/20 hover:brightness-110 transition-all">
                                Join as Coordinator
                            </a>
                            <a href="#role-showcase" class="px-8 py-4 border border-white/20 rounded-2xl font-bold text-white bg-white/10 hover:bg-white/15 transition-all shadow-sm">
                                Explore Modules
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" class="px-8 py-4 bg-[#2D6A2F] text-white rounded-2xl font-bold hover:shadow-lg hover:shadow-[#2D6A2F]/20 hover:brightness-110 transition-all">
                                Access Dashboard →
                            </a>
                        @endguest
                    </div>
                </div>

                <!-- Right Interactive App Mockup -->
                <div class="lg:col-span-6 relative">
                    <!-- Glow background for the mockup frame -->
                    <div class="absolute -inset-1.5 bg-gradient-to-tr from-[#2D6A2F] to-[#5A8A3C] rounded-3xl blur opacity-25 group-hover:opacity-30 transition duration-1000"></div>
                    
                    <!-- Main Frame mockup -->
                    <div class="relative bg-slate-900 text-white rounded-3xl border border-white/10 shadow-2xl overflow-hidden">
                        
                        <!-- Top Header bar -->
                        <div class="bg-slate-950 px-6 py-4 flex items-center justify-between border-b border-slate-800">
                            <div class="flex items-center gap-2">
                                <div class="w-3.5 h-3.5 rounded-full bg-[#2D6A2F]/20 flex items-center justify-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#6EC95A] animate-ping"></span>
                                </div>
                                <span class="text-[10px] font-bold tracking-wider uppercase text-[#6EC95A]">Live Operation Monitor</span>
                            </div>
                            <div class="flex gap-1.5">
                                <button onclick="setMockupTab('dispatch')" id="btn-mock-dispatch" class="px-2.5 py-1 text-[10px] rounded-md bg-slate-800 text-[#6EC95A] font-bold border border-slate-700 transition">
                                    2D Map
                                </button>
                                <button onclick="setMockupTab('spline')" id="btn-mock-spline" class="px-2.5 py-1 text-[10px] rounded-md bg-slate-900 text-slate-400 font-semibold border border-transparent hover:text-slate-250 transition">
                                    3D Globe
                                </button>
                                <button onclick="setMockupTab('proposals')" id="btn-mock-proposals" class="px-2.5 py-1 text-[10px] rounded-md bg-slate-900 text-slate-400 font-semibold border border-transparent hover:text-slate-250 transition">
                                    Inbox
                                </button>
                            </div>
                        </div>

                        <!-- Panel Display Area -->
                        <div class="p-6 h-[340px] relative overflow-y-auto">
                            
                            <!-- TAB 1: DISPATCH MAP MOCKUP -->
                            <div id="mock-tab-dispatch" class="space-y-4">
                                <div class="flex justify-between items-center bg-slate-950/50 p-3.5 rounded-xl border border-slate-800">
                                    <div>
                                        <h4 class="text-[9px] text-slate-400 font-bold uppercase">Active Route</h4>
                                        <p class="text-xs font-bold text-slate-100 mt-0.5">Consolidated Hub Run #HH-409</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="px-2 py-0.5 text-[9px] font-extrabold uppercase rounded bg-teal-500/10 text-teal-400 border border-teal-500/20">En Route</span>
                                        <p class="text-[10px] text-slate-400 mt-1">Est: 45 min remaining</p>
                                    </div>
                                </div>

                                <!-- Custom Vector Map -->
                                <div class="bg-slate-950 rounded-xl border border-slate-800 p-4 relative h-48 overflow-hidden">
                                    <svg viewBox="0 0 400 160" class="w-full h-full">
                                        <!-- Connective paths -->
                                        <path d="M 50 120 Q 200 40 350 80" fill="none" stroke="#2D6A2F" stroke-width="4" stroke-linecap="round" stroke-opacity="0.3"/>
                                        <path d="M 50 120 Q 200 40 350 80" fill="none" stroke="#6EC95A" stroke-width="3" stroke-linecap="round" class="animated-route-path" stroke-opacity="0.8"/>
                                        
                                        <!-- Secondary consolidation paths -->
                                        <path d="M 190 20 L 200 68" fill="none" stroke="#6EC95A" stroke-width="2" stroke-dasharray="4,4" stroke-opacity="0.5"/>

                                        <!-- General Santos City Node -->
                                        <circle cx="350" cy="80" r="8" fill="#14B8A6" class="animate-pulse"/>
                                        <circle cx="350" cy="80" r="4" fill="#0D9488"/>
                                        <text x="350" y="105" fill="#94A3B8" font-size="9" font-weight="bold" text-anchor="middle">GenSan Terminal</text>

                                        <!-- Polomolok Intermediate Node -->
                                        <circle cx="200" cy="68" r="7" fill="#6EC95A"/>
                                        <circle cx="200" cy="68" r="3.5" fill="#2D6A2F"/>
                                        <text x="200" y="90" fill="#94A3B8" font-size="9" font-weight="bold" text-anchor="middle">Polomolok Sub-Hub</text>

                                        <!-- Tupi Northern Node -->
                                        <circle cx="50" cy="120" r="7" fill="#F59E0B" class="animate-pulse"/>
                                        <circle cx="50" cy="120" r="3.5" fill="#D97706"/>
                                        <text x="50" y="142" fill="#94A3B8" font-size="9" font-weight="bold" text-anchor="middle">Tupi Farms</text>

                                        <!-- Moving Delivery Truck Node -->
                                        <g id="map-truck-marker" transform="translate(0, 0)">
                                            <circle cx="0" cy="0" r="5" fill="#6EC95A" class="animate-ping"/>
                                            <circle cx="0" cy="0" r="3" fill="#6EC95A"/>
                                            <animateMotion path="M 50 120 Q 200 40 350 80" dur="8s" repeatCount="indefinite" rotate="auto" />
                                        </g>
                                    </svg>

                                    <!-- Floating GPS Coordinates Card inside map -->
                                    <div class="absolute bottom-3 right-3 bg-slate-900/90 backdrop-blur border border-slate-750 px-2 py-1 rounded-lg flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#6EC95A] animate-ping"></span>
                                        <span class="text-[9px] font-mono text-slate-350">GPS BROADCAST ACTIVE</span>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 2: SPLINE 3D GLOBE MOCKUP -->
                            <div id="mock-tab-spline" class="hidden space-y-4">
                                <div class="bg-slate-950 rounded-xl border border-slate-800 p-0 relative h-64 overflow-hidden flex items-center justify-center">
                                    <spline-viewer url="https://prod.spline.design/J8no4uRFtC-f3JDu/scene.splinecode" class="w-full h-full" style="height: 100%; min-height: 256px;"></spline-viewer>
                                </div>
                            </div>

                            <!-- TAB 3: PROPOSAL INBOX MOCKUP (INTERACTIVE) -->
                            <div id="mock-tab-proposals" class="hidden space-y-3">
                                <p class="text-[10px] text-slate-400 font-bold mb-2 uppercase">3 Pending Cooperative Proposals waiting for Route-Grouping:</p>
                                
                                <!-- Proposal Card 1 -->
                                <div id="prop-card-1" class="bg-slate-950 p-3 rounded-xl border border-slate-800 hover:border-[#2D6A2F]/40 transition duration-300 flex items-center justify-between">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-150">Tupi Pineapple Co-op</span>
                                            <span class="text-[9px] bg-slate-800 text-slate-300 px-1.5 py-0.5 rounded">Pineapples</span>
                                        </div>
                                        <div class="flex gap-4 text-[10px] text-slate-400">
                                            <span>📦 4.8 Tons</span>
                                            <span>📍 Tupi Hub</span>
                                        </div>
                                    </div>
                                    <button onclick="approveProposal('prop-card-1')" class="bg-[#2D6A2F] hover:bg-[#2D6A2F]/80 text-white text-[10px] font-extrabold px-3 py-1.5 rounded-lg shadow transition">
                                        Pool Load
                                    </button>
                                </div>

                                <!-- Proposal Card 2 -->
                                <div id="prop-card-2" class="bg-slate-950 p-3 rounded-xl border border-slate-800 hover:border-[#2D6A2F]/40 transition duration-300 flex items-center justify-between">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-150">Polomolok Fruit Growers</span>
                                            <span class="text-[9px] bg-slate-800 text-slate-300 px-1.5 py-0.5 rounded">Bananas</span>
                                        </div>
                                        <div class="flex gap-4 text-[10px] text-slate-400">
                                            <span>📦 3.2 Tons</span>
                                            <span>📍 Polomolok Hub</span>
                                        </div>
                                    </div>
                                    <button onclick="approveProposal('prop-card-2')" class="bg-[#2D6A2F] hover:bg-[#2D6A2F]/80 text-white text-[10px] font-extrabold px-3 py-1.5 rounded-lg shadow transition">
                                        Pool Load
                                    </button>
                                </div>

                                <!-- Proposal Card 3 -->
                                <div id="prop-card-3" class="bg-slate-950 p-3 rounded-xl border border-slate-800 hover:border-[#2D6A2F]/40 transition duration-300 flex items-center justify-between">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-150">Matutum Organic Farmers</span>
                                            <span class="text-[9px] bg-slate-800 text-slate-300 px-1.5 py-0.5 rounded">Vegetables</span>
                                        </div>
                                        <div class="flex gap-4 text-[10px] text-slate-400">
                                            <span>📦 1.5 Tons</span>
                                            <span>📍 Matutum Hub</span>
                                        </div>
                                    </div>
                                    <button onclick="approveProposal('prop-card-3')" class="bg-[#2D6A2F] hover:bg-[#2D6A2F]/80 text-white text-[10px] font-extrabold px-3 py-1.5 rounded-lg shadow transition">
                                        Pool Load
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Operational Ticker Bottom bar -->
                        <div class="bg-slate-950 px-6 py-4.5 border-t border-slate-800 flex items-center gap-4 text-xs font-mono">
                            <span class="text-[#6EC95A] font-bold uppercase shrink-0">Live Log:</span>
                            <div class="overflow-hidden w-full h-5 relative">
                                <div id="live-log-container" class="absolute w-full space-y-1 leading-5 transition-transform duration-500">
                                    <p class="text-slate-300">🚛 Truck #04 loaded with 4.8T pineapples at Tupi hub.</p>
                                    <p class="text-slate-300">⚡ Smart Route calculated savings: ₱3,450 fuel.</p>
                                    <p class="text-slate-300">📋 Driver assigned to Consolidated Run #HH-409.</p>
                                    <p class="text-slate-300">🌾 Matutum Organic Co-op joined route pool.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ambient absolute elements floating around the mockup -->
                    <div class="hidden sm:block absolute -right-6 -bottom-6 bg-slate-900 border border-slate-800 p-4 rounded-2xl shadow-xl hover-float z-20">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-teal-500/10 flex items-center justify-center text-teal-400 font-black text-xs">
                                📊
                            </div>
                            <div>
                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Fleet Space Util.</p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-xs font-black text-white">92.6% Optimized</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hidden sm:block absolute -left-8 top-16 bg-slate-900 border border-slate-800 p-4 rounded-2xl shadow-xl hover-float-delayed z-20">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-[#6EC95A]/10 flex items-center justify-center text-[#6EC95A] font-black text-xs">
                                ₱
                            </div>
                            <div>
                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Fuel Expense Pool</p>
                                <p class="text-xs font-black text-white mt-0.5">Splits Saved Pro-Rata</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Trusted By Logobar Section (Warm sage background, waves top & bottom) -->
        <div class="wave-divider fill-[#EFF2E9]">
            <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M985.66,92.83C906.67,72,823.78,31,743.84,14.19c-82.26-17.34-168.06-16.33-250.45.39-57.84,11.73-114,31.07-172,41.86C263,67.23,183.1,50.77,90,26.79,57.05,18.3,26.9,8.75,0,0V120H1200V95.8C1132.19,118.92,1055.71,111.31,985.66,92.83Z"></path>
            </svg>
        </div>
        <section class="py-8 bg-[#EFF2E9] text-[#3D5C2A] px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto flex flex-col lg:flex-row items-center justify-between gap-6">
                <span class="text-[10px] font-extrabold uppercase tracking-widest text-[#3D5C2A]/70 heading-font shrink-0">Powering regional supply chains across Mindanao</span>
                <div class="flex flex-wrap items-center justify-center gap-x-10 gap-y-4">
                    <!-- Logo 1: Tupi Growers -->
                    <div class="flex items-center gap-2 text-[#3D5C2A]/80 hover:text-[#2D6A2F] transition-colors duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                        <span class="font-extrabold tracking-tight text-xs uppercase heading-font">Tupi Agri-Coop</span>
                    </div>
                    <!-- Logo 2: GenSan Logistics -->
                    <div class="flex items-center gap-2 text-[#3D5C2A]/80 hover:text-[#2D6A2F] transition-colors duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="2" ry="2"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        <span class="font-extrabold tracking-tight text-xs uppercase heading-font">GenSan Freight Alliance</span>
                    </div>
                    <!-- Logo 3: Polomolok Fruits -->
                    <div class="flex items-center gap-2 text-[#3D5C2A]/80 hover:text-[#2D6A2F] transition-colors duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        <span class="font-extrabold tracking-tight text-xs uppercase heading-font">Polomolok Growers</span>
                    </div>
                    <!-- Logo 4: Matutum Transport -->
                    <div class="flex items-center gap-2 text-[#3D5C2A]/80 hover:text-[#2D6A2F] transition-colors duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 4.24 4.24M14.83 9.17l4.24-4.24M14.83 14.83l4.24 4.24M9.17 14.83l-4.24 4.24"/></svg>
                        <span class="font-extrabold tracking-tight text-xs uppercase heading-font">Matutum Transit</span>
                    </div>
                </div>
            </div>
        </section>
        <div class="wave-divider fill-[#EFF2E9] rotate-180">
            <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M985.66,92.83C906.67,72,823.78,31,743.84,14.19c-82.26-17.34-168.06-16.33-250.45.39-57.84,11.73-114,31.07-172,41.86C263,67.23,183.1,50.77,90,26.79,57.05,18.3,26.9,8.75,0,0V120H1200V95.8C1132.19,118.92,1055.71,111.31,985.66,92.83Z"></path>
            </svg>
        </div>

        <!-- Features Section (Warm background) -->
        <section id="features" class="py-20 scroll-mt-24 bg-[#F8F6F1]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-20">
                    <span class="text-xs font-bold uppercase tracking-widest text-[#2D6A2F] bg-[#2D6A2F]/10 px-3 py-1 rounded-full border border-[#2D6A2F]/15">Enterprise Solutions</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 heading-font mt-4">Platform Features for Orchestrating Cooperative Agri-Freight</h2>
                    <p class="mt-4 text-base text-slate-500 leading-relaxed">Designed to help coordinate regional growers, logistics operators, and local delivery drivers.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="bg-white border border-[#2D6A2F]/8 p-8 rounded-3xl shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between group">
                        <div>
                            <div class="w-12 h-12 bg-[#EFF2E9] rounded-2xl flex items-center justify-center text-[#2D6A2F] group-hover:scale-110 group-hover:bg-[#2D6A2F] group-hover:text-white transition-all duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mt-6 heading-font">Dynamic Fleet Assignment</h3>
                            <p class="text-slate-500 mt-3 font-normal leading-relaxed text-sm">Register delivery trucks, log estimated capacity, and match certified drivers to coordinated multi-stop regional runs through a unified operations terminal.</p>
                        </div>
                        <div class="pt-6 mt-6 border-t border-slate-100 flex items-center justify-between text-xs text-slate-450 group-hover:text-[#2D6A2F] transition">
                            <span class="font-semibold uppercase tracking-wider">Automated Dispatch</span>
                            <span>→</span>
                        </div>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-white border border-[#2D6A2F]/8 p-8 rounded-3xl shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between group">
                        <div>
                            <div class="w-12 h-12 bg-[#EFF2E9] rounded-2xl flex items-center justify-center text-[#2D6A2F] group-hover:scale-110 group-hover:bg-[#2D6A2F] group-hover:text-white transition-all duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mt-6 heading-font">Live Geolocation Tracking</h3>
                            <p class="text-slate-500 mt-3 font-normal leading-relaxed text-sm">Follow active shipments visually along transport corridors. Drivers supply telemetry signals from their mobile browsers without complex software installs.</p>
                        </div>
                        <div class="pt-6 mt-6 border-t border-slate-100 flex items-center justify-between text-xs text-slate-450 group-hover:text-[#2D6A2F] transition">
                            <span class="font-semibold uppercase tracking-wider">Browser Telemetry</span>
                            <span>→</span>
                        </div>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-white border border-[#2D6A2F]/8 p-8 rounded-3xl shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between group">
                        <div>
                            <div class="w-12 h-12 bg-[#EFF2E9] rounded-2xl flex items-center justify-center text-[#2D6A2F] group-hover:scale-110 group-hover:bg-[#2D6A2F] group-hover:text-white transition-all duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mt-6 heading-font">Route-Based Load Pooling</h3>
                            <p class="text-slate-500 mt-3 font-normal leading-relaxed text-sm">Consolidate multiple small cooperative harvests sequentially along the path of a single high-capacity freight carrier, aiming to reduce empty miles and split transport costs.</p>
                        </div>
                        <div class="pt-6 mt-6 border-t border-slate-100 flex items-center justify-between text-xs text-slate-450 group-hover:text-[#2D6A2F] transition">
                            <span class="font-semibold uppercase tracking-wider">Cooperative Pooling</span>
                            <span>→</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Platform Live Metrics Section (Rich Earthy Warm Dark) -->
        <section class="relative overflow-hidden bg-[#1A1F12] text-white py-16 px-8 sm:px-12 mx-4 sm:mx-6 lg:mx-8 rounded-3xl border border-white/5 shadow-xl my-12">
            <!-- Decorative green glow inside stats section -->
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_bottom_left,rgba(80,160,60,0.08),transparent_60%)] pointer-events-none z-0"></div>
            
            <div class="relative z-10 grid grid-cols-1 md:grid-cols-4 gap-8 divide-y md:divide-y-0 md:divide-x divide-[#2D6A2F]/20">
                <!-- Stat 1 -->
                <div class="flex flex-col items-center md:items-start text-center md:text-left space-y-2 md:pr-6">
                    <span class="text-3xl font-black heading-font text-[#6EC95A]">Reduced</span>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Expense Savings</span>
                    <p class="text-xs text-slate-400 leading-relaxed">Direct fuel & truck space expenses targeted to be saved by southern Mindanao growers through sequential pooling.</p>
                </div>
                <!-- Stat 2 -->
                <div class="flex flex-col items-center md:items-start text-center md:text-left space-y-2 md:px-8 pt-6 md:pt-0">
                    <span class="text-3xl font-black heading-font text-[#6EC95A]">Active</span>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Hauled Crops</span>
                    <p class="text-xs text-slate-400 leading-relaxed">Pineapples, bananas, and organic produce targeted for routing from Matutum, Tupi, and Polomolok hubs.</p>
                </div>
                <!-- Stat 3 -->
                <div class="flex flex-col items-center md:items-start text-center md:text-left space-y-2 md:px-8 pt-6 md:pt-0">
                    <span class="text-3xl font-black heading-font text-[#6EC95A]">Optimized</span>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Truck Space Util.</span>
                    <p class="text-xs text-slate-400 leading-relaxed">Aims to reduce empty backhaul runs. Our routing coordinates high-volume freight matching dynamically.</p>
                </div>
                <!-- Stat 4 -->
                <div class="flex flex-col items-center md:items-start text-center md:text-left space-y-2 md:pl-8 pt-6 md:pt-0">
                    <span class="text-3xl font-black heading-font text-teal-400">Streamlined</span>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Quick Onboarding</span>
                    <p class="text-xs text-slate-400 leading-relaxed">Quickly onboard your cooperative, register driver browser telemetry, and coordinate a route.</p>
                </div>
            </div>
        </section>

        <!-- Role Showcase Section (Interactive Tabs, warm outer, dark inners) -->
        <section id="role-showcase" class="py-20 bg-[#F8F6F1] scroll-mt-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-14">
                    <span class="text-xs font-bold uppercase tracking-widest text-[#2D6A2F] bg-[#EFF2E9] px-3 py-1 rounded-full border border-[#2D6A2F]/15">Dynamic Workspace Portals</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 heading-font mt-4">One Integrated Platform, Three Custom Environments</h2>
                </div>

                <!-- Tabs header -->
                <div class="flex flex-wrap justify-center gap-2 mb-10 bg-[#EFF2E9] p-1.5 rounded-2xl max-w-2xl mx-auto border border-[#2D6A2F]/10">
                    <button onclick="setRoleTab('farmer')" id="tab-btn-farmer" class="px-5 py-2.5 rounded-xl text-sm font-bold transition bg-[#2D6A2F] text-white shadow-sm">
                        Farmers
                    </button>
                    <button onclick="setRoleTab('logistics')" id="tab-btn-logistics" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:text-slate-800 transition">
                        Logistics Partners
                    </button>
                    <button onclick="setRoleTab('driver')" id="tab-btn-driver" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:text-slate-800 transition">
                        Drivers
                    </button>
                </div>

                <!-- Showcase panels -->
                <div class="bg-white border border-[#2D6A2F]/10 rounded-3xl shadow-xl p-8 sm:p-12 min-h-[420px] transition-all duration-300">
                    
                    <!-- FARMER PORTAL VIEW -->
                    <div id="role-panel-farmer" class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center transition-all duration-500 opacity-100 scale-100">
                        <div class="lg:col-span-6 space-y-6">
                            <h3 class="text-2xl font-bold text-slate-900 heading-font">Get Access to Grouped Shipping Without High Minimums</h3>
                            <p class="text-slate-500 leading-relaxed text-sm sm:text-base">
                                Small farms usually struggle with logistics because shipping companies demand massive minimum volumes. In the Farmer Portal, simply submit your upcoming harvest dimensions. HARVEST's engine finds other growers along your highway corridor to build a full load.
                            </p>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 rounded-xl bg-[#F8F6F1] border border-[#2D6A2F]/5">
                                    <h4 class="font-bold text-sm text-slate-900 mt-2">Corridor Matching</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">Autogrouped with local neighbors.</p>
                                </div>
                                <div class="p-4 rounded-xl bg-[#F8F6F1] border border-[#2D6A2F]/5">
                                    <h4 class="font-bold text-sm text-slate-900 mt-2">Fair Pricing</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">Pay only for the space you use.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Mockup illustration inside tab -->
                        <div class="lg:col-span-6 bg-[#F8F6F1] p-6 rounded-2xl border border-[#2D6A2F]/5 space-y-4">
                            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-3">
                                <div class="flex justify-between items-center pb-2 border-b border-slate-100">
                                    <h4 class="font-bold text-sm text-slate-800">New Harvest Posting</h4>
                                    <span class="px-2 py-0.5 text-[9px] bg-emerald-100 text-[#2D6A2F] rounded font-bold uppercase">Ready</span>
                                </div>
                                <div class="grid grid-cols-2 gap-3 text-xs">
                                    <div>
                                        <p class="text-slate-400">Crop Category</p>
                                        <p class="font-bold text-slate-700 mt-0.5">Fruit / Pineapples</p>
                                    </div>
                                    <div>
                                        <p class="text-slate-400">Harvest Volume</p>
                                        <p class="font-bold text-slate-700 mt-0.5">4.8 Metric Tons</p>
                                    </div>
                                    <div>
                                        <p class="text-slate-400">Pickup Location</p>
                                        <p class="font-bold text-slate-700 mt-0.5">Tupi Highway Hub</p>
                                    </div>
                                    <div>
                                        <p class="text-slate-400">Preferred Dispatch</p>
                                        <p class="font-bold text-slate-700 mt-0.5">May 30, 2026</p>
                                    </div>
                                </div>
                                <button class="w-full bg-[#2D6A2F] hover:bg-[#2D6A2F]/90 text-white rounded-lg py-2.5 text-xs font-bold shadow transition">
                                    Publish harvest to Logistics Board
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- LOGISTICS PARTNER PORTAL VIEW -->
                    <div id="role-panel-logistics" class="hidden grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                        <div class="lg:col-span-6 space-y-6">
                            <h3 class="text-2xl font-bold text-slate-900 heading-font">Optimize Multi-Stop Pickup Routes in a Single Screen</h3>
                            <p class="text-slate-500 leading-relaxed text-sm sm:text-base">
                                For freight partners, HARVEST acts as a smart dispatch system. View regional grower listings, select compatible harvests, and generate highly optimized sequential route proposals. Track truck load volumes and allocate available drivers instantly.
                            </p>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 rounded-xl bg-[#F8F6F1] border border-[#2D6A2F]/5">
                                    <h4 class="font-bold text-sm text-slate-900 mt-2">Sequential Routing</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">Calculates optimal multi-stop lines.</p>
                                </div>
                                <div class="p-4 rounded-xl bg-[#F8F6F1] border border-[#2D6A2F]/5">
                                    <h4 class="font-bold text-sm text-slate-900 mt-2">Fleet Monitoring</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">Track truck space and drivers.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Mockup illustration inside tab -->
                        <div class="lg:col-span-6 bg-[#F8F6F1] p-6 rounded-2xl border border-[#2D6A2F]/5 space-y-4">
                            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-3.5">
                                <div class="flex justify-between items-center pb-2 border-b border-slate-100">
                                    <h4 class="font-bold text-sm text-slate-800">Dispatch Optimizer</h4>
                                    <span class="px-2 py-0.5 text-[9px] bg-teal-100 text-teal-800 rounded font-bold uppercase">Optimal Route Ready</span>
                                </div>
                                <div class="space-y-2">
                                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-100 flex justify-between items-center text-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                            <span class="font-semibold">Stop 1: Tupi Cooperative</span>
                                        </div>
                                        <span class="text-slate-400">Load: 4.8T</span>
                                    </div>
                                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-100 flex justify-between items-center text-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-[#2D6A2F]"></span>
                                            <span class="font-semibold">Stop 2: Polomolok Farmers</span>
                                        </div>
                                        <span class="text-slate-400">Load: 3.2T</span>
                                    </div>
                                    <div class="p-2.5 bg-[#1A1F12] text-slate-100 rounded-lg flex justify-between items-center text-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-[#6EC95A]"></span>
                                            <span class="font-semibold text-white">Destination: GenSan Terminal</span>
                                        </div>
                                        <span class="text-[#6EC95A] font-bold">Total: 8.0T / 10T max</span>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button class="flex-1 bg-[#1A1F12] text-white rounded-lg py-2 text-xs font-bold shadow hover:bg-black transition">
                                        Assign Driver
                                    </button>
                                    <button class="flex-1 bg-[#2D6A2F] text-white rounded-lg py-2 text-xs font-bold shadow hover:bg-[#2D6A2F]/90 transition">
                                        Deploy Run
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DRIVER PORTAL VIEW -->
                    <div id="role-panel-driver" class="hidden grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                        <div class="lg:col-span-6 space-y-6">
                            <h3 class="text-2xl font-bold text-slate-900 heading-font">Mobile-Optimized Route Milestones & GPS Updates</h3>
                            <p class="text-slate-500 leading-relaxed text-sm sm:text-base">
                                Drivers operate on a simplified mobile PWA interface. Instead of texting updates, drivers view assigned stops, toggle milestone buttons as they load each crop cargo, and broadcast their GPS location effortlessly to keep coordinators informed.
                            </p>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 rounded-xl bg-[#F8F6F1] border border-[#2D6A2F]/5">
                                    <h4 class="font-bold text-sm text-slate-900 mt-2">Mobile PWA Layout</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">Lightweight, loads in low signal.</p>
                                </div>
                                <div class="p-4 rounded-xl bg-[#F8F6F1] border border-[#2D6A2F]/5">
                                    <h4 class="font-bold text-sm text-slate-900 mt-2">Telemetry Signal</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">Instant geographic updates.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Mockup illustration inside tab -->
                        <div class="lg:col-span-6 flex justify-center">
                            <!-- Mock Smartphone Screen -->
                            <div class="w-64 bg-slate-900 border-[6px] border-slate-800 rounded-[36px] shadow-2xl p-4 text-white relative aspect-[9/18]">
                                <!-- Phone Notch -->
                                <div class="absolute top-2.5 left-1/2 -translate-x-1/2 w-20 h-4 rounded-full bg-slate-800 flex items-center justify-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-900"></span>
                                </div>
                                
                                <div class="pt-5 space-y-3.5">
                                    <div class="text-center">
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Active Route Assigned</p>
                                        <p class="text-xs font-extrabold text-slate-200 mt-0.5">Route #HH-409</p>
                                    </div>

                                    <!-- Step Milestones in Phone Screen -->
                                    <div class="space-y-2 pt-2 text-left">
                                        <div class="p-2.5 rounded-xl bg-[#2D6A2F]/15 border border-[#2D6A2F]/20 flex gap-2.5 items-start">
                                            <span class="text-[#6EC95A] font-bold text-[10px] mt-0.5">✓</span>
                                            <div>
                                                <h5 class="text-[10px] font-bold text-slate-100">Tupi Hub Pickup</h5>
                                                <p class="text-[8px] text-[#6EC95A] font-semibold mt-0.5">Completed — Loaded 4.8T</p>
                                            </div>
                                        </div>

                                        <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 flex gap-2.5 items-start">
                                            <span class="w-3.5 h-3.5 rounded-full bg-slate-850 flex items-center justify-center text-[8px] font-bold mt-0.5 text-slate-400">2</span>
                                            <div class="flex-1">
                                                <h5 class="text-[10px] font-bold text-slate-200">Polomolok Sub-Hub</h5>
                                                <p class="text-[8px] text-slate-400 mt-0.5">Load scheduled: 3.2T bananas</p>
                                                <button class="w-full mt-2 bg-[#2D6A2F] text-white rounded py-1 text-[9px] font-bold hover:bg-[#2D6A2F]/90 transition">
                                                    Mark Loaded at Hub
                                                </button>
                                            </div>
                                        </div>

                                        <div class="p-2.5 rounded-xl bg-slate-950/40 border border-slate-900/60 opacity-50 flex gap-2.5 items-start">
                                            <span class="w-3.5 h-3.5 rounded-full bg-slate-850 flex items-center justify-center text-[8px] font-bold mt-0.5 text-slate-500">3</span>
                                            <div>
                                                <h5 class="text-[10px] font-bold text-slate-400">GenSan Distribution</h5>
                                                <p class="text-[8px] text-slate-500 mt-0.5">Final Drop-off point</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Geolocation tracking Switch Inside Phone Screen -->
                                    <div class="bg-slate-950 p-2.5 rounded-xl border border-slate-800 flex items-center justify-between mt-2">
                                        <div class="flex flex-col text-left">
                                            <span class="text-[8px] font-bold text-slate-400 uppercase">GPS Broadcaster</span>
                                            <span class="text-[9px] text-[#6EC95A] font-bold">ACTIVE</span>
                                        </div>
                                        <div class="w-8 h-4 rounded-full bg-[#2D6A2F] p-0.5 flex justify-end items-center cursor-pointer">
                                            <div class="w-3 h-3 rounded-full bg-white"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works Section (Warm Sage Background with wave top) -->
        <div class="wave-divider fill-[#EFF2E9] bg-[#F8F6F1]">
            <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M985.66,92.83C906.67,72,823.78,31,743.84,14.19c-82.26-17.34-168.06-16.33-250.45.39-57.84,11.73-114,31.07-172,41.86C263,67.23,183.1,50.77,90,26.79,57.05,18.3,26.9,8.75,0,0V120H1200V95.8C1132.19,118.92,1055.71,111.31,985.66,92.83Z"></path>
            </svg>
        </div>
        <section id="how-it-works" class="py-20 bg-[#EFF2E9] scroll-mt-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-20">
                    <span class="text-xs font-bold uppercase tracking-widest text-[#2D6A2F] bg-white/60 px-3 py-1 rounded-full border border-[#2D6A2F]/10">Process Overview</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 heading-font mt-4">Simple, Coordinated Workflow</h2>
                    <p class="mt-4 text-slate-550 leading-relaxed text-slate-600">How HARVEST connects regional agricultural assets in Southern Mindanao step-by-step.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-12 relative">
                    
                    <!-- Dotted Connector Line (Desktop Only) -->
                    <div class="hidden md:block absolute top-12 left-[12%] right-[12%] h-0.5 border-t-2 border-dashed border-[#2D6A2F]/30 z-0"></div>

                    <!-- Step 1 -->
                    <div class="relative z-10 flex flex-col items-center text-center space-y-4">
                        <div class="w-16 h-16 rounded-2xl bg-white border border-[#2D6A2F]/20 text-[#2D6A2F] flex items-center justify-center font-black text-lg shadow-md">
                            01
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 heading-font">Account Onboarding</h3>
                        <p class="text-slate-500 leading-relaxed text-sm max-w-xs">
                            Users authenticate and set up profiles matching their agricultural context: Farmer Cooperatives, Freight Partners, or Drivers.
                        </p>
                    </div>

                    <!-- Step 2 -->
                    <div class="relative z-10 flex flex-col items-center text-center space-y-4">
                        <div class="w-16 h-16 rounded-2xl bg-white border border-[#2D6A2F]/20 text-[#2D6A2F] flex items-center justify-center font-black text-lg shadow-md">
                            02
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 heading-font">Route & Load Planning</h3>
                        <p class="text-slate-500 leading-relaxed text-sm max-w-xs">
                            Growers submit their crop quantities, allowing Logistics Partners to group regional pickups into consolidated transport routes.
                        </p>
                    </div>

                    <!-- Step 3 -->
                    <div class="relative z-10 flex flex-col items-center text-center space-y-4">
                        <div class="w-16 h-16 rounded-2xl bg-white border border-[#2D6A2F]/20 text-[#2D6A2F] flex items-center justify-center font-black text-lg shadow-md">
                            03
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 heading-font">Dispatch & Telemetry</h3>
                        <p class="text-slate-500 leading-relaxed text-sm max-w-xs">
                            Coordinators deploy scheduled routes. Assigned drivers track milestones and broadcast GPS geolocation to the hub dashboard.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section id="faq" class="py-20 bg-[#F8F6F1] scroll-mt-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <span class="text-xs font-bold uppercase tracking-widest text-[#2D6A2F] bg-[#EFF2E9] px-3 py-1 rounded-full border border-[#2D6A2F]/15">Help Center</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 heading-font mt-4">Frequently Asked Questions</h2>
                    <p class="mt-4 text-base text-slate-500 leading-relaxed">Everything you need to know about route consolidation, platform operations, and system security.</p>
                </div>

                <div class="max-w-3xl mx-auto space-y-4">
                    <!-- FAQ 1 -->
                    <div class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden transition duration-300">
                        <button onclick="toggleFaq(0)" class="w-full px-6 py-5 flex items-center justify-between text-left focus:outline-none">
                            <span class="text-sm font-bold text-slate-900">How does agricultural route-pooling work?</span>
                            <span id="faq-icon-0" class="text-[#2D6A2F] font-bold transition-transform duration-300">+</span>
                        </button>
                        <div id="faq-ans-0" class="hidden px-6 pb-5">
                            <p class="text-xs text-slate-500 leading-relaxed">Our platform aggregates crop dimensions and pickup dates from local cooperatives. The routing engine sequences these farm locations along a single path, matching them to a high-capacity freight truck.</p>
                        </div>
                    </div>

                    <!-- FAQ 2 -->
                    <div class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden transition duration-300">
                        <button onclick="toggleFaq(1)" class="w-full px-6 py-5 flex items-center justify-between text-left focus:outline-none">
                            <span class="text-sm font-bold text-slate-900">How secure is browser-based GPS telemetry?</span>
                            <span id="faq-icon-1" class="text-[#2D6A2F] font-bold transition-transform duration-300">+</span>
                        </button>
                        <div id="faq-ans-1" class="hidden px-6 pb-5">
                            <p class="text-xs text-slate-500 leading-relaxed">Fully secure. Drivers broadcast location coordinates using standard browser GPS signals only while their assigned route is active. Broadcasters automatically disconnect upon route completion.</p>
                        </div>
                    </div>

                    <!-- FAQ 3 -->
                    <div class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden transition duration-300">
                        <button onclick="toggleFaq(2)" class="w-full px-6 py-5 flex items-center justify-between text-left focus:outline-none">
                            <span class="text-sm font-bold text-slate-900">Is there a fee to register our cooperative or business?</span>
                            <span id="faq-icon-2" class="text-[#2D6A2F] font-bold transition-transform duration-300">+</span>
                        </button>
                        <div id="faq-ans-2" class="hidden px-6 pb-5">
                            <p class="text-xs text-slate-500 leading-relaxed">Onboarding is currently open for local cooperatives and freight operators in Mindanao. We aim to keep the core coordination system accessible to support regional agriculture.</p>
                        </div>
                    </div>

                    <!-- FAQ 4 -->
                    <div class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden transition duration-300">
                        <button onclick="toggleFaq(3)" class="w-full px-6 py-5 flex items-center justify-between text-left focus:outline-none">
                            <span class="text-sm font-bold text-slate-900">How do growers split fuel and space expenses?</span>
                            <span id="faq-icon-3" class="text-[#2D6A2F] font-bold transition-transform duration-300">+</span>
                        </button>
                        <div id="faq-ans-3" class="hidden px-6 pb-5">
                            <p class="text-xs text-slate-500 leading-relaxed">HARVEST calculates proportional transport costs automatically based on crop weight (tons) and pickup-to-hub distance (km) registered during scheduling.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pre-Footer Glowing Call to Action (CTA) (Wave divider top, Forest green bg) -->
        <div class="wave-divider fill-[#2D6A2F] bg-[#F8F6F1]">
            <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V120H0V0C26.9,8.75,57.05,18.3,90,26.79,183.1,50.77,263,67.23,321.39,56.44Z"></path>
            </svg>
        </div>
        <section class="bg-[#2D6A2F] text-white py-20 px-8 relative overflow-hidden">
            <!-- Subtle backdrop grid pattern in background -->
            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:24px_24px] pointer-events-none"></div>
            
            <div class="relative z-10 max-w-3xl mx-auto text-center space-y-8">
                <h2 class="text-3xl sm:text-5xl font-black heading-font text-white leading-tight">Ready to Coordinate Agricultural Shipments?</h2>
                <p class="text-white/80 text-base sm:text-lg leading-relaxed max-w-2xl mx-auto">
                    Aim to bring logistics visibility, transparency, and resource pooling to transport routes across General Santos, Polomolok, and Tupi.
                </p>
                <div class="pt-4 flex flex-wrap justify-center gap-4">
                    @guest
                        <a href="{{ route('register') }}" class="px-8 py-4 bg-white text-[#2D6A2F] font-bold rounded-2xl hover:bg-slate-100 transition-all shadow-lg">
                            Register Your Organization
                        </a>
                        <a href="{{ route('login') }}" class="px-8 py-4 bg-white/10 text-white font-semibold rounded-2xl hover:bg-white/15 border border-white/20 transition-all">
                            Access Portal
                        </a>
                    @else
                        <a href="{{ url('/dashboard') }}" class="px-8 py-4 bg-white text-slate-900 font-bold rounded-2xl hover:bg-slate-50 shadow-lg transition-all">
                            Open Dashboard
                        </a>
                    @endguest
                </div>
            </div>
        </section>

        <!-- Footer (Earthy dark) -->
        <footer class="w-full bg-[#1A1F12] text-white/60 border-t border-[#2D6A2F]/15 py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-12 gap-8 items-start mb-8">
                <!-- Branding and bio -->
                <div class="md:col-span-6 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-[#2D6A2F] to-[#5A8A3C] flex items-center justify-center shadow">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8a13 13 0 0 1-10 10Z"/>
                            </svg>
                        </div>
                        <span class="font-bold text-white text-lg heading-font">HarvestHaul</span>
                    </div>
                    <p class="text-xs leading-relaxed max-w-sm">
                        Optimizing agricultural transport routing, vehicle capacity limits, and pricing structures for farmer cooperatives and shipping networks.
                    </p>
                </div>

                <!-- Fast Menu links -->
                <div class="md:col-span-6 flex flex-wrap gap-x-12 gap-y-4 md:justify-end text-xs font-semibold text-white/50 uppercase tracking-wider pt-2">
                    <a href="#features" class="hover:text-white transition">Features</a>
                    <a href="#role-showcase" class="hover:text-white transition">Portal Modules</a>
                    <a href="#faq" class="hover:text-white transition">FAQ</a>
                    <a href="#how-it-works" class="hover:text-white transition">How It Works</a>
                </div>
            </div>

            <!-- Bottom Copyright row -->
            <div class="max-w-7xl mx-auto border-t border-white/5 pt-8 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs text-white/40">
                <p>&copy; {{ date('Y') }} HARVEST System. Engineered for Mindanao Hub Corridors. All rights reserved.</p>
                <div class="flex gap-4">
                    <a href="#" class="hover:underline">Privacy Policy</a>
                    <a href="#" class="hover:underline">Terms of Service</a>
                </div>
            </div>
        </footer>

        <!-- Inline Interactive Page Logic -->
        <script>
            // Header scroll transition logic
            window.addEventListener('scroll', function() {
                const header = document.getElementById('main-header');
                const logoText = document.getElementById('logo-text');
                const headerCta = document.getElementById('header-cta');
                
                if (window.scrollY > 20) {
                    header.className = "fixed top-0 left-0 right-0 z-50 bg-[#F8F6F1]/90 backdrop-blur-md border-b border-[#2D6A2F]/10 text-slate-800 transition-all duration-300 shadow-sm";
                    if (logoText) logoText.className = "text-2xl font-bold tracking-tight heading-font text-[#2D6A2F] transition-colors duration-300";
                    if (headerCta) headerCta.className = "px-5 py-2.5 bg-[#2D6A2F] text-white rounded-xl text-sm font-bold hover:bg-[#2D6A2F]/90 transition shadow-sm";
                    header.querySelectorAll('.nav-link').forEach(link => {
                        link.className = "nav-link text-sm font-semibold text-slate-600 hover:text-[#2D6A2F] hover:bg-[#2D6A2F]/5 px-4 py-2 rounded-full transition-all";
                    });
                } else {
                    header.className = "fixed top-0 left-0 right-0 z-50 bg-transparent border-b border-transparent transition-all duration-300 text-white";
                    if (logoText) logoText.className = "text-2xl font-bold tracking-tight heading-font text-white transition-colors duration-300";
                    if (headerCta) headerCta.className = "px-5 py-2.5 bg-white text-[#2D6A2F] rounded-xl text-sm font-bold hover:bg-white/90 transition shadow-sm";
                    header.querySelectorAll('.nav-link').forEach(link => {
                        link.className = "nav-link text-sm font-semibold text-white/80 hover:text-white hover:bg-white/10 px-4 py-2 rounded-full transition-all";
                    });
                }
            });

            // Mobile Menu Toggle
            function toggleMobileMenu() {
                var menu = document.getElementById('mobileMenu');
                if (menu.classList.contains('hidden')) {
                    menu.classList.remove('hidden');
                } else {
                    menu.classList.add('hidden');
                }
            }

            // Interactive SaaS Mockup Tabs
            function setMockupTab(tabId) {
                var dispatchTab = document.getElementById('mock-tab-dispatch');
                var splineTab = document.getElementById('mock-tab-spline');
                var proposalsTab = document.getElementById('mock-tab-proposals');
                
                var dispatchBtn = document.getElementById('btn-mock-dispatch');
                var splineBtn = document.getElementById('btn-mock-spline');
                var proposalsBtn = document.getElementById('btn-mock-proposals');

                // Reset all
                dispatchTab.classList.add('hidden');
                splineTab.classList.add('hidden');
                proposalsTab.classList.add('hidden');
                
                dispatchBtn.className = 'px-2.5 py-1 text-[10px] rounded-md bg-slate-900 text-slate-400 font-semibold border border-transparent hover:text-slate-200 transition';
                splineBtn.className = 'px-2.5 py-1 text-[10px] rounded-md bg-slate-900 text-slate-400 font-semibold border border-transparent hover:text-slate-200 transition';
                proposalsBtn.className = 'px-2.5 py-1 text-[10px] rounded-md bg-slate-900 text-slate-400 font-semibold border border-transparent hover:text-slate-200 transition';

                if (tabId === 'dispatch') {
                    dispatchTab.classList.remove('hidden');
                    dispatchBtn.className = 'px-2.5 py-1 text-[10px] rounded-md bg-slate-800 text-[#6EC95A] font-bold border border-slate-700 transition';
                } else if (tabId === 'spline') {
                    splineTab.classList.remove('hidden');
                    splineBtn.className = 'px-2.5 py-1 text-[10px] rounded-md bg-slate-800 text-[#6EC95A] font-bold border border-slate-700 transition';
                } else {
                    proposalsTab.classList.remove('hidden');
                    proposalsBtn.className = 'px-2.5 py-1 text-[10px] rounded-md bg-slate-800 text-[#6EC95A] font-bold border border-slate-700 transition';
                }
            }

            // Simulated proposal approval
            function approveProposal(cardId) {
                var card = document.getElementById(cardId);
                if (!card) return;
                
                var btn = card.querySelector('button');
                btn.innerHTML = 'Pooled ✓';
                btn.className = 'bg-[#6EC95A] text-slate-950 text-[10px] font-bold px-3 py-1.5 rounded-lg transition';
                
                card.className = 'bg-[#2D6A2F]/15 p-3 rounded-xl border border-[#2D6A2F]/30 flex items-center justify-between transition duration-300';
                
                var coopName = card.querySelector('span.text-slate-150').innerText;
                var cropName = card.querySelector('span.bg-slate-800').innerText;
                var logContainer = document.getElementById('live-log-container');
                
                var newLog = document.createElement('p');
                newLog.className = 'text-[#6EC95A] font-bold';
                newLog.innerText = `🔄 [Cooperative pooled] Combined ${coopName} (${cropName}) into route.`;
                
                logContainer.insertBefore(newLog, logContainer.firstChild);
                
                if (logContainer.children.length > 5) {
                    logContainer.removeChild(logContainer.lastChild);
                }
            }

            // Rotate Live Operational Ticker Text
            var tickerIndex = 0;
            setInterval(function() {
                var container = document.getElementById('live-log-container');
                if (!container || container.children.length <= 1) return;
                
                tickerIndex = (tickerIndex + 1) % container.children.length;
                container.style.transform = `translateY(-${tickerIndex * 20}px)`;
            }, 3000);

            // Interactive Role Tab switching
            function setRoleTab(role) {
                var roles = ['farmer', 'logistics', 'driver'];
                
                roles.forEach(function(r) {
                    var btn = document.getElementById(`tab-btn-${r}`);
                    var panel = document.getElementById(`role-panel-${r}`);
                    
                    if (r === role) {
                        btn.className = "px-5 py-2.5 rounded-xl text-sm font-bold transition bg-[#2D6A2F] text-white shadow-sm";
                        panel.classList.remove('hidden');
                        panel.className = "grid grid-cols-1 lg:grid-cols-12 gap-12 items-center transition-all duration-500 opacity-100 scale-100";
                    } else {
                        btn.className = "px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:text-slate-800 transition";
                        panel.classList.add('hidden');
                    }
                });
            }

            // FAQ Accordion Toggle
            function toggleFaq(index) {
                var ans = document.getElementById(`faq-ans-${index}`);
                var icon = document.getElementById(`faq-icon-${index}`);
                
                if (ans.classList.contains('hidden')) {
                    ans.classList.remove('hidden');
                    icon.innerText = '−';
                    icon.className = 'text-[#2D6A2F] font-bold transition duration-300 transform rotate-180';
                } else {
                    ans.classList.add('hidden');
                    icon.innerText = '+';
                    icon.className = 'text-slate-400 transition duration-300 transform rotate-0';
                }
            }
        </script>
    </body>
</html>
