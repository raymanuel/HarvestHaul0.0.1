<x-guest-layout>
    <div class="max-w-md mx-auto mt-20 bg-white p-8 rounded-xl shadow border border-gray-200 text-center">
        <div class="text-5xl mb-4">📧</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Verify your email</h1>
        <p class="text-gray-500 mb-6">
            Thanks for registering! Please verify your email address by clicking the link we just sent you.
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-4 text-sm text-green-600 bg-green-50 border border-green-200 rounded-lg p-3">
                A new verification link has been sent to your email.
            </div>
        @endif

        {{-- Shown after verification is detected --}}
        <a id="dashboard-btn"
           href="{{ route('dashboard') }}"
           class="hidden w-full inline-block bg-[#2D8A37] text-white font-bold py-2 px-4 rounded-lg hover:bg-opacity-90 transition mb-4">
            Go to Dashboard →
        </a>

        <p id="waiting-msg" class="text-sm text-gray-400 mb-6 italic">
            Waiting for verification...
        </p>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit"
                class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 transition">
                Resend Verification Email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-4">
            @csrf
            <button type="submit" class="text-sm text-gray-400 hover:text-gray-600 underline">
                Log out
            </button>
        </form>
    </div>

    <script>

        const dashboardBtn = document.getElementById('dashboard-btn');
        const waitingMsg   = document.getElementById('waiting-msg');

        // Track whether the user was already verified when the page first loaded
        let wasVerifiedOnLoad = false;

        // Check status on page load first
        fetch('/verification-status')
        .then(r => r.json())
        .then(data => {
            if (data.verified) {
                // Already verified before clicking anything — redirect silently
                wasVerifiedOnLoad = true;
                window.location.href = "{{ route('dashboard') }}";
            }
        });

    // Poll every 4 seconds
    const interval = setInterval(async () => {
        try {
            const response = await fetch('/verification-status');
            const data = await response.json();

            if (data.verified && !wasVerifiedOnLoad) {
                // Verified AFTER this page loaded — they clicked the link
                clearInterval(interval);
                waitingMsg.classList.add('hidden');
                dashboardBtn.classList.remove('hidden');
            }
            } catch (e) {
                // silent fail
            }
        }, 4000);

    </script>
</x-guest-layout>
