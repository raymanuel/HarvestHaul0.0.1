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

        <style>
            body {
                font-family: 'Plus Jakarta Sans', sans-serif;
            }
            .heading-font {
                font-family: 'Outfit', sans-serif;
            }
            /* Grid and Ambient Glow Background Patterns */
            .bg-grid-pattern {
                background-size: 40px 40px;
                background-image: 
                    linear-gradient(to right, rgba(16, 185, 129, 0.04) 1px, transparent 1px),
                    linear-gradient(to bottom, rgba(16, 185, 129, 0.04) 1px, transparent 1px);
            }
            .ambient-glow-1 {
                background: radial-gradient(circle, rgba(16, 185, 129, 0.08) 0%, rgba(20, 184, 166, 0.02) 70%, transparent 100%);
            }
            .ambient-glow-2 {
                background: radial-gradient(circle, rgba(245, 158, 11, 0.04) 0%, rgba(16, 185, 129, 0.01) 60%, transparent 100%);
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
    <body class="bg-[#FAFBF9] text-[#1b1b18] antialiased min-h-screen relative overflow-x-hidden">

        <!-- Background Ambient Effects -->
        <div class="absolute inset-0 bg-grid-pattern pointer-events-none z-0"></div>
        <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] ambient-glow-1 rounded-full pointer-events-none z-0"></div>
        <div class="absolute top-[30%] right-[-10%] w-[60%] h-[60%] ambient-glow-2 rounded-full pointer-events-none z-0"></div>
        <div class="absolute bottom-[-10%] left-[20%] w-[50%] h-[50%] ambient-glow-1 rounded-full pointer-events-none z-0"></div>

        <!-- Sticky Header with Glassmorphism -->
        <header class="sticky top-0 z-50 bg-[#FAFBF9]/80 backdrop-blur-xl border-b border-emerald-500/10 transition-all duration-300">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex justify-between items-center">

                <!-- Logo Section -->
                <a href="/" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center shadow-md shadow-emerald-600/10 group-hover:scale-105 transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8a13 13 0 0 1-10 10Z"/>
                            <path d="M9 21s-4.5-3-4.5-7"/>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold tracking-tight heading-font bg-gradient-to-r from-emerald-800 to-teal-800 bg-clip-text text-transparent">HarvestHaul</span>
                </a>

                <!-- Navigation Links -->
                <nav class="hidden md:flex items-center gap-1.5 bg-white/50 backdrop-blur-md p-1.5 rounded-full border border-emerald-500/5 shadow-sm">
                    <a href="#features" class="text-sm font-medium text-slate-600 hover:text-emerald-700 hover:bg-emerald-50/50 px-4 py-2 rounded-full transition-all">Features</a>
                    <a href="#simulator" class="text-sm font-medium text-slate-600 hover:text-emerald-700 hover:bg-emerald-50/50 px-4 py-2 rounded-full transition-all">Savings Calculator</a>
                    <a href="#role-showcase" class="text-sm font-medium text-slate-600 hover:text-emerald-700 hover:bg-emerald-50/50 px-4 py-2 rounded-full transition-all">Portal Modules</a>
                    <a href="#how-it-works" class="text-sm font-medium text-slate-600 hover:text-emerald-700 hover:bg-emerald-50/50 px-4 py-2 rounded-full transition-all">How It Works</a>
                </nav>

                <!-- Auth Buttons -->
                <div class="hidden md:flex items-center gap-4">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl text-sm font-semibold hover:shadow-md hover:shadow-emerald-700/10 hover:brightness-105 transition shadow-sm">
                                Open Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-700 hover:text-emerald-700 px-4 py-2 transition">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-800 transition shadow-sm">
                                    Get Started
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex md:hidden">
                    <button type="button" onclick="toggleMobileMenu()" class="w-10 h-10 rounded-lg flex items-center justify-center bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 focus:outline-none" aria-label="Toggle Menu">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Drawer Menu -->
            <div id="mobileMenu" class="hidden md:hidden border-t border-emerald-500/10 bg-white/95 backdrop-blur-xl px-6 py-6 space-y-4 shadow-inner">
                <a href="#features" onclick="toggleMobileMenu()" class="block text-base font-semibold text-slate-700 hover:text-emerald-700 py-2 border-b border-slate-50">Features</a>
                <a href="#simulator" onclick="toggleMobileMenu()" class="block text-base font-semibold text-slate-700 hover:text-emerald-700 py-2 border-b border-slate-50">Savings Calculator</a>
                <a href="#role-showcase" onclick="toggleMobileMenu()" class="block text-base font-semibold text-slate-700 hover:text-emerald-700 py-2 border-b border-slate-50">Portal Modules</a>
                <a href="#how-it-works" onclick="toggleMobileMenu()" class="block text-base font-semibold text-slate-700 hover:text-emerald-700 py-2 border-b border-slate-50">How It Works</a>
                <div class="pt-4 flex flex-col gap-3">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="w-full text-center px-4 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-bold">Open Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="w-full text-center px-4 py-3 border border-slate-200 text-slate-700 rounded-xl font-semibold hover:bg-slate-50">Log in</a>
                        <a href="{{ route('register') }}" class="w-full text-center px-4 py-3 bg-slate-900 text-white rounded-xl font-semibold hover:bg-slate-800">Register</a>
                    @endauth
                </div>
            </div>
        </header>

        <!-- Main Body -->
        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 flex-1">

            <!-- Hero Section -->
            <section class="py-12 lg:py-24 grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
                
                <!-- Left text column -->
                <div class="lg:col-span-6 flex flex-col items-start space-y-8">
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold tracking-wide uppercase border border-emerald-600/25 bg-emerald-50/70 text-emerald-800 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
                        B2B Crop Routing & Logistics Engine
                    </span>
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 leading-[1.08] heading-font">
                        Consolidate Crop Shipments with <span class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">Real-Time Routing</span>
                    </h1>
                    <p class="text-lg text-slate-600 leading-relaxed max-w-xl">
                        Optimize agricultural logistics in Southern Mindanao. HarvestHaul automates vehicle sharing, maps sequence pickups, and bridges cooperative transport lanes from Tupi and Polomolok to General Santos.
                    </p>

                    <!-- Stats badging floating inside hero -->
                    <div class="grid grid-cols-3 gap-6 w-full max-w-lg p-5 rounded-2xl bg-white border border-slate-100 shadow-xl shadow-slate-100/50">
                        <div>
                            <p class="text-2xl sm:text-3xl font-black heading-font text-emerald-700">14</p>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mt-1">Regional Hubs</p>
                        </div>
                        <div class="border-l border-slate-100 pl-6">
                            <p class="text-2xl sm:text-3xl font-black heading-font text-emerald-700">84%</p>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mt-1">Space Util.</p>
                        </div>
                        <div class="border-l border-slate-100 pl-6">
                            <p class="text-2xl sm:text-3xl font-black heading-font text-teal-600">-15%</p>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mt-1">Fuel Saved</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-4 pt-2">
                        @guest
                            <a href="{{ route('register') }}" class="px-8 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-2xl font-bold hover:shadow-lg hover:shadow-emerald-700/20 hover:brightness-105 transition-all">
                                Join as Coordinator
                            </a>
                            <a href="#simulator" class="px-8 py-4 border border-slate-200 rounded-2xl font-bold text-slate-700 bg-white hover:bg-slate-50 hover:border-slate-300 transition-all shadow-sm">
                                Test Simulator
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" class="px-8 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-2xl font-bold hover:shadow-lg hover:shadow-emerald-700/20 hover:brightness-105 transition-all">
                                Access Dashboard →
                            </a>
                        @endguest
                    </div>
                </div>

                <!-- Right Interactive App Mockup -->
                <div class="lg:col-span-6 relative">
                    <!-- Glow background for the mockup frame -->
                    <div class="absolute -inset-1.5 bg-gradient-to-tr from-emerald-600 to-teal-500 rounded-3xl blur opacity-20 group-hover:opacity-30 transition duration-1000"></div>
                    
                    <!-- Main Frame mockup -->
                    <div class="relative bg-slate-900 text-white rounded-3xl border border-slate-800 shadow-2xl overflow-hidden">
                        
                        <!-- Top Header bar -->
                        <div class="bg-slate-950 px-6 py-4 flex items-center justify-between border-b border-slate-800">
                            <div class="flex items-center gap-2">
                                <div class="w-3.5 h-3.5 rounded-full bg-emerald-500/20 flex items-center justify-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                                </div>
                                <span class="text-xs font-semibold tracking-wider uppercase text-emerald-400">Live Operation Monitor</span>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="setMockupTab('dispatch')" id="btn-mock-dispatch" class="px-3 py-1 text-xs rounded-md bg-slate-800 text-emerald-400 font-bold border border-slate-700 transition">
                                    Map
                                </button>
                                <button onclick="setMockupTab('proposals')" id="btn-mock-proposals" class="px-3 py-1 text-xs rounded-md bg-slate-900 text-slate-400 font-semibold border border-transparent hover:text-slate-200 transition">
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
                                        <h4 class="text-xs text-slate-400 font-semibold uppercase">Active Route</h4>
                                        <p class="text-sm font-bold text-slate-100 mt-0.5">Consolidated Hub Run #HH-409</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="px-2 py-0.5 text-[10px] font-extrabold uppercase rounded bg-teal-500/10 text-teal-400 border border-teal-500/20">En Route</span>
                                        <p class="text-xs font-semibold text-slate-400 mt-1">Est: 45 min remaining</p>
                                    </div>
                                </div>

                                <!-- Custom Vector Map -->
                                <div class="bg-slate-950 rounded-xl border border-slate-800 p-4 relative h-48 overflow-hidden">
                                    <svg viewBox="0 0 400 160" class="w-full h-full">
                                        <!-- Connective paths -->
                                        <path d="M 50 120 Q 200 40 350 80" fill="none" stroke="#334155" stroke-width="4" stroke-linecap="round"/>
                                        <path d="M 50 120 Q 200 40 350 80" fill="none" stroke="#10B981" stroke-width="3" stroke-linecap="round" class="animated-route-path" stroke-opacity="0.8"/>
                                        
                                        <!-- Secondary consolidation paths -->
                                        <path d="M 190 20 L 200 68" fill="none" stroke="#10B981" stroke-width="2" stroke-dasharray="4,4" stroke-opacity="0.5"/>

                                        <!-- General Santos City Node -->
                                        <circle cx="350" cy="80" r="8" fill="#14B8A6" class="animate-pulse"/>
                                        <circle cx="350" cy="80" r="4" fill="#0D9488"/>
                                        <text x="350" y="105" fill="#94A3B8" font-size="10" font-weight="bold" text-anchor="middle">GenSan Terminal</text>

                                        <!-- Polomolok Intermediate Node -->
                                        <circle cx="200" cy="68" r="7" fill="#10B981"/>
                                        <circle cx="200" cy="68" r="3.5" fill="#059669"/>
                                        <text x="200" y="90" fill="#94A3B8" font-size="10" font-weight="bold" text-anchor="middle">Polomolok Sub-Hub</text>

                                        <!-- Tupi Northern Node -->
                                        <circle cx="50" cy="120" r="7" fill="#F59E0B" class="animate-pulse"/>
                                        <circle cx="50" cy="120" r="3.5" fill="#D97706"/>
                                        <text x="50" y="142" fill="#94A3B8" font-size="10" font-weight="bold" text-anchor="middle">Tupi Farms</text>

                                        <!-- Moving Delivery Truck Node -->
                                        <g id="map-truck-marker" transform="translate(0, 0)">
                                            <!-- SVG animateMotion along the path -->
                                            <circle cx="0" cy="0" r="5" fill="#34D399" class="animate-ping"/>
                                            <circle cx="0" cy="0" r="3" fill="#34D399"/>
                                            <animateMotion path="M 50 120 Q 200 40 350 80" dur="8s" repeatCount="indefinite" rotate="auto" />
                                        </g>
                                    </svg>

                                    <!-- Floating GPS Coordinates Card inside map -->
                                    <div class="absolute bottom-3 right-3 bg-slate-900/90 backdrop-blur border border-slate-700 px-2.5 py-1.5 rounded-lg flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                        <span class="text-[10px] font-mono text-slate-300">GPS TELEMETRY ACTIVE</span>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 2: PROPOSAL INBOX MOCKUP (INTERACTIVE) -->
                            <div id="mock-tab-proposals" class="hidden space-y-3.5">
                                <p class="text-xs text-slate-400 font-semibold mb-2">3 Pending Cooperative Proposals waiting for Route-Grouping:</p>
                                
                                <!-- Proposal Card 1 -->
                                <div id="prop-card-1" class="bg-slate-950 p-3 rounded-xl border border-slate-800 hover:border-emerald-500/40 transition duration-300 flex items-center justify-between">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-100">Tupi Pineapple Co-op</span>
                                            <span class="text-[9px] bg-slate-800 text-slate-300 px-1.5 py-0.5 rounded">Pineapples</span>
                                        </div>
                                        <div class="flex gap-4 text-[10px] text-slate-400">
                                            <span>📦 4.8 Tons</span>
                                            <span>📍 Tupi Hub</span>
                                        </div>
                                    </div>
                                    <button onclick="approveProposal('prop-card-1')" class="bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-extrabold px-3 py-1.5 rounded-lg shadow transition">
                                        Pool Load
                                    </button>
                                </div>

                                <!-- Proposal Card 2 -->
                                <div id="prop-card-2" class="bg-slate-950 p-3 rounded-xl border border-slate-800 hover:border-emerald-500/40 transition duration-300 flex items-center justify-between">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-100">Polomolok Fruit Growers</span>
                                            <span class="text-[9px] bg-slate-800 text-slate-300 px-1.5 py-0.5 rounded">Bananas</span>
                                        </div>
                                        <div class="flex gap-4 text-[10px] text-slate-400">
                                            <span>📦 3.2 Tons</span>
                                            <span>📍 Polomolok Hub</span>
                                        </div>
                                    </div>
                                    <button onclick="approveProposal('prop-card-2')" class="bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-extrabold px-3 py-1.5 rounded-lg shadow transition">
                                        Pool Load
                                    </button>
                                </div>

                                <!-- Proposal Card 3 -->
                                <div id="prop-card-3" class="bg-slate-950 p-3 rounded-xl border border-slate-800 hover:border-emerald-500/40 transition duration-300 flex items-center justify-between">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-100">Matutum Organic Farmers</span>
                                            <span class="text-[9px] bg-slate-800 text-slate-300 px-1.5 py-0.5 rounded">Vegetables</span>
                                        </div>
                                        <div class="flex gap-4 text-[10px] text-slate-400">
                                            <span>📦 1.5 Tons</span>
                                            <span>📍 Matutum Hub</span>
                                        </div>
                                    </div>
                                    <button onclick="approveProposal('prop-card-3')" class="bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-extrabold px-3 py-1.5 rounded-lg shadow transition">
                                        Pool Load
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Operational Ticker Bottom bar -->
                        <div class="bg-slate-950 px-6 py-4.5 border-t border-slate-800 flex items-center gap-4 text-xs font-mono">
                            <span class="text-emerald-400 font-bold uppercase shrink-0">Live Log:</span>
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
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Fleet Utilization</p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-sm font-black text-white">92.6%</span>
                                    <span class="text-[9px] font-bold text-emerald-400 bg-emerald-500/10 px-1 rounded">+14.2%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hidden sm:block absolute -left-8 top-16 bg-slate-900 border border-slate-800 p-4 rounded-2xl shadow-xl hover-float-delayed z-20">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-400 font-black text-xs">
                                ₱
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Hub Savings</p>
                                <p class="text-sm font-black text-white mt-0.5">₱142,500+</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Separation Border -->
            <div class="relative py-12">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-slate-200/60"></div>
                </div>
            </div>

            <!-- Features Section -->
            <section id="features" class="py-12 scroll-mt-24">
                <div class="text-center max-w-3xl mx-auto mb-20">
                    <span class="text-xs font-bold uppercase tracking-widest text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-500/10">Enterprise Solutions</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 heading-font mt-4">Everything You Need to Orchestrate Cooperative Agri-Freight</h2>
                    <p class="mt-4 text-base text-slate-500 leading-relaxed">Sophisticated, practical tools built to coordinate regional growers, logistics operators, and local delivery drivers.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="bg-white border border-slate-100 hover:border-emerald-500/15 p-8 rounded-3xl shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between group">
                        <div>
                            <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-[#2D8A37] group-hover:scale-110 transition-transform duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mt-6 heading-font">Dynamic Fleet Assignment</h3>
                            <p class="text-slate-500 mt-3 font-normal leading-relaxed text-sm">Register delivery trucks, log dimensional capacity, and map certified drivers directly to coordinated multi-stop regional runs through a unified operations terminal.</p>
                        </div>
                        <div class="pt-6 mt-6 border-t border-slate-50 flex items-center justify-between text-xs text-slate-400 group-hover:text-emerald-700 transition">
                            <span class="font-semibold uppercase tracking-wider">Automated Dispatch</span>
                            <span>→</span>
                        </div>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-white border border-slate-100 hover:border-emerald-500/15 p-8 rounded-3xl shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between group">
                        <div>
                            <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-[#2D8A37] group-hover:scale-110 transition-transform duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mt-6 heading-font">Live Geolocation Tracking</h3>
                            <p class="text-slate-500 mt-3 font-normal leading-relaxed text-sm">Follow active shipments visually along transport corridors. Drivers supply telemetry signals from their mobile browsers without complex software installs.</p>
                        </div>
                        <div class="pt-6 mt-6 border-t border-slate-50 flex items-center justify-between text-xs text-slate-400 group-hover:text-emerald-700 transition">
                            <span class="font-semibold uppercase tracking-wider">Browser Telemetry</span>
                            <span>→</span>
                        </div>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-white border border-slate-100 hover:border-emerald-500/15 p-8 rounded-3xl shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between group">
                        <div>
                            <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-[#2D8A37] group-hover:scale-110 transition-transform duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mt-6 heading-font">Route-Based Load Pooling</h3>
                            <p class="text-slate-500 mt-3 font-normal leading-relaxed text-sm">Consolidate multiple small cooperative harvests sequentially along the path of a single high-capacity freight carrier, cutting deadhead runs and individual costs.</p>
                        </div>
                        <div class="pt-6 mt-6 border-t border-slate-50 flex items-center justify-between text-xs text-slate-400 group-hover:text-emerald-700 transition">
                            <span class="font-semibold uppercase tracking-wider">Cooperative Pooling</span>
                            <span>→</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Separation Border -->
            <div class="relative py-12">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-slate-200/60"></div>
                </div>
            </div>

            <!-- Savings Calculator Section (SaaS Engagement Widget) -->
            <section id="simulator" class="py-12 scroll-mt-24">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
                    
                    <!-- Left Context Panel -->
                    <div class="lg:col-span-5 space-y-6">
                        <span class="text-xs font-bold uppercase tracking-widest text-teal-700 bg-teal-50 px-3 py-1 rounded-full border border-teal-500/10">Dynamic ROI Calculator</span>
                        <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 heading-font leading-tight">See How Collaborative Agriculture Pooling Pays Off</h2>
                        <p class="text-slate-600 leading-relaxed text-sm sm:text-base">
                            Individual shipping creates highly underutilized trucks and duplicate routes. With HarvestHaul's sequential dispatch engine, farms pool logistics to share the costs. 
                        </p>
                        <div class="space-y-3.5 pt-2">
                            <div class="flex items-center gap-3.5">
                                <div class="w-6 h-6 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 font-bold text-xs">✓</div>
                                <p class="text-sm font-semibold text-slate-700">Sequence pickups to utilize 100% of vehicle capacity</p>
                            </div>
                            <div class="flex items-center gap-3.5">
                                <div class="w-6 h-6 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 font-bold text-xs">✓</div>
                                <p class="text-sm font-semibold text-slate-700">Split freight expenses proportionally based on weight</p>
                            </div>
                            <div class="flex items-center gap-3.5">
                                <div class="w-6 h-6 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 font-bold text-xs">✓</div>
                                <p class="text-sm font-semibold text-slate-700">Reduce overall regional carbon footprint in Mindanao</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right Slider Widget Panel -->
                    <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-100 shadow-xl p-8 sm:p-10 relative overflow-hidden">
                        <!-- Diagonal decorative header background -->
                        <div class="absolute top-0 inset-x-0 h-2 bg-gradient-to-r from-emerald-500 via-teal-500 to-amber-500"></div>

                        <h3 class="text-lg font-bold text-slate-900 heading-font mb-6 flex items-center gap-2">
                            <span>🚛</span> Configure Consolidation Scenario
                        </h3>

                        <div class="space-y-6">
                            <!-- Slider 1 -->
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <label class="font-bold text-slate-700">Number of Cooperating Farms</label>
                                    <span class="text-emerald-700 font-bold" id="val-farms">5 Farms</span>
                                </div>
                                <input type="range" min="2" max="12" value="5" class="w-full h-2 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-emerald-600" id="slider-farms" oninput="calculateSavings()">
                                <div class="flex justify-between text-[10px] text-slate-400 font-bold uppercase">
                                    <span>2 (Min)</span>
                                    <span>12 (Max)</span>
                                </div>
                            </div>

                            <!-- Slider 2 -->
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <label class="font-bold text-slate-700">Average Volume per Farm (Tons)</label>
                                    <span class="text-emerald-700 font-bold" id="val-volume">4.5 Tons</span>
                                </div>
                                <input type="range" min="1" max="15" step="0.5" value="4.5" class="w-full h-2 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-emerald-600" id="slider-volume" oninput="calculateSavings()">
                                <div class="flex justify-between text-[10px] text-slate-400 font-bold uppercase">
                                    <span>1 Ton</span>
                                    <span>15 Tons</span>
                                </div>
                            </div>

                            <!-- Slider 3 -->
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <label class="font-bold text-slate-700">Total Transit Distance (km)</label>
                                    <span class="text-emerald-700 font-bold" id="val-distance">65 km</span>
                                </div>
                                <input type="range" min="10" max="150" value="65" class="w-full h-2 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-emerald-600" id="slider-distance" oninput="calculateSavings()">
                                <div class="flex justify-between text-[10px] text-slate-400 font-bold uppercase">
                                    <span>10 km</span>
                                    <span>150 km</span>
                                </div>
                            </div>
                        </div>

                        <!-- Results Dashboard Inside Calculator -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-8 pt-8 border-t border-slate-100 bg-slate-50/50 p-4 rounded-2xl">
                            <div class="text-center p-2">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Trips Required</p>
                                <div class="mt-1 flex items-baseline justify-center gap-1">
                                    <span class="text-slate-400 line-through text-xs font-bold" id="calc-trips-trad">5</span>
                                    <span class="text-lg font-black text-emerald-700" id="calc-trips-pooled">2</span>
                                </div>
                                <p class="text-[9px] text-emerald-600 font-bold mt-0.5">Pooled Run</p>
                            </div>

                            <div class="text-center p-2 border-l border-slate-200/60">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Fuel Saved</p>
                                <p class="text-lg font-black text-emerald-700 mt-1" id="calc-fuel">78L</p>
                                <p class="text-[9px] text-slate-400 font-semibold mt-0.5">Consolidated</p>
                            </div>

                            <div class="text-center p-2 border-l border-slate-200/60">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Est. Cash Saved</p>
                                <p class="text-lg font-black text-teal-700 mt-1" id="calc-cash">₱4,680</p>
                                <p class="text-[9px] text-emerald-600 font-bold mt-0.5" id="calc-pct-saved">-60% saved</p>
                            </div>

                            <div class="text-center p-2 border-l border-slate-200/60">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">CO₂ Prevented</p>
                                <p class="text-lg font-black text-emerald-800 mt-1" id="calc-co2">209 kg</p>
                                <p class="text-[9px] text-slate-400 font-semibold mt-0.5">Emissions reduced</p>
                            </div>
                        </div>
                    </div>

                </div>
            </section>

            <!-- Separation Border -->
            <div class="relative py-12">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-slate-200/60"></div>
                </div>
            </div>

            <!-- Role Showcase Section (Interactive Tabs) -->
            <section id="role-showcase" class="py-12 scroll-mt-24">
                <div class="text-center max-w-2xl mx-auto mb-14">
                    <span class="text-xs font-bold uppercase tracking-widest text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-500/10">Dynamic Workspace Portals</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 heading-font mt-4">One Integrated Platform, Four Custom Environments</h2>
                </div>

                <!-- Tabs header -->
                <div class="flex flex-wrap justify-center gap-2 mb-10 bg-slate-100 p-1.5 rounded-2xl max-w-2xl mx-auto border border-slate-200/40">
                    <button onclick="setRoleTab('farmer')" id="tab-btn-farmer" class="px-5 py-2.5 rounded-xl text-sm font-bold transition bg-white text-emerald-700 shadow-sm border border-slate-200/30">
                        🌾 Farmers
                    </button>
                    <button onclick="setRoleTab('logistics')" id="tab-btn-logistics" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:text-slate-800 transition">
                        🏢 Logistics Partners
                    </button>
                    <button onclick="setRoleTab('driver')" id="tab-btn-driver" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:text-slate-800 transition">
                        🚛 Drivers
                    </button>
                    <button onclick="setRoleTab('admin')" id="tab-btn-admin" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:text-slate-800 transition">
                        🛡️ Admins
                    </button>
                </div>

                <!-- Showcase panels -->
                <div class="bg-white border border-slate-100 rounded-3xl shadow-xl p-8 sm:p-12 min-h-[420px] transition-all duration-300">
                    
                    <!-- FARMER PORTAL VIEW -->
                    <div id="role-panel-farmer" class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                        <div class="lg:col-span-6 space-y-6">
                            <h3 class="text-2xl font-bold text-slate-900 heading-font">Get Access to Grouped Shipping Without High Minimums</h3>
                            <p class="text-slate-500 leading-relaxed text-sm sm:text-base">
                                Small farms usually struggle with logistics because shipping companies demand massive minimum volumes. In the Farmer Portal, simply submit your upcoming harvest dimensions. HarvestHaul's engine finds other growers along your highway corridor to build a full load.
                            </p>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                                    <span class="text-lg">📈</span>
                                    <h4 class="font-bold text-sm text-slate-900 mt-2">Corridor Matching</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">Autogrouped with local neighbors.</p>
                                </div>
                                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                                    <span class="text-lg">⚖️</span>
                                    <h4 class="font-bold text-sm text-slate-900 mt-2">Fair Pricing</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">Pay only for the space you use.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Mockup illustration inside tab -->
                        <div class="lg:col-span-6 bg-slate-50 p-6 rounded-2xl border border-slate-100 space-y-4">
                            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-3">
                                <div class="flex justify-between items-center pb-2 border-b border-slate-100">
                                    <h4 class="font-bold text-sm text-slate-800">New Harvest Posting</h4>
                                    <span class="px-2 py-0.5 text-[9px] bg-emerald-100 text-emerald-800 rounded font-bold uppercase">Ready</span>
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
                                <button class="w-full bg-emerald-600 text-white rounded-lg py-2.5 text-xs font-bold shadow hover:bg-emerald-700 transition">
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
                                For freight partners, HarvestHaul acts as a smart dispatch system. View regional grower listings, select compatible harvests, and generate highly optimized sequential route proposals. Track truck load volumes and allocate available drivers instantly.
                            </p>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                                    <span class="text-lg">🗺️</span>
                                    <h4 class="font-bold text-sm text-slate-900 mt-2">Sequential Routing</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">Calculates optimal multi-stop lines.</p>
                                </div>
                                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                                    <span class="text-lg">🚛</span>
                                    <h4 class="font-bold text-sm text-slate-900 mt-2">Fleet Monitoring</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">Track truck space and drivers.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Mockup illustration inside tab -->
                        <div class="lg:col-span-6 bg-slate-50 p-6 rounded-2xl border border-slate-100 space-y-4">
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
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            <span class="font-semibold">Stop 2: Polomolok Farmers</span>
                                        </div>
                                        <span class="text-slate-400">Load: 3.2T</span>
                                    </div>
                                    <div class="p-2.5 bg-slate-900 text-slate-100 rounded-lg flex justify-between items-center text-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-teal-400"></span>
                                            <span class="font-semibold text-white">Destination: GenSan Terminal</span>
                                        </div>
                                        <span class="text-slate-300 font-bold">Total: 8.0T / 10T max</span>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button class="flex-1 bg-slate-900 text-white rounded-lg py-2 text-xs font-bold shadow hover:bg-slate-800 transition">
                                        Assign Driver
                                    </button>
                                    <button class="flex-1 bg-emerald-600 text-white rounded-lg py-2 text-xs font-bold shadow hover:bg-emerald-700 transition">
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
                                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                                    <span class="text-lg">📱</span>
                                    <h4 class="font-bold text-sm text-slate-900 mt-2">Mobile PWA Layout</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">Lightweight, loads in low signal.</p>
                                </div>
                                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                                    <span class="text-lg">📡</span>
                                    <h4 class="font-bold text-sm text-slate-900 mt-2">Telemetry Signal</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">Instant geographic updates.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Mockup illustration inside tab -->
                        <div class="lg:col-span-6 flex justify-center">
                            <!-- Mock Smartphone Screen -->
                            <div class="w-64 bg-slate-900 border-[6px] border-slate-850 rounded-[36px] shadow-2xl p-4 text-white relative aspect-[9/18]">
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
                                    <div class="space-y-2 pt-2">
                                        <div class="p-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex gap-2.5 items-start">
                                            <span class="text-emerald-400 font-bold text-[10px] mt-0.5">✓</span>
                                            <div>
                                                <h5 class="text-[10px] font-bold text-slate-100">Tupi Hub Pickup</h5>
                                                <p class="text-[8px] text-emerald-400 font-semibold mt-0.5">Completed — Loaded 4.8T</p>
                                            </div>
                                        </div>

                                        <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 flex gap-2.5 items-start">
                                            <span class="w-3.5 h-3.5 rounded-full bg-slate-800 flex items-center justify-center text-[8px] font-bold mt-0.5 text-slate-400">2</span>
                                            <div class="flex-1">
                                                <h5 class="text-[10px] font-bold text-slate-200">Polomolok Sub-Hub</h5>
                                                <p class="text-[8px] text-slate-400 mt-0.5">Load scheduled: 3.2T bananas</p>
                                                <button class="w-full mt-2 bg-emerald-600 text-white rounded py-1 text-[9px] font-bold hover:bg-emerald-700 transition">
                                                    Mark Loaded at Hub
                                                </button>
                                            </div>
                                        </div>

                                        <div class="p-2.5 rounded-xl bg-slate-950/40 border border-slate-900/60 opacity-50 flex gap-2.5 items-start">
                                            <span class="w-3.5 h-3.5 rounded-full bg-slate-800 flex items-center justify-center text-[8px] font-bold mt-0.5 text-slate-500">3</span>
                                            <div>
                                                <h5 class="text-[10px] font-bold text-slate-400">GenSan Distribution</h5>
                                                <p class="text-[8px] text-slate-500 mt-0.5">Final Drop-off point</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Geolocation tracking Switch Inside Phone Screen -->
                                    <div class="bg-slate-950 p-2.5 rounded-xl border border-slate-800 flex items-center justify-between mt-2">
                                        <div class="flex flex-col">
                                            <span class="text-[8px] font-bold text-slate-400 uppercase">GPS Broadcaster</span>
                                            <span class="text-[9px] text-emerald-400 font-bold">ACTIVE</span>
                                        </div>
                                        <div class="w-8 h-4 rounded-full bg-emerald-600 p-0.5 flex justify-end items-center cursor-pointer">
                                            <div class="w-3 h-3 rounded-full bg-white"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ADMIN PORTAL VIEW -->
                    <div id="role-panel-admin" class="hidden grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                        <div class="lg:col-span-6 space-y-6">
                            <h3 class="text-2xl font-bold text-slate-900 heading-font">Maintain System Security, Accounts & Verify Co-op Licenses</h3>
                            <p class="text-slate-500 leading-relaxed text-sm sm:text-base">
                                Administrators supervise the entire network. Manage the crop matrix (varieties and categories), review business compliance records uploaded by logistics entities, toggle account access levels, and analyze logs to preserve trust.
                            </p>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                                    <span class="text-lg">🛡️</span>
                                    <h4 class="font-bold text-sm text-slate-900 mt-2">Document Verification</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">Review licensing and cooperative uploads.</p>
                                </div>
                                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                                    <span class="text-lg">🌿</span>
                                    <h4 class="font-bold text-sm text-slate-900 mt-2">Crop Matrix Editor</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">Manage agricultural taxonomies.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Mockup illustration inside tab -->
                        <div class="lg:col-span-6 bg-slate-50 p-6 rounded-2xl border border-slate-100 space-y-4">
                            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-3.5">
                                <div class="flex justify-between items-center pb-2 border-b border-slate-100">
                                    <h4 class="font-bold text-sm text-slate-800">Compliance Processing Queue</h4>
                                    <span class="px-2 py-0.5 text-[9px] bg-amber-100 text-amber-800 rounded font-bold uppercase">2 Pending Review</span>
                                </div>

                                <div class="space-y-3">
                                    <!-- Pending Document Row -->
                                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 flex items-center justify-between text-xs">
                                        <div>
                                            <p class="font-bold text-slate-800">General Santos Logistics Inc.</p>
                                            <p class="text-[10px] text-slate-400 mt-0.5">📄 Freight Operator License (#F-903)</p>
                                        </div>
                                        <div class="flex gap-1.5 shrink-0">
                                            <button class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-2 py-1 rounded font-semibold text-[10px] transition">View</button>
                                            <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-2.5 py-1 rounded font-bold text-[10px] shadow transition">Approve</button>
                                        </div>
                                    </div>

                                    <!-- Pending Document Row 2 -->
                                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 flex items-center justify-between text-xs">
                                        <div>
                                            <p class="font-bold text-slate-800">Tupi Pineapple Co-op Assoc.</p>
                                            <p class="text-[10px] text-slate-400 mt-0.5">📄 Certificate of Registration (#R-412)</p>
                                        </div>
                                        <div class="flex gap-1.5 shrink-0">
                                            <button class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-2 py-1 rounded font-semibold text-[10px] transition">View</button>
                                            <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-2.5 py-1 rounded font-bold text-[10px] shadow transition">Approve</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </section>

            <!-- Separation Border -->
            <div class="relative py-12">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-slate-200/60"></div>
                </div>
            </div>

            <!-- How It Works Section -->
            <section id="how-it-works" class="py-12 scroll-mt-24">
                <div class="text-center max-w-2xl mx-auto mb-20">
                    <span class="text-xs font-bold uppercase tracking-widest text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-500/10">Process Overview</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 heading-font mt-4">Simple, Highly Optimized Workflow</h2>
                    <p class="mt-4 text-slate-500 leading-relaxed">How HarvestHaul connects regional agricultural assets in Southern Mindanao step-by-step.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-12 relative">
                    
                    <!-- Dotted Connector Line (Desktop Only) -->
                    <div class="hidden md:block absolute top-12 left-1/6 right-1/6 h-0.5 border-t-2 border-dashed border-emerald-500/20 z-0"></div>

                    <!-- Step 1 -->
                    <div class="relative z-10 flex flex-col items-center text-center space-y-4">
                        <div class="w-16 h-16 rounded-2xl bg-white border border-emerald-500/15 text-emerald-700 flex items-center justify-center font-black text-lg shadow-md">
                            01
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 heading-font">Account Onboarding</h3>
                        <p class="text-slate-500 leading-relaxed text-sm max-w-xs">
                            Users authenticate and set up profiles matching their agricultural context: Farmer Cooperatives, Freight Partners, or Drivers.
                        </p>
                    </div>

                    <!-- Step 2 -->
                    <div class="relative z-10 flex flex-col items-center text-center space-y-4">
                        <div class="w-16 h-16 rounded-2xl bg-white border border-emerald-500/15 text-emerald-700 flex items-center justify-center font-black text-lg shadow-md">
                            02
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 heading-font">Route & Load Planning</h3>
                        <p class="text-slate-500 leading-relaxed text-sm max-w-xs">
                            Growers submit their crop quantities, allowing Logistics Partners to group regional pickups into highly efficient transport routes.
                        </p>
                    </div>

                    <!-- Step 3 -->
                    <div class="relative z-10 flex flex-col items-center text-center space-y-4">
                        <div class="w-16 h-16 rounded-2xl bg-white border border-emerald-500/15 text-emerald-700 flex items-center justify-center font-black text-lg shadow-md">
                            03
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 heading-font">Dispatch & Telemetry</h3>
                        <p class="text-slate-500 leading-relaxed text-sm max-w-xs">
                            Coordinators deploy optimized routes. Assigned drivers track milestones and broadcast GPS geolocation to the hub dashboard.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Pre-Footer Glowing Call to Action (CTA) -->
            <section class="my-20 relative rounded-3xl overflow-hidden shadow-2xl bg-slate-950 border border-slate-800">
                <!-- Glowing gradient overlays -->
                <div class="absolute inset-0 bg-gradient-to-tr from-emerald-950 via-slate-950 to-teal-950 pointer-events-none z-0"></div>
                <div class="absolute top-[-30%] right-[-10%] w-96 h-96 rounded-full bg-emerald-500/10 blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-[-30%] left-[-10%] w-96 h-96 rounded-full bg-teal-500/10 blur-[100px] pointer-events-none"></div>
                
                <div class="relative z-10 px-8 py-16 sm:py-20 max-w-3xl mx-auto text-center space-y-8">
                    <h2 class="text-3xl sm:text-5xl font-black heading-font text-white leading-tight">Ready to Optimize Agricultural Shipments?</h2>
                    <p class="text-slate-400 text-base sm:text-lg leading-relaxed max-w-2xl mx-auto">
                        Bring coordinate logistics, transparency, and resource pooling to transport routes across General Santos, Polomolok, and Tupi.
                    </p>
                    <div class="pt-4 flex flex-wrap justify-center gap-4">
                        @guest
                            <a href="{{ route('register') }}" class="px-8 py-4 bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-bold rounded-2xl hover:brightness-110 shadow-lg shadow-emerald-500/15 transition-all">
                                Register Your Organization
                            </a>
                            <a href="{{ route('login') }}" class="px-8 py-4 bg-white/10 text-white font-semibold rounded-2xl hover:bg-white/15 border border-white/10 transition-all">
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

        </main>

        <!-- Footer -->
        <footer class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 border-t border-slate-200/60 pt-12 pb-14">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start mb-8">
                <!-- Branding and bio -->
                <div class="md:col-span-6 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center shadow">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8a13 13 0 0 1-10 10Z"/>
                            </svg>
                        </div>
                        <span class="font-bold text-slate-900 text-lg heading-font">HarvestHaul</span>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed max-w-sm">
                        Optimizing agricultural transport routing, vehicle capacity limits, and pricing structures for farmer cooperatives and shipping networks.
                    </p>
                </div>

                <!-- Fast Menu links -->
                <div class="md:col-span-6 flex flex-wrap gap-x-12 gap-y-4 md:justify-end text-xs font-semibold text-slate-500 uppercase tracking-wider pt-2">
                    <a href="#features" class="hover:text-emerald-700 transition">Features</a>
                    <a href="#simulator" class="hover:text-emerald-700 transition">Savings Calculator</a>
                    <a href="#role-showcase" class="hover:text-emerald-700 transition">Portal Modules</a>
                    <a href="#how-it-works" class="hover:text-emerald-700 transition">How It Works</a>
                </div>
            </div>

            <!-- Bottom Copyright row -->
            <div class="border-t border-slate-100 pt-8 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs text-slate-400">
                <p>&copy; {{ date('Y') }} HarvestHaul System. Engineered for Mindanao Hub Corridors. All rights reserved.</p>
                <div class="flex gap-4">
                    <a href="#" class="hover:underline">Privacy Policy</a>
                    <a href="#" class="hover:underline">Terms of Service</a>
                </div>
            </div>
        </footer>

        <!-- Inline Interactive Page Logic -->
        <script>
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
                // Get tabs
                var dispatchTab = document.getElementById('mock-tab-dispatch');
                var proposalsTab = document.getElementById('mock-tab-proposals');
                var dispatchBtn = document.getElementById('btn-mock-dispatch');
                var proposalsBtn = document.getElementById('btn-mock-proposals');

                if (tabId === 'dispatch') {
                    dispatchTab.classList.remove('hidden');
                    proposalsTab.classList.add('hidden');

                    dispatchBtn.className = 'px-3 py-1 text-xs rounded-md bg-slate-800 text-emerald-400 font-bold border border-slate-700 transition';
                    proposalsBtn.className = 'px-3 py-1 text-xs rounded-md bg-slate-900 text-slate-400 font-semibold border border-transparent hover:text-slate-200 transition';
                } else {
                    dispatchTab.classList.add('hidden');
                    proposalsTab.classList.remove('hidden');

                    dispatchBtn.className = 'px-3 py-1 text-xs rounded-md bg-slate-900 text-slate-400 font-semibold border border-transparent hover:text-slate-200 transition';
                    proposalsBtn.className = 'px-3 py-1 text-xs rounded-md bg-slate-800 text-emerald-400 font-bold border border-slate-700 transition';
                }
            }

            // Simulated proposal approval
            function approveProposal(cardId) {
                var card = document.getElementById(cardId);
                if (!card) return;
                
                // Add green success state to the button inside
                var btn = card.querySelector('button');
                btn.innerHTML = 'Pooled ✓';
                btn.className = 'bg-teal-500 text-slate-950 text-[10px] font-bold px-3 py-1.5 rounded-lg transition';
                
                // Add border highlight
                card.className = 'bg-emerald-950/20 p-3 rounded-xl border border-emerald-500/40 flex items-center justify-between transition duration-300';
                
                // Trigger live ticker update
                var coopName = card.querySelector('span.text-slate-100').innerText;
                var cropName = card.querySelector('span.bg-slate-800').innerText;
                var logContainer = document.getElementById('live-log-container');
                
                var newLog = document.createElement('p');
                newLog.className = 'text-emerald-400 font-bold';
                newLog.innerText = `🔄 [Cooperative pooled] Combined ${coopName} (${cropName}) into route.`;
                
                logContainer.insertBefore(newLog, logContainer.firstChild);
                
                // Keep the live logs within 5 items and animate slightly
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
                // Simple vertical translation to cycle logs
                container.style.transform = `translateY(-${tickerIndex * 20}px)`;
            }, 3000);

            // Interactive ROI Savings Calculator Logic
            function calculateSavings() {
                // Inputs
                var farmsVal = parseFloat(document.getElementById('slider-farms').value);
                var volumeVal = parseFloat(document.getElementById('slider-volume').value);
                var distanceVal = parseFloat(document.getElementById('slider-distance').value);

                // Update text indicators
                document.getElementById('val-farms').innerText = farmsVal + " Cooperating Farms";
                document.getElementById('val-volume').innerText = volumeVal.toFixed(1) + " Tons / Farm";
                document.getElementById('val-distance').innerText = distanceVal + " km Transit";

                // Traditional Trips vs Pooled Trips
                // 1 truck has a maximum capacity of 10 tons.
                var totalVolume = farmsVal * volumeVal;
                var traditionalTrips = farmsVal; // Every farm hires 1 truck independently
                var pooledTrips = Math.max(1, Math.ceil(totalVolume / 10.0)); // Consolidated runs of 10T trucks

                // Calculate savings details
                // Standard fuel assumption: 0.12 liters of diesel per kilometer per trip
                var fuelTraditional = traditionalTrips * distanceVal * 0.14;
                var fuelPooled = pooledTrips * distanceVal * 0.16; // slightly higher fuel consumption for loaded truck
                
                var litersSaved = Math.max(0, Math.round(fuelTraditional - fuelPooled));
                var pricePerLiter = 60.0; // PHP fuel cost assumption
                var cashSaved = litersSaved * pricePerLiter;
                
                // CO2 calculation: ~2.68 kg CO2 per liter of diesel
                var co2Saved = Math.max(0, Math.round(litersSaved * 2.68));
                
                // Percent fuel cost saved
                var pctSaved = fuelTraditional > 0 ? Math.round(((fuelTraditional - fuelPooled) / fuelTraditional) * 100) : 0;

                // Update results display
                document.getElementById('calc-trips-trad').innerText = traditionalTrips;
                document.getElementById('calc-trips-pooled').innerText = pooledTrips;
                document.getElementById('calc-fuel').innerText = litersSaved + " Liters";
                document.getElementById('calc-cash').innerText = "₱" + cashSaved.toLocaleString();
                document.getElementById('calc-co2').innerText = co2Saved + " kg";
                document.getElementById('calc-pct-saved').innerText = `-${pctSaved}% fuel cost`;
            }

            // Interactive Role Tab switching
            function setRoleTab(role) {
                var roles = ['farmer', 'logistics', 'driver', 'admin'];
                
                roles.forEach(function(r) {
                    var btn = document.getElementById(`tab-btn-${r}`);
                    var panel = document.getElementById(`role-panel-${r}`);
                    
                    if (r === role) {
                        // Activate tab UI styles
                        btn.className = "px-5 py-2.5 rounded-xl text-sm font-bold transition bg-white text-emerald-700 shadow-sm border border-slate-200/30";
                        panel.classList.remove('hidden');
                        panel.className = "grid grid-cols-1 lg:grid-cols-12 gap-12 items-center transition-all duration-500 opacity-100 scale-100";
                    } else {
                        // Deactivate tab UI styles
                        btn.className = "px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:text-slate-800 transition";
                        panel.classList.add('hidden');
                    }
                });
            }

            // Initial calculation run
            window.addEventListener('DOMContentLoaded', function() {
                calculateSavings();
            });
        </script>
    </body>
</html>
