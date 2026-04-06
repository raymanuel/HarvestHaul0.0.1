<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'HarvestHaul' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Figtree', sans-serif;
            /* Light, modern gradient background */
            background: linear-gradient(135deg, #f1f5f9 0%, #faf5ff 50%, #eff6ff 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .glass-card {
            width: 100%;
            max-width: 400px;
            padding: 2.5rem; /* Slightly reduced padding for a tighter look */
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        /* Standardized styles for the elements inside the slot */
        .brand-title {
            color: #2D8A37;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
            text-align: left;
        }

        /* Customizing input style for the glass aesthetic */
        input {
            width: 100%;
            padding: 0.8rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(0,0,0,0.08);
            box-sizing: border-box;
            font-size: 0.95rem;
            background: rgba(255, 255, 255, 0.8);
            transition: all 0.2s;
        }

        input:focus {
            outline: none;
            border-color: #2D8A37;
            box-shadow: 0 0 0 3px rgba(45, 138, 55, 0.1);
        }

        button.primary-btn {
            width: 100%;
            padding: 0.9rem;
            background: #111827;
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }

        button.primary-btn:hover {
            background: #000;
        }

        button.primary-btn:active {
            transform: scale(0.98);
        }
    </style>
</head>

<body>
    <div class="glass-card">
        {{-- Brand Title is part of the layout for consistency --}}
        <div class="brand-title">HarvestHaul</div>

        {{-- The specific form fields from Farmer or Logistics will appear here --}}
        {{ $slot }}
    </div>
</body>
</html>
