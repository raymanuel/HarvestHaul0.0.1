<!DOCTYPE html>
<html lang="en">
<head>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
            min-height: 100vh; /* Changed from height to min-height for mobile scrolling */
            display: flex; align-items: center; justify-content: center;
            padding: 2rem 1rem;
        }
        .glass-card {
            width: 100%;
            max-width: 800px; /* Increased from 400px to allow side-by-side */
            padding: 3rem;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(12px);
            border-radius: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        form {
            max-width: 340px;
            margin: 0 auto; /* Centers the form inside the wide 800px card */
        }
        .form-group {
            margin-bottom: 1.25rem;
            text-align: left;
        }
        input {
            width: 100%;
            padding: 0.85rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid rgba(0, 0, 0, 0.15);
            background: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
            color: #1f2937;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        input:focus {
            outline: none;
            border-color: #2D8A37;
            box-shadow: 0 0 0 3px rgba(45, 138, 55, 0.1);
            background: #ffffff;
        }
        button[type="submit"] {
            width: 100%;
            padding: 0.9rem;
            background: #2D8A37;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(45, 138, 55, 0.2);
        }
        button[type="submit"]:hover {
            background: #246d2b;
            transform: translateY(-1px);
        }
        button[type="submit"]:active {
            transform: translateY(1px);
        }
    </style>
</head>
<body>
    <div class="glass-card">
        {{ $slot }}
    </div>
</body>
</html>
