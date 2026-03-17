<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HarvestHaul</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <style>
        body {
            margin: 0; padding: 0;
            font-family: 'Figtree', sans-serif;
            background: linear-gradient(135deg, #f1f5f9 0%, #faf5ff 50%, #eff6ff 100%);
            height: 100vh;
            display: flex; align-items: center; justify-content: center;
        }
        .glass-card {
            width: 100%; max-width: 400px; padding: 3rem;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(12px); border-radius: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .brand-title { color: #2D8A37; font-size: 1.8rem; font-weight: 700; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.2rem; text-align: left; }
        input {
            width: 100%; padding: 0.9rem; border-radius: 0.75rem;
            border: 1px solid rgba(0,0,0,0.1); box-sizing: border-box;
            font-size: 1rem;
        }
        button {
            width: 100%; padding: 0.9rem; background: #111827;
            color: white; border: none; border-radius: 0.75rem;
            font-weight: 600; cursor: pointer; transition: background 0.2s;
        }
        button:hover { background: #000; }
    </style>
</head>

<body>
    <div class="glass-card">
        {{-- <div class="brand-title">HarvestHaul</div> --}}
        {{ $slot }}
    </div>
</body>
</html>
