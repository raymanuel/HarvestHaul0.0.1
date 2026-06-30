<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Email Verified — HarvestHaul</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Figtree', sans-serif;
            background: linear-gradient(135deg, #f1f5f9 0%, #faf5ff 50%, #eff6ff 100%);
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
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .sub {
            font-size: 0.875rem;
            color: #6b7280;
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
            background: #2D8A37;
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
            color: #9ca3af;
            margin-bottom: 1.5rem;
        }

        .countdown-msg span {
            font-weight: 700;
            color: #374151;
        }

        .fallback-btn {
            display: none;
            width: 100%;
            padding: 0.875rem;
            background: #111827;
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }

        .fallback-btn:hover { background: #000; }
    </style>
</head>
<body>
    <div class="card">
        <span class="icon">✅</span>
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
