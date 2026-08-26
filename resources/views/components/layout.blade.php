<!DOCTYPE html>
<html lang="en" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="HarvestHaul — Coordinated agribusiness platform for General Santos City. Manage harvests, logistics, and crop distribution.">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="32x32" href="/favicon-32x32.png">
    <title>{{ $title ?? 'HarvestHaul Portal — Coordinated Agribusiness' }}</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#065F46">
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
    
    <!-- Fonts -->
    <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">
    
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

    {{-- Stack for page-specific head assets (Leaflet CSS, etc.) --}}
    @stack('head')

    <style>
        body {
            background-color: #FFFFFF;
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

        /* Sidebar collapse: instant snap (animated width/padding caused reflow jank).
           Geometry lives in app.css (#sidebar-nav.sidebar-collapsed ~ #main-content). */
        .top-navbar {
            left: 16rem;
            right: 0;
        }
        @media (min-width: 1024px) {
            #main-content > main {
                padding-top: 5rem;
            }
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
            background: #334155;
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
        .sidebar-collapsed [data-submenu-panel] {
            padding-left: 0;
        }

        /* Focus-visible rings for accessibility */
        .nav-link:focus-visible,
        button:focus-visible,
        a:focus-visible {
            outline: 2px solid #065F46;
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

        html.dark #top-navbar {
            background-color: #101A2B;
            border-color: rgba(255, 255, 255, 0.08);
        }
        html.dark #top-navbar h2 {
            color: #ffffff;
        }
        html.dark #top-navbar span {
            color: rgba(255, 255, 255, 0.7);
        }
        html.dark #top-navbar .border-l {
            border-color: rgba(255, 255, 255, 0.15);
        }
        html.dark #top-navbar p {
            color: #ffffff;
        }

        /* Light mode: neutral cream topbar with slate ghost bell button */
        #top-navbar #notifications-menu > button {
            background: rgba(15, 23, 42, 0.06);
            border-color: rgba(15, 23, 42, 0.12);
            color: rgba(15, 23, 42, 0.65);
        }
        #top-navbar #notifications-menu > button:hover {
            background: rgba(15, 23, 42, 0.1);
            color: #0f172a;
        }
        #top-navbar #notifications-menu #notification-badge {
            border-color: #e2e8f0;
        }

        /* Dark mode: neutral slate topbar with white ghost bell button */
        html.dark #top-navbar #notifications-menu > button {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.15);
            color: rgba(255, 255, 255, 0.85);
        }
        html.dark #top-navbar #notifications-menu > button:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }
        html.dark #top-navbar #notifications-menu #notification-badge {
            border-color: #1e293b;
        }

        /* Stat card info tooltip */
        .card-info {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: help;
        }
        .card-info::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%);
            background: #1e293b;
            color: #e2e8f0;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.5;
            white-space: normal;
            width: max-content;
            max-width: 230px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.15s, visibility 0.15s;
            z-index: 60;
            pointer-events: none;
            text-align: left;
        }
        .card-info:hover::after,
        .card-info:focus-visible::after {
            opacity: 1;
            visibility: visible;
        }
        #sidebar-nav .nav-link.nav-active {
            background-color: #065F46;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(6, 95, 70, 0.35);
        }
        #sidebar-nav .nav-link.nav-active .nav-letter {
            background-color: rgba(255, 255, 255, 0.18);
            color: #ffffff;
        }
    </style>
</head>
<body class="app-shell m-0 p-0 text-slate-800 antialiased min-h-screen overflow-x-hidden">

    <!-- Skip to content link for keyboard accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-[100] focus:bg-brand focus:text-white focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-harvest">
        Skip to main content
    </a>

    <!-- Mobile Top Header -->
    <header class="lg:hidden sticky top-0 z-50 bg-white dark:bg-slate-900 text-slate-900 dark:text-white px-5 py-4 flex justify-between items-center border-b border-slate-200 dark:border-black/20 shadow-md">
        <a href="/dashboard" class="flex items-center gap-2 group">
            <div class="w-8 h-8 rounded-lg bg-brand-700 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8a13 13 0 0 1-10 10Z"/>
                </svg>
            </div>
            <span class="text-lg font-bold tracking-tight heading-font">HarvestHaul</span>
        </a>
        <button onclick="toggleMobileSidebar()" class="p-2 bg-slate-900/5 hover:bg-slate-900/10 rounded-lg text-slate-700 dark:bg-white/10 dark:hover:bg-white/20 dark:text-white transition" aria-label="Open Navigation Menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
    </header>

    <div class="flex">
        <!-- Overlay Backdrop for Mobile Navigation -->
        <div id="sidebar-overlay" onclick="toggleMobileSidebar()" class="hidden fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-30 transition-opacity"></div>
        <!-- Sidebar Navigation Drawer (Collapsible) -->
        <aside id="sidebar-nav" class="fixed inset-y-0 left-0 z-40 w-64 bg-[#101A2B] text-slate-300 border-r border-black/20 flex flex-col justify-between transform -translate-x-full lg:translate-x-0 shadow-2xl lg:shadow-none">
            
            <!-- Sidebar Header & Logo -->
            <div class="px-5 py-5 border-b border-black/20 shrink-0 flex items-center logo-container">
                <a href="/dashboard" class="flex items-center gap-3 group logo-link">
                    <div class="w-9 h-9 rounded-xl bg-brand-700 flex items-center justify-center shadow-md shadow-[#065F46]/10 shrink-0">
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
                    <a href="/dashboard" data-tooltip="Dashboard" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->is('dashboard') ? (Auth::check() && Auth::user()->role === 'buyer' ? 'bg-harvest text-white shadow-md shadow-harvest/10' : 'nav-active') : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                        <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">D</span>
                        <span class="nav-label">Dashboard</span>
                    </a>
                </div>

                <!-- ROLE 1: FARMER VIEW NODES -->
                @if(Auth::check() && Auth::user()->role === 'farmer')
                    <div class="space-y-1.5">
                        <a href="{{ route('harvests.index') }}" data-tooltip="My Active Harvests" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('harvests.*') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">M</span>
                            <span class="nav-label">My Active Harvests</span>
                        </a>

                        @php
                            $isFarmerSellingActive = request()->routeIs('farmer.negotiations') ||
                                                     request()->routeIs('farmer.proposals') ||
                                                     request()->routeIs('farmer.haul-requests');
                        @endphp
                        <div class="space-y-1.5">
                            <button type="button" data-submenu-toggle data-tooltip="Selling" class="nav-link w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-semibold transition text-slate-300 hover:text-white hover:bg-white/10 select-none {{ $isFarmerSellingActive ? ' nav-active' : '' }}">
                                <div class="flex items-center gap-3">
                                    <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">S</span>
                                    <span class="nav-label">Selling</span>
                                </div>
                                <span class="nav-label">
                                    <svg data-submenu-chevron xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transform transition-transform {{ $isFarmerSellingActive ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </span>
                            </button>

                            <div data-submenu-panel class="{{ $isFarmerSellingActive ? '' : 'hidden' }} mt-1 pl-4 space-y-1.5 transition-all">
                                <a href="{{ route('farmer.negotiations') }}" data-tooltip="My Negotiations" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('farmer.negotiations') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                    <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">N</span>
                                    <span class="nav-label">My Negotiations</span>
                                </a>

                                <a href="{{ route('farmer.proposals') }}" data-tooltip="Route Offers" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('farmer.proposals') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                    <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">R</span>
                                    <span class="nav-label">Route Offers</span>
                                </a>

                                @if(Auth::user()->farmerProfile?->affiliation_type !== 'cooperative')
                                    <a href="{{ route('farmer.haul-requests') }}" data-tooltip="Haul Requests" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('farmer.haul-requests') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                        <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">H</span>
                                        <span class="nav-label">Haul Requests</span>
                                    </a>
                                @endif
                            </div>
                        </div>

                        @php
                            $isFarmerShipmentsActive = request()->routeIs('farmer.logistics') || request()->routeIs('tracking.index');
                        @endphp
                        <div class="space-y-1.5">
                            <button type="button" data-submenu-toggle data-tooltip="Shipments" class="nav-link w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-semibold transition text-slate-300 hover:text-white hover:bg-white/10 select-none {{ $isFarmerShipmentsActive ? ' nav-active' : '' }}">
                                <div class="flex items-center gap-3">
                                    <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">T</span>
                                    <span class="nav-label">Shipments</span>
                                </div>
                                <span class="nav-label">
                                    <svg data-submenu-chevron xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transform transition-transform {{ $isFarmerShipmentsActive ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </span>
                            </button>

                            <div data-submenu-panel class="{{ $isFarmerShipmentsActive ? '' : 'hidden' }} mt-1 pl-4 space-y-1.5 transition-all">
                                <a href="{{ route('farmer.logistics') }}" data-tooltip="My Logistics" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('farmer.logistics') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                    <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">L</span>
                                    <span class="nav-label">My Logistics</span>
                                </a>

                                <a href="{{ route('tracking.index') }}" data-tooltip="Track Shipments" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('tracking.index') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                    <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">T</span>
                                    <span class="nav-label">Track Shipments</span>
                                </a>
                            </div>
                        </div>

                        <a href="{{ route('farmer.reports.sales') }}" data-tooltip="Reports" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('farmer.reports.*') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">R</span>
                            <span class="nav-label">Reports</span>
                        </a>

                        @php
                            $isFarmerReferenceActive = request()->routeIs('prices.full') || request()->routeIs('farmer.documents*');
                        @endphp
                        <div class="space-y-1.5">
                            <button type="button" data-submenu-toggle data-tooltip="Reference" class="nav-link w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-semibold transition text-slate-300 hover:text-white hover:bg-white/10 select-none {{ $isFarmerReferenceActive ? ' nav-active' : '' }}">
                                <div class="flex items-center gap-3">
                                    <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">B</span>
                                    <span class="nav-label">Reference</span>
                                </div>
                                <span class="nav-label">
                                    <svg data-submenu-chevron xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transform transition-transform {{ $isFarmerReferenceActive ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </span>
                            </button>

                            <div data-submenu-panel class="{{ $isFarmerReferenceActive ? '' : 'hidden' }} mt-1 pl-4 space-y-1.5 transition-all">
                                <a href="{{ route('prices.full') }}" data-tooltip="Market Prices" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('prices.full') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                    <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">M</span>
                                    <span class="nav-label">Market Prices</span>
                                </a>

                                <a href="{{ route('farmer.documents') }}" data-tooltip="Regulatory Documents" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('farmer.documents*') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                    <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">D</span>
                                    <span class="nav-label">Regulatory Documents</span>
                                </a>
                            </div>
                        </div>

                        <a href="{{ route('profile.show') }}" data-tooltip="My Profile" class="nav-link lg:hidden flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('profile.*') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">M</span>
                            <span class="nav-label">My Profile</span>
                        </a>
                    </div>
                @endif

                <!-- ROLE 5: BUYER VIEW NODES -->
                @if(Auth::check() && (Auth::user()->role === 'buyer' || (Auth::user()->role === 'logistics_partner' && $authUser->logisticsProfile && $authUser->logisticsProfile->isCooperative())))
                    <div class="space-y-1.5">
                        <p class="section-label text-[10px] font-bold text-white/60 uppercase tracking-widest px-4">Market</p>
                        
                        <a href="{{ route('buyer.crop-board') }}" data-tooltip="Crop Board" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('buyer.crop-board') ? (Auth::user()->role === 'buyer' ? 'bg-harvest text-white shadow-md shadow-harvest/10' : 'nav-active') : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">C</span>
                            <span class="nav-label">Crop Board</span>
                        </a>

                        <a href="{{ route('buyer.tracking') }}" data-tooltip="Delivery Tracking" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('buyer.tracking') ? (Auth::user()->role === 'buyer' ? 'bg-harvest text-white shadow-md shadow-harvest/10' : 'nav-active') : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">D</span>
                            <span class="nav-label">Delivery Tracking</span>
                        </a>

                        <a href="{{ route('prices.full') }}" data-tooltip="Market Prices" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('prices.full') ? (Auth::user()->role === 'buyer' ? 'bg-harvest text-white shadow-md shadow-harvest/10' : 'nav-active') : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">M</span>
                            <span class="nav-label">Market Prices</span>
                        </a>

                        <a href="{{ route('profile.show') }}" data-tooltip="My Profile" class="nav-link lg:hidden flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('profile.*') ? (Auth::user()->role === 'buyer' ? 'bg-harvest text-white shadow-md shadow-harvest/10' : 'nav-active') : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">M</span>
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
                        <button type="button" data-submenu-toggle data-tooltip="Trust & Verification" class="nav-link w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-semibold transition text-slate-300 hover:text-white hover:bg-white/10 select-none {{ $isTrustVerificationActive ? ' nav-active' : '' }}">
                            <div class="flex items-center gap-3">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">T</span>
                                <span class="nav-label">Trust & Verification</span>
                            </div>
                            <span class="nav-label">
                                <svg data-submenu-chevron xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transform transition-transform {{ $isTrustVerificationActive ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        </button>
                        
                        <div data-submenu-panel class="{{ $isTrustVerificationActive ? '' : 'hidden' }} mt-1 pl-4 space-y-1.5 transition-all">
                            <a href="{{ route('admin.users') }}" data-tooltip="User Management" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.users*') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">U</span>
                                <span class="nav-label">User Management</span>
                            </a>

                            @php
                                $isFarmerVerificationTab = request()->routeIs('admin.farmers*') || request()->routeIs('admin.farmer-documents*');
                                $isLogisticsVerificationTab = request()->routeIs('admin.logistics', 'admin.logistics.*') || request()->routeIs('admin.logistics-documents*');
                            @endphp
                            <a href="{{ route('admin.farmers') }}" data-tooltip="Farmer Verification" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ $isFarmerVerificationTab ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">F</span>
                                <span class="nav-label">Farmer Verification</span>
                            </a>

                            <a href="{{ route('admin.buyers') }}" data-tooltip="Buyer Verification" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.buyers*') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">B</span>
                                <span class="nav-label">Buyer Verification</span>
                            </a>

                            <a href="{{ route('admin.logistics') }}" data-tooltip="Logistics Partners" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ $isLogisticsVerificationTab ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">L</span>
                                <span class="nav-label">Logistics Partners</span>
                            </a>

                            <a href="{{ route('admin.drivers') }}" data-tooltip="Driver Verification" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.drivers*') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">D</span>
                                <span class="nav-label">Driver Verification</span>
                            </a>
                        </div>
                    </div>

                    @php
                        $isAgriculturalMatrixActive = request()->routeIs('admin.harvests*') || request()->routeIs('admin.crops*');
                    @endphp
                    <!-- Platform Settings Group Dropdown -->
                    <div class="space-y-1.5">
                        <button type="button" data-submenu-toggle data-tooltip="Crops & Harvests" class="nav-link w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-semibold transition text-slate-300 hover:text-white hover:bg-white/10 select-none {{ $isAgriculturalMatrixActive ? ' nav-active' : '' }}">
                            <div class="flex items-center gap-3">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">C</span>
                                <span class="nav-label">Crops & Harvests</span>
                            </div>
                            <span class="nav-label">
                                <svg data-submenu-chevron xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transform transition-transform {{ $isAgriculturalMatrixActive ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        </button>
                        
                        <div data-submenu-panel class="{{ $isAgriculturalMatrixActive ? '' : 'hidden' }} mt-1 pl-4 space-y-1.5 transition-all">
                            <a href="{{ route('admin.harvests') }}" data-tooltip="Harvest Oversight" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.harvests*') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">H</span>
                                <span class="nav-label">Harvest Oversight</span>
                            </a>

                            <a href="{{ route('admin.crops.index') }}" data-tooltip="Crop Registry" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.crops*') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">C</span>
                                <span class="nav-label">Crop Registry</span>
                            </a>
                        </div>
                    </div>

                    @php
                        $isGovernanceActive = request()->routeIs('admin.audit-logs*') || request()->routeIs('admin.analytics*');
                    @endphp
                    <!-- System Audit Group Dropdown -->
                    <div class="space-y-1.5">
                        <button type="button" data-submenu-toggle data-tooltip="Governance" class="nav-link w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-semibold transition text-slate-300 hover:text-white hover:bg-white/10 select-none {{ $isGovernanceActive ? ' nav-active' : '' }}">
                            <div class="flex items-center gap-3">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">G</span>
                                <span class="nav-label">Governance</span>
                            </div>
                            <span class="nav-label">
                                <svg data-submenu-chevron xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transform transition-transform {{ $isGovernanceActive ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        </button>
                        
                        <div data-submenu-panel class="{{ $isGovernanceActive ? '' : 'hidden' }} mt-1 pl-4 space-y-1.5 transition-all">
                            <a href="{{ route('admin.analytics') }}" data-tooltip="Platform Analytics" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.analytics*') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">P</span>
                                <span class="nav-label">Platform Analytics</span>
                            </a>
                            <a href="{{ route('admin.audit-logs') }}" data-tooltip="Platform Audit Logs" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.audit-logs*') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">P</span>
                                <span class="nav-label">Platform Audit Logs</span>
                            </a>
                        </div>
                    </div>
                @endif

                <!-- ROLE 3: LOGISTICS PARTNER NODES -->
                @if(Auth::check() && Auth::user()->role === 'logistics_partner')
                    <div class="space-y-1.5">
                        <a href="{{ route('pooling.index') }}" data-tooltip="Proposal Inbox" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('pooling.index') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">P</span>
                            <span class="nav-label">Proposal Inbox</span>
                        </a>

                        @php
                            $isLogisticsOpsActive = request()->routeIs('route.optimization') || request()->routeIs('pooling.cost-ledger*');
                        @endphp
                        <div class="space-y-1.5">
                            <button type="button" data-submenu-toggle data-tooltip="Operations" class="nav-link w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-semibold transition text-slate-300 hover:text-white hover:bg-white/10 select-none {{ $isLogisticsOpsActive ? ' nav-active' : '' }}">
                                <div class="flex items-center gap-3">
                                    <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">O</span>
                                    <span class="nav-label">Operations</span>
                                </div>
                                <span class="nav-label">
                                    <svg data-submenu-chevron xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transform transition-transform {{ $isLogisticsOpsActive ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </span>
                            </button>

                            <div data-submenu-panel class="{{ $isLogisticsOpsActive ? '' : 'hidden' }} mt-1 pl-4 space-y-1.5 transition-all">
                                <a href="{{ route('route.optimization') }}" data-tooltip="Route Planning" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('route.optimization') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                    <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">R</span>
                                    <span class="nav-label">Route Planning</span>
                                </a>

                                <a href="{{ route('pooling.cost-ledger.index') }}" data-tooltip="Cost Ledger" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('pooling.cost-ledger*') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                    <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">C</span>
                                    <span class="nav-label">Cost Ledger</span>
                                </a>
                            </div>
                        </div>

                        @php
                            $isLogisticsFleetActive = request()->routeIs('logistics.drivers*') ||
                                                      request()->routeIs('logistics.vehicles*') ||
                                                      request()->routeIs('logistics.reports.*') ||
                                                      request()->routeIs('logistics.analytics') ||
                                                      request()->routeIs('logistics.capacity');
                        @endphp
                        <div class="space-y-1.5">
                            <button type="button" data-submenu-toggle data-tooltip="Fleet" class="nav-link w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-semibold transition text-slate-300 hover:text-white hover:bg-white/10 select-none {{ $isLogisticsFleetActive ? ' nav-active' : '' }}">
                                <div class="flex items-center gap-3">
                                    <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">F</span>
                                    <span class="nav-label">Fleet</span>
                                </div>
                                <span class="nav-label">
                                    <svg data-submenu-chevron xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transform transition-transform {{ $isLogisticsFleetActive ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </span>
                            </button>

                            <div data-submenu-panel class="{{ $isLogisticsFleetActive ? '' : 'hidden' }} mt-1 pl-4 space-y-1.5 transition-all">
                                <a href="{{ route('logistics.drivers.index') }}" data-tooltip="Manage Fleet" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('logistics.drivers*') || request()->routeIs('logistics.vehicles*') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                    <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">M</span>
                                    <span class="nav-label">Manage Fleet</span>
                                </a>

                                <a href="{{ route('logistics.reports.trips') }}" data-tooltip="Fleet Reports" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('logistics.reports.*') || request()->routeIs('logistics.analytics') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                    <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">R</span>
                                    <span class="nav-label">Fleet Reports</span>
                                </a>

                                <a href="{{ route('logistics.capacity') }}" data-tooltip="Fleet Capacity" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('logistics.capacity') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                    <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">C</span>
                                    <span class="nav-label">Fleet Capacity</span>
                                </a>
                            </div>
                        </div>

                        @php
                            $isLogisticsReferenceActive = request()->routeIs('prices.full') || request()->routeIs('logistics.documents*');
                        @endphp
                        <div class="space-y-1.5">
                            <button type="button" data-submenu-toggle data-tooltip="Reference" class="nav-link w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-semibold transition text-slate-300 hover:text-white hover:bg-white/10 select-none {{ $isLogisticsReferenceActive ? ' nav-active' : '' }}">
                                <div class="flex items-center gap-3">
                                    <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">B</span>
                                    <span class="nav-label">Reference</span>
                                </div>
                                <span class="nav-label">
                                    <svg data-submenu-chevron xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transform transition-transform {{ $isLogisticsReferenceActive ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </span>
                            </button>

                            <div data-submenu-panel class="{{ $isLogisticsReferenceActive ? '' : 'hidden' }} mt-1 pl-4 space-y-1.5 transition-all">
                                <a href="{{ route('prices.full') }}" data-tooltip="Market Prices" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('prices.full') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                    <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">M</span>
                                    <span class="nav-label">Market Prices</span>
                                </a>

                                <a href="{{ route('logistics.documents') }}" data-tooltip="Business License Docs" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('logistics.documents*') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                                    <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">B</span>
                                    <span class="nav-label">Business License Docs</span>
                                </a>
                            </div>
                        </div>

                        <a href="{{ route('profile.show') }}" data-tooltip="My Profile" class="nav-link lg:hidden flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('profile.*') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">M</span>
                            <span class="nav-label">My Profile</span>
                        </a>
                    </div>
                @endif

                <!-- ROLE 4: DRIVER NODES -->
                @if(Auth::check() && Auth::user()->role === 'driver')
                    <div class="space-y-1.5">
                        <p class="section-label text-[10px] font-bold text-white/60 uppercase tracking-widest px-4">On Route</p>

                        <a href="{{ route('driver.dashboard') }}" data-tooltip="Route Navigation" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('driver.*') ? 'nav-active' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-white/10 text-white/70 text-xs font-bold flex items-center justify-center uppercase">R</span>
                            <span class="nav-label">Route Navigation</span>
                        </a>
                    </div>
                @endif

            </nav>

            

        </aside>
        <!-- Main Display Content Shell Wrapper (Offset on desktop) -->
        <div id="main-content" tabindex="-1" class="main-wrapper flex-1 lg:pl-64 min-w-0 flex flex-col min-h-screen outline-none">
            <!-- Horizontal Desktop Navbar -->
            <nav id="top-navbar" class="top-navbar hidden lg:flex fixed top-0 z-30 h-20 bg-white border-b border-slate-200 px-8 items-center justify-between shadow-sm">
                <!-- Left side: collapse toggle + portal indicator -->
                <div class="flex items-center gap-4">
                    <!-- Topbar collapse toggle -->
                    <button onclick="toggleSidebarCollapse()" class="w-9 h-9 rounded-xl bg-slate-900/5 border border-slate-900/10 flex items-center justify-center text-slate-600 hover:text-slate-900 hover:bg-slate-900/10 transition dark:bg-white/10 dark:border-white/15 dark:text-white/80 dark:hover:text-white dark:hover:bg-white/20" aria-label="Toggle sidebar">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-white/60">HarvestHaul</span>
                        <h2 class="text-sm font-bold text-slate-900 mt-0.5"><span class="{{ Auth::user()->role === 'buyer' ? 'text-harvest-dark dark:text-harvest-light' : 'text-slate-700 dark:text-white' }} uppercase font-black">{{ ['admin' => 'Administrator', 'farmer' => 'Farmer', 'buyer' => 'Buyer', 'logistics_partner' => 'Logistics Partner', 'driver' => 'Driver'][Auth::user()->role] ?? Auth::user()->role }}</span></h2>
                    </div>
                </div>

                <!-- User profile and avatar menu -->
                <div class="flex items-center gap-6 select-none">
                    <!-- Notifications Dropdown -->
                    <x-notification-dropdown />

                    <!-- Dark Mode Toggle (Admin, Farmer, Logistics & Buyer) -->
                    @if(Auth::check() && (Auth::user()->role === 'admin' || Auth::user()->role === 'farmer' || Auth::user()->role === 'logistics_partner' || Auth::user()->role === 'buyer'))
                        <button onclick="toggleDarkMode()" class="w-10 h-10 rounded-xl bg-slate-900/5 border border-slate-900/10 flex items-center justify-center text-slate-600 hover:text-slate-900 hover:bg-slate-900/10 transition cursor-pointer dark:bg-white/10 dark:border-white/15 dark:text-white/80 dark:hover:text-white dark:hover:bg-white/20" title="Toggle dark mode" aria-label="Toggle dark mode">
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
                        <button onclick="toggleProfileDropdown()" class="flex items-center gap-3.5 pl-6 border-l border-slate-900/15 hover:opacity-90 transition cursor-pointer focus:outline-none text-left dark:border-white/15" aria-haspopup="true" aria-expanded="false" id="profile-menu-btn">
                            <div class="w-10 h-10 rounded-xl bg-slate-900/5 border border-slate-900/10 flex items-center justify-center text-slate-700 font-extrabold uppercase text-sm select-none dark:bg-white/10 dark:border-white/15 dark:text-white">
                                {{ substr(Auth::user()->name, 0, 2) }}
                            </div>
                            <div class="hidden sm:block">
                                <p class="text-sm font-bold text-slate-700 leading-none flex items-center gap-1 dark:text-white">
                                    {{ Auth::user()->name }}
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-slate-500 dark:text-white/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </p>
                                <p class="text-[10px] text-slate-500 font-semibold mt-1 dark:text-white/60">{{ Auth::user()->email }}</p>
                            </div>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="profile-dropdown" class="hidden absolute right-0 mt-2 w-56 bg-white dark:bg-slate-800 border border-slate-200/85 dark:border-slate-700 rounded-2xl shadow-xl z-50 overflow-hidden py-1.5 px-1.5 space-y-1">
                            @if(Auth::check() && (Auth::user()->role === 'farmer' || Auth::user()->role === 'logistics_partner' || Auth::user()->role === 'buyer'))
                                <a href="{{ route('profile.show') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/40 hover:text-brand-700 dark:hover:text-brand-light transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Profile Settings
                                </a>
                                <a href="{{ route('notifications.preferences') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/40 hover:text-brand-700 dark:hover:text-brand-light transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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

        // Apply sidebar collapse/expand state to sidebar, content, and top navbar.
        // Layout is driven entirely by CSS (see app.css @media min-width:1024px),
        // so this only toggles the class — no inline styles that could leak on mobile.
        function setSidebarCollapsed(isCollapsed) {
            var sidebar = document.getElementById('sidebar-nav');
            sidebar.classList.toggle('sidebar-collapsed', isCollapsed);
        }

        // Desktop sidebar collapse toggle
        function toggleSidebarCollapse() {
            var sidebar = document.getElementById('sidebar-nav');
            var isCollapsed = sidebar.classList.contains('sidebar-collapsed');

            if (isCollapsed) {
                // Expand
                sidebar.classList.remove('sidebar-collapsed');
                setSidebarCollapsed(false);
            } else {
                // Collapse
                sidebar.classList.add('sidebar-collapsed');
                setSidebarCollapsed(true);
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
                    sidebar.classList.add('sidebar-collapsed');
                    setSidebarCollapsed(true);
                }
            }
        })();

        // Keep the collapsed class in sync with the desktop breakpoint so the
        // mobile drawer always shows the full nav and nothing leaks on resize.
        window.matchMedia('(min-width: 1024px)').addEventListener('change', function(e) {
            var sidebar = document.getElementById('sidebar-nav');
            if (e.matches) {
                if (localStorage.getItem('sidebar-collapsed') === 'true') {
                    sidebar.classList.add('sidebar-collapsed');
                }
            } else {
                sidebar.classList.remove('sidebar-collapsed');
            }
        });

        // Sidebar submenu accordions: any button with [data-submenu-toggle]
        // flips the sibling panel in its group container and rotates its chevron.
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-submenu-toggle]');
            if (!btn) return;

            var sidebar = document.getElementById('sidebar-nav');
            if (sidebar && sidebar.classList.contains('sidebar-collapsed')) {
                sidebar.classList.remove('sidebar-collapsed');
                setSidebarCollapsed(false);
                localStorage.setItem('sidebar-collapsed', 'false');
            }

            var panel = btn.parentElement.querySelector('[data-submenu-panel]');
            var chevron = btn.querySelector('[data-submenu-chevron]');
            if (!panel) return;

            var isHidden = panel.classList.contains('hidden');
            if (isHidden) {
                panel.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-90');
            } else {
                panel.classList.add('hidden');
                if (chevron) chevron.classList.remove('rotate-90');
            }
        });

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
            var btn = document.getElementById('profile-menu-btn');
            dropdown.classList.toggle('hidden');
            if (btn) btn.setAttribute('aria-expanded', !dropdown.classList.contains('hidden'));
        }

        // Close dropdown when clicking outside
        window.addEventListener('click', function(e) {
            var profileDropdown = document.getElementById('profile-dropdown');
            var profileMenu = document.getElementById('profile-menu');
            var btn = document.getElementById('profile-menu-btn');
            if (profileDropdown && profileMenu && !profileMenu.contains(e.target)) {
                profileDropdown.classList.add('hidden');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            }
        });

    </script>

    {{-- SweetAlert Global Flash Handler --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('error'))
                @php
                    $errorText = session('error');
                    $isGateNotice = str_contains($errorText, 'pending verification') || str_contains($errorText, 'No new data');
                @endphp
                Swal.fire({
                    icon: '{{ $isGateNotice ? 'info' : 'error' }}',
                    title: '{{ $isGateNotice ? 'Notice' : 'Error' }}',
                    text: @json($errorText),
                    timer: 4500,
                    timerProgressBar: true,
                    showConfirmButton: true,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '{{ $isGateNotice ? '#059669' : '#ef4444' }}',
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
                confirmColor: opts.confirmColor || '#065F46',
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

    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>

    {{-- Stack for page-specific JS (Leaflet, Turf, init code) --}}
    @stack('scripts')

    {{-- Floating Negotiations Widget --}}
    @if(Auth::check() && !Route::is('negotiations.room') && !Route::is('haul-negotiations.room') && (Auth::user()->role === 'farmer' || Auth::user()->role === 'buyer' || (Auth::user()->role === 'logistics_partner' && $authUser->logisticsProfile && $authUser->logisticsProfile->isCooperative())))
        <x-negotiations-widget />
    @endif

</body>
</html>
