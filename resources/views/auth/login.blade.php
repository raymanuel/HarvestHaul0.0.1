<x-guest-layout>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center text-left w-full max-w-4xl mx-auto">
        
        <!-- Left Showcase Side (Visible on desktop) -->
        <div class="md:col-span-5 hidden md:flex flex-col justify-between h-[420px] rounded-2xl p-6 text-white relative overflow-hidden shadow-lg border border-[#2D6A2F]/10 bg-slate-950">
            <!-- Full-bleed background image -->
            <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-80" style="background-image: url('/images/login-bg.png');"></div>
            <!-- Scrim overlay -->
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/60 to-slate-950/80 pointer-events-none"></div>
            
            <div class="relative z-10 space-y-4">
                <a href="/" class="flex items-center gap-2.5 group">
                    <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center border border-white/15 group-hover:scale-105 transition-transform duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8a13 13 0 0 1-10 10Z"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight text-white heading-font">HarvestHaul</span>
                </a>
                
                <h2 class="text-2xl font-extrabold tracking-tight leading-snug pt-4">Mindanao Crop Routing Gateway</h2>
                <p class="text-xs text-[#EFF2E9]/90 leading-relaxed font-medium">
                    Bridge shipping lanes across Southern Mindanao. Sequence your pickup schedules, optimize regional delivery fleets, and share resource lanes from Tupi and Polomolok to General Santos.
                </p>
            </div>


        </div>

        <!-- Right Login Form Side -->
        <div class="col-span-1 md:col-span-7 w-full flex flex-col justify-center px-2">
            
            <!-- Header Text -->
            <div class="mb-6 text-center md:text-left">
                <!-- Mini Logo for Mobile Views -->
                <div class="flex md:hidden justify-center items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-[#2D6A2F] flex items-center justify-center text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8a13 13 0 0 1-10 10Z"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-[#2D6A2F] heading-font">HarvestHaul</span>
                </div>

                <h2 class="text-2xl font-extrabold tracking-tight text-slate-800">Welcome Back</h2>
                <p class="text-xs text-slate-450 mt-1 font-semibold">Log in to manage your cooperative shipments</p>
            </div>

            <!-- Validation Errors -->
            @if ($errors->any())
                <div class="mb-5 p-3.5 rounded-xl bg-amber-500/10 border border-amber-550/20 text-amber-800 text-xs">
                    <div class="flex items-start gap-2.5">
                        <span class="text-base leading-none">⚠️</span>
                        <ul class="space-y-1 list-none p-0 m-0">
                            @foreach ($errors->all() as $error)
                                <li class="font-semibold">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Main Credentials Form -->
            <form method="POST" action="{{ route('login') }}" class="w-full space-y-4">
                @csrf

                <!-- Email field -->
                <div class="form-group">
                    <label for="email" class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">Email Address</label>
                    <div class="relative">
                        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Enter your email address" required autofocus class="pl-4 pr-4 py-3 w-full bg-white/80 border border-slate-200 focus:border-[#2D6A2F] focus:ring-4 focus:ring-[#2D6A2F]/10 rounded-xl transition text-sm text-slate-800 placeholder-slate-450">
                    </div>
                </div>

                <!-- Password field with visibility toggle -->
                <div class="form-group">
                    <label for="password" class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required class="pl-4 pr-10 py-3 w-full bg-white/80 border border-slate-200 focus:border-[#2D6A2F] focus:ring-4 focus:ring-[#2D6A2F]/10 rounded-xl transition text-sm text-slate-800 placeholder-slate-455">
                        <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-450 hover:text-[#2D6A2F] focus:outline-none" aria-label="Toggle Password Visibility">
                            <!-- Open eye icon -->
                            <svg id="eye-open-icon" xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <!-- Closed eye icon -->
                            <svg id="eye-closed-icon" xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Remember Me checkbox and submit -->
                <div class="flex items-center justify-between text-xs py-1 select-none">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-650 font-semibold">
                        <input type="checkbox" name="remember" class="w-4.5 h-4.5 rounded border-slate-200 text-[#2D6A2F] focus:ring-[#2D6A2F]/20 cursor-pointer">
                        Remember session
                    </label>
                </div>

                <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-[#2D6A2F] to-[#5A8A3C] text-white font-bold rounded-xl shadow-md shadow-[#2D6A2F]/10 hover:shadow-lg hover:shadow-[#2D6A2F]/20 hover:brightness-105 active:scale-[0.98] transition duration-200">
                    Enter Portal
                </button>
            </form>

            <!-- Bottom Registration & Home links -->
            <div class="mt-6 pt-5 border-t border-slate-100 text-center md:text-left flex flex-col sm:flex-row justify-between gap-3 text-xs">
                <span class="text-slate-500 font-semibold">
                    New user? 
                    <a href="{{ route('register') }}" class="text-[#2D6A2F] hover:text-[#2D6A2F]/80 font-bold hover:underline">Create account</a>
                </span>
                <a href="/" class="text-slate-400 hover:text-slate-650 font-bold flex items-center justify-center gap-1">
                    ← Return to Homepage
                </a>
            </div>

        </div>

    </div>

    <!-- Password visibility toggle logic -->
    <script>
        function togglePasswordVisibility() {
            var passwordInput = document.getElementById('password');
            var openIcon = document.getElementById('eye-open-icon');
            var closedIcon = document.getElementById('eye-closed-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                openIcon.classList.add('hidden');
                closedIcon.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                openIcon.classList.remove('hidden');
                closedIcon.classList.add('hidden');
            }
        }
    </script>

</x-guest-layout>
