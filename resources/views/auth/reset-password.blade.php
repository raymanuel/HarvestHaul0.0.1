<!DOCTYPE html>
<html lang="en" class="overflow-x-hidden">
<head>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preload" as="image" href="/images/login-bg.webp">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#16283C">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').then(function(reg) {
                    console.log('Service Worker registered: ', reg.scope);
                }).catch(function(err) {
                    console.error('Service Worker registration failed: ', err);
                });
            });
        }
    </script>
    <title>HarvestHaul — Reset Password</title>
    <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        html, body { height:100%; font-family:'DM Sans',sans-serif; }
        input { outline: none; }
        input:focus { outline: none; }
    </style>
</head>
<body class="overflow-x-hidden">
    <div class="auth-split" style="display:flex; min-height:100vh; width:100%;">

        {{-- Left Panel --}}
        <div class="auth-left" style="flex:1; display:flex; flex-direction:column; justify-content:space-between; padding:3rem 4rem; position:relative; overflow:hidden; background:#0E1620;">
            <div style="position:absolute; inset:0; background-image:url('/images/login-bg.webp'); background-size:cover; background-position:center; opacity:0.25;"></div>
            <div style="position:absolute; inset:0; background:linear-gradient(160deg, #0E1620 0%, rgba(14, 22, 32,0.4) 40%, rgba(22, 40, 60,0.15) 100%);"></div>

            <div style="position:relative; z-index:1; display:flex; align-items:center; gap:10px;">
                <div style="width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,#16283C,#0E1620); display:flex; align-items:center; justify-content:center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 22 16 8"/>
                        <path d="M3.47 12.53 5 11l1.53 1.53a3.5 3.5 0 0 1 0 4.94L5 19l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/>
                        <path d="M7.47 8.53 9 7l1.53 1.53a3.5 3.5 0 0 1 0 4.94L9 15l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/>
                        <path d="M11.47 4.53 13 3l1.53 1.53a3.5 3.5 0 0 1 0 4.94L13 11l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/>
                        <path d="M20 2h2v2a4 4 0 0 1-4 4h-2V6a4 4 0 0 1 4-4Z"/>
                        <path d="M11.47 17.47 13 19l-1.53 1.53a3.5 3.5 0 0 1-4.94 0L5 19l1.53-1.53a3.5 3.5 0 0 1 4.94 0Z"/>
                    </svg>
                </div>
                <span style="font-size:1.35rem; font-weight:800; color:white; letter-spacing:-0.02em;">HarvestHaul</span>
            </div>

            <div style="position:relative; z-index:1; max-width:480px;">
                <h1 style="font-size:2.5rem; font-weight:800; color:white; line-height:1.15; letter-spacing:-0.03em; margin-bottom:16px; font-family:'Schibsted Grotesk',sans-serif;">
                    Create New<br>
                    <span style="color:#F26B5E;">Password</span>
                </h1>
                <p style="font-size:0.9rem; color:rgba(255,255,255,0.6); line-height:1.7; font-weight:500; max-width:400px;">
                    Choose a strong password for your account.
                </p>
            </div>

            <div style="position:relative; z-index:1; display:flex; justify-content:space-between; align-items:center; font-size:12px; color:rgba(255,255,255,0.3); font-weight:500;">
                <span>&copy; 2026 HarvestHaul</span>
                <div style="display:flex; gap:20px;">
                    <a href="{{ route('legal.terms') }}" style="color:rgba(255,255,255,0.3); text-decoration:none;">Terms</a>
                    <a href="{{ route('legal.privacy') }}" style="color:rgba(255,255,255,0.3); text-decoration:none;">Privacy</a>
                </div>
            </div>
        </div>

        {{-- Right Panel --}}
        <div style="flex:1; display:flex; align-items:center; justify-content:center; padding:3rem; background:#FAFAFA;">
            <div style="width:100%; max-width:420px;">

                <div style="margin-bottom:28px;">
                    <h2 style="font-size:1.6rem; font-weight:800; color:#1a1a1a; letter-spacing:-0.02em; line-height:1.2;">Reset Password</h2>
                    <p style="font-size:13px; color:#64748b; font-weight:500; margin-top:6px;">Enter your new password below</p>
                </div>

                @if ($errors->any())
                    <div style="margin-bottom:20px; padding:12px 16px; border-radius:12px; background:rgba(239,68,68,0.06); border:1px solid rgba(239,68,68,0.12);">
                        <ul style="list-style:none; padding:0; margin:0; font-size:12px; color:#dc2626; font-weight:600;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div style="margin-bottom:18px;">
                        <label for="email" style="display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#64748b; margin-bottom:6px;">Email</label>
                        <input type="email" name="email" id="email" value="{{ $email ?? old('email') }}" required readonly
                            style="width:100%; padding:12px 16px; border-radius:12px; border:1.5px solid #e5e7eb; background:#f5f4f1; font-size:14px; color:#6a6a6a; font-family:inherit; cursor:not-allowed;">
                    </div>

                    <div style="margin-bottom:18px;">
                        <label for="password" style="display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#64748b; margin-bottom:6px;">New Password</label>
                        <input type="password" name="password" id="password" placeholder="Min. 8 characters" required autocomplete="new-password"
                            style="width:100%; padding:12px 16px; border-radius:12px; border:1.5px solid #e5e7eb; background:white; font-size:14px; color:#1a1a1a; transition:all 0.2s; font-family:inherit;"
                            onfocus="this.style.borderColor='#16283C'; this.style.boxShadow='0 0 0 4px rgba(22, 40, 60,0.08)'"
                            onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                    </div>

                    <div style="margin-bottom:20px;">
                        <label for="password_confirmation" style="display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#64748b; margin-bottom:6px;">Confirm Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Repeat password" required autocomplete="new-password"
                            style="width:100%; padding:12px 16px; border-radius:12px; border:1.5px solid #e5e7eb; background:white; font-size:14px; color:#1a1a1a; transition:all 0.2s; font-family:inherit;"
                            onfocus="this.style.borderColor='#16283C'; this.style.boxShadow='0 0 0 4px rgba(22, 40, 60,0.08)'"
                            onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                    </div>

                    <button type="submit" style="width:100%; padding:13px; border:none; border-radius:12px; background:linear-gradient(135deg,#16283C,#0E1620); color:white; font-size:14px; font-weight:700; cursor:pointer; transition:all 0.2s; font-family:inherit; box-shadow:0 4px 12px rgba(22, 40, 60,0.15);"
                        onmouseover="this.style.boxShadow='0 6px 20px rgba(22, 40, 60,0.25)'; this.style.transform='translateY(-1px)'"
                        onmouseout="this.style.boxShadow='0 4px 12px rgba(22, 40, 60,0.15)'; this.style.transform='none'"
                        onmousedown="this.style.transform='translateY(0)'">
                        Reset Password
                    </button>
                </form>

                <div style="margin-top:24px; text-align:center;">
                    <a href="{{ route('login') }}" style="font-size:13px; color:#16283C; font-weight:700; text-decoration:none;">
                        &larr; Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
