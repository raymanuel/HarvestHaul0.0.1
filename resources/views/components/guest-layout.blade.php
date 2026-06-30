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
            background: linear-gradient(135deg, #F5F2EC 0%, #FAF6F0 50%, #EFF2E6 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 2rem 1rem;
        }
        .glass-card {
            width: 100%;
            max-width: 800px;
            padding: 3rem;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(16px);
            border-radius: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 25px 50px -12px rgba(45, 106, 47, 0.06);
            text-align: center;
        }
        form {
            max-width: 340px;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 1.25rem;
            text-align: left;
        }
        input {
            width: 100%;
            padding: 0.85rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid rgba(45, 106, 47, 0.15);
            background: rgba(255, 255, 255, 0.85);
            font-size: 0.95rem;
            color: #1f2937;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        input:focus {
            outline: none;
            border-color: #2D6A2F;
            box-shadow: 0 0 0 3px rgba(45, 106, 47, 0.1);
            background: #ffffff;
        }
        button[type="submit"] {
            width: 100%;
            padding: 0.9rem;
            background: #2D6A2F;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(45, 106, 47, 0.2);
        }
        button[type="submit"]:hover {
            background: #245525;
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
