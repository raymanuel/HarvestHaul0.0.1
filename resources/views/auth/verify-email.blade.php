<x-register-layout>
    <h2 style="font-size: 1.1rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem;">
        Check your email
    </h2>
    <p style="font-size: 0.85rem; color: #6b7280; margin-bottom: 1.5rem; line-height: 1.6;">
        We sent a verification link to your email address. Click it to activate your account.
        This window will close automatically once verified.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div style="margin-bottom: 1rem; padding: 0.75rem; background: rgba(45,138,55,0.08);
                    border: 1px solid rgba(45,138,55,0.3); border-radius: 0.6rem;
                    font-size: 0.8rem; color: #2D8A37;">
            A new verification link has been sent to your email.
        </div>
    @endif

    <p id="waiting-msg" style="font-size: 0.8rem; color: #9ca3af; font-style: italic; margin-bottom: 1.25rem;">
        ⏳ Waiting for verification...
    </p>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="primary-btn">Resend Verification Email</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" style="margin-top: 1rem;">
        @csrf
        <button type="submit"
            style="background: none; border: none; font-size: 0.8rem; color: #9ca3af;
                   cursor: pointer; text-decoration: underline;">
            Log out
        </button>
    </form>

    <script>
        const interval = setInterval(async () => {
            try {
                const res  = await fetch('/verification-status');
                const data = await res.json();

                if (data.verified) {
                    clearInterval(interval);
                    document.getElementById('waiting-msg').textContent = '✓ Verified! Closing window...';
                    window.close();
                    
                    // Fallback if browser blocks window.close()
                    setTimeout(() => {
                        window.location.href = "{{ route('dashboard') }}";
                    }, 1500);
                }
            } catch (e) {
                // silent fail — keep polling
            }
        }, 4000);
    </script>
</x-register-layout>
