<!DOCTYPE html>
<html lang="en" class="overflow-x-hidden">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
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
    <title>Email Verified — HarvestHaul</title>
    <link rel="stylesheet" href="/fonts/fonts.css" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
            background: #F5F6F2;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .card {
            width: 100%;
            max-width: 400px;
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.1);
            padding: 3rem 2.5rem;
            text-align: center;
        }

        .icon {
            font-size: 3rem;
            margin-bottom: 1.25rem;
            display: block;
        }

        h1 {
            font-size: 1.4rem;
            font-weight: 800;
            font-family: 'Schibsted Grotesk', 'DM Sans', sans-serif;
            color: #17202B;
            margin-bottom: 0.5rem;
        }

        .sub {
            font-size: 0.875rem;
            color: #5A6573;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .progress-track {
            width: 100%;
            height: 4px;
            background: rgba(0,0,0,0.06);
            border-radius: 9999px;
            overflow: hidden;
            margin-bottom: 0.75rem;
        }

        .progress-bar {
            height: 100%;
            width: 100%;
            background: #16283C;
            border-radius: 9999px;
            transform-origin: left;
            animation: drain 5s linear forwards;
        }

        @keyframes drain {
            from { transform: scaleX(1); }
            to   { transform: scaleX(0); }
        }

        .countdown-msg {
            font-size: 0.78rem;
            color: #5A6573;
            margin-bottom: 1.5rem;
        }

        .countdown-msg span {
            font-weight: 700;
            color: #17202B;
        }

        .fallback-btn {
            display: none;
            width: 100%;
            padding: 0.875rem;
            background: #16283C;
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }

        .fallback-btn:hover { background: #0E1620; }
    </style>
</head>
<body class="overflow-x-hidden">
    <div class="card">
        <span class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#16283C" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10" stroke-width="2"></circle>
                <path d="m8.5 12.5 2.5 2.5 5-5.5"></path>
            </svg>
        </span>
        <h1>Email Verified</h1>
        <p class="sub">
            Your HarvestHaul account is now active.<br>
            Redirecting you to your dashboard shortly.
        </p>

        <div class="progress-track">
            <div class="progress-bar"></div>
        </div>

        <p class="countdown-msg">
            Redirecting in <span id="countdown">5</span> seconds...
        </p>

        <a id="fallback-btn" class="fallback-btn" href="{{ route('dashboard') }}">
            Go to Dashboard →
        </a>
    </div>

    <script>
        // Attempt to close the verify-email tab if it was the opener
        if (window.opener && !window.opener.closed) {
            window.opener.close();
        }

        let seconds = 5;
        const countdownEl = document.getElementById('countdown');
        const fallbackBtn = document.getElementById('fallback-btn');

        const timer = setInterval(() => {
            seconds--;
            countdownEl.textContent = seconds;

            if (seconds <= 0) {
                clearInterval(timer);
                window.location.href = "{{ route('dashboard') }}";

                // Surface fallback if redirect is blocked
                setTimeout(() => {
                    fallbackBtn.style.display = 'block';
                }, 800);
            }
        }, 1000);
    </script>
</body>
</html>
