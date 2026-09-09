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
    <meta name="theme-color" content="#16283C">
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
            background-color: var(--color-surface);
        }

        /* Custom Scrollbar for sidebar */
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: var(--color-scrollbar-thumb);
            border-radius: 2px;
        }
        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: var(--color-scrollbar-thumb-hover);
        }

        /* Topbar left offset matches sidebar width */
        .top-navbar { left: 16rem; right: 0; }
        #main-content > main { padding-top: 2rem; }
        @media (min-width: 1024px) {
            #main-content > main { padding-top: 5rem; }
        }

        /* Sidebar collapse: hide text labels.
           Animates opacity + transform only (compositor-friendly). max-width
           collapses the space instantly so the centered badges sit right. */
        .sidebar-collapsed .nav-label,
        .sidebar-collapsed .section-label,
        .sidebar-collapsed .logo-text {
            opacity: 0;
            transform: translateX(-6px);
            max-width: 0;
            overflow: hidden;
            white-space: nowrap;
            transition: opacity 0.15s, transform 0.15s;
        }
        #sidebar-nav:not(.sidebar-collapsed) .nav-label,
        #sidebar-nav:not(.sidebar-collapsed) .section-label,
        #sidebar-nav:not(.sidebar-collapsed) .logo-text {
            opacity: 1;
            transform: none;
            max-width: 16rem;
            transition: opacity 0.2s 0.1s, transform 0.2s 0.1s;
        }

        /* Collapsed link centering */
        .sidebar-collapsed .nav-link {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }
        /* Collapsed: show first-letter badges; expanded: hide */
        .sidebar-collapsed .nav-letter { display: flex !important; }
        #sidebar-nav:not(.sidebar-collapsed) .nav-letter { display: none !important; }
        .sidebar-collapsed .section-label { height: 0; margin: 0; padding: 0; }
        .sidebar-collapsed .logo-link { justify-content: center; }

        /* Tooltip on hover/focus when collapsed */
        .sidebar-collapsed .nav-link { position: relative; }
        .sidebar-collapsed .nav-link::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 12px;
            background: var(--color-surface-card-dark);
            color: var(--color-text-dark);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            z-index: 100;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            pointer-events: none;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.15s ease, visibility 0.15s;
        }
        .sidebar-collapsed .nav-link:hover::after,
        .sidebar-collapsed .nav-link:focus-visible::after,
        .sidebar-collapsed .nav-link:focus-within::after {
            opacity: 1;
            visibility: visible;
        }

        /* Submenu accordion: expand/collapse via grid rows (compositor-friendly, no height JS) */
        .submenu-panel { display: grid; grid-template-rows: 0fr; transition: grid-template-rows 0.25s ease; }
        .submenu-panel.submenu-open { grid-template-rows: 1fr; }
        .submenu-panel > * { min-height: 0; }

        /* Mobile drawer exits faster than it enters */
        #sidebar-nav.sidebar-nav-exit { transition-duration: 0.2s; }

        /* Collapse toggle */
        .collapse-toggle { transition: transform 0.25s; }
        .sidebar-collapsed .collapse-toggle { transform: rotate(180deg); }
        .sidebar-collapsed [data-submenu-panel] { padding-left: 0; }

        /* Focus-visible: gold on dark sidebar, navy on light surfaces */
        .nav-link:focus-visible,
        button:focus-visible,
        a:focus-visible {
            outline: 2px solid var(--color-gold-light);
            outline-offset: 2px;
            border-radius: 8px;
        }
        html:not(.dark) .nav-link:focus-visible,
        html:not(.dark) button:focus-visible,
        html:not(.dark) a:focus-visible {
            outline-color: var(--color-brand);
        }

        /* Touch targets */
        .nav-link { min-height: 44px; }

        /* Body text readability */
        p, .text-sm, .text-xs { line-height: 1.6; }

        /* Dark topbar */
        html.dark #top-navbar {
            background-color: var(--color-surface-card-dark);
            border-color: var(--color-dark-border);
        }
        html.dark #top-navbar h2 { color: #ffffff; }
        html.dark #top-navbar span { color: var(--color-text-dark-muted); }
        html.dark #top-navbar .border-l { border-color: var(--color-dark-border-light); }
        html.dark #top-navbar p { color: #ffffff; }

        /* Ghost buttons — light mode (bumped opacity for 4.5:1+ contrast) */
        #top-navbar #notifications-menu > button {
            background: var(--color-ghost-btn-bg);
            border-color: var(--color-ghost-btn-border);
            color: var(--color-ghost-btn-text);
        }
        #top-navbar #notifications-menu > button:hover {
            background: var(--color-ghost-btn-bg-hover);
            color: var(--color-text);
        }
        #top-navbar #notifications-menu #notification-badge {
            border-color: var(--color-soil-light);
        }
        /* Ghost buttons — dark mode */
        html.dark #top-navbar #notifications-menu > button {
            background: var(--color-ghost-btn-bg-dark);
            border-color: var(--color-ghost-btn-border-dark);
            color: var(--color-ghost-btn-text-dark);
        }
        html.dark #top-navbar #notifications-menu > button:hover {
            background: var(--color-ghost-btn-bg-dark-hover);
            color: #ffffff;
        }
        html.dark #top-navbar #notifications-menu #notification-badge {
            border-color: var(--color-surface-card-dark);
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
            background: var(--color-brand);
            color: var(--color-text-dark);
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

        /* Sidebar is ALWAYS dark navy — base, hover, active states */
        #sidebar-nav .nav-link {
            color: var(--color-sidebar-text);
        }
        #sidebar-nav .nav-link:hover {
            color: var(--color-sidebar-text-hover);
            background-color: var(--color-dark-hover-bg);
        }
        #sidebar-nav .nav-letter {
            background-color: var(--color-sidebar-letter-bg);
            color: var(--color-sidebar-letter-text);
        }
        #sidebar-nav .section-label {
            color: var(--color-sidebar-section-text);
        }
        #sidebar-nav .nav-link.nav-active {
            background-color: var(--color-nav-active-bg);
            color: var(--color-gold-light);
        }
        #sidebar-nav .nav-link.nav-active .nav-letter {
            background-color: var(--color-nav-active-letter-bg);
            color: var(--color-gold-light);
        }
    </style>
</head>
<body class="app-shell m-0 p-0 text-slate-800 antialiased min-h-screen overflow-x-hidden">

    <!-- Skip to content link for keyboard accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-[100] focus:bg-brand focus:text-white focus:dark:bg-[#D7BC7A] focus:dark:text-[#17202B] focus:px-4 focus:py-2 focus:rounded-xl focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-harvest">
        Skip to main content
    </a>

    <!-- Mobile Top Header -->
    <header class="lg:hidden sticky top-0 z-50 bg-[var(--color-surface)] dark:bg-[var(--color-surface-card-dark)] text-slate-900 dark:text-white px-5 py-4 flex justify-between items-center border-b border-slate-900/10 dark:border-black/20 shadow-md">
        <a href="/dashboard" class="flex items-center gap-2 group">
            <div class="w-8 h-8 rounded-md bg-white border border-slate-200 flex items-center justify-center">
                        <x-brand-logo class="w-5 h-5 text-[var(--color-brand-green)]" />
            </div>
            <span class="text-lg font-bold tracking-tight heading-font text-brand dark:text-white">HarvestHaul</span>
        </a>
        <button id="mobile-menu-btn" onclick="toggleMobileSidebar()" aria-controls="sidebar-nav" aria-expanded="false" class="p-2 bg-slate-900/5 hover:bg-slate-900/10 rounded-xl text-slate-700 dark:bg-white/10 dark:hover:bg-white/20 dark:text-white transition" aria-label="Open Navigation Menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
    </header>

    <div class="flex">
        <!-- Overlay Backdrop for Mobile Navigation -->
        <div id="sidebar-overlay" onclick="toggleMobileSidebar()" class="hidden fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-30 opacity-0 transition-opacity duration-300"></div>
        <!-- Sidebar Navigation Drawer (Collapsible) -->
        <aside id="sidebar-nav" aria-label="Sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-[var(--color-brand-dark)] text-slate-300 border-r border-[var(--color-dark-border)] flex flex-col justify-between transform -translate-x-full lg:translate-x-0 shadow-2xl lg:shadow-none transition-transform duration-300 ease-out">
            
            <!-- Sidebar Header & Logo -->
            <div class="px-5 py-5 border-b border-[var(--color-dark-border)] shrink-0 flex items-center logo-container">
                <a href="/dashboard" class="flex items-center gap-3 group logo-link">
                    <div class="w-9 h-9 rounded-xl bg-white border border-slate-200 flex items-center justify-center shadow-md shrink-0">
                <x-brand-logo class="w-5 h-5 text-[var(--color-brand-green)]" />
                    </div>
                    <span class="text-xl font-bold tracking-tight text-white heading-font logo-text">HarvestHaul</span>
                </a>
            </div>

            <!-- Navigation Links Scroll Area -->
            <x-sidebar />

        </aside>
        <!-- Main Display Content Shell Wrapper (Offset on desktop) -->
        <div id="main-content" tabindex="-1" class="main-wrapper flex-1 lg:pl-64 min-w-0 flex flex-col min-h-screen outline-none">
            <!-- Horizontal Desktop Navbar -->
            <nav id="top-navbar" class="top-navbar hidden lg:flex fixed top-0 z-30 h-20 bg-[var(--color-surface)] border-b border-slate-900/5 px-8 items-center justify-between shadow-sm dark:bg-[var(--color-surface-card-dark)] dark:border-black/20">
                <!-- Left side: collapse toggle + portal indicator -->
                <div class="flex items-center gap-4">
                    <!-- Topbar collapse toggle -->
                    <button onclick="toggleSidebarCollapse()" class="w-9 h-9 rounded-xl bg-slate-900/5 border border-slate-900/10 flex items-center justify-center text-slate-600 hover:text-slate-900 hover:bg-slate-900/10 transition dark:bg-white/10 dark:border-white/15 dark:text-white/80 dark:hover:text-white dark:hover:bg-white/20" aria-label="Toggle sidebar">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-brand dark:text-brand-light">HarvestHaul</span>
                        <h2 class="text-sm font-bold text-slate-900 mt-0.5"><span class="{{ Auth::user()->role === 'buyer' ? 'text-harvest-dark dark:text-harvest-light' : 'text-slate-700 dark:text-white' }} uppercase font-black">{{ ['admin' => 'Administrator', 'farmer' => 'Farmer', 'buyer' => 'Buyer', 'logistics_partner' => 'Logistics Partner', 'driver' => 'Driver'][Auth::user()->role] ?? Auth::user()->role }}</span></h2>
                    </div>
                </div>

                <!-- User profile and avatar menu -->
                <div class="flex items-center gap-4 select-none">
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
                        <button onclick="toggleProfileDropdown()" class="flex items-center gap-3.5 pl-5 border-l border-slate-900/15 hover:opacity-90 transition cursor-pointer focus:outline-none text-left dark:border-white/15" aria-haspopup="true" aria-expanded="false" id="profile-menu-btn">
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
                                @if(Auth::user()->role === 'logistics_partner')
                                    <a href="{{ route('logistics.documents') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/40 hover:text-brand-700 dark:hover:text-brand-light transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Business Docs
                                    </a>
                                    @if(Auth::user()->logisticsProfile?->isCooperative())
                                        <a href="{{ route('logistics.members.index') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/40 hover:text-brand-700 dark:hover:text-brand-light transition-all">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            Members
                                        </a>
                                    @endif
                                @endif
                            @endif
                            <form method="POST" action="{{ route('logout') }}" class="w-full" id="logout-form">
                                @csrf
                                <button type="button" onclick="swalConfirm(document.getElementById('logout-form'), {title:'Sign Out', text:'Are you sure you want to sign out?', icon:'question', confirmText:'Yes, sign out', cancelText:'Cancel', confirmColor:'#ef4444'})" class="cursor-pointer w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-bold text-[var(--color-error-text)] hover:bg-[var(--color-error-bg)] transition-all duration-200 active:scale-[0.97] text-left">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 text-[var(--color-error-text)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
        function openMobileSidebar() {
            var sidebar = document.getElementById('sidebar-nav');
            var overlay = document.getElementById('sidebar-overlay');
            var btn = document.getElementById('mobile-menu-btn');
            clearTimeout(window.__sidebarCloseTimer);
            sidebar.classList.remove('-translate-x-full', 'sidebar-nav-exit');
            if (overlay) {
                overlay.classList.remove('hidden');
                void overlay.offsetWidth;
                overlay.classList.add('opacity-100');
            }
            document.body.style.overflow = 'hidden';
            if (btn) btn.setAttribute('aria-expanded', 'true');
            var firstLink = sidebar.querySelector('a, button');
            if (firstLink) firstLink.focus();
        }

        function closeMobileSidebar(releaseFocus) {
            var sidebar = document.getElementById('sidebar-nav');
            var overlay = document.getElementById('sidebar-overlay');
            var btn = document.getElementById('mobile-menu-btn');
            clearTimeout(window.__sidebarCloseTimer);
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.add('sidebar-nav-exit');
            if (overlay) {
                overlay.classList.remove('opacity-100');
                overlay.addEventListener('transitionend', function onClose(e) {
                    if (e.propertyName !== 'opacity') return;
                    overlay.removeEventListener('transitionend', onClose);
                    if (!overlay.classList.contains('opacity-100')) overlay.classList.add('hidden');
                });
                window.__sidebarCloseTimer = setTimeout(function() {
                    if (!overlay.classList.contains('opacity-100')) overlay.classList.add('hidden');
                }, 350);
            }
            document.body.style.overflow = '';
            if (btn) btn.setAttribute('aria-expanded', 'false');
            if (releaseFocus) {
                if (document.activeElement && sidebar.contains(document.activeElement)) btn && btn.focus();
            }
        }

        function toggleMobileSidebar(forceOpen) {
            var sidebar = document.getElementById('sidebar-nav');
            var isOpen = !sidebar.classList.contains('-translate-x-full');
            if (typeof forceOpen === 'boolean') isOpen = !forceOpen;
            if (!isOpen) {
                openMobileSidebar();
            } else {
                closeMobileSidebar(true);
            }
        }

        // Close mobile drawer on Escape and restore focus to the toggle
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                var sidebar = document.getElementById('sidebar-nav');
                if (sidebar && !sidebar.classList.contains('-translate-x-full') && window.innerWidth < 1024) {
                    closeMobileSidebar(true);
                }
            }
        });

        // Modal open/close utilities (used by x-modal, users, crops, etc.)
        function openModal(id) {
            var el = document.getElementById(id);
            if (el) el.classList.remove('hidden');
        }
        function closeModal(id) {
            var el = document.getElementById(id);
            if (el) el.classList.add('hidden');
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

            var isOpen = panel.classList.contains('submenu-open');
            if (isOpen) {
                panel.classList.remove('submenu-open');
                if (chevron) chevron.classList.remove('rotate-90');
                btn.setAttribute('aria-expanded', 'false');
            } else {
                panel.classList.add('submenu-open');
                if (chevron) chevron.classList.add('rotate-90');
                btn.setAttribute('aria-expanded', 'true');
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

    {{-- Flash messages render once as inline banners (see x-flash-success / x-flash-error) --}}
    <script>
        window.__nextSteps = @json(session('next_steps'));
    </script>

    <script src="{{ asset('assets/js/swal-helpers.js') }}"></script>

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
                    field.classList.remove('border-green-500', 'dark:border-green-400');
                    field.removeAttribute('aria-describedby');
                    if (existing) existing.remove();
                } else {
                    field.classList.remove('border-green-500', 'dark:border-green-400');
                    field.classList.add('border-red-500', 'dark:border-red-400');
                    if (!existing) {
                        var err = document.createElement('p');
                        err.id = errorId;
                        err.className = 'mt-1 text-xs text-[var(--color-error-text)]';
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
    @if(Auth::check() && (Auth::user()->role === 'farmer' || Auth::user()->role === 'buyer' || (Auth::user()->role === 'logistics_partner' && $authUser->logisticsProfile && $authUser->logisticsProfile->isCooperative())))
        <x-negotiations-widget />
    @endif

</body>
</html>
