<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'HarvestHaul Portal — Coordinated Agribusiness' }}</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script>
        (function() {
            var theme = localStorage.getItem('theme');
            var isDark = theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches);
            @if(Auth::check() && (Auth::user()->role === 'admin' || Auth::user()->role === 'farmer' || Auth::user()->role === 'logistics_partner'))
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            @endif
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #F8FAFC;
        }
        .heading-font {
            font-family: 'Outfit', sans-serif;
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
        .sidebar-collapsed .logo-text,
        .sidebar-collapsed .logout-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
            white-space: nowrap;
            transition: opacity 0.15s, width 0.2s;
        }

        /* Expanded state: show text labels */
        #sidebar-nav:not(.sidebar-collapsed) .nav-label,
        #sidebar-nav:not(.sidebar-collapsed) .section-label,
        #sidebar-nav:not(.sidebar-collapsed) .logo-text,
        #sidebar-nav:not(.sidebar-collapsed) .logout-text {
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
        .sidebar-collapsed .nav-link .nav-icon {
            margin: 0;
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

        /* Collapsed logout centering */
        .sidebar-collapsed .logout-btn {
            justify-content: center;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
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

        /* Dark Mode overrides for Admin Layout */
        html.dark body {
            background-color: #0f172a;
            color: #f1f5f9;
        }
        html.dark #top-navbar {
            background-color: #1e293b;
            border-color: #334155;
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

    <!-- Mobile Top Header (Nice Admin Style) -->
    <header class="lg:hidden sticky top-0 z-50 bg-[#111827] text-white px-5 py-4 flex justify-between items-center border-b border-slate-800 shadow-md">
        <a href="/dashboard" class="flex items-center gap-2 group">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center">
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
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center shadow-md shadow-emerald-600/10 shrink-0">
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
                    <a href="/dashboard" data-tooltip="Dashboard" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->is('dashboard') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                        <span class="nav-icon shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                        </span>
                        <span class="nav-label">Dashboard</span>
                    </a>
                </div>

                <!-- ROLE 1: FARMER VIEW NODES -->
                @if(Auth::check() && Auth::user()->role === 'farmer')
                    <div class="space-y-1.5">
                        <p class="section-label text-[10px] font-bold text-slate-500 uppercase tracking-widest px-4">Agricultural Workspace</p>
                        
                        <a href="{{ route('harvests.index') }}" data-tooltip="My Active Harvests" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('harvests.*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-icon shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </span>
                            <span class="nav-label">My Active Harvests</span>
                        </a>

                        <a href="{{ route('farmer.proposals') }}" data-tooltip="Negotiation Hub" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('farmer.proposals') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-icon shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </span>
                            <span class="nav-label">Negotiation Hub</span>
                        </a>

                        <a href="{{ route('tracking.index') }}" data-tooltip="Track Shipments" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('tracking.index') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-icon shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1" />
                                </svg>
                            </span>
                            <span class="nav-label">Track Shipments</span>
                        </a>

                        <a href="{{ route('farmer.documents') }}" data-tooltip="Regulatory Documents" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('farmer.documents*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-icon shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </span>
                            <span class="nav-label">Regulatory Documents</span>
                        </a>
                    </div>
                @endif

                <!-- ROLE 2: ADMIN VIEW NODES (Nice Admin Groupings) --                @if(Auth::check() && Auth::user()->role === 'admin')
                    @php
                        $isTrustVerificationActive = request()->routeIs('admin.users*') ||
                                                     request()->routeIs('admin.farmers*') ||
                                                     request()->routeIs('admin.farmer-documents*') ||
                                                     request()->routeIs('admin.logistics') ||
                                                     request()->routeIs('admin.logistics.*') ||
                                                     request()->routeIs('admin.logistics-documents*') ||
                                                     request()->routeIs('admin.drivers*');
                    @endphp
                    <!-- People / Users Sub-Group Dropdown -->
                    <div class="space-y-1.5">
                        <button onclick="toggleTrustVerification()" data-tooltip="Trust & Verification" class="nav-link w-full flex items-center justify-between gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition text-slate-400 hover:text-white hover:bg-slate-800/60 select-none">
                            <div class="flex items-center gap-3">
                                <span class="nav-icon shrink-0 text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </span>
                                <span class="nav-label">Trust & Verification</span>
                            </div>
                            <span class="nav-label">
                                <svg id="trust-verification-chevron" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transform transition-transform {{ $isTrustVerificationActive ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        </button>
                        
                        <div id="trust-verification-dropdown" class="{{ $isTrustVerificationActive ? '' : 'hidden' }} mt-1 pl-4 space-y-1.5 transition-all">
                            <a href="{{ route('admin.users') }}" data-tooltip="User Management" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.users*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                                <span class="nav-icon shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </span>
                                <span class="nav-label">User Management</span>
                            </a>

                            <a href="{{ route('admin.farmers') }}" data-tooltip="Farmer Verification" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.farmers*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                                <span class="nav-icon shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                    </svg>
                                </span>
                                <span class="nav-label">Farmer Verification</span>
                            </a>
                            
                            <a href="{{ route('admin.farmer-documents') }}" data-tooltip="Farmer Licenses" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.farmer-documents*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                                <span class="nav-icon shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </span>
                                <span class="nav-label">Farmer Licenses</span>
                            </a>

                            <a href="{{ route('admin.logistics') }}" data-tooltip="Logistics Registry" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.logistics', 'admin.logistics.*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                                <span class="nav-icon shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1m-6 0a1 1 0 001-1m9 1a1 1 0 01-1-1m-3 0a1 1 0 001-1m-1 0H8m9-1v-4a1 1 0 00-1-1h-2" />
                                    </svg>
                                </span>
                                <span class="nav-label">Logistics Registry</span>
                            </a>
                            
                            <a href="{{ route('admin.logistics-documents') }}" data-tooltip="Logistics Credentials" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.logistics-documents*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                                <span class="nav-icon shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                    </svg>
                                </span>
                                <span class="nav-label">Logistics Credentials</span>
                            </a>

                            <a href="{{ route('admin.drivers') }}" data-tooltip="Driver Verification" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.drivers*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                                <span class="nav-icon shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </span>
                                <span class="nav-label">Driver Verification</span>
                            </a>
                        </div>
                    </div>

                    @php
                        $isAgriculturalMatrixActive = request()->routeIs('admin.harvests*') || request()->routeIs('admin.crops*');
                    @endphp
                    <!-- Platform Settings Group Dropdown -->
                    <div class="space-y-1.5">
                        <button onclick="toggleAgriculturalMatrix()" data-tooltip="Agricultural Matrix" class="nav-link w-full flex items-center justify-between gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition text-slate-400 hover:text-white hover:bg-slate-800/60 select-none">
                            <div class="flex items-center gap-3">
                                <span class="nav-icon shrink-0 text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.271.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.271.477-4.5 1.253" />
                                    </svg>
                                </span>
                                <span class="nav-label">Agricultural Matrix</span>
                            </div>
                            <span class="nav-label">
                                <svg id="agricultural-matrix-chevron" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transform transition-transform {{ $isAgriculturalMatrixActive ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        </button>
                        
                        <div id="agricultural-matrix-dropdown" class="{{ $isAgriculturalMatrixActive ? '' : 'hidden' }} mt-1 pl-4 space-y-1.5 transition-all">
                            <a href="{{ route('admin.harvests') }}" data-tooltip="Harvest Oversight" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.harvests*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                                <span class="nav-icon shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </span>
                                <span class="nav-label">Harvest Oversight</span>
                            </a>

                            <a href="{{ route('admin.crops.index') }}" data-tooltip="Crop Registry" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.crops*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                                <span class="nav-icon shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                </span>
                                <span class="nav-label">Crop registry</span>
                            </a>
                        </div>
                    </div>

                    @php
                        $isGovernanceActive = request()->routeIs('admin.audit-logs*');
                    @endphp
                    <!-- System Audit Group Dropdown -->
                    <div class="space-y-1.5">
                        <button onclick="toggleGovernance()" data-tooltip="Governance" class="nav-link w-full flex items-center justify-between gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition text-slate-400 hover:text-white hover:bg-slate-800/60 select-none">
                            <div class="flex items-center gap-3">
                                <span class="nav-icon shrink-0 text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                                    </svg>
                                </span>
                                <span class="nav-label">Governance</span>
                            </div>
                            <span class="nav-label">
                                <svg id="governance-chevron" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transform transition-transform {{ $isGovernanceActive ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        </button>
                        
                        <div id="governance-dropdown" class="{{ $isGovernanceActive ? '' : 'hidden' }} mt-1 pl-4 space-y-1.5 transition-all">
                            <a href="{{ route('admin.audit-logs') }}" data-tooltip="Platform Audit Logs" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.audit-logs*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                                <span class="nav-icon shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </span>
                                <span class="nav-label">Platform Audit Logs</span>
                            </a>
                        </div>
                    </div>
                @endif

                <!-- ROLE 3: LOGISTICS PARTNER NODES -->
                @if(Auth::check() && Auth::user()->role === 'logistics_partner')
                    <div class="space-y-1.5">
                        <p class="section-label text-[10px] font-bold text-slate-500 uppercase tracking-widest px-4">Operational Dispatch</p>
                        
                        <a href="{{ route('route.optimization') }}" data-tooltip="Dispatch Console" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('route.optimization') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-icon shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </span>
                            <span class="nav-label">Dispatch Console</span>
                        </a>

                        <a href="{{ route('pooling.index') }}" data-tooltip="Proposal Inbox" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('pooling.index') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-icon shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </span>
                            <span class="nav-label">Proposal Inbox</span>
                        </a>

                        <a href="{{ route('pooling.cost-ledger.index') }}" data-tooltip="Cost Ledger" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('pooling.cost-ledger*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-icon shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </span>
                            <span class="nav-label">Cost Ledger</span>
                        </a>

                        <a href="{{ route('logistics.documents') }}" data-tooltip="Business License Docs" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('logistics.documents*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-icon shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </span>
                            <span class="nav-label">Business License Docs</span>
                        </a>

                        <a href="{{ route('logistics.drivers.index') }}" data-tooltip="Manage Drivers" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('logistics.drivers*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-icon shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </span>
                            <span class="nav-label">Manage Drivers</span>
                        </a>

                        <a href="{{ route('logistics.vehicles.index') }}" data-tooltip="Manage Vehicles" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('logistics.vehicles*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <span class="nav-icon shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10M13 16h6m-6 0H6m13 0a2 2 0 002-2v-4a1 1 0 00-1-1h-6.18c-.09-.27-.27-.49-.52-.61l-2.6-1.3a1 1 0 00-1.12.18l-1.6 1.6" />
                                </svg>
                            </span>
                            <span class="nav-label">Manage Vehicles</span>
                        </a>
                    </div>
                @endif

                <!-- ROLE 4: DRIVER NODES -->
                @if(Auth::check() && Auth::user()->role === 'driver')
                    <div class="space-y-1.5">
                        <p class="section-label text-[10px] font-bold text-slate-500 uppercase tracking-widest px-4">Freight Mobile Portal</p>
                        
                        <a href="#" data-tooltip="Route Navigation" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800/60 transition">
                            <span class="nav-icon shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </span>
                            <span class="nav-label">Route Navigation</span>
                        </a>
                        <a href="#" data-tooltip="Delivery Confirmations" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800/60 transition">
                            <span class="nav-icon shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </span>
                            <span class="nav-label">Delivery Confirmations</span>
                        </a>
                    </div>
                @endif

            </nav>

            <!-- Sidebar User/Logout Panel (Nice Admin Bottom Bar) -->
            <div class="p-4 border-t border-slate-800 shrink-0 bg-slate-950/30">
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="logout-btn w-full flex items-center justify-center gap-2.5 px-4 py-3 bg-red-600/10 text-red-400 hover:bg-red-600 hover:text-white font-bold rounded-xl text-xs border border-red-500/20 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span class="logout-text">Exit Portal</span>
                    </button>
                </form>
            </div>

        </aside>

        <!-- Main Display Content Shell Wrapper (Offset on desktop) -->
        <div id="main-wrapper" class="main-wrapper flex-1 lg:pl-64 min-w-0 flex flex-col min-h-screen">

            <!-- Horizontal Desktop Navbar (Nice Admin Layout) -->
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
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">HarvestHaul Operations Workspace</span>
                        <h2 class="text-sm font-bold text-slate-700 mt-0.5">Role Domain: <span class="text-emerald-700 uppercase font-black">{{ Auth::user()->role }}</span></h2>
                    </div>
                </div>

                <!-- User profile and avatar menu -->
                <div class="flex items-center gap-6 select-none">
                    <!-- Notifications Dropdown -->
                    <div class="relative" id="notifications-menu">
                        <button onclick="toggleNotificationsDropdown()" class="relative w-10 h-10 rounded-xl bg-slate-50 dark:bg-slate-700 border border-slate-100 dark:border-slate-650 flex items-center justify-center text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white transition cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span id="notification-badge" class="hidden absolute top-1.5 right-1.5 w-2.5 h-2.5 rounded-full bg-red-500 border border-white dark:border-slate-850"></span>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="notifications-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white dark:bg-slate-800 border border-slate-200/85 dark:border-slate-700 rounded-2xl shadow-xl z-50 overflow-hidden">
                            <div class="px-4 py-3 bg-slate-50 dark:bg-slate-900/40 border-b border-slate-150 dark:border-slate-700/60 flex items-center justify-between">
                                <span class="text-[10px] font-bold text-slate-700 dark:text-slate-350 uppercase tracking-wider">Notifications</span>
                                <button onclick="markAllNotificationsAsRead()" class="text-[9px] text-emerald-650 dark:text-emerald-400 font-bold hover:underline">Mark all read</button>
                            </div>
                            <div id="notifications-list" class="max-h-64 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-750">
                                <p class="text-center text-xs text-slate-400 dark:text-slate-500 py-6">No notifications</p>
                            </div>
                        </div>
                    </div>

                    <!-- Dark Mode Toggle (Admin, Farmer & Logistics) -->
                    @if(Auth::check() && (Auth::user()->role === 'admin' || Auth::user()->role === 'farmer' || Auth::user()->role === 'logistics_partner'))
                        <button onclick="toggleDarkMode()" class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-500 hover:text-slate-800 transition cursor-pointer" title="Toggle dark mode">
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

                    <!-- Profile Node -->
                    <div class="flex items-center gap-3.5 pl-6 border-l border-slate-100">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-50 to-teal-50 border border-slate-100 flex items-center justify-center text-slate-600 font-extrabold uppercase text-sm select-none">
                            {{ substr(Auth::user()->name, 0, 2) }}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-800 leading-none">{{ Auth::user()->name }}</p>
                            <p class="text-[10px] text-slate-400 font-semibold mt-1">{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Render Area -->
            <main class="flex-1 p-6 lg:p-10 relative">
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
            var mainWrapper = document.getElementById('main-wrapper');
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
                    var mainWrapper = document.getElementById('main-wrapper');
                    sidebar.classList.add('sidebar-collapsed');
                    sidebar.style.width = '4.5rem';
                    mainWrapper.style.paddingLeft = '4.5rem';
                }
            }
        })();

        // Trust & Verification dropdown toggle
        function toggleTrustVerification() {
            var sidebar = document.getElementById('sidebar-nav');
            var mainWrapper = document.getElementById('main-wrapper');
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

        // Agricultural Matrix dropdown toggle
        function toggleAgriculturalMatrix() {
            var sidebar = document.getElementById('sidebar-nav');
            var mainWrapper = document.getElementById('main-wrapper');
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
            var mainWrapper = document.getElementById('main-wrapper');
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

        // Close dropdown when clicking outside
        window.addEventListener('click', function(e) {
            var dropdown = document.getElementById('notifications-dropdown');
            var menu = document.getElementById('notifications-menu');
            if (dropdown && menu && !menu.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Fetch Notifications
        function fetchNotifications() {
            fetch('/api/notifications')
                .then(response => response.json())
                .then(data => {
                    var badge = document.getElementById('notification-badge');
                    if (data.unread_count > 0) {
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }

                    var list = document.getElementById('notifications-list');
                    if (data.notifications.length === 0) {
                        list.innerHTML = `<p class="text-center text-xs text-slate-400 dark:text-slate-500 py-6">No notifications</p>`;
                        return;
                    }

                    var html = '';
                    data.notifications.forEach(n => {
                        var isUnread = !n.read_at;
                        var bgClass = isUnread ? 'bg-emerald-500/5 dark:bg-emerald-400/5' : '';
                        var indicator = isUnread ? `<span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>` : '';
                        var link = n.link ? n.link : '#';
                        
                        html += `
                            <div class="px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition flex items-start justify-between gap-3 ${bgClass}" onclick="markNotificationRead(${n.id}, '${link}')">
                                <div class="flex-1 cursor-pointer">
                                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200">${n.title}</p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">${n.message}</p>
                                    <span class="text-[9px] text-slate-400 dark:text-slate-500 mt-1 block">${new Date(n.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                                </div>
                                ${indicator}
                            </div>
                        `;
                    });
                    list.innerHTML = html;
                });
        }

        // Mark a notification as read and redirect
        function markNotificationRead(id, link) {
            fetch(`/api/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            }).then(() => {
                fetchNotifications();
                if (link && link !== '#') {
                    window.location.href = link;
                }
            });
        }

        // Mark all as read
        function markAllNotificationsAsRead() {
            fetch('/api/notifications/read-all', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            }).then(() => {
                fetchNotifications();
            });
        }

        // Initialize and poll
        document.addEventListener('DOMContentLoaded', function() {
            fetchNotifications();
            setInterval(fetchNotifications, 15000);
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
                    iconColor: '#10b981',
                    customClass: { popup: 'rounded-xl shadow-lg border border-emerald-200/30' }
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
                    customClass: { popup: 'rounded-xl shadow-lg' }
                });
            @endif
        });

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
                confirmColor: opts.confirmColor || '#10b981',
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

</body>
</html>
