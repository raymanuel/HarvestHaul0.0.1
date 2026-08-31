@props(['title' => 'HarvestHaul — Driver Portal', 'themeColor' => '#16283C'])

<!DOCTYPE html>
<html lang="en" class="overflow-x-hidden">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="theme-color" content="{{ $themeColor }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="HarvestHaul Driver Portal — Manage jobs, tracking, and fuel logs for crop deliveries in General Santos City.">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="32x32" href="/favicon-32x32.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="HarvestHaul">
    <link rel="manifest" href="/manifest.json">
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
    <title>{{ $title }}</title>

    <!-- Theme Initializer -->
    <script>
        (function() {
            var theme = localStorage.getItem('theme');
            var isDark = theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <!-- Fonts -->
    <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background: #F7F4EC;
            transition: background 0.3s ease, color 0.3s ease;
        }
        html.dark body {
            background: radial-gradient(circle at 50% 0%, #1a1d24 0%, #121815 100%);
        }
    </style>
    @stack('head')
</head>
<body class="text-slate-800 dark:text-slate-100 antialiased min-h-screen pb-12 overflow-x-hidden">
    <main>
        {{ $slot }}
    </main>

    <!-- Notification & Dark Mode Scripts -->
    <script>
        function toggleDarkMode() {
            var isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }
    </script>

    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('assets/js/swal-helpers.js') }}"></script>

    {{-- SweetAlert Global Flash Handler --}}
    <script>
        window.__nextSteps = @json(session('next_steps'));
        document.addEventListener('DOMContentLoaded', function() {
            showNextSteps();
            @if(session('error'))
                @php
                    $errorText = session('error');
                    $isGateNotice = str_contains($errorText, 'pending verification') || str_contains($errorText, 'No new data');
                @endphp
                Swal.fire({
                    icon: '{{ $isGateNotice ? 'info' : 'error' }}',
                    title: '{{ $isGateNotice ? 'Notice' : 'Error' }}',
                    text: @json($errorText),
                    timer: 4500,
                    timerProgressBar: true,
                    showConfirmButton: true,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '{{ $isGateNotice ? '#16283C' : '#ef4444' }}',
                    toast: false,
                    background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                    color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b',
                    customClass: { popup: 'rounded-xl shadow-lg' }
                });
            @endif
        });

        function swalConfirm(formOrCallback, opts = {}) {
            const defaults = {
                title: opts.title || 'Are you sure?',
                text: opts.text || 'This action cannot be undone.',
                icon: opts.icon || 'warning',
                confirmText: opts.confirmText || 'Yes, proceed',
                cancelText: opts.cancelText || 'Cancel',
                confirmColor: opts.confirmColor || '#16283C',
                cancelColor: opts.cancelColor || '#5A6573'
            };

            Swal.fire({
                title: defaults.title,
                text: defaults.text,
                icon: defaults.icon,
                showCancelButton: true,
                confirmButtonText: defaults.confirmText,
                cancelButtonText: defaults.cancelText,
                confirmButtonColor: defaults.confirmColor,
                cancelButtonColor: defaults.cancelColor,
                background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b',
                customClass: { popup: 'rounded-xl shadow-2xl' },
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    if (typeof formOrCallback === 'function') {
                        formOrCallback();
                    } else if (formOrCallback && formOrCallback.submit) {
                        formOrCallback.submit();
                    }
                }
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
