@props([
    'type' => 'farmer',
    'title' => '',
    'description' => '',
])

@if($type === 'farmer')
    <div class="mb-8 bg-gradient-to-r from-harvest-50 via-harvest-50/50 to-transparent border border-harvest/20 rounded-3xl p-6 shadow-sm flex items-start gap-4 relative overflow-hidden group">
        <div class="absolute right-0 top-0 w-32 h-32 bg-harvest/5 rounded-full blur-2xl group-hover:scale-150 transition-all duration-700"></div>
        <div class="w-12 h-12 rounded-2xl bg-harvest-100 border border-harvest/30 flex items-center justify-center text-harvest shrink-0 shadow-inner select-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div>
            <h3 class="text-base font-bold text-harvest-dark dark:text-harvest heading-font">{{ $title }}</h3>
            <p class="text-xs text-harvest-dark/90 dark:text-harvest/90 mt-1.5 leading-relaxed max-w-3xl font-medium">{{ $description }}</p>
        </div>
    </div>
@elseif($type === 'logistics')
    <div class="mb-8 bg-gradient-to-r from-amber-500/10 via-amber-600/5 to-transparent border border-amber-500/20 rounded-3xl p-6 shadow-sm flex items-start gap-4 relative overflow-hidden group">
        <div class="absolute right-0 top-0 w-32 h-32 bg-amber-500/5 rounded-full blur-2xl group-hover:scale-150 transition-all duration-700"></div>
        <div class="w-12 h-12 rounded-2xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0 shadow-inner select-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div>
            <h3 class="text-base font-bold text-amber-800 dark:text-amber-300 heading-font">{{ $title }}</h3>
            <p class="text-xs text-amber-700/95 dark:text-amber-400/90 mt-1.5 leading-relaxed max-w-3xl font-medium">{{ $description }}</p>
        </div>
    </div>
@endif
