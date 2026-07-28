@props(['maxWidth' => '420px'])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'HarvestHaul' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet" />

    {{-- Leaflet or other page-specific head injections --}}
    @stack('head')

    <style>
        body {
            margin: 0;
            padding: 2rem 1rem;
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, #FAFAF5 0%, #F5F0E6 50%, #E8DCC8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .glass-card {
            width: 100%;
            max-width: {{ $maxWidth }};
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 25px 50px -12px rgba(58, 125, 68, 0.06);
            text-align: center;
        }

        .brand-title {
            color: #3A7D44;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            font-family: 'Instrument Serif', sans-serif;
        }

        .form-group {
            margin-bottom: 1rem;
            text-align: left;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"] {
            width: 100%;
            padding: 0.8rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(58, 125, 68, 0.15);
            box-sizing: border-box;
            font-size: 0.95rem;
            background: rgba(255, 255, 255, 0.85);
            transition: all 0.2s;
        }

        input:focus {
            outline: none;
            border-color: #3A7D44;
            box-shadow: 0 0 0 3px rgba(58, 125, 68, 0.1);
        }

        button.primary-btn {
            width: 100%;
            padding: 0.9rem;
            background: #3A7D44;
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }

        button.primary-btn:hover { background: #2E6336; }
        button.primary-btn:active { transform: scale(0.98); }
        @keyframes spin { to { transform: rotate(360deg); } }
        #legal-modal-overlay > div::-webkit-scrollbar { width: 6px; }
        #legal-modal-overlay > div::-webkit-scrollbar-track { background: transparent; }
        #legal-modal-overlay > div::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 9999px; }
        #legal-modal-overlay > div::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
        #legal-modal-body h1 { font-size:1.4rem; font-weight:700; color:#111827; margin:0 0 1.25rem 0; padding:0 0 0.75rem 0; border-bottom:2px solid rgba(58,125,68,0.08); line-height:1.3; }
        #legal-modal-body h2 { font-size:1rem; font-weight:600; color:#1f2937; margin:1.5rem 0 0.5rem 0; padding:0; line-height:1.4; }
        #legal-modal-body p { margin:0 0 1rem 0; padding:0; }
        #legal-modal-body ul { margin:0.25rem 0 1.25rem 1.25rem; padding:0; }
        #legal-modal-body li { margin-bottom:0.35rem; padding:0; }
        #legal-modal-body .updated { font-size:0.8rem; color:#9ca3af; margin-bottom:1.5rem; display:block; }
        #legal-modal-body .back { display:none; }
        #legal-modal-body strong { color:#111827; font-weight:600; }
        .heading-font { font-family: 'Instrument Serif', sans-serif; }
        .btn-loading { pointer-events:none; opacity:0.7; }
        .btn-loading::after { content:''; display:inline-block; width:16px; height:16px; border:2px solid rgba(255,255,255,0.3); border-top-color:#fff; border-radius:50%; animation:spin 0.6s linear infinite; margin-left:8px; vertical-align:middle; }
    </style>
</head>

<body>
    <div class="glass-card">
        <div class="brand-title">HarvestHaul</div>
        {{ $slot }}
    </div>

    @stack('scripts')
    <script>
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const btn = this.querySelector('button[type="submit"]');
                if (btn) { btn.classList.add('btn-loading'); btn.disabled = true; }
            });
        });
    </script>
</body>
</html>