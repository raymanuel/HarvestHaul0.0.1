@props(['title' => 'HarvestHaul — Driver Portal', 'themeColor' => '#3A7D44'])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="theme-color" content="{{ $themeColor }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        slate: {
                            950: '#020617',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at 50% 0%, #FAFAF5 0%, #F0EFE8 100%);
            transition: background 0.3s ease, color 0.3s ease;
        }
        html.dark body {
            background: radial-gradient(circle at 50% 0%, #111318 0%, #0a0c10 100%);
        }
        .heading-font {
            font-family: 'Outfit', sans-serif;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(0, 0, 0, 0.06);
            transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }
        html.dark .glass-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
    @stack('head')
</head>
<body class="text-slate-800 dark:text-slate-100 antialiased min-h-screen pb-12">

    {{ $slot }}

    <!-- Notification & Dark Mode Scripts -->
    <script>
        function toggleDarkMode() {
            var isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }
    </script>

    {{-- SweetAlert Global Flash Handler --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: @json(session('success')),
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                    background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                    color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b',
                    iconColor: '#3A7D44',
                    customClass: { popup: 'rounded-xl shadow-lg border border-[#3A7D44]/20' }
                });
            @endif
            @if(session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: @json(session('error')),
                    timer: 4500,
                    timerProgressBar: true,
                    showConfirmButton: true,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ef4444',
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
                confirmColor: opts.confirmColor || '#3A7D44',
                cancelColor: opts.cancelColor || '#64748b'
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
