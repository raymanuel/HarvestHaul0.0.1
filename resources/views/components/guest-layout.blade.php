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
<<<<<<< HEAD
            background: linear-gradient(135deg, #F5F2EC 0%, #FAF6F0 50%, #EFF2E6 100%);
            min-height: 100vh;
=======
            background: linear-gradient(135deg, #f1f5f9 0%, #faf5ff 50%, #eff6ff 100%);
            min-height: 100vh; /* Changed from height to min-height for mobile scrolling */
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
            display: flex; align-items: center; justify-content: center;
            padding: 2rem 1rem;
        }
        .glass-card {
            width: 100%;
<<<<<<< HEAD
            max-width: 800px;
            padding: 3rem;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(16px);
            border-radius: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 25px 50px -12px rgba(45, 106, 47, 0.06);
=======
            max-width: 800px; /* Increased from 400px to allow side-by-side */
            padding: 3rem;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(12px);
            border-radius: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
            text-align: center;
        }
        form {
            max-width: 340px;
<<<<<<< HEAD
            margin: 0 auto;
=======
            margin: 0 auto; /* Centers the form inside the wide 800px card */
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
        }
        .form-group {
            margin-bottom: 1.25rem;
            text-align: left;
        }
        input {
            width: 100%;
            padding: 0.85rem 1rem;
            border-radius: 0.5rem;
<<<<<<< HEAD
            border: 1px solid rgba(45, 106, 47, 0.15);
            background: rgba(255, 255, 255, 0.85);
=======
            border: 1px solid rgba(0, 0, 0, 0.15);
            background: rgba(255, 255, 255, 0.8);
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
            font-size: 0.95rem;
            color: #1f2937;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        input:focus {
            outline: none;
<<<<<<< HEAD
            border-color: #2D6A2F;
            box-shadow: 0 0 0 3px rgba(45, 106, 47, 0.1);
=======
            border-color: #2D8A37;
            box-shadow: 0 0 0 3px rgba(45, 138, 55, 0.1);
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
            background: #ffffff;
        }
        button[type="submit"] {
            width: 100%;
            padding: 0.9rem;
<<<<<<< HEAD
            background: #2D6A2F;
=======
            background: #2D8A37;
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 0.5rem;
<<<<<<< HEAD
            box-shadow: 0 4px 6px -1px rgba(45, 106, 47, 0.2);
        }
        button[type="submit"]:hover {
            background: #245525;
=======
            box-shadow: 0 4px 6px -1px rgba(45, 138, 55, 0.2);
        }
        button[type="submit"]:hover {
            background: #246d2b;
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
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
