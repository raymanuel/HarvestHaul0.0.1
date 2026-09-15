<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Delivery confirmed. Thank you for your HarvestHaul order.">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <meta name="theme-color" content="#16283C">
    <title>Delivery Confirmed — HarvestHaul</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #F5F6F2 0%, #EFEADB 50%, #E7E1CF 100%);
        }
    </style>
</head>
<body class="antialiased text-slate-800">

    <main class="w-full max-w-xl mx-auto px-4 py-12">
        <div class="bg-white border border-slate-200/70 rounded-2xl shadow-sm p-8 text-center">
            <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl mx-auto mb-5">✓</div>
            <p class="text-xs font-bold uppercase tracking-widest text-slate-500">HarvestHaul · Delivery</p>
            <h1 class="text-2xl font-bold text-[#16283C] tracking-tight heading-font mt-2">
                Thank you, delivery confirmed
            </h1>
            @if (session('success'))
                <p class="text-sm text-emerald-700 font-semibold mt-3">
                    {{ session('success') }}
                </p>
            @else
                <p class="text-sm text-slate-500 font-medium mt-3 leading-relaxed">
                    Your delivery has been confirmed and the order is now complete. The truck is released for its next run.
                </p>
            @endif
            <p class="text-xs text-slate-400 mt-6">
                Questions about this delivery? Contact the team that sent you this link.
            </p>
        </div>
    </main>

</body>
</html>