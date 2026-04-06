<x-guest-layout>
    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold text-[#2D8A37] mb-2">Join HarvestHaul</h1>
        <p class="text-gray-500">Select your account type to continue</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <a href="{{ route('register.role', 'farmer') }}"
           class="flex flex-col items-center p-8 bg-green-50 border-2 border-green-200 rounded-2xl hover:border-green-500 hover:bg-green-100 transition-all duration-300 no-underline group">
            <span class="text-6xl mb-4 group-hover:scale-110 transition-transform">👨‍🌾</span>
            <span class="text-2xl font-bold text-green-700 mb-2">Farmer</span>
            <p class="text-sm text-gray-500 text-center">
                Post harvests, pool resources, and reach more buyers.
            </p>
        </a>

        <a href="{{ route('register.role', 'logistics_partner') }}"
           class="flex flex-col items-center p-8 bg-blue-50 border-2 border-blue-200 rounded-2xl hover:border-blue-500 hover:bg-blue-100 transition-all duration-300 no-underline group">
            <span class="text-6xl mb-4 group-hover:scale-110 transition-transform">🚛</span>
            <span class="text-2xl font-bold text-blue-700 mb-2">Partner</span>
            <p class="text-sm text-gray-500 text-center">
                Manage fleets, assign drivers, and optimize deliveries.
            </p>
        </a>

    </div>

    <div class="mt-8 text-center text-sm text-gray-500">
        Already have an account?
        <a href="{{ route('login') }}" class="text-[#2D8A37] font-semibold hover:underline">Log in here</a>
    </div>
</x-guest-layout>
