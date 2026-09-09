<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="32x32" href="/favicon-32x32.png">
    <title>{{ $title ?? 'HarvestHaul Portal — Coordinated Agribusiness' }}</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#16283C">
    <script>/* service worker registration */</script>
    <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">
    {{-- Vite assets will be injected by layout --}}
</head>
