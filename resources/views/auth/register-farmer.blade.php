<x-register-layout>
    @if ($errors->any())
    <div class="mb-4 p-3 bg-red-500/20 border border-red-500/50 rounded-lg">
        <ul class="text-xs text-red-400 list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    <form action="{{ route('register.store') }}" method="POST">
        @csrf
        {{-- Hidden role to tell the backend this is a Farmer --}}
        <input type="hidden" name="role" value="farmer">

        <div class="form-group">
            <input type="text" name="name" placeholder="Full Name" required value="{{ old('name') }}">
        </div>

        <div class="form-group">
            <input type="email" name="email" placeholder="Email Address" required value="{{ old('email') }}">
        </div>

        <div class="form-group">
            <input type="text" name="phone" placeholder="Phone Number" required value="{{ old('phone') }}">
        </div>

        <div class="form-group">
            <input type="password" name="password" placeholder="Password" required>
        </div>

        <div class="form-group">
            <input type="password" name="password_confirmation" placeholder="Confirm Password" required>
        </div>

        <div class="form-group">
            <input type="text" name="farm_location" placeholder="Farm Location (City/Municipality)" required value="{{ old('farm_location') }}">
        </div>

        <button type="submit" class="primary-btn">
            Register as Farmer
        </button>

        {{-- Footer Link for Role Switching --}}
        <div style="margin-top: 1.5rem; font-size: 0.85rem; color: #6b7280;">
            Not a farmer?
            <a href="{{ route('register.role', 'logistics_partner') }}" style="color: #2D8A37; font-weight: 600; text-decoration: none;">
                Sign up as Partner
            </a>
        </div>
    </form>
</x-register-layout>
