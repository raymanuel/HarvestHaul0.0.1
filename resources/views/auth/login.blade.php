<!DOCTYPE html>
<html lang="en">
<head>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HarvestHaul — Welcome</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet" />
        <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        html, body { height:100%; overflow:hidden; font-family:'DM Sans',sans-serif; }

        .btn-loading { pointer-events:none; opacity:0.7; }
        .btn-loading::after { content:''; display:inline-block; width:16px; height:16px; border:2px solid rgba(255,255,255,0.3); border-top-color:#fff; border-radius:50%; animation:spin 0.6s linear infinite; margin-left:8px; vertical-align:middle; }
        .panel-slide { transition: transform 0.7s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.7s cubic-bezier(0.22, 1, 0.36, 1); }
        .slide-in { transform: translateX(0); opacity: 1; }
        .slide-out-left { transform: translateX(-40px); opacity: 0; pointer-events: none; }
        .slide-in-right { transform: translateX(0); opacity: 1; }
        .slide-out-right { transform: translateX(40px); opacity: 0; pointer-events: none; }
        .hide-panel { position: absolute; pointer-events: none; }
        input { outline: none; }
        input:focus { outline: none; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
        .fade-in { animation: fadeIn 0.5s ease-out; }
        @keyframes float { 0%,100% { transform:translateY(0); } 50% { transform:translateY(-12px); } }
        .float-anim { animation: float 5s ease-in-out infinite; }
        @keyframes spin { to { transform:rotate(360deg); } }
        @media (max-width:768px) {
            .auth-split { flex-direction:column !important; }
            .auth-left { flex: none !important; height:45vh !important; padding:1.5rem 1.5rem 1rem !important; }
            .auth-right { flex: none !important; height:55vh !important; padding:1.5rem !important; overflow-y:auto !important; }
            .auth-left-hero h1 { font-size:1.6rem !important; }
            .auth-left-hero p { font-size:0.8rem !important; }
            .auth-desk-only { display:none !important; }
            .auth-right-inner { max-width:100% !important; }
        }
    </style>
</head>
<body>
    <div class="auth-split" style="display:flex; min-height:100vh; width:100%; position:relative;">

        {{-- ====== LEFT: WELCOME PANEL ====== --}}
        <div class="auth-left" style="flex:1; display:flex; flex-direction:column; justify-content:space-between; padding:3rem 4rem; position:relative; overflow:hidden; background:#1a2e1a;">
            {{-- Background texture --}}
            <div style="position:absolute; inset:0; background-image:url('/images/login-bg.png'); background-size:cover; background-position:center; opacity:0.25;"></div>
            <div style="position:absolute; inset:0; background:linear-gradient(160deg, #1a2e1a 0%, rgba(26,46,26,0.4) 40%, rgba(45,90,39,0.15) 100%);"></div>

            {{-- Decorative elements --}}
            <div style="position:absolute; top:-80px; right:-80px; width:300px; height:300px; border-radius:50%; background:radial-gradient(circle, rgba(58,125,68,0.12) 0%, transparent 70%);"></div>
            <div style="position:absolute; bottom:-60px; left:-60px; width:250px; height:250px; border-radius:50%; background:radial-gradient(circle, rgba(58,125,68,0.08) 0%, transparent 70%);"></div>

            {{-- Top: Logo --}}
            <div style="position:relative; z-index:1; display:flex; align-items:center; gap:10px;">
                <div style="width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,#2D5A27,#3A7D44); display:flex; align-items:center; justify-content:center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8a13 13 0 0 1-10 10Z"/>
                    </svg>
                </div>
                <span style="font-size:1.35rem; font-weight:800; color:white; letter-spacing:-0.02em;">HarvestHaul</span>
            </div>

            {{-- Center: Hero content --}}
            <div class="auth-left-hero" style="position:relative; z-index:1; max-width:480px;">
                <div style="display:inline-block; padding:4px 14px; border-radius:100px; background:rgba(58,125,68,0.15); border:1px solid rgba(58,125,68,0.2); margin-bottom:20px;">
                    <span style="font-size:11px; font-weight:700; color:#3A7D44; letter-spacing:0.04em; text-transform:uppercase;">Mindanao Agri-Logistics</span>
                </div>
                <h1 style="font-size:2.5rem; font-weight:800; color:white; line-height:1.15; letter-spacing:-0.03em; margin-bottom:16px; font-family:'Instrument Serif',sans-serif;">
                    Bridge the<br>
                    <span style="color:#C8A415;">Supply Chain</span>
                </h1>
                <p style="font-size:0.9rem; color:rgba(255,255,255,0.6); line-height:1.7; font-weight:500; max-width:400px;">
                    Connect growers with haulers and buyers across Southern Mindanao. 
                    Route shipments, split costs, and move harvests to market — faster.
                </p>

                {{-- Feature bullets --}}
                <div style="margin-top:28px; display:flex; flex-direction:column; gap:12px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2D5A27" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <span style="font-size:13px; color:rgba(255,255,255,0.65); font-weight:500;">Post & discover harvest listings</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2D5A27" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <span style="font-size:13px; color:rgba(255,255,255,0.65); font-weight:500;">Pool logistics & share fleet resources</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2D5A27" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <span style="font-size:13px; color:rgba(255,255,255,0.65); font-weight:500;">Negotiate & close deals in real-time</span>
                    </div>
                </div>
            </div>

            {{-- Bottom: footer --}}
            <div class="auth-desk-only" style="position:relative; z-index:1; display:flex; justify-content:space-between; align-items:center; font-size:12px; color:rgba(255,255,255,0.3); font-weight:500;">
                <span>&copy; 2026 HarvestHaul</span>
                <div style="display:flex; gap:20px;">
                    <a href="{{ route('legal.terms') }}" style="color:rgba(255,255,255,0.3); text-decoration:none; transition:color 0.2s; font-weight:600;">Terms</a>
                    <a href="{{ route('legal.privacy') }}" style="color:rgba(255,255,255,0.3); text-decoration:none; transition:color 0.2s; font-weight:600;">Privacy</a>
                </div>
            </div>

            {{-- Decorative plant icon --}}
            <div class="float-anim" style="position:absolute; right:60px; bottom:100px; z-index:1; opacity:0.12;">
                <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 10a4 4 0 00-4-4H4v4a4 4 0 004 4h4z"/><path d="M12 10a4 4 0 014-4h4v4a4 4 0 01-4 4h-4z"/><path d="M12 14v7"/><path d="M10 3l2 2 2-2"/>
                </svg>
            </div>
        </div>

        {{-- ====== RIGHT: AUTH PANELS CONTAINER ====== --}}
        <div class="auth-right" style="flex:1; display:flex; align-items:center; justify-content:center; padding:3rem; background:#FAFAF5; position:relative; overflow-y:auto;">

            {{-- Panel wrapper for slide transitions --}}
            <div class="auth-right-inner" style="position:relative; width:100%; max-width:420px;">

                {{-- ====== LOGIN FORM ====== --}}
                <div id="login-panel" class="panel-slide" style="width:100%; transform:translateX(0); opacity:1;">



                    <div style="margin-bottom:28px;">
                        <h2 style="font-size:1.6rem; font-weight:800; color:#1a1a1a; letter-spacing:-0.02em; line-height:1.2;">Welcome,</h2>
                        <p style="font-size:13px; color:#8a8a8a; font-weight:500; margin-top:6px;">Log in to manage your operations</p>
                    </div>

                    @if ($errors->any() && !old('role'))
                        <div role="alert" style="margin-bottom:20px; padding:12px 16px; border-radius:12px; background:rgba(239,68,68,0.06); border:1px solid rgba(239,68,68,0.12);">
                            <div style="display:flex; align-items:flex-start; gap:10px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-top:1px; flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                <ul style="list-style:none; padding:0; margin:0; font-size:12px; color:#dc2626; font-weight:600;">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div style="margin-bottom:18px;">
                            <label style="display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#8a8a8a; margin-bottom:6px;">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus autocomplete="email"
                                style="width:100%; padding:12px 16px; border-radius:12px; border:1.5px solid #e2e0dc; background:white; font-size:14px; color:#1a1a1a; transition:all 0.2s; font-family:inherit;"
                                onfocus="this.style.borderColor='#3A7D44'; this.style.boxShadow='0 0 0 4px rgba(58,125,68,0.08)'"
                                onblur="this.style.borderColor='#e2e0dc'; this.style.boxShadow='none'">
                        </div>

                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#8a8a8a; margin-bottom:6px;">Password</label>
                            <div style="position:relative;">
                                <input type="password" id="login-password" name="password" placeholder="Enter your password" required autocomplete="new-password"
                                    style="width:100%; padding:12px 44px 12px 16px; border-radius:12px; border:1.5px solid #e2e0dc; background:white; font-size:14px; color:#1a1a1a; transition:all 0.2s; font-family:inherit;"
                                    onfocus="this.style.borderColor='#3A7D44'; this.style.boxShadow='0 0 0 4px rgba(58,125,68,0.08)'"
                                    onblur="this.style.borderColor='#e2e0dc'; this.style.boxShadow='none'">
                                <button type="button" onclick="toggleLoginPassword()" aria-label="Toggle password visibility" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#b0b0b0; padding:4px; display:flex;">
                                    <svg id="login-eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <svg id="login-eye-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:12px; color:#6a6a6a; font-weight:600;">
                                <input type="checkbox" name="remember" style="width:16px; height:16px; border-radius:4px; border:1.5px solid #d4d2ce; accent-color:#3A7D44; cursor:pointer;">
                                Remember me
                            </label>
                            <a href="{{ route('password.request') }}" style="font-size:12px; color:#3A7D44; font-weight:600; text-decoration:none; transition:color 0.2s;"
                               onmouseover="this.style.color='#2E6336'" onmouseout="this.style.color='#3A7D44'">Forgot password?</a>
                        </div>

                        <button type="submit" id="login-submit" style="width:100%; padding:13px; border:none; border-radius:12px; background:linear-gradient(135deg,#2D5A27,#3A7D44); color:white; font-size:14px; font-weight:700; cursor:pointer; transition:all 0.2s; font-family:inherit; box-shadow:0 4px 12px rgba(45,90,39,0.15);"
                            onmouseover="this.style.boxShadow='0 6px 20px rgba(45,90,39,0.25)'; this.style.transform='translateY(-1px)'"
                            onmouseout="this.style.boxShadow='0 4px 12px rgba(45,90,39,0.15)'; this.style.transform='none'"
                            onmousedown="this.style.transform='translateY(0)'">
                            Sign In
                        </button>
                    </form>

                    <div style="margin-top:24px; padding-top:20px; border-top:1px solid #ece9e4; text-align:center;">
                        <span style="font-size:13px; color:#7a7a7a; font-weight:500;">
                            Don't have an account?
                            <a href="#" onclick="event.preventDefault(); showRegister();" style="color:#3A7D44; font-weight:700; text-decoration:none; margin-left:4px; transition:color 0.2s;"
                               onmouseover="this.style.color='#2E6336'" onmouseout="this.style.color='#3A7D44'">Create account</a>
                        </span>
                    </div>
                    <div style="margin-top:14px; text-align:center;">
                        <a href="/" style="font-size:12px; color:#a0a0a0; text-decoration:none; font-weight:600; transition:color 0.2s;"
                           onmouseover="this.style.color='#3A7D44'" onmouseout="this.style.color='#a0a0a0'">
                            &larr; Back to homepage
                        </a>
                    </div>
                </div>

                {{-- ====== REGISTER PANEL (slides in from right) ====== --}}
                <div id="register-panel" class="panel-slide" style="position:absolute; top:0; left:0; right:0; transform:translateX(100%); opacity:0; pointer-events:none;">

                    <div style="margin-bottom:22px; display:flex; align-items:center; gap:12px;">
                        <button onclick="showLogin()" style="background:none; border:none; cursor:pointer; color:#8a8a8a; padding:4px; display:flex; transition:color 0.2s;"
                            onmouseover="this.style.color='#3A7D44'" onmouseout="this.style.color='#8a8a8a'">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                        </button>
                        <div>
                            <h2 style="font-size:1.2rem; font-weight:800; color:#1a1a1a; letter-spacing:-0.02em;">Create your account</h2>
                            <p style="font-size:12px; color:#8a8a8a; font-weight:500; margin-top:1px;">Fill in your details to get started</p>
                        </div>
                    </div>

                    @if ($errors->any() && old('role'))
                        <div role="alert" style="margin-bottom:16px; padding:12px 16px; border-radius:12px; background:rgba(239,68,68,0.06); border:1px solid rgba(239,68,68,0.12);">
                            <div style="display:flex; align-items:flex-start; gap:10px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" style="margin-top:1px;flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                <ul style="list-style:none; padding:0; margin:0; font-size:12px; color:#dc2626; font-weight:600;">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register.store') }}" onsubmit="return validateRegisterForm()">
                        @csrf
                        <input type="hidden" name="role" id="register-role-input" value="">

                        <div style="margin-bottom:16px; position:relative;">
                            <label style="display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#8a8a8a; margin-bottom:5px;">I am a</label>
                            <select id="register-role-select" required onchange="document.getElementById('register-role-input').value=this.value"
                                style="width:100%; padding:11px 14px; border-radius:10px; border:1.5px solid #e2e0dc; background:white; font-size:13px; color:#1a1a1a; transition:all 0.2s; font-family:inherit; appearance:none; cursor:pointer;"
                                onfocus="this.style.borderColor='#3A7D44'; this.style.boxShadow='0 0 0 4px rgba(58,125,68,0.08)'"
                                onblur="this.style.borderColor='#e2e0dc'; this.style.boxShadow='none'">
                                <option value="" disabled {{ old('role') ? '' : 'selected' }}>Select your role</option>
                                <option value="farmer" {{ old('role') === 'farmer' ? 'selected' : '' }}>Farmer</option>
                                <option value="logistics_partner" {{ old('role') === 'logistics_partner' ? 'selected' : '' }}>Logistics Coordinator</option>
                                <option value="buyer" {{ old('role') === 'buyer' ? 'selected' : '' }}>Commercial Buyer</option>
                            </select>
                            <div style="position:absolute; right:14px; top:38px; pointer-events:none;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#b0b0b0" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                            </div>
                        </div>

                        <div style="margin-bottom:14px;">
                            <label style="display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#8a8a8a; margin-bottom:5px;">Full Name</label>
                            <input type="text" name="name" placeholder="Juan Dela Cruz" required value="{{ old('name') }}" autocomplete="name"
                                style="width:100%; padding:11px 14px; border-radius:10px; border:1.5px solid #e2e0dc; background:white; font-size:13px; color:#1a1a1a; transition:all 0.2s; font-family:inherit;"
                                onfocus="this.style.borderColor='#3A7D44'; this.style.boxShadow='0 0 0 4px rgba(58,125,68,0.08)'"
                                onblur="this.style.borderColor='#e2e0dc'; this.style.boxShadow='none'">
                        </div>

                        <div style="margin-bottom:14px;">
                            <label style="display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#8a8a8a; margin-bottom:5px;">Email</label>
                            <input type="email" name="email" placeholder="you@example.com" required value="{{ old('email') }}" autocomplete="email"
                                style="width:100%; padding:11px 14px; border-radius:10px; border:1.5px solid #e2e0dc; background:white; font-size:13px; color:#1a1a1a; transition:all 0.2s; font-family:inherit;"
                                onfocus="this.style.borderColor='#3A7D44'; this.style.boxShadow='0 0 0 4px rgba(58,125,68,0.08)'"
                                onblur="this.style.borderColor='#e2e0dc'; this.style.boxShadow='none'">
                        </div>

                        <div style="margin-bottom:14px;">
                            <label style="display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#8a8a8a; margin-bottom:5px;">Phone Number</label>
                            <input type="text" name="phone" placeholder="09XX XXX XXXX" required value="{{ old('phone') }}" autocomplete="tel"
                                style="width:100%; padding:11px 14px; border-radius:10px; border:1.5px solid #e2e0dc; background:white; font-size:13px; color:#1a1a1a; transition:all 0.2s; font-family:inherit;"
                                onfocus="this.style.borderColor='#3A7D44'; this.style.boxShadow='0 0 0 4px rgba(58,125,68,0.08)'"
                                onblur="this.style.borderColor='#e2e0dc'; this.style.boxShadow='none'">
                        </div>

                        <div style="margin-bottom:14px;">
                            <label style="display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#8a8a8a; margin-bottom:5px;">Password</label>
                            <div style="position:relative;">
                                <input type="password" id="reg-password" name="password" placeholder="Min. 8 characters" required autocomplete="new-password"
                                    style="width:100%; padding:11px 40px 11px 14px; border-radius:10px; border:1.5px solid #e2e0dc; background:white; font-size:13px; color:#1a1a1a; transition:all 0.2s; font-family:inherit;"
                                    onfocus="this.style.borderColor='#3A7D44'; this.style.boxShadow='0 0 0 4px rgba(58,125,68,0.08)'"
                                    onblur="this.style.borderColor='#e2e0dc'; this.style.boxShadow='none'">
                                <button type="button" onclick="toggleRegPassword('reg-password','reg-eye-1')" aria-label="Toggle password visibility" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#b0b0b0; padding:4px; display:flex;">
                                    <svg id="reg-eye-1" xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                        </div>

                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#8a8a8a; margin-bottom:5px;">Confirm Password</label>
                            <div style="position:relative;">
                                <input type="password" id="reg-password-confirm" name="password_confirmation" placeholder="Repeat password" required autocomplete="new-password"
                                    style="width:100%; padding:11px 40px 11px 14px; border-radius:10px; border:1.5px solid #e2e0dc; background:white; font-size:13px; color:#1a1a1a; transition:all 0.2s; font-family:inherit;"
                                    onfocus="this.style.borderColor='#3A7D44'; this.style.boxShadow='0 0 0 4px rgba(58,125,68,0.08)'"
                                    onblur="this.style.borderColor='#e2e0dc'; this.style.boxShadow='none'">
                                <button type="button" onclick="toggleRegPassword('reg-password-confirm','reg-eye-2')" aria-label="Toggle password visibility" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#b0b0b0; padding:4px; display:flex;">
                                    <svg id="reg-eye-2" xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                        </div>

                        <div style="margin-bottom:16px;">
                            <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer; padding:10px 14px; border-radius:10px; background:#f5f4f1; border:1px solid #e8e5e0;">
                                <input type="checkbox" name="accepted_terms" value="1" {{ old('accepted_terms') ? 'checked' : '' }}
                                    style="margin-top:2px; width:15px; height:15px; border-radius:3px; accent-color:#3A7D44; cursor:pointer; flex-shrink:0;">
                                <span style="font-size:11px; color:#6a6a6a; line-height:1.5; font-weight:500;">
                                    I agree to the
                                    <a href="javascript:void(0)" onclick="openLegalModal('{{ route('legal.terms') }}')" style="color:#3A7D44; font-weight:700; text-decoration:underline;">Terms</a>
                                    and
                                    <a href="javascript:void(0)" onclick="openLegalModal('{{ route('legal.privacy') }}')" style="color:#3A7D44; font-weight:700; text-decoration:underline;">Privacy Policy</a>.
                                </span>
                            </label>
                        </div>

                        <button type="submit" style="width:100%; padding:12px; border:none; border-radius:10px; background:linear-gradient(135deg,#2D5A27,#3A7D44); color:white; font-size:13px; font-weight:700; cursor:pointer; transition:all 0.2s; font-family:inherit; box-shadow:0 4px 12px rgba(45,90,39,0.15);"
                            onmouseover="this.style.boxShadow='0 6px 20px rgba(45,90,39,0.25)'; this.style.transform='translateY(-1px)'"
                            onmouseout="this.style.boxShadow='0 4px 12px rgba(45,90,39,0.15)'; this.style.transform='none'"
                            onmousedown="this.style.transform='translateY(0)'">
                            Create Account
                        </button>
                    </form>

                    <div style="margin-top:22px; padding-top:18px; border-top:1px solid #ece9e4; text-align:center;">
                        <span style="font-size:13px; color:#7a7a7a; font-weight:500;">
                            Already have an account?
                            <a href="#" onclick="event.preventDefault(); showLogin();" style="color:#3A7D44; font-weight:700; text-decoration:none; margin-left:4px; transition:color 0.2s;"
                               onmouseover="this.style.color='#2E6336'" onmouseout="this.style.color='#3A7D44'">Sign in</a>
                        </span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Legal Modal --}}
    <div id="legal-modal-overlay" style="position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.6);display:none;align-items:center;justify-content:center;padding:1rem;backdrop-filter:blur(4px);" onclick="if(event.target===this)closeLegalModal()">
        <div onclick="event.stopPropagation()" style="background:#fff;border-radius:1.5rem;max-width:640px;width:100%;max-height:80vh;overflow-y:auto;position:relative;box-shadow:0 25px 50px -12px rgba(0,0,0,0.3);">
            <div style="position:sticky;top:0;z-index:10;background:linear-gradient(135deg,#2D5A27,#3A7D44);border-radius:1.5rem 1.5rem 0 0;padding:1.25rem 2rem 1rem;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <span style="font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:rgba(255,255,255,0.7);">HarvestHaul</span>
                        <div id="legal-modal-title" style="font-size:1.1rem;font-weight:700;color:#fff;margin-top:0.15rem;">Loading...</div>
                    </div>
                    <button onclick="closeLegalModal()" style="width:30px;height:30px;display:flex;align-items:center;justify-content:center;border-radius:50%;border:none;background:rgba(255,255,255,0.2);color:#fff;cursor:pointer;font-size:1.1rem;transition:all 0.15s;font-family:inherit;backdrop-filter:blur(4px);" onmouseover="this.style.background='rgba(255,255,255,0.35)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">&times;</button>
                </div>
            </div>
            <div style="padding:1.5rem 2rem 2rem;">
                <div id="legal-modal-body" style="font-family:'DM Sans',sans-serif;font-size:0.9rem;line-height:1.7;color:#374151;text-align:justify;"></div>
            </div>
        </div>
    </div>

    <script>
        // Loading states
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const btn = this.querySelector('button[type="submit"]');
                if (btn) {
                    btn.classList.add('btn-loading');
                    btn.disabled = true;
                }
            });
        });

        function toggleLoginPassword() {
            const pwd = document.getElementById('login-password');
            const open = document.getElementById('login-eye-open');
            const closed = document.getElementById('login-eye-closed');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                open.style.display = 'none';
                closed.style.display = 'block';
            } else {
                pwd.type = 'password';
                open.style.display = 'block';
                closed.style.display = 'none';
            }
        }

        function showRegister() {
            const login = document.getElementById('login-panel');
            const register = document.getElementById('register-panel');

            register.style.transform = 'translateX(0)';
            register.style.opacity = '1';
            register.style.pointerEvents = 'auto';

            login.style.transform = 'translateX(-40px)';
            login.style.opacity = '0';
            login.style.pointerEvents = 'none';
        }

        function showLogin() {
            const login = document.getElementById('login-panel');
            const register = document.getElementById('register-panel');

            register.style.transform = 'translateX(100%)';
            register.style.opacity = '0';
            register.style.pointerEvents = 'none';

            login.style.transform = 'translateX(0)';
            login.style.opacity = '1';
            login.style.pointerEvents = 'auto';
        }

        function toggleRegPassword(fieldId, iconId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(iconId);
            if (field.type === 'password') {
                field.type = 'text';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
            } else {
                field.type = 'password';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
            }
        }

        function validateRegisterForm() {
            const pwd = document.getElementById('reg-password').value;
            const confirm = document.getElementById('reg-password-confirm').value;
            if (pwd !== confirm) {
                alert('Passwords do not match.');
                return false;
            }
            return true;
        }

        function openLegalModal(url) {
            const overlay = document.getElementById('legal-modal-overlay');
            const body = document.getElementById('legal-modal-body');
            const title = document.getElementById('legal-modal-title');
            body.innerHTML = '<div style="text-align:center;padding:3rem 1rem;"><div style="width:32px;height:32px;border:3px solid #e5e7eb;border-top-color:#3A7D44;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 1rem;"></div><p style="color:#9ca3af;font-size:0.85rem;">Loading...</p></div>';
            title.textContent = 'HarvestHaul';
            overlay.style.display = 'flex';
            fetch(url).then(r => r.text()).then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                body.innerHTML = doc.querySelector('.container').innerHTML;
                const h1 = body.querySelector('h1');
                if (h1) title.textContent = h1.textContent;
            }).catch(() => {
                body.innerHTML = '<p style="color:#dc2626;padding:2rem;text-align:center;">Failed to load.</p>';
            });
        }
        function closeLegalModal() {
            document.getElementById('legal-modal-overlay').style.display = 'none';
        }

        @if ($errors->any() && old('role'))
        document.addEventListener('DOMContentLoaded', function() {
            showRegister();
        });
        @endif
    </script>
</body>
</html>
