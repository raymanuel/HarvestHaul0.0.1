<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'HarvestHaul' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --brand-green: #2D8A37;
            --obsidian: #0A0A0A;
            --glass-white: rgba(255, 255, 255, 0.75);
        }

        body {
            margin: 0; padding: 0;
            font-family: 'figtree', ui-sans-serif, system-ui, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
        }

        .sidebar {
            width: 220px;
            background-color: #111827;
            display: flex;
            flex-direction: column;
            padding: 2.5rem 1.5rem;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 40;
            border-right: 1px solid rgba(255,255,255,0.05);
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        .sidebar-hidden {
            transform: translateX(-100%);
        }

        .brand-title {
            color: var(--brand-green);
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 2.5rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.85rem 1rem;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 0.75rem;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .nav-link:hover {
            background: rgba(45, 138, 55, 0.15);
            color: #4ade80;
        }

        .nav-link.active {
            background: rgba(45, 138, 55, 0.2);
            color: var(--brand-green);
            border-left: 3px solid var(--brand-green);
        }

        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.5rem;
            width: 100%;
            margin-top: 2rem;
        }

        .report-widget {
            background: white;
            border-radius: 1.5rem;
            padding: 1.75rem;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            min-width: 0;
        }

        .report-widget:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.08);
            border-color: rgba(45,138,55,0.2);
        }

        .logout-form { margin-top: auto; padding-top: 1rem; }
        .logout-btn {
            width: 100%;
            background: rgba(255,255,255,0.05);
            color: #ef4444;
            border: 1px solid rgba(239,68,68,0.2);
            padding: 0.8rem;
            border-radius: 0.75rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        .logout-btn:hover {
            background: #ef4444;
            color: white;
        }

        /* Topbar for mobile */
        .topbar {
            display: none;
            position: sticky;
            top: 0;
            z-index: 50;
            background-color: #111827;
            padding: 0.85rem 1.25rem;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .topbar-brand {
            color: white;
            font-size: 1.2rem;
            font-weight: 800;
            text-decoration: none;
        }

        .topbar-brand span { color: var(--brand-green); }

        .hamburger-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: white;
            padding: 0.25rem;
            display: flex;
            align-items: center;
        }

        /* Overlay for mobile sidebar */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 30;
        }

        .sidebar-overlay.active { display: block; }

        /* Responsive table wrapper */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Main layout */
        .app-shell {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            min-width: 0;
            margin-left: 220px;
            padding: 2rem 2rem 3rem;
            background-color: var(--glass-white);
            min-height: 100vh;
            box-sizing: border-box;
        }

        /* Mobile breakpoint */
        @media (max-width: 1023px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .topbar { display: flex; }
            .main-content { margin-left: 0; padding: 1.25rem 1rem 2rem; }
        }
    </style>
</head>
<body>

    {{-- Mobile Topbar --}}
    <div class="topbar">
        <a href="/dashboard" class="topbar-brand">Harvest<span>Haul</span></a>
        <button class="hamburger-btn" id="hamburger" aria-label="Open menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
    </div>

    {{-- Overlay --}}
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <div class="app-shell">

        {{-- Sidebar --}}
        <aside class="sidebar" id="sidebar">
            <a href="/dashboard" class="brand-title">
                <span style="color:white">Harvest</span>Haul
            </a>

            <nav>
                <a href="/dashboard" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">🏠 Dashboard</a>

                @if(Auth::check() && Auth::user()->role === 'farmer')
                    <a href="{{ route('harvests.index') }}" class="nav-link {{ request()->routeIs('harvests.*') ? 'active' : '' }}">🚜 Post Harvest</a>
                    <a href="#" class="nav-link">🤝 Resource Pooling</a>
                    <a href="#" class="nav-link">🚚 Track Shipments</a>
                    <a href="#" class="nav-link">📊 Market Trends</a>
                @endif

                @if(Auth::check() && Auth::user()->role === 'admin')
                    <a href="{{ route('admin.users') }}"      class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">👥 User Management</a>
                    <a href="{{ route('admin.farmers') }}"    class="nav-link {{ request()->routeIs('admin.farmers*') ? 'active' : '' }}">🌾 Farmer Verification</a>
                    <a href="{{ route('admin.logistics') }}"  class="nav-link {{ request()->routeIs('admin.logistics*') ? 'active' : '' }}">🚛 Logistics Verification</a>
                    <a href="{{ route('admin.audit-logs') }}" class="nav-link {{ request()->routeIs('admin.audit-logs*') ? 'active' : '' }}">🔍 Audit Logs</a>
                @endif

                @if(Auth::check() && Auth::user()->role === 'logistics_partner')
                    <a href="#" class="nav-link">🚛 Fleet Management</a>
                    <a href="#" class="nav-link">👥 Driver Accounts</a>
                    <a href="{{ route('route.optimization') }}" class="nav-link {{ request()->routeIs('route.optimization') ? 'active' : '' }}">📍 Route Optimization</a>
                    <a href="#" class="nav-link">📝 Assign Tasks</a>
                @endif

                @if(Auth::check() && Auth::user()->role === 'driver')
                    <a href="#" class="nav-link">📍 Waypoint Navigation</a>
                    <a href="#" class="nav-link">📷 Proof of Delivery</a>
                    <a href="#" class="nav-link">⚠️ Report Issue</a>
                    <a href="#" class="nav-link">🕒 Trip History</a>
                @endif
            </nav>

            <form method="POST" action="{{ route('logout') }}" class="logout-form">
                @csrf
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </aside>

        {{-- Main Content --}}
        <main class="main-content">
            {{ $slot }}
        </main>

    </div>

    <script>
        const hamburger = document.getElementById('hamburger');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        function openSidebar() {
            sidebar.classList.add('open');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        hamburger.addEventListener('click', openSidebar);
        overlay.addEventListener('click', closeSidebar);
    </script>

</body>
</html>
