<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Email Verified — HarvestHaul</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-lg border border-gray-200 p-10 text-center">

        <div class="text-6xl mb-4">✅</div>

        <h1 class="text-2xl font-bold text-gray-800 mb-2">Email Verified!</h1>
        <p class="text-gray-500 mb-6">
            Your account has been successfully verified. You may now close this tab.
        </p>

        <p id="countdown-msg" class="text-sm text-gray-400 mb-6">
            This tab will close in <span id="countdown" class="font-bold text-gray-600">5</span> seconds...
        </p>

        {{-- Fallback if browser blocks window.close() --}}
        <a id="fallback-btn"
           href="{{ route('dashboard') }}"
           class="hidden w-full inline-block bg-green-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-green-700 transition">
            Go to Dashboard →
        </a>
    </div>

    <script>
        let seconds = 5;
        const countdownEl = document.getElementById('countdown');
        const fallbackBtn = document.getElementById('fallback-btn');
        const countdownMsg = document.getElementById('countdown-msg');

        const timer = setInterval(() => {
            seconds--;
            countdownEl.textContent = seconds;

            if (seconds <= 0) {
                clearInterval(timer);
                window.close();

                // If window.close() was blocked by the browser, show fallback
                setTimeout(() => {
                    countdownMsg.classList.add('hidden');
                    fallbackBtn.classList.remove('hidden');
                    fallbackBtn.textContent = "Browser blocked auto-close. Go to Dashboard →";
                }, 500);
            }
        }, 1000);
    </script>
</body>
</html>
