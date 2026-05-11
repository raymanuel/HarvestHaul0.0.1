<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HarvestHaul — Agricultural Logistics Platform</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=syne:400,500,600,700,800|plus-jakarta-sans:400,500,600" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green-900: #0f2b13;
            --green-800: #1a4220;
            --green-700: #1f5227;
            --green-600: #2D8A37;
            --green-500: #3aaa45;
            --green-400: #5ec469;
            --green-100: #d4f0d7;
            --green-50:  #edfaee;
            --ink:       #0d1b0f;
            --ink-2:     #2a3d2c;
            --muted:     #5a7060;
            --surface:   #f5faf5;
            --white:     #ffffff;
            --border:    rgba(45,138,55,0.15);
            --font-display: 'Syne', sans-serif;
            --font-body:    'Plus Jakarta Sans', sans-serif;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: var(--font-body);
            background: var(--white);
            color: var(--ink);
            overflow-x: hidden;
        }

        /* ── NAV ── */
        nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            padding: 0 2rem;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            transition: background 0.3s;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .nav-logo-icon {
            width: 36px;
            height: 36px;
            background: var(--green-600);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-logo-text {
            font-family: var(--font-display);
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -0.02em;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            list-style: none;
        }

        .nav-links a {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--ink-2);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: background 0.2s, color 0.2s;
        }

        .nav-links a:hover { background: var(--green-50); color: var(--green-600); }

        .nav-cta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-ghost {
            font-family: var(--font-body);
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--ink-2);
            text-decoration: none;
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .btn-ghost:hover { background: var(--green-50); }

        .btn-primary {
            font-family: var(--font-body);
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--white);
            text-decoration: none;
            padding: 0.5rem 1.25rem;
            background: var(--green-600);
            border-radius: 8px;
            transition: background 0.2s, transform 0.15s;
        }

        .btn-primary:hover { background: var(--green-700); transform: translateY(-1px); }

        /* burger */
        .burger {
            display: none;
            flex-direction: column;
            gap: 5px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
        }

        .burger span {
            display: block;
            width: 22px;
            height: 2px;
            background: var(--ink);
            border-radius: 2px;
            transition: transform 0.3s, opacity 0.3s;
        }

        .burger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
        .burger.open span:nth-child(2) { opacity: 0; }
        .burger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

        /* mobile menu */
        .mobile-menu {
            display: none;
            position: fixed;
            top: 64px; left: 0; right: 0;
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.5rem 1.5rem;
            z-index: 99;
            flex-direction: column;
            gap: 0.5rem;
        }

        .mobile-menu.open { display: flex; }

        .mobile-menu a {
            font-size: 0.9375rem;
            font-weight: 500;
            color: var(--ink-2);
            text-decoration: none;
            padding: 0.625rem 0.75rem;
            border-radius: 8px;
        }

        .mobile-menu a:hover { background: var(--green-50); color: var(--green-600); }

        .mobile-menu .mobile-divider {
            height: 1px;
            background: var(--border);
            margin: 0.5rem 0;
        }

        .mobile-menu .btn-primary {
            text-align: center;
            padding: 0.75rem;
            font-size: 0.9375rem;
        }

        /* ── HERO ── */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 7rem 2rem 4rem;
            position: relative;
            overflow: hidden;
        }

        .hero-bg {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 60% 40%, rgba(45,138,55,0.08) 0%, transparent 70%),
                radial-gradient(ellipse 40% 40% at 80% 70%, rgba(94,196,105,0.06) 0%, transparent 60%);
            pointer-events: none;
        }

        .hero-grid-bg {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(45,138,55,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(45,138,55,0.05) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }

        .hero-inner {
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--green-50);
            border: 1px solid var(--green-100);
            border-radius: 100px;
            padding: 0.375rem 0.875rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--green-700);
            margin-bottom: 1.5rem;
        }

        .hero-badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--green-500);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }

        .hero-title {
            font-family: var(--font-display);
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            line-height: 1.08;
            letter-spacing: -0.03em;
            color: var(--ink);
            margin-bottom: 1.5rem;
        }

        .hero-title .accent {
            color: var(--green-600);
            position: relative;
        }

        .hero-desc {
            font-size: 1.0625rem;
            line-height: 1.7;
            color: var(--muted);
            margin-bottom: 2.5rem;
            max-width: 480px;
        }

        .hero-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-hero-primary {
            font-family: var(--font-body);
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--white);
            text-decoration: none;
            padding: 0.875rem 2rem;
            background: var(--green-600);
            border-radius: 10px;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 20px rgba(45,138,55,0.3);
        }

        .btn-hero-primary:hover {
            background: var(--green-700);
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(45,138,55,0.35);
        }

        .btn-hero-secondary {
            font-family: var(--font-body);
            font-size: 0.9375rem;
            font-weight: 500;
            color: var(--ink-2);
            text-decoration: none;
            padding: 0.875rem 2rem;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            transition: background 0.2s, border-color 0.2s;
        }

        .btn-hero-secondary:hover { background: var(--green-50); border-color: var(--green-400); }

        .hero-stats {
            display: flex;
            gap: 2rem;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border);
        }

        .hero-stat-num {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--ink);
            line-height: 1;
        }

        .hero-stat-label {
            font-size: 0.8125rem;
            color: var(--muted);
            margin-top: 4px;
        }

        /* hero visual */
        .hero-visual {
            position: relative;
        }

        .hero-card-main {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 20px 60px rgba(15,43,19,0.08);
        }

        .hero-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
        }

        .hero-card-title {
            font-family: var(--font-display);
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--ink);
        }

        .status-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.625rem;
            border-radius: 100px;
            background: var(--green-50);
            color: var(--green-700);
            border: 1px solid var(--green-100);
        }

        .route-visual {
            background: var(--surface);
            border-radius: 12px;
            height: 140px;
            position: relative;
            overflow: hidden;
            margin-bottom: 1.25rem;
        }

        .route-line {
            position: absolute;
            top: 50%;
            left: 15%;
            right: 15%;
            height: 2px;
            background: linear-gradient(90deg, var(--green-500), var(--green-400));
            transform: translateY(-50%);
        }

        .route-dot {
            position: absolute;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2.5px solid var(--white);
            top: 50%;
            transform: translateY(-50%);
        }

        .route-dot.start { background: var(--green-600); left: 15%; margin-left: -6px; }
        .route-dot.farm1 { background: var(--green-500); left: 38%; margin-left: -6px; }
        .route-dot.farm2 { background: var(--green-500); left: 58%; margin-left: -6px; }
        .route-dot.farm3 { background: var(--green-400); left: 75%; margin-left: -6px; }
        .route-dot.end   { background: var(--ink-2); right: 15%; margin-right: -6px; }

        .route-label {
            position: absolute;
            font-size: 0.625rem;
            font-weight: 600;
            color: var(--muted);
            top: calc(50% + 14px);
            transform: translateX(-50%);
            white-space: nowrap;
        }

        .route-label.start { left: 15%; }
        .route-label.farm1 { left: 38%; }
        .route-label.farm2 { left: 58%; }
        .route-label.farm3 { left: 75%; }
        .route-label.end   { right: 15%; transform: translateX(50%); }

        .farm-rows { display: flex; flex-direction: column; gap: 0.625rem; }

        .farm-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.625rem 0.875rem;
            background: var(--surface);
            border-radius: 10px;
        }

        .farm-row-left { display: flex; align-items: center; gap: 10px; }

        .farm-avatar {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--green-100);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--green-800);
        }

        .farm-name { font-size: 0.8125rem; font-weight: 600; color: var(--ink); }
        .farm-detail { font-size: 0.6875rem; color: var(--muted); }

        .farm-kg {
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--green-700);
        }

        /* floating cards */
        .float-card {
            position: absolute;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 0.875rem 1rem;
            box-shadow: 0 8px 32px rgba(15,43,19,0.1);
        }

        .float-card-1 {
            top: -1.5rem;
            right: -1.5rem;
            min-width: 160px;
        }

        .float-card-2 {
            bottom: -1rem;
            left: -1.5rem;
            min-width: 180px;
        }

        .float-label {
            font-size: 0.6875rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }

        .float-value {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--ink);
            line-height: 1;
        }

        .float-sub {
            font-size: 0.75rem;
            color: var(--green-600);
            font-weight: 600;
            margin-top: 4px;
        }

        /* ── FEATURES ── */
        .section {
            padding: 6rem 2rem;
        }

        .section-inner {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--green-600);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 1rem;
        }

        .section-label::before {
            content: '';
            width: 16px;
            height: 2px;
            background: var(--green-500);
            border-radius: 2px;
        }

        .section-title {
            font-family: var(--font-display);
            font-size: clamp(1.75rem, 3.5vw, 2.75rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.025em;
            color: var(--ink);
            margin-bottom: 1rem;
        }

        .section-desc {
            font-size: 1.0625rem;
            color: var(--muted);
            line-height: 1.7;
            max-width: 560px;
        }

        .section-header { margin-bottom: 3.5rem; }

        /* features grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .feature-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.75rem;
            transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 40px rgba(15,43,19,0.08);
            border-color: var(--green-300, #86efac);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            background: var(--green-50);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
        }

        .feature-title {
            font-family: var(--font-display);
            font-size: 1rem;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 0.5rem;
        }

        .feature-desc {
            font-size: 0.9rem;
            color: var(--muted);
            line-height: 1.65;
        }

        /* ── HOW IT WORKS ── */
        .how-section {
            padding: 6rem 2rem;
            background: var(--ink);
            position: relative;
            overflow: hidden;
        }

        .how-bg {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 60% at 20% 50%, rgba(45,138,55,0.15) 0%, transparent 70%),
                radial-gradient(ellipse 40% 40% at 80% 30%, rgba(94,196,105,0.08) 0%, transparent 60%);
            pointer-events: none;
        }

        .how-inner {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .how-section .section-label { color: var(--green-400); }
        .how-section .section-label::before { background: var(--green-500); }
        .how-section .section-title { color: var(--white); }
        .how-section .section-desc { color: rgba(255,255,255,0.55); }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 2rem;
            margin-top: 3.5rem;
        }

        .step-card {
            position: relative;
        }

        .step-num {
            font-family: var(--font-display);
            font-size: 3.5rem;
            font-weight: 800;
            color: rgba(45,138,55,0.2);
            line-height: 1;
            margin-bottom: 1rem;
        }

        .step-title {
            font-family: var(--font-display);
            font-size: 1.0625rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 0.625rem;
        }

        .step-desc {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.5);
            line-height: 1.65;
        }

        /* ── WHY ── */
        .why-section {
            padding: 6rem 2rem;
            background: var(--surface);
        }

        .why-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .why-list { display: flex; flex-direction: column; gap: 1.5rem; margin-top: 2rem; }

        .why-item { display: flex; gap: 1rem; align-items: flex-start; }

        .why-icon {
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 10px;
            background: var(--green-600);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .why-text-title {
            font-family: var(--font-display);
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 0.25rem;
        }

        .why-text-desc { font-size: 0.875rem; color: var(--muted); line-height: 1.6; }

        .why-visual {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 40px rgba(15,43,19,0.06);
        }

        .metric-row {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .metric-item { }

        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }

        .metric-name { font-size: 0.8125rem; font-weight: 600; color: var(--ink-2); }
        .metric-val  { font-size: 0.8125rem; font-weight: 700; color: var(--green-700); }

        .metric-bar {
            height: 6px;
            background: var(--green-50);
            border-radius: 100px;
            overflow: hidden;
        }

        .metric-fill {
            height: 100%;
            background: var(--green-600);
            border-radius: 100px;
            transition: width 1s ease;
        }

        /* ── CTA ── */
        .cta-section {
            padding: 6rem 2rem;
            text-align: center;
        }

        .cta-inner {
            max-width: 640px;
            margin: 0 auto;
        }

        .cta-title {
            font-family: var(--font-display);
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.025em;
            color: var(--ink);
            margin-bottom: 1rem;
        }

        .cta-desc {
            font-size: 1.0625rem;
            color: var(--muted);
            line-height: 1.7;
            margin-bottom: 2.5rem;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* ── FOOTER ── */
        footer {
            border-top: 1px solid var(--border);
            padding: 2rem;
        }

        .footer-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer-copy { font-size: 0.875rem; color: var(--muted); }

        .footer-links { display: flex; gap: 1.5rem; }

        .footer-links a {
            font-size: 0.875rem;
            color: var(--muted);
            text-decoration: none;
        }

        .footer-links a:hover { color: var(--green-600); }

        /* ── RESPONSIVE ── */
        @media (max-width: 1024px) {
            .hero-inner { grid-template-columns: 1fr; gap: 3rem; }
            .hero-visual { display: none; }
            .why-grid { grid-template-columns: 1fr; }
            .why-visual { display: none; }
        }

        @media (max-width: 768px) {
            nav { padding: 0 1.25rem; }
            .nav-links, .nav-cta { display: none; }
            .burger { display: flex; }
            .hero { padding: 6rem 1.25rem 3rem; }
            .hero-stats { gap: 1.5rem; flex-wrap: wrap; }
            .section { padding: 4rem 1.25rem; }
            .how-section { padding: 4rem 1.25rem; }
            .why-section { padding: 4rem 1.25rem; }
            .cta-section { padding: 4rem 1.25rem; }
            footer { padding: 1.5rem 1.25rem; }
            .footer-inner { flex-direction: column; align-items: flex-start; gap: 1rem; }
        }

        /* entrance animations */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .fade-up { animation: fadeUp 0.6s ease forwards; }
        .fade-up-1 { animation-delay: 0.1s; opacity: 0; }
        .fade-up-2 { animation-delay: 0.2s; opacity: 0; }
        .fade-up-3 { animation-delay: 0.35s; opacity: 0; }
        .fade-up-4 { animation-delay: 0.5s; opacity: 0; }
    </style>
</head>
<body>

    {{-- ── NAVBAR ── --}}
    <nav>
        <a href="/" class="nav-logo">
            <div class="nav-logo-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8a13 13 0 0 1-10 10Z"/>
                    <path d="M9 21s-4.5-3-4.5-7"/>
                    <path d="M7 20s-4-3.5-4-9"/>
                </svg>
            </div>
            <span class="nav-logo-text">HarvestHaul</span>
        </a>

        <ul class="nav-links">
            <li><a href="#features">Features</a></li>
            <li><a href="#how-it-works">How It Works</a></li>
            <li><a href="#why">Why Us</a></li>
        </ul>

        @if (Route::has('login'))
            <div class="nav-cta">
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn-primary">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn-ghost">Log in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn-primary">Get Started</a>
                    @endif
                @endauth
            </div>
        @endif

        <button class="burger" id="burger" aria-label="Open menu">
            <span></span><span></span><span></span>
        </button>
    </nav>

    {{-- mobile menu --}}
    <div class="mobile-menu" id="mobile-menu">
        <a href="#features">Features</a>
        <a href="#how-it-works">How It Works</a>
        <a href="#why">Why Us</a>
        <div class="mobile-divider"></div>
        @if (Route::has('login'))
            @auth
                <a href="{{ url('/dashboard') }}" class="btn-primary">Dashboard</a>
            @else
                <a href="{{ route('login') }}" style="color: var(--muted);">Log in</a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn-primary">Get Started Free</a>
                @endif
            @endauth
        @endif
    </div>

    {{-- ── HERO ── --}}
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-grid-bg"></div>

        <div class="hero-inner">
            <div>
                <div class="hero-badge fade-up fade-up-1">
                    <span class="hero-badge-dot"></span>
                    Built for GenSan Agriculture
                </div>

                <h1 class="hero-title fade-up fade-up-2">
                    Move More Crops.<br>
                    <span class="accent">Together.</span>
                </h1>

                <p class="hero-desc fade-up fade-up-3">
                    HarvestHaul connects farmers, cooperatives, and logistics partners on one platform — pooling resources, optimizing routes, and getting harvests where they need to go.
                </p>

                @guest
                    <div class="hero-actions fade-up fade-up-4">
                        <a href="{{ route('register') }}" class="btn-hero-primary">Join the Network</a>
                        <a href="#how-it-works" class="btn-hero-secondary">See How It Works</a>
                    </div>
                @else
                    <div class="hero-actions fade-up fade-up-4">
                        <a href="{{ route('dashboard') }}" class="btn-hero-primary">Go to Dashboard</a>
                    </div>
                @endguest

                <div class="hero-stats fade-up fade-up-4">
                    <div>
                        <div class="hero-stat-num">3</div>
                        <div class="hero-stat-label">User Roles</div>
                    </div>
                    <div>
                        <div class="hero-stat-num">GenSan</div>
                        <div class="hero-stat-label">South Cotabato</div>
                    </div>
                    <div>
                        <div class="hero-stat-num">Live</div>
                        <div class="hero-stat-label">Route Optimization</div>
                    </div>
                </div>
            </div>

            {{-- Hero visual card --}}
            <div class="hero-visual fade-up fade-up-3" style="position: relative; padding: 1.5rem;">

                <div class="float-card float-card-1">
                    <div class="float-label">Truck Load</div>
                    <div class="float-value">87%</div>
                    <div class="float-sub">↑ Capacity used</div>
                </div>

                <div class="hero-card-main">
                    <div class="hero-card-header">
                        <div class="hero-card-title">Active Pooling Job</div>
                        <span class="status-badge">● In Progress</span>
                    </div>

                    <div class="route-visual">
                        <div class="route-line"></div>
                        <div class="route-dot start"></div>
                        <div class="route-dot farm1"></div>
                        <div class="route-dot farm2"></div>
                        <div class="route-dot farm3"></div>
                        <div class="route-dot end"></div>
                        <span class="route-label start">Hub</span>
                        <span class="route-label farm1">Farm A</span>
                        <span class="route-label farm2">Farm B</span>
                        <span class="route-label farm3">Farm C</span>
                        <span class="route-label end">Market</span>
                    </div>

                    <div class="farm-rows">
                        <div class="farm-row">
                            <div class="farm-row-left">
                                <div class="farm-avatar">LF</div>
                                <div>
                                    <div class="farm-name">Lagao Fruit Farm</div>
                                    <div class="farm-detail">Alugbati · Pickup #1</div>
                                </div>
                            </div>
                            <div class="farm-kg">123 kg</div>
                        </div>
                        <div class="farm-row">
                            <div class="farm-row-left">
                                <div class="farm-avatar">TB</div>
                                <div>
                                    <div class="farm-name">Tupi Banana Co.</div>
                                    <div class="farm-detail">Saba · Pickup #2</div>
                                </div>
                            </div>
                            <div class="farm-kg">340 kg</div>
                        </div>
                        <div class="farm-row">
                            <div class="farm-row-left">
                                <div class="farm-avatar">PS</div>
                                <div>
                                    <div class="farm-name">Polomolok Supplies</div>
                                    <div class="farm-detail">Ampalaya · Pickup #3</div>
                                </div>
                            </div>
                            <div class="farm-kg">210 kg</div>
                        </div>
                    </div>
                </div>

                <div class="float-card float-card-2">
                    <div class="float-label">Route Saved</div>
                    <div class="float-value" style="font-size:1.25rem;">12.4 km</div>
                    <div class="float-sub">vs. 3 separate trips</div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── FEATURES ── --}}
    <section class="section" id="features">
        <div class="section-inner">
            <div class="section-header">
                <div class="section-label">Platform Features</div>
                <h2 class="section-title">Everything your harvest<br>logistics needs</h2>
                <p class="section-desc">From farm to market — HarvestHaul handles the coordination so you don't have to.</p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--green-600)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 11l19-9-9 19-2-8-8-2z"/>
                        </svg>
                    </div>
                    <div class="feature-title">Route Optimization</div>
                    <p class="feature-desc">OSRM-powered routing detects farms along your path and builds the most efficient multi-stop pickup route automatically.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--green-600)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
                        </svg>
                    </div>
                    <div class="feature-title">Resource Pooling</div>
                    <p class="feature-desc">Knapsack and nearest-neighbor algorithms select the optimal farm combination to maximize truck capacity on every run.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--green-600)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                        </svg>
                    </div>
                    <div class="feature-title">Live Map View</div>
                    <p class="feature-desc">Leaflet-powered interactive map shows all active farm listings, destinations, and route detours in real time.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--green-600)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <div class="feature-title">Multi-Role Platform</div>
                    <p class="feature-desc">Separate dashboards for farmers, logistics coordinators, drivers, and admins — each with role-specific tools and views.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--green-600)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                        </svg>
                    </div>
                    <div class="feature-title">Harvest Tracking</div>
                    <p class="feature-desc">Track every harvest from posting to delivery — with status updates, quality grades, and full destination records.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--green-600)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
                        </svg>
                    </div>
                    <div class="feature-title">Verified Partners</div>
                    <p class="feature-desc">Admin-verified farmers and logistics partners with document upload and approval workflows built in.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ── HOW IT WORKS ── --}}
    <section class="how-section" id="how-it-works">
        <div class="how-bg"></div>
        <div class="how-inner">
            <div class="section-header">
                <div class="section-label">The Process</div>
                <h2 class="section-title">From sign-up to delivery<br>in four steps</h2>
                <p class="section-desc">A straightforward flow designed around how agriculture actually works in South Cotabato.</p>
            </div>

            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-num">01</div>
                    <div class="step-title">Create Your Account</div>
                    <p class="step-desc">Register as a farmer, logistics coordinator, or driver. Your account is verified by an admin before going live.</p>
                </div>
                <div class="step-card">
                    <div class="step-num">02</div>
                    <div class="step-title">Post a Haul Request</div>
                    <p class="step-desc">Farmers list their harvest — crop type, quantity, pickup location, and destination. It appears on the map instantly.</p>
                </div>
                <div class="step-card">
                    <div class="step-num">03</div>
                    <div class="step-title">Plan the Route</div>
                    <p class="step-desc">Logistics coordinators select a truck, set start and end points, and the system generates an optimized pooling plan.</p>
                </div>
                <div class="step-card">
                    <div class="step-num">04</div>
                    <div class="step-title">Confirm & Deliver</div>
                    <p class="step-desc">Confirm the job, assign the driver, and track the delivery from pickup to market. Every step is logged.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ── WHY ── --}}
    <section class="why-section" id="why">
        <div class="section-inner">
            <div class="why-grid">
                <div>
                    <div class="section-label">Why HarvestHaul</div>
                    <h2 class="section-title">Built for the way<br>GenSan farms work</h2>
                    <p class="section-desc">Not a generic logistics app. Designed from the ground up around the realities of agricultural hauling in South Cotabato.</p>

                    <div class="why-list">
                        <div class="why-item">
                            <div class="why-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                            </div>
                            <div>
                                <div class="why-text-title">Reduce empty truck runs</div>
                                <p class="why-text-desc">Pool multiple farms into a single optimized trip — less fuel, lower cost per kilogram delivered.</p>
                            </div>
                        </div>
                        <div class="why-item">
                            <div class="why-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                            </div>
                            <div>
                                <div class="why-text-title">No more phone-tag coordination</div>
                                <p class="why-text-desc">Everything from haul requests to job confirmation happens in one place, with a full audit trail.</p>
                            </div>
                        </div>
                        <div class="why-item">
                            <div class="why-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                            </div>
                            <div>
                                <div class="why-text-title">Farmer visibility, farmer control</div>
                                <p class="why-text-desc">Farmers choose their destination, set their quantity, and track their crop every step of the way.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="why-visual">
                    <div style="font-family: var(--font-display); font-size: 0.875rem; font-weight: 700; color: var(--ink); margin-bottom: 1.5rem;">Platform Efficiency Metrics</div>
                    <div class="metric-row">
                        <div class="metric-item">
                            <div class="metric-header">
                                <span class="metric-name">Average truck capacity used</span>
                                <span class="metric-val">87%</span>
                            </div>
                            <div class="metric-bar"><div class="metric-fill" style="width: 87%"></div></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-header">
                                <span class="metric-name">Farms served per trip</span>
                                <span class="metric-val">3.2 avg</span>
                            </div>
                            <div class="metric-bar"><div class="metric-fill" style="width: 64%"></div></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-header">
                                <span class="metric-name">Route distance saved</span>
                                <span class="metric-val">34%</span>
                            </div>
                            <div class="metric-bar"><div class="metric-fill" style="width: 34%"></div></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-header">
                                <span class="metric-name">Verified partners onboarded</span>
                                <span class="metric-val">100%</span>
                            </div>
                            <div class="metric-bar"><div class="metric-fill" style="width: 100%"></div></div>
                        </div>
                    </div>

                    <div style="margin-top: 1.75rem; padding-top: 1.5rem; border-top: 1px solid var(--border); display: flex; gap: 1.5rem;">
                        <div>
                            <div style="font-family: var(--font-display); font-size: 1.75rem; font-weight: 800; color: var(--ink);">GenSan</div>
                            <div style="font-size: 0.8125rem; color: var(--muted); margin-top: 2px;">Primary coverage area</div>
                        </div>
                        <div>
                            <div style="font-family: var(--font-display); font-size: 1.75rem; font-weight: 800; color: var(--green-600);">Live</div>
                            <div style="font-size: 0.8125rem; color: var(--muted); margin-top: 2px;">System status</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── CTA ── --}}
    @guest
    <section class="cta-section">
        <div class="cta-inner">
            <h2 class="cta-title">Ready to haul smarter?</h2>
            <p class="cta-desc">Join HarvestHaul and start connecting your farm or fleet to the most efficient agricultural logistics network in South Cotabato.</p>
            <div class="cta-buttons">
                <a href="{{ route('register') }}" class="btn-hero-primary">Create Your Account</a>
                <a href="{{ route('login') }}" class="btn-hero-secondary">Log In</a>
            </div>
        </div>
    </section>
    @endguest

    {{-- ── FOOTER ── --}}
    <footer>
        <div class="footer-inner">
            <div class="footer-copy">&copy; {{ date('Y') }} HarvestHaul. Built in General Santos City.</div>
            <div class="footer-links">
                <a href="#">Privacy</a>
                <a href="#">Terms</a>
                <a href="{{ route('login') }}">Log in</a>
            </div>
        </div>
    </footer>

    <script>
        const burger = document.getElementById('burger');
        const mobileMenu = document.getElementById('mobile-menu');

        burger.addEventListener('click', () => {
            burger.classList.toggle('open');
            mobileMenu.classList.toggle('open');
        });

        // close mobile menu when a link is clicked
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                burger.classList.remove('open');
                mobileMenu.classList.remove('open');
            });
        });

        // smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>
