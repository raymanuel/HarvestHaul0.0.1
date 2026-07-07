<x-register-layout>
    <h2 style="font-size: 1.1rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem;">
        Verify your email
    </h2>
    <p style="font-size: 0.85rem; color: #6b7280; margin-bottom: 1.5rem; line-height: 1.6;">
        We sent a 6-digit code to your email. Enter it below to activate your account.
    </p>

    @if (session('status') == 'otp-sent')
        <div style="margin-bottom: 1rem; padding: 0.75rem; background: rgba(45,138,55,0.08);
                    border: 1px solid rgba(45,138,55,0.3); border-radius: 0.6rem;
                    font-size: 0.8rem; color: #2D8A37;">
            A new OTP code has been sent to your email.
        </div>
    @endif

    <form method="POST" action="{{ route('verification.verify-otp') }}">
        @csrf
        <div style="margin-bottom: 1.25rem;">
            <label for="otp" style="display: block; font-size: 0.8rem; font-weight: 600; color: #374151; margin-bottom: 0.4rem;">
                Enter 6-digit OTP Code
            </label>
            <input id="otp" type="text" name="otp" required maxlength="6" inputmode="numeric" autocomplete="one-time-code"
                   style="width: 100%; padding: 0.85rem 1rem; font-size: 1.5rem; font-weight: 700; letter-spacing: 0.5rem; text-align: center;
                          border: 2px solid #d1d5db; border-radius: 0.75rem; outline: none; transition: border-color 0.2s;
                          font-family: 'Courier New', monospace;"
                   onfocus="this.style.borderColor='#2D6A2F'" onblur="this.style.borderColor='#d1d5db'">
            @error('otp')
                <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.4rem;">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="primary-btn">Verify Email</button>
    </form>

    <form method="POST" action="{{ route('verification.resend-otp') }}" style="margin-top: 1.25rem;">
        @csrf
        <button type="submit"
            style="background: none; border: none; font-size: 0.8rem; color: #2D6A2F;
                   cursor: pointer; text-decoration: underline; font-weight: 600;">
            Resend OTP Code
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" style="margin-top: 0.75rem;">
        @csrf
        <button type="submit"
            style="background: none; border: none; font-size: 0.8rem; color: #9ca3af;
                   cursor: pointer; text-decoration: underline;">
            Log out
        </button>
    </form>
</x-register-layout>
