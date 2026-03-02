<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('Login & Registration', 'Laravel App')</title>
<style>
    body {
        margin: 0;
        padding: 0;
        font-family: 'figtree', ui-sans-serif, system-ui, sans-serif;
        /* Subtle atmospheric gradient background */
        background: linear-gradient(135deg, #f1f5f9 0%, #faf5ff 50%, #eff6ff 100%);
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login-container {
        width: 100%;
        max-width: 400px;
        padding: 2rem;
        /* Glass effect for the card */
        background: rgba(255, 255, 255, 0.4);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 1rem;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
    }

    .form-group {
        margin-bottom: 1rem;
    }

    input {
        width: 100%;
        padding: 0.8rem 1rem;
        border: none;
        border-radius: 0.5rem;
        background-color: white;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        font-size: 1rem;
        outline: none;
        transition: all 0.2s ease;
        box-sizing: border-box; /* Ensures padding doesn't affect width */
    }

    input:focus {
        box-shadow: 0 0 0 3px rgba(192, 132, 252, 0.3); /* Soft purple glow */
    }

    input::placeholder {
        color: #9ca3af;
    }

    button {
        width: 100%;
        padding: 0.8rem;
        background-color: #111827; /* Near black */
        color: white;
        border: none;
        border-radius: 0.5rem;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s ease, transform 0.1s ease;
        margin-top: 0.5rem;
    }

    button:hover {
        background-color: #000000;
    }
    button:active {
        transform: scale(0.98);
    }

    * This targets ONLY the logout button inside its container */
    .logout-button-container button {
        width: auto;             /* Prevents the button from stretching */
        min-width: 120px;       /* Gives it a nice consistent small width */
        padding: 8px 20px;      /* Smaller padding for a "shorter" look */
        font-size: 14px;
        background-color: #111827;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: opacity 0.2s;
    }

    .logout-button-container {
        display: flex;
        justify-content: center; /* Centers the button horizontally */
        width: 100%;
        margin-top: 20px;
    }

    /* Optional: remove the margin from the very last group so the button spacing is tight */
    .form-group:last-of-type {
        margin-bottom: 20px;
    }
</style>

</head>
<body class="bg-linear-to-tr from-slate-100 via-purple-50 to-blue-100 min-h-screen flex items-center justify-center p-6">

    <main>
        {{ $slot }}
    </main>

</body>
</html>
