<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'HarvestHaul' }}</title>

    <style>
        body {
            margin: 0; padding: 0;
            font-family: 'figtree', ui-sans-serif, system-ui, sans-serif;
            background: linear-gradient(135deg, #f1f5f9 0%, #faf5ff 50%, #eff6ff 100%);
            min-height: 100vh;
            display: flex; /* Positions sidebar and content side-by-side */

        }

        /* Fixed Sidebar on the Left */
        .sidebar {
            width: 260px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.3);
            display: flex;
            flex-direction: column;
            padding: 2rem 1.5rem;
            height: 100vh;
            position: sticky;
            top: 0;
            background-color: #111827;
        }

        .brand-title {
            color: #2D8A37;
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 2.5rem;
            text-decoration: none;
        }

        /* Main Content Area - Flexible */
        .main-content {
            flex: 1;
            padding: 0;
            display: flex;
            flex-direction: column;
            /* CHANGE: Change center to flex-start */
            align-items: flex-start;
            overflow-y: auto;
            background-color: #0d3c2e;

        }

        .glass-card {
            width: 100%;
            max-width: {{ $maxWidth ?? '1000px' }};
            padding: 2rem;
            padding-top: 0;

            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 0;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
            /* ADD: Ensure card content stays left-aligned */
            text-align: left;
        }



        .nav-link {
            display: block;
            padding: 0.8rem 1rem;
            color: #475569;
            text-decoration: none;
            border-radius: 0.75rem;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
            font-weight: 500;
        }

        .nav-link:hover {
            background: rgba(45, 138, 55, 0.1);
            color: #2D8A37;
        }

        /* The INTERNAL modern cards you want */
        .report-widget {
            width: 25%;
            background: rgba(255, 255, 255, 0.85);
            border-radius: 1.5rem;
            padding: 1.5rem;
            border: 1px solid white;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        .report-widget:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }



        .logout-form { margin-top: auto; }
        .logout-btn {
            width: 100%;
            background: ghostwhite;
            color: #111827;;
            border: none;
            padding: 0.8rem;
            border-radius: 0.75rem;
            cursor: pointer;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <a href="/dashboard" class="brand-title">HarvestHaul</a>

        <!-- add Auth check role here for buttons -->
        <nav>
            <a href="/dashboard" class="nav-link">🏠 Dashboard</a>

            @if(Auth::check() && Auth::user()->role === 'producer')
                <a href="#" class="nav-link">🚜 Post Harvest</a>
                <a href="#" class="nav-link">🤝 Resource Pooling</a>
                <a href="#" class="nav-link">🚚 Track Shipments</a>
                <a href="#" class="nav-link">📊 Market Trends</a>
                <a href="#" class="nav-link">⭐ Rate Partners</a>
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
