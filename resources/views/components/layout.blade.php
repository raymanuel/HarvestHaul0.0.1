<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'HarvestHaul' }}</title>

    <style>
        /* CSS Variables for Brand Consistency */
        :root {
            --brand-green: #2D8A37;
            --obsidian: #0A0A0A; /* Matches Welcome Page Background */
            --slate-text: #475569;
            --glass-white: rgba(255, 255, 255, 0.75);
        }

        body {
            margin: 0; padding: 0;
            font-family: 'figtree', ui-sans-serif, system-ui, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
            display: flex;
        }

        /* Fixed Sidebar - Obsidian Theme */
        .sidebar {
            width: 220px; /* Slightly wider for better text breathing room */
            background: rgba(255, 255, 255, 0.7);
            background-color: #111827;
            display: flex;
            flex-direction: column;
            padding: 2.5rem 1.5rem;
            height: 100vh;
            position: sticky;
            top: 0;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
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
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            display: block;
            min-width: 0;
            background-color: var(--glass-white); /* Subtle contrast from the cards */
            min-height: 100vh;
        }

        /* The Glass Dashboard Area */
        .glass-card {
            width: 100%;
            max-width: {{ $maxWidth ?? '1200px' }};
            padding: 3rem 2rem;
            background-color: var(--glass-white);
            backdrop-filter: blur(12px);
            min-height: 100vh;
            text-align: left;
            display: block !important;
            box-sizing: border-box;
            border-left: 1px solid rgba(0, 0, 0, 0.05);
        }

        /* Navigation Links - Dark Mode Style */
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.85rem 1rem;
            color: #94a3b8; /* Muted slate for dark background */
            text-decoration: none;
            border-radius: 0.75rem;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-link:hover {
            background: rgba(45, 138, 55, 0.15);
            color: #4ade80; /* Vibrant leaf green on hover */
        }

        .nav-link.active {
            background: rgba(45, 138, 55, 0.2);
            color: var(--brand-green);
            border-left: 3px solid var(--brand-green);
        }

        /* The Internal Grid System */
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, 240px);
            gap: 5rem;
            width: 100%;
            margin-top: 2rem;
            justify-content: flex-start;
        }

        .report-widget {
            width: 240px !important;
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            border: 1px solid rgba(0, 0, 0, 0.03);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .report-widget:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08);
            border-color: rgba(45, 138, 55, 0.2);
        }

        /* Logout Section */
        .logout-form { margin-top: auto; }
        .logout-btn {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            color: #ef4444; /* Red for destructive action */
            border: 1px solid rgba(239, 68, 68, 0.2);
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
    </style>
</head>
<body>

    <aside class="sidebar">
        <a href="/dashboard" class="brand-title">
            <span style="color: white">Harvest</span>Haul
        </a>

        <nav>
            <a href="/dashboard" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">🏠 Dashboard</a>

            @if(Auth::check() && Auth::user()->role === 'farmer')
                <a href="#" class="nav-link">🚜 Post Harvest</a>
                <a href="#" class="nav-link">🤝 Resource Pooling</a>
                <a href="#" class="nav-link">🚚 Track Shipments</a>
                <a href="#" class="nav-link">📊 Market Trends</a>
            @endif

            @if(Auth::check() && Auth::user()->role === 'admin')
                <a href="#" class="nav-link">📁 User Verification</a>
                <a href="#" class="nav-link">🔑 Role Management</a>
                <a href="#" class="nav-link">🔍 System Audit</a>
            @endif

            @if(Auth::check() && Auth::user()->role === 'logistics_partner')

                <a href="#" class="nav-link">🚛 Fleet Management</a>

                <a href="#" class="nav-link">👥 Driver Accounts</a>

                <a href="#" class="nav-link">📍 Route Optimization</a>

                <a href="#" class="nav-link">💰 Revenue Analytics</a>

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

    <main class="main-content">
        <div class="glass-card">
            {{ $slot }}
        </div>
    </main>

</body>
</html>
