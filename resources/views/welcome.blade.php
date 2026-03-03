<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>HarvestHaul</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] antialiased min-h-screen flex flex-col items-center p-6 lg:p-8">

        <header class="w-full lg:max-w-4xl flex justify-between items-center mb-12">
            <a href="/" class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="#2D8A37" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8a13 13 0 0 1-10 10Z"/><path d="M9 21s-4.5-3-4.5-7"/><path d="M7 20s-4-3.5-4-9"/>
                </svg>
                <span class="text-xl font-bold tracking-tight text-[#2D8A37]">HarvestHaul</span>
            </a>

            @if (Route::has('login'))
                <nav class="flex gap-4">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="px-5 py-2 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm font-medium hover:bg-gray-50 dark:hover:bg-[#161615] transition">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-5 py-2 text-sm font-medium hover:opacity-70 transition">
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="px-5 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm text-sm font-medium hover:opacity-90 transition">
                                Get Started
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </header>

        <main class="flex-1 flex flex-col items-center justify-center w-full lg:max-w-4xl">
            <div class="text-center">
                <h1 class="text-4xl lg:text-5xl font-semibold mb-4 tracking-tight">
                    Collaborative Logistics for Multi-Farm Success
                </h1>
                <p class="text-lg text-[#706f6c] dark:text-[#A1A09A] max-w-2xl mx-auto mb-8">
                    Resource pooling and marketplace logistics tailored for local agriculture.
                </p>

            </div>
        </main>

        <footer class="py-10 text-sm text-[#706f6c] dark:text-[#A1A09A]">
            &copy; {{ date('Y') }} HarvestHaul. All rights reserved.
        </footer>

    </body>
</html>
