<!DOCTYPE html>
<html lang="en">
<head>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HarvestHaul</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />

    <style>
        body {
            margin: 0; padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #FAFAF5 0%, #F5F0E6 50%, #E8DCC8 100%);
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
            box-shadow: 0 25px 50px -12px rgba(58, 125, 68, 0.06);
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
            border: 1px solid rgba(58, 125, 68, 0.15);
            background: rgba(255, 255, 255, 0.85);
            font-size: 0.95rem;
            color: #1f2937;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        input:focus {
            outline: none;
            border-color: #3A7D44;
            box-shadow: 0 0 0 3px rgba(58, 125, 68, 0.1);
            background: #ffffff;
        }
        button[type="submit"] {
            width: 100%;
            padding: 0.9rem;
            background: #3A7D44;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(58, 125, 68, 0.2);
        }
        button[type="submit"]:hover {
            background: #2E6336;
            transform: translateY(-1px);
        }
        button[type="submit"]:active {
            transform: translateY(1px);
        }
        .heading-font { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body>
    <div class="glass-card">
        {{ $slot }}
    </div>
</body>
</html>