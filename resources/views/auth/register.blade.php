<x-guest-layout>
    <div class="flex flex-col items-center justify-center min-h-screen">

        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold text-[#2D8A37]">HarvestHaul</h1>
            <p class="text-gray-500 text-sm">Create your professional account</p>
        </div>


            <form action="{{ route('register') }}" method="POST">
                @csrf

                <div class="mb-5">
                    <label class="block text-xs uppercase tracking-widest text-gray-400 mb-2 font-semibold">User Type</label>
                    <select name="role" id="roleSelect" class="w-full p-3 bg-black/40 border border-white/10 rounded-lg focus:ring-2 focus:ring-[#2D8A37] outline-none transition" required>
                        <option value="" disabled selected>Who are you?</option>
                        <option value="farmer">Farmer (Producer)</option>
                        <option value="logistics_partner">Logistics Partner</option>
                    </select>
                </div>

                <div id="farmerFields" class="hidden mb-5 animate-in fade-in duration-300">
                    <label class="block text-xs uppercase tracking-widest text-green-500 mb-2 font-semibold">RSBSA Number</label>
                    <input type="text" name="rsbsa_number" placeholder="00-00-00-000-000000" class="w-full p-3 bg-black/20 border border-green-500/30 rounded-lg">
                </div>

                <div class="mb-5">
                    <input type="text" name="name" placeholder="Full Name" class="w-full p-3 bg-black/20 border border-white/10 rounded-lg" required>
                </div>

                <div class="mb-5">
                    <input type="email" name="email" placeholder="Email Address" class="w-full p-3 bg-black/20 border border-white/10 rounded-lg" required>
                </div>

                <div class="mb-6">
                    <input type="password" name="password" placeholder="Password" class="w-full p-3 bg-black/20 border border-white/10 rounded-lg" required>
                </div>

                <button type="submit" class="w-full py-4 bg-[#2D8A37] hover:bg-[#246e2c] text-white font-bold rounded-xl shadow-lg shadow-green-900/40 transition transform active:scale-95">
                    Complete Registration
                </button>
            </form>


        <p class="mt-8 text-sm text-gray-500">
            Already have an account? <a href="{{ route('login') }}" class="text-[#2D8A37] hover:underline">Log in here</a>
        </p>
    </div>

    <script>
        document.getElementById('roleSelect').addEventListener('change', function() {
            const isFarmer = this.value === 'farmer';
            document.getElementById('farmerFields').classList.toggle('hidden', !isFarmer);
            // Add Partner toggles here if needed later
        });
    </script>
</x-guest-layout>
