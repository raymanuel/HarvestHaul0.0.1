<x-guest-layout>
    <div class="max-w-md mx-auto mt-20 bg-white p-8 rounded-xl shadow border border-gray-200 text-center">
        <div class="text-5xl mb-4">📧</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Verify your email</h1>
        <p class="text-gray-500 mb-6">
            Thanks for registering! Before you continue, please verify your email
            address by clicking the link we just sent you.
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-4 text-sm text-green-600 bg-green-50 border border-green-200 rounded-lg p-3">
                A new verification link has been sent to your email.
            </div>
        @endif

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
    // Poll every 4 seconds to check if email has been verified
    const interval = setInterval(async () => {
        try {
            const response = await fetch('/verification-status');
            const data = await response.json();

            if (data.verified) {
                clearInterval(interval);
                window.close();
            }
        } catch (e) {
            // silent fail — just keep polling
        }
    }, 4000);
</script>
</x-guest-layout>
