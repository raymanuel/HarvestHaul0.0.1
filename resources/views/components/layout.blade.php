<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'HarvestHaul Portal — Coordinated Agribusiness' }}</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#3A7D44">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').then(function(reg) {
                    console.log('Global Service Worker registered successfully with scope: ', reg.scope);
                }).catch(function(err) {
                    console.error('Global Service Worker registration failed: ', err);
                });
            });
        }
    </script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    
    <script>
        (function() {
            var theme = localStorage.getItem('theme');
            var isDark = theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches);
            @if(Auth::check() && (Auth::user()->role === 'admin' || Auth::user()->role === 'farmer' || Auth::user()->role === 'logistics_partner' || Auth::user()->role === 'buyer'))
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            @endif
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Stack for page-specific head assets (Leaflet CSS, etc.) --}}
    @stack('head')

    <style>
        body {
            font-family: 'DM Sans', sans-serif;
            background-color: #FAFAF5;
        }
        .heading-font {
            font-family: 'Instrument Serif', sans-serif;
        }
        /* Custom Scrollbar for sidebar */
        .custom-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
        }
        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Sidebar collapse transitions */
        #sidebar-nav {
            transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .main-wrapper {
            transition: padding-left 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .top-navbar {
            transition: left 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Collapsed state: hide text labels */
        .sidebar-collapsed .nav-label,
        .sidebar-collapsed .section-label,
        .sidebar-collapsed .logo-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
            white-space: nowrap;
            transition: opacity 0.15s, width 0.2s;
        }

        /* Expanded state: show text labels */
        #sidebar-nav:not(.sidebar-collapsed) .nav-label,
        #sidebar-nav:not(.sidebar-collapsed) .section-label,
        #sidebar-nav:not(.sidebar-collapsed) .logo-text {
            opacity: 1;
            width: auto;
            transition: opacity 0.2s 0.1s, width 0.2s;
        }

        /* Collapsed link centering */
        .sidebar-collapsed .nav-link {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }
        /* Collapsed state: show first-letter badges */
        .sidebar-collapsed .nav-letter {
            display: flex !important;
        }
        /* Expanded state: hide first-letter badges */
        #sidebar-nav:not(.sidebar-collapsed) .nav-letter {
            display: none !important;
        }
        .sidebar-collapsed .section-label {
            height: 0;
            margin: 0;
            padding: 0;
        }

        /* Collapsed logo centering */
        .sidebar-collapsed .logo-link {
            justify-content: center;
        }

        

        /* Tooltip on hover when collapsed */
        .sidebar-collapsed .nav-link {
            position: relative;
        }
        .sidebar-collapsed .nav-link:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 12px;
            background: #1e293b;
            color: #e2e8f0;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            z-index: 100;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            pointer-events: none;
        }

        /* Collapse toggle button */
        .collapse-toggle {
            transition: transform 0.25s;
        }
        .sidebar-collapsed .collapse-toggle {
            transform: rotate(180deg);
        }
        .sidebar-collapsed #trust-verification-dropdown,
        .sidebar-collapsed #agricultural-matrix-dropdown,
        .sidebar-collapsed #governance-dropdown {
            padding-left: 0;
        }

        /* Focus-visible rings for accessibility */
        .nav-link:focus-visible,
        button:focus-visible,
        a:focus-visible {
            outline: 2px solid #3A7D44;
            outline-offset: 2px;
            border-radius: 8px;
        }

        /* Increase touch targets on sidebar nav links */
        .nav-link {
            min-height: 44px;
        }

        /* Body text line height for readability */
        p, .text-sm, .text-xs {
            line-height: 1.6;
        }

        /* Dark Mode overrides for Admin Layout */
        html.dark body {
            background-color: #111318;
            color: #f1f5f9;
        }
        html.dark #top-navbar {
            background-color: #1a1d24;
            border-color: #2a2d35;
        }
        html.dark #top-navbar h2 {
            color: #e2e8f0;
        }
        html.dark #top-navbar span {
            color: #94a3b8;
        }
        html.dark #top-navbar .bg-slate-50 {
            background-color: #334155;
            border-color: #475569;
            color: #cbd5e1;
        }
        html.dark #top-navbar .bg-slate-50:hover {
            background-color: #475569;
            color: #ffffff;
        }
        html.dark #top-navbar .border-l {
            border-color: #334155;
        }
        html.dark #top-navbar p {
            color: #f1f5f9;
        }
    </style>
</head>
<body class="text-slate-800 antialiased min-h-screen">

    <!-- Skip to content link for keyboard accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-[100] focus:bg-brand focus:text-white focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-harvest">
        Skip to main content
    </a>

    <!-- Mobile Top Header -->
    <header class="lg:hidden sticky top-0 z-50 bg-[#111827] text-white px-5 py-4 flex justify-between items-center border-b border-slate-800 shadow-md">
        <a href="/dashboard" class="flex items-center gap-2 group">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-[#3A7D44] to-[#2E6336] flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8a13 13 0 0 1-10 10Z"/>
                </svg>
            </div>
            <span class="text-lg font-bold tracking-tight heading-font">HarvestHaul</span>
        </a>
        <button onclick="toggleMobileSidebar()" class="p-2 bg-slate-800 hover:bg-slate-700 rounded-lg text-slate-300 transition" aria-label="Open Navigation Menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
    </header>

    <!-- Overlay Backdrop for Mobile Navigation -->
    <div id="sidebar-overlay" onclick="toggleMobileSidebar()" class="hidden fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-30 transition-opacity"></div>

    <div class="flex">

        <!-- Sidebar Navigation Drawer (Collapsible) -->
        <aside id="sidebar-nav" class="fixed inset-y-0 left-0 z-40 w-64 bg-[#111827] text-slate-300 border-r border-slate-800 flex flex-col justify-between transform -translate-x-full lg:translate-x-0 shadow-2xl lg:shadow-none">
            
            <!-- Sidebar Header & Logo -->
            <div class="px-5 py-5 border-b border-slate-800 shrink-0 flex items-center logo-container">
                <a href="/dashboard" class="flex items-center gap-3 group logo-link">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#3A7D44] to-[#2E6336] flex items-center justify-center shadow-md shadow-[#3A7D44]/10 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8a13 13 0 0 1-10 10Z"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight text-white heading-font logo-text">HarvestHaul</span>
                </a>
            </div>

            <!-- Navigation Links Scroll Area -->
            <nav class="flex-1 px-3 py-6 overflow-y-auto custom-scroll space-y-7">
                
                <!-- Base Dashboard Node -->
                <div class="space-y-1.5">
                    <a href="/dashboard" data-tooltip="Dashboard" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->is('dashboard') ? (Auth::check() && Auth::user()->role === 'buyer' ? 'bg-harvest text-white shadow-md shadow-harvest/10' : 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10') : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                        <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">D</span>
                        <span class="nav-label">Dashboard</span>
                    </a>
                </div>

                <!-- ROLE 1: FARMER VIEW NODES -->
                @if(Auth::check() && Auth::user()->role === 'farmer')
                    <div class="space-y-1.5">
                        <p class="section-label text-[10px] font-bold text-slate-500 uppercase tracking-widest px-4">Crops & Harvests</p>
                        
                        <a href="{{ route('harvests.index') }}" data-tooltip="My Active Harvests" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('harvests.*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">M</span>
                            <span class="nav-label">My Active Harvests</span>
                        </a>

                        <a href="{{ route('farmer.proposals') }}" data-tooltip="Pooling Proposals" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('farmer.proposals') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">P</span>
                            <span class="nav-label">Pooling Proposals</span>
                        </a>

                        <a href="{{ route('tracking.index') }}" data-tooltip="Track Shipments" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('tracking.index') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">T</span>
                            <span class="nav-label">Track Shipments</span>
                        </a>

                        <a href="{{ route('farmer.documents') }}" data-tooltip="Regulatory Documents" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('farmer.documents*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">R</span>
                            <span class="nav-label">Regulatory Documents</span>
                        </a>

                        <a href="{{ route('farmer.reports.profit-expense') }}" data-tooltip="Profit & Expense" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('farmer.reports.*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">P</span>
                            <span class="nav-label">Profit & Expense</span>
                        </a>

                        <a href="{{ route('profile.show') }}" data-tooltip="My Profile" class="nav-link lg:hidden flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('profile.*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">M</span>
                            <span class="nav-label">My Profile</span>
                        </a>
                    </div>
                @endif

                <!-- ROLE 5: BUYER VIEW NODES -->
                @if(Auth::check() && (Auth::user()->role === 'buyer' || (Auth::user()->role === 'logistics_partner' && $authUser->logisticsProfile && $authUser->logisticsProfile->isCooperative())))
                    <div class="space-y-1.5">
                        <p class="section-label text-[10px] font-bold text-slate-500 uppercase tracking-widest px-4">Market</p>
                        
                        <a href="{{ route('buyer.crop-board') }}" data-tooltip="Crop Board" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('buyer.crop-board') ? (Auth::user()->role === 'buyer' ? 'bg-harvest text-white shadow-md shadow-harvest/10' : 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10') : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">C</span>
                            <span class="nav-label">Crop Board</span>
                        </a>

                        <a href="{{ route('buyer.tracking') }}" data-tooltip="Delivery Tracking" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('buyer.tracking') ? (Auth::user()->role === 'buyer' ? 'bg-harvest text-white shadow-md shadow-harvest/10' : 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10') : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">D</span>
                            <span class="nav-label">Delivery Tracking</span>
                        </a>

                        <a href="{{ route('profile.show') }}" data-tooltip="My Profile" class="nav-link lg:hidden flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('profile.*') ? (Auth::user()->role === 'buyer' ? 'bg-harvest text-white shadow-md shadow-harvest/10' : 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10') : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">M</span>
                            <span class="nav-label">My Profile</span>
                        </a>
                    </div>
                @endif

                <!-- ROLE 2: ADMIN VIEW NODES -->                @if(Auth::check() && Auth::user()->role === 'admin')
                    @php
                        $isTrustVerificationActive = request()->routeIs('admin.users*') ||
                                                     request()->routeIs('admin.farmers*') ||
                                                     request()->routeIs('admin.farmer-documents*') ||
                                                     request()->routeIs('admin.logistics') ||
                                                     request()->routeIs('admin.logistics.*') ||
                                                     request()->routeIs('admin.logistics-documents*') ||
                                                     request()->routeIs('admin.buyers*') ||
                                                     request()->routeIs('admin.drivers*');
                    @endphp
                    <!-- People / Users Sub-Group Dropdown -->
                    <div class="space-y-1.5">
                        <button onclick="toggleTrustVerification()" data-tooltip="Trust & Verification" class="nav-link w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-semibold transition text-slate-400 hover:text-white hover:bg-slate-800/60 select-none">
                            <div class="flex items-center gap-3">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">T</span>
                                <span class="nav-label">Trust & Verification</span>
                            </div>
                            <span class="nav-label">
                                <svg id="trust-verification-chevron" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transform transition-transform {{ $isTrustVerificationActive ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        </button>
                        
                        <div id="trust-verification-dropdown" class="{{ $isTrustVerificationActive ? '' : 'hidden' }} mt-1 pl-4 space-y-1.5 transition-all">
                            <a href="{{ route('admin.users') }}" data-tooltip="User Management" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.users*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">U</span>
                                <span class="nav-label">User Management</span>
                            </a>

                            <a href="{{ route('admin.farmers') }}" data-tooltip="Farmer Verification" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.farmers*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">F</span>
                                <span class="nav-label">Farmer Verification</span>
                            </a>
                            
                            <a href="{{ route('admin.farmer-documents') }}" data-tooltip="Farmer Licenses" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.farmer-documents*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">F</span>
                                <span class="nav-label">Farmer Licenses</span>
                            </a>

                            <a href="{{ route('admin.buyers') }}" data-tooltip="Buyer Verification" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.buyers*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">B</span>
                                <span class="nav-label">Buyer Verification</span>
                            </a>

                            <a href="{{ route('admin.logistics') }}" data-tooltip="Logistics Registry" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.logistics', 'admin.logistics.*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">L</span>
                                <span class="nav-label">Logistics Registry</span>
                            </a>
                            
                            <a href="{{ route('admin.logistics-documents') }}" data-tooltip="Logistics Credentials" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.logistics-documents*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">L</span>
                                <span class="nav-label">Logistics Credentials</span>
                            </a>

                            <a href="{{ route('admin.drivers') }}" data-tooltip="Driver Verification" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.drivers*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">D</span>
                                <span class="nav-label">Driver Verification</span>
                            </a>
                        </div>
                    </div>

                    @php
                        $isAgriculturalMatrixActive = request()->routeIs('admin.harvests*') || request()->routeIs('admin.crops*');
                    @endphp
                    <!-- Platform Settings Group Dropdown -->
                    <div class="space-y-1.5">
                        <button onclick="toggleAgriculturalMatrix()" data-tooltip="Crops & Harvests" class="nav-link w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-semibold transition text-slate-400 hover:text-white hover:bg-slate-800/60 select-none">
                            <div class="flex items-center gap-3">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">C</span>
                                <span class="nav-label">Crops & Harvests</span>
                            </div>
                            <span class="nav-label">
                                <svg id="agricultural-matrix-chevron" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transform transition-transform {{ $isAgriculturalMatrixActive ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        </button>
                        
                        <div id="agricultural-matrix-dropdown" class="{{ $isAgriculturalMatrixActive ? '' : 'hidden' }} mt-1 pl-4 space-y-1.5 transition-all">
                            <a href="{{ route('admin.harvests') }}" data-tooltip="Harvest Oversight" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.harvests*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">H</span>
                                <span class="nav-label">Harvest Oversight</span>
                            </a>

                            <a href="{{ route('admin.crops.index') }}" data-tooltip="Crop Registry" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.crops*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">C</span>
                                <span class="nav-label">Crop Registry</span>
                            </a>
                        </div>
                    </div>

                    @php
                        $isGovernanceActive = request()->routeIs('admin.audit-logs*') || request()->routeIs('admin.analytics*');
                    @endphp
                    <!-- System Audit Group Dropdown -->
                    <div class="space-y-1.5">
                        <button onclick="toggleGovernance()" data-tooltip="Governance" class="nav-link w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-semibold transition text-slate-400 hover:text-white hover:bg-slate-800/60 select-none">
                            <div class="flex items-center gap-3">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">G</span>
                                <span class="nav-label">Governance</span>
                            </div>
                            <span class="nav-label">
                                <svg id="governance-chevron" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transform transition-transform {{ $isGovernanceActive ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        </button>
                        
                        <div id="governance-dropdown" class="{{ $isGovernanceActive ? '' : 'hidden' }} mt-1 pl-4 space-y-1.5 transition-all">
                            <a href="{{ route('admin.analytics') }}" data-tooltip="Platform Analytics" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.analytics*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">P</span>
                                <span class="nav-label">Platform Analytics</span>
                            </a>
                            <a href="{{ route('admin.audit-logs') }}" data-tooltip="Platform Audit Logs" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.audit-logs*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">P</span>
                                <span class="nav-label">Platform Audit Logs</span>
                            </a>
                        </div>
                    </div>
                @endif

                <!-- ROLE 3: LOGISTICS PARTNER NODES -->
                @if(Auth::check() && Auth::user()->role === 'logistics_partner')
                    <div class="space-y-1.5">
                        <p class="section-label text-[10px] font-bold text-slate-500 uppercase tracking-widest px-4">Operations</p>
                        
                        <a href="{{ route('route.optimization') }}" data-tooltip="Route Planning" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('route.optimization') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">R</span>
                            <span class="nav-label">Route Planning</span>
                        </a>

                        <a href="{{ route('pooling.index') }}" data-tooltip="Proposal Inbox" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('pooling.index') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">P</span>
                            <span class="nav-label">Proposal Inbox</span>
                        </a>

                        <a href="{{ route('pooling.cost-ledger.index') }}" data-tooltip="Cost Ledger" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('pooling.cost-ledger*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">C</span>
                            <span class="nav-label">Cost Ledger</span>
                        </a>

                        <a href="{{ route('logistics.analytics') }}" data-tooltip="Fleet Analytics" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('logistics.analytics') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">F</span>
                            <span class="nav-label">Fleet Analytics</span>
                        </a>

                        <a href="{{ route('logistics.reports.trips') }}" data-tooltip="Trip Report" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('logistics.reports.*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">T</span>
                            <span class="nav-label">Trip Report</span>
                        </a>

                        <a href="{{ route('logistics.documents') }}" data-tooltip="Business License Docs" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('logistics.documents*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">B</span>
                            <span class="nav-label">Business License Docs</span>
                        </a>

                        <a href="{{ route('logistics.drivers.index') }}" data-tooltip="Manage Drivers" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('logistics.drivers*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">M</span>
                            <span class="nav-label">Manage Drivers</span>
                        </a>

                        <a href="{{ route('logistics.vehicles.index') }}" data-tooltip="Manage Vehicles" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('logistics.vehicles*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">M</span>
                            <span class="nav-label">Manage Vehicles</span>
                        </a>

                        <a href="{{ route('profile.show') }}" data-tooltip="My Profile" class="nav-link lg:hidden flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('profile.*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">M</span>
                            <span class="nav-label">My Profile</span>
                        </a>
                    </div>
                @endif

                <!-- ROLE 4: DRIVER NODES -->
                @if(Auth::check() && Auth::user()->role === 'driver')
                    <div class="space-y-1.5">
                        <p class="section-label text-[10px] font-bold text-slate-500 uppercase tracking-widest px-4">On Route</p>
                        
                        <a href="{{ route('driver.dashboard') }}" data-tooltip="Route Navigation" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('driver.*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">R</span>
                            <span class="nav-label">Route Navigation</span>
                        </a>
                        <a href="{{ route('driver.dashboard') }}" data-tooltip="Delivery Confirmations" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('driver.jobs.*') ? 'bg-[#3A7D44] text-white shadow-md shadow-[#3A7D44]/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center uppercase">D</span>
                            <span class="nav-label">Delivery Confirmations</span>
                        </a>
                    </div>
                @endif

            </nav>

            

        </aside>

        <!-- Main Display Content Shell Wrapper (Offset on desktop) -->
        <div id="main-content" tabindex="-1" class="main-wrapper flex-1 lg:pl-64 min-w-0 flex flex-col min-h-screen outline-none">

            <!-- Horizontal Desktop Navbar -->
            <nav id="top-navbar" class="top-navbar hidden lg:flex sticky top-0 z-30 h-20 bg-white border-b border-slate-200/80 px-8 items-center justify-between shadow-sm">
                <!-- Left side: collapse toggle + portal indicator -->
                <div class="flex items-center gap-4">
                    <!-- Topbar collapse toggle -->
                    <button onclick="toggleSidebarCollapse()" class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition" title="Toggle sidebar">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">HarvestHaul</span>
                        <h2 class="text-sm font-bold text-slate-700 mt-0.5"><span class="{{ Auth::user()->role === 'buyer' ? 'text-harvest dark:text-harvest' : 'text-[#3A7D44]' }} uppercase font-black">{{ Auth::user()->role }}</span></h2>
                    </div>
                </div>

                <!-- User profile and avatar menu -->
                <div class="flex items-center gap-6 select-none">
                    <!-- Notifications Dropdown -->
                    <x-notification-dropdown />

                    <!-- Dark Mode Toggle (Admin, Farmer, Logistics & Buyer) -->
                    @if(Auth::check() && (Auth::user()->role === 'admin' || Auth::user()->role === 'farmer' || Auth::user()->role === 'logistics_partner' || Auth::user()->role === 'buyer'))
                        <button onclick="toggleDarkMode()" class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-slate-700 border border-slate-100 dark:border-slate-600 flex items-center justify-center text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white transition cursor-pointer" title="Toggle dark mode">
                            <!-- Moon Icon (shown in light mode) -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                            <!-- Sun Icon (shown in dark mode) -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </button>
                    @endif

                    <!-- Profile Menu Dropdown -->
                    <div class="relative" id="profile-menu">
                        <button onclick="toggleProfileDropdown()" class="flex items-center gap-3.5 pl-6 border-l border-slate-100 dark:border-slate-700 hover:opacity-90 transition cursor-pointer focus:outline-none text-left">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#3A7D44]/10 to-[#2E6336]/10 dark:from-slate-700 dark:to-slate-600 border border-slate-100 dark:border-slate-600 flex items-center justify-center text-slate-600 dark:text-[#3A7D44] font-extrabold uppercase text-sm select-none">
                                {{ substr(Auth::user()->name, 0, 2) }}
                            </div>
                            <div class="hidden sm:block">
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-200 leading-none flex items-center gap-1">
                                    {{ Auth::user()->name }}
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </p>
                                <p class="text-[10px] text-slate-400 dark:text-slate-400 font-semibold mt-1">{{ Auth::user()->email }}</p>
                            </div>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="profile-dropdown" class="hidden absolute right-0 mt-2 w-56 bg-white dark:bg-slate-800 border border-slate-200/85 dark:border-slate-700 rounded-2xl shadow-xl z-50 overflow-hidden py-1.5 px-1.5 space-y-1">
                            @if(Auth::check() && (Auth::user()->role === 'farmer' || Auth::user()->role === 'logistics_partner' || Auth::user()->role === 'buyer'))
                                <a href="{{ route('profile.show') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/40 hover:text-[#3A7D44] dark:hover:text-[#3A7D44] transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Profile Settings
                                </a>
                                <a href="{{ route('notifications.preferences') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/40 hover:text-[#3A7D44] dark:hover:text-[#3A7D44] transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    Notification Settings
                                </a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}" class="w-full" id="logout-form">
                                @csrf
                                <button type="button" onclick="swalConfirm(document.getElementById('logout-form'), {title:'Sign Out', text:'Are you sure you want to sign out?', icon:'question', confirmText:'Yes, sign out', cancelText:'Cancel', confirmColor:'#ef4444'})" class="cursor-pointer w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-bold text-red-650 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/20 hover:text-red-700 dark:hover:text-red-300 transition-all duration-200 active:scale-[0.97] text-left">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Render Area -->
            <main class="flex-1 px-6 lg:px-10 relative">
                {{ $slot }}
            </main>

        </div>

    </div>

    <!-- Toggle scripts -->
    <script>
        // Mobile sidebar toggle
        function toggleMobileSidebar() {
            var sidebar = document.getElementById('sidebar-nav');
            var overlay = document.getElementById('sidebar-overlay');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        // Desktop sidebar collapse toggle
        function toggleSidebarCollapse() {
            var sidebar = document.getElementById('sidebar-nav');
            var mainWrapper = document.querySelector('.main-wrapper');
            var isCollapsed = sidebar.classList.contains('sidebar-collapsed');

            if (isCollapsed) {
                // Expand
                sidebar.classList.remove('sidebar-collapsed');
                sidebar.style.width = '16rem'; // w-64
                mainWrapper.style.paddingLeft = '16rem';
            } else {
                // Collapse
                sidebar.classList.add('sidebar-collapsed');
                sidebar.style.width = '4.5rem'; // ~72px icon-only
                mainWrapper.style.paddingLeft = '4.5rem';
            }

            // Persist state
            localStorage.setItem('sidebar-collapsed', !isCollapsed);
        }

        // Restore sidebar state on load (desktop only)
        (function() {
            if (window.innerWidth >= 1024) {
                var saved = localStorage.getItem('sidebar-collapsed');
                if (saved === 'true') {
                    var sidebar = document.getElementById('sidebar-nav');
                var mainWrapper = document.querySelector('.main-wrapper');
                sidebar.classList.add('sidebar-collapsed');
                sidebar.style.width = '4.5rem';
                mainWrapper.style.paddingLeft = '4.5rem';
                }
            }
        })();

        // Trust & Verification dropdown toggle
        function toggleTrustVerification() {
            var sidebar = document.getElementById('sidebar-nav');
            var mainWrapper = document.querySelector('.main-wrapper');
            var isCollapsed = sidebar.classList.contains('sidebar-collapsed');

            // If collapsed, expand first so content is visible
            if (isCollapsed) {
                sidebar.classList.remove('sidebar-collapsed');
                sidebar.style.width = '16rem'; // w-64
                mainWrapper.style.paddingLeft = '16rem';
                localStorage.setItem('sidebar-collapsed', 'false');
            }

            var dropdown = document.getElementById('trust-verification-dropdown');
            var chevron = document.getElementById('trust-verification-chevron');
            var isHidden = dropdown.classList.contains('hidden');

            if (isHidden) {
                dropdown.classList.remove('hidden');
                chevron.classList.add('rotate-90');
            } else {
                dropdown.classList.add('hidden');
                chevron.classList.remove('rotate-90');
            }
        }

        // Crops & Harvests dropdown toggle
        function toggleAgriculturalMatrix() {
            var sidebar = document.getElementById('sidebar-nav');
            var mainWrapper = document.querySelector('.main-wrapper');
            var isCollapsed = sidebar.classList.contains('sidebar-collapsed');

            // If collapsed, expand first so content is visible
            if (isCollapsed) {
                sidebar.classList.remove('sidebar-collapsed');
                sidebar.style.width = '16rem'; // w-64
                mainWrapper.style.paddingLeft = '16rem';
                localStorage.setItem('sidebar-collapsed', 'false');
            }

            var dropdown = document.getElementById('agricultural-matrix-dropdown');
            var chevron = document.getElementById('agricultural-matrix-chevron');
            var isHidden = dropdown.classList.contains('hidden');

            if (isHidden) {
                dropdown.classList.remove('hidden');
                chevron.classList.add('rotate-90');
            } else {
                dropdown.classList.add('hidden');
                chevron.classList.remove('rotate-90');
            }
        }

        // Governance dropdown toggle
        function toggleGovernance() {
            var sidebar = document.getElementById('sidebar-nav');
            var mainWrapper = document.querySelector('.main-wrapper');
            var isCollapsed = sidebar.classList.contains('sidebar-collapsed');

            // If collapsed, expand first so content is visible
            if (isCollapsed) {
                sidebar.classList.remove('sidebar-collapsed');
                sidebar.style.width = '16rem'; // w-64
                mainWrapper.style.paddingLeft = '16rem';
                localStorage.setItem('sidebar-collapsed', 'false');
            }

            var dropdown = document.getElementById('governance-dropdown');
            var chevron = document.getElementById('governance-chevron');
            var isHidden = dropdown.classList.contains('hidden');

            if (isHidden) {
                dropdown.classList.remove('hidden');
                chevron.classList.add('rotate-90');
            } else {
                dropdown.classList.add('hidden');
                chevron.classList.remove('rotate-90');
            }
        }

        // Dark Mode toggle
        function toggleDarkMode() {
            var isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }

        // Notifications Dropdown Toggle
        function toggleNotificationsDropdown() {
            var dropdown = document.getElementById('notifications-dropdown');
            dropdown.classList.toggle('hidden');
        }

        // Profile Dropdown Toggle
        function toggleProfileDropdown() {
            var dropdown = document.getElementById('profile-dropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        window.addEventListener('click', function(e) {
            var profileDropdown = document.getElementById('profile-dropdown');
            var profileMenu = document.getElementById('profile-menu');
            if (profileDropdown && profileMenu && !profileMenu.contains(e.target)) {
                profileDropdown.classList.add('hidden');
            }
        });

    </script>

    {{-- SweetAlert Global Flash Handler --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: @json(session('success')),
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                    background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                    color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b',
                    iconColor: '#3A7D44',
                    customClass: { popup: 'rounded-xl shadow-lg border border-[#3A7D44]/20' },
                    ariaLive: 'polite'
                });
            @endif
            @if(session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: @json(session('error')),
                    timer: 4500,
                    timerProgressBar: true,
                    showConfirmButton: true,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ef4444',
                    toast: false,
                    background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                    color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b',
                    customClass: { popup: 'rounded-xl shadow-lg' },
                    ariaLive: 'assertive'
                });
            @endif
            @if(session('warning'))
                Swal.fire({
                    icon: 'warning',
                    title: 'Notice',
                    text: @json(session('warning')),
                    timer: 5000,
                    timerProgressBar: true,
                    showConfirmButton: true,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#f59e0b',
                    toast: false,
                    background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                    color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b',
                    customClass: { popup: 'rounded-xl shadow-lg' },
                    ariaLive: 'assertive'
                });
            @endif
        });

        /**
         * Global modal helpers.
         * Usage: openModal('modal-id') / closeModal('modal-id')
         */
        function openModal(id) {
            const m = document.getElementById(id);
            if (m) m.classList.remove('hidden');
        }
        function closeModal(id) {
            const m = document.getElementById(id);
            if (m) m.classList.add('hidden');
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.fixed.inset-0.z-50:not(.hidden)').forEach(function(m) {
                    if (!m.id || m.id === 'sidebar-overlay') return;
                    m.classList.add('hidden');
                });
            }
        });

        /**
         * Password visibility toggle.
         * Usage: togglePassword('input-id')
         */
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const eyeOpen = document.getElementById(inputId + '-eye-open');
            const eyeClosed = document.getElementById(inputId + '-eye-closed');
            if (!input) return;
            if (input.type === 'password') {
                input.type = 'text';
                if (eyeOpen) eyeOpen.classList.add('hidden');
                if (eyeClosed) eyeClosed.classList.remove('hidden');
            } else {
                input.type = 'password';
                if (eyeOpen) eyeOpen.classList.remove('hidden');
                if (eyeClosed) eyeClosed.classList.add('hidden');
            }
        }

        /**
         * Global SweetAlert confirm helper.
         * Usage: swalConfirm(formEl, { title, text, confirmText, icon })
         */
        function swalConfirm(formOrCallback, opts = {}) {
            const defaults = {
                title: opts.title || 'Are you sure?',
                text: opts.text || 'This action cannot be undone.',
                icon: opts.icon || 'warning',
                confirmText: opts.confirmText || 'Yes, proceed',
                cancelText: opts.cancelText || 'Cancel',
                confirmColor: opts.confirmColor || '#3A7D44',
                cancelColor: opts.cancelColor || '#64748b'
            };

            Swal.fire({
                title: defaults.title,
                text: defaults.text,
                icon: defaults.icon,
                showCancelButton: true,
                confirmButtonText: defaults.confirmText,
                cancelButtonText: defaults.cancelText,
                confirmButtonColor: defaults.confirmColor,
                cancelButtonColor: defaults.cancelColor,
                background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b',
                customClass: { popup: 'rounded-xl shadow-2xl' },
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    if (typeof formOrCallback === 'function') {
                        formOrCallback();
                    } else if (formOrCallback && formOrCallback.submit) {
                        formOrCallback.submit();
                    }
                }
            });
        }
    </script>

    {{-- Inline form validation on blur --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('form').forEach(function(form) {
                form.querySelectorAll('input[required], input[type="email"], input[type="password"], textarea[required]').forEach(function(input) {
                    input.addEventListener('blur', function() {
                        validateField(this);
                    });
                    input.addEventListener('input', function() {
                        if (this.classList.contains('border-red-500')) {
                            validateField(this);
                        }
                    });
                });

                form.addEventListener('submit', function(e) {
                    var fields = form.querySelectorAll('input[required], input[type="email"], input[type="password"], textarea[required]');
                    var firstInvalid = null;
                    fields.forEach(function(field) {
                        validateField(field);
                        if (field.classList.contains('border-red-500') && !firstInvalid) {
                            firstInvalid = field;
                        }
                    });
                    if (firstInvalid) {
                        e.preventDefault();
                        firstInvalid.focus();
                    }
                });
            });

            function validateField(field) {
                var errorId = field.name + '-error';
                var existing = document.getElementById(errorId);
                var valid = true;
                var message = '';

                if (field.hasAttribute('required') && !field.value.trim()) {
                    valid = false;
                    message = 'This field is required';
                } else if (field.type === 'email' && field.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) {
                    valid = false;
                    message = 'Please enter a valid email address';
                } else if (field.type === 'password' && field.value && field.value.length < 8) {
                    valid = false;
                    message = 'Password must be at least 8 characters';
                }

                field.setAttribute('aria-invalid', valid ? 'false' : 'true');

                if (valid) {
                    field.classList.remove('border-red-500', 'dark:border-red-400');
                    field.classList.add('border-green-500', 'dark:border-green-400');
                    field.removeAttribute('aria-describedby');
                    if (existing) existing.remove();
                } else {
                    field.classList.remove('border-green-500', 'dark:border-green-400');
                    field.classList.add('border-red-500', 'dark:border-red-400');
                    if (!existing) {
                        var err = document.createElement('p');
                        err.id = errorId;
                        err.className = 'mt-1 text-xs text-red-500 dark:text-red-400';
                        err.textContent = message;
                        field.parentNode.insertBefore(err, field.nextSibling);
                    }
                    field.setAttribute('aria-describedby', errorId);
                }
            }
        });
    </script>

    {{-- Stack for page-specific JS (Leaflet, Turf, init code) --}}
    @stack('scripts')

    {{-- Floating Negotiations Widget --}}
    @if(Auth::check() && (Auth::user()->role === 'farmer' || Auth::user()->role === 'buyer' || (Auth::user()->role === 'logistics_partner' && $authUser->logisticsProfile && $authUser->logisticsProfile->isCooperative())))
        <x-negotiations-widget />
    @endif

</body>
</html>
