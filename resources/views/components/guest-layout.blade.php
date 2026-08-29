<!DOCTYPE html>
<html lang="en" class="overflow-x-hidden">
<head>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HarvestHaul — B2B crop distribution and logistics platform connecting farmers, buyers, logistics partners, and drivers in General Santos City and Polomolok.">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="32x32" href="/favicon-32x32.png">
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
    <title>HarvestHaul</title>
    <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">

    <style>
        body {
            margin: 0; padding: 0;
            background: linear-gradient(135deg, #FAFAFA 0%, #F5F5F5 50%, #EEEEEE 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 2rem 1rem;
        }
        .glass-card {
            width: 100%;
            max-width: 800px;
            padding: 3rem;
            border-radius: 1.5rem;
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
            border: 1px solid rgba(22, 40, 60, 0.15);
            background: rgba(255, 255, 255, 0.85);
            font-size: 0.95rem;
            color: #1f2937;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        input:focus {
            outline: none;
            border-color: #16283C;
            box-shadow: 0 0 0 3px rgba(22, 40, 60, 0.1);
            background: #ffffff;
        }
        button[type="submit"] {
            width: 100%;
            padding: 0.9rem;
            background: #16283C;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(22, 40, 60, 0.2);
        }
        button[type="submit"]:hover {
            background: #0E1620;
            transform: translateY(-1px);
        }
        button[type="submit"]:active {
            transform: translateY(1px);
        }
    </style>
</head>
<body class="overflow-x-hidden">
    <main>
        <div class="glass-card">
            {{ $slot }}
        </div>
    </main>
</body>
</html>