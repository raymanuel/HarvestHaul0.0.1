@props(['maxWidth' => '420px'])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'HarvestHaul' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    {{-- Leaflet or other page-specific head injections --}}
    @stack('head')

    <style>
        body {
            margin: 0;
            padding: 2rem 1rem;
            font-family: 'Figtree', sans-serif;
            background: linear-gradient(135deg, #F5F2EC 0%, #FAF6F0 50%, #EFF2E6 100%);
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
            box-shadow: 0 25px 50px -12px rgba(45, 106, 47, 0.06);
            text-align: center;
        }

        .brand-title {
            color: #2D6A2F;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
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
            border: 1px solid rgba(45, 106, 47, 0.15);
            box-sizing: border-box;
            font-size: 0.95rem;
            background: rgba(255, 255, 255, 0.85);
            transition: all 0.2s;
        }

        input:focus {
            outline: none;
            border-color: #2D6A2F;
            box-shadow: 0 0 0 3px rgba(45, 106, 47, 0.1);
        }

        button.primary-btn {
            width: 100%;
            padding: 0.9rem;
            background: #2D6A2F;
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }

        button.primary-btn:hover { background: #245525; }
        button.primary-btn:active { transform: scale(0.98); }
    </style>
</head>

<body>
    <div class="glass-card">
        <div class="brand-title">HarvestHaul</div>
        {{ $slot }}
    </div>

    @stack('scripts')
</body>
</html>
