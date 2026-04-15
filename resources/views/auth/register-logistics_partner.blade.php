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
        {{-- Hidden role ensures the backend knows this is a Logistics Partner --}}
        <input type="hidden" name="role" value="logistics_partner">

        <div class="form-group">
            <input type="text" name="name" placeholder="Full Name / Representative Name" required value="{{ old('name') }}">
        </div>

        <div class="form-group">
            <input type="email" name="email" placeholder="Company Email Address" required value="{{ old('email') }}">
        </div>

        <div class="form-group">
            <input type="text" name="phone" placeholder="Contact Number" required value="{{ old('phone') }}">
        </div>

        <div class="form-group">
            <input type="text" name="company_name" placeholder="Registered Company Name" required value="{{ old('company_name') }}">
        </div>

        <div class="form-group">
            <input type="text" name="business_permit_no" placeholder="Business Permit No. (Optional)" value="{{ old('business_permit_no') }}">
        </div>

        <div class="form-group">
            <input type="password" name="password" placeholder="Password" required>
        </div>

        <div class="form-group">
            <input type="password" name="password_confirmation" placeholder="Confirm Password" required>
        </div>

        <button type="submit" class="primary-btn">
            Register as Logistics Coordinator
        </button>

        {{-- Footer Link for Role Switching --}}
        <div style="margin-top: 1.5rem; font-size: 0.85rem; color: #6b7280;">
            Not a logistics coordinator?
            <a href="{{ route('register.role', 'farmer') }}" style="color: #2D8A37; font-weight: 600; text-decoration: none;">
                Sign up as Farmer
            </a>
        </div>
    </form>
</x-register-layout>
