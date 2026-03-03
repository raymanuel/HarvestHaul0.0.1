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
            <div class="text-xl font-bold tracking-tight text-[#f53003]">
                HarvestHaul
            </div>

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

                <div class="flex flex-wrap justify-center gap-4">
                    <a href="{{ route('register') }}" class="px-8 py-3 bg-[#f53003] text-white font-medium rounded-sm shadow-sm hover:bg-[#d42a02] transition">
                        Join the Marketplace
                    </a>
                    <a href="#about" class="px-8 py-3 border border-[#19140035] dark:border-[#3E3E3A] font-medium rounded-sm hover:bg-gray-50 dark:hover:bg-[#161615] transition">
                        Learn More
                    </a>
                </div>
            </div>
        </main>

        <footer class="py-10 text-sm text-[#706f6c] dark:text-[#A1A09A]">
            &copy; {{ date('Y') }} HarvestHaul. All rights reserved.
        </footer>

    </body>
</html>
