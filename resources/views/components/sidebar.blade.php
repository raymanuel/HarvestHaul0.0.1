@php
    $role = Auth::user()->role;
    $nav = config('navigation');
    $items = $nav[$role]['items'] ?? [];
    $sectionLabel = $nav[$role]['section_label'] ?? null;
@endphp

<nav class="flex-1 px-2.5 py-4 overflow-y-auto custom-scroll space-y-1.5" aria-label="Sidebar navigation">

    {{-- Dashboard --}}
    <a href="/dashboard" data-tooltip="Dashboard" aria-label="Dashboard" @if(request()->is('dashboard'))aria-current="page"@endif class="nav-link flex items-center px-3 py-2 rounded-lg text-sm font-semibold transition {{ request()->is('dashboard') ? 'nav-active' : '' }}">
        <span class="nav-letter shrink-0 w-7 h-7 rounded-md bg-slate-900/5 text-slate-500 dark:bg-white/10 dark:text-white/70 text-[11px] font-bold flex items-center justify-center uppercase">D</span>
        <span class="nav-label">Dashboard</span>
    </a>

    @if($sectionLabel)
        <p class="section-label mt-5 text-[10px] font-bold text-slate-400 dark:text-white/60 uppercase tracking-widest px-4">{{ $sectionLabel }}</p>
    @endif

    @foreach($items as $item)
        @php
            $mobileOnly = $item['mobile_only'] ?? false;
            $hasChildren = !empty($item['children']);
            $parentRoute = $item['route'];
            $parentUrl = $item['route_url'] ?? null;
            $isActive = request()->routeIs($parentRoute);

            $activeClass = $isActive ? 'nav-active' : '';

            $childRoutes = $hasChildren ? collect($item['children'])->pluck('route')->implode('|') : null;
            $isSubmenuActive = $hasChildren ? request()->routeIs($childRoutes) : false;
            $showItem = true;
        @endphp

        @if($hasChildren)
            @if($showItem)
            {{-- Parent with children (submenu toggle) --}}
            <button type="button" data-submenu-toggle data-tooltip="{{ $item['tooltip'] }}" aria-label="{{ $item['label'] }}" aria-expanded="{{ $isSubmenuActive ? 'true' : 'false' }}" class="nav-link w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold transition select-none {{ $isSubmenuActive ? ' nav-active' : '' }}">
                    <div class="flex items-center gap-3">
                        <span class="nav-letter shrink-0 w-7 h-7 rounded-md bg-slate-900/5 text-slate-500 dark:bg-white/10 dark:text-white/70 text-[11px] font-bold flex items-center justify-center uppercase">{{ $item['letter'] }}</span>
                        <span class="nav-label">{{ $item['label'] }}</span>
                    </div>
                    <span class="nav-label">
                        <svg data-submenu-chevron xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 transform transition-transform {{ $isSubmenuActive ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </span>
                </button>

                <div data-submenu-panel role="group" class="submenu-panel {{ $isSubmenuActive ? 'submenu-open' : '' }} pl-3">
                    <div class="space-y-1 overflow-hidden">
                    @foreach($item['children'] as $child)
                        @php
                            $childCondition = $child['condition'] ?? null;
                            $childIsActive = request()->routeIs($child['route']);
                            $childActiveClass = $childIsActive ? 'nav-active' : '';
                        @endphp

                        @if($childCondition === 'farmer_non_cooperative')
                            @if(Auth::user()->farmerProfile?->affiliation_type !== 'cooperative')
                                <a href="{{ route($child['route_url']) }}" data-tooltip="{{ $child['tooltip'] }}" aria-label="{{ $child['label'] }}" @if($childIsActive)aria-current="page"@endif class="nav-link flex items-center px-3 py-2 rounded-lg text-sm font-semibold transition {{ $childActiveClass }}">
                                    <span class="nav-letter shrink-0 w-7 h-7 rounded-md bg-slate-900/5 text-slate-500 dark:bg-white/10 dark:text-white/70 text-[11px] font-bold flex items-center justify-center uppercase">{{ $child['letter'] }}</span>
                                    <span class="nav-label">{{ $child['label'] }}</span>
                                </a>
                            @endif
                        @else
                            <a href="{{ route($child['route_url']) }}" data-tooltip="{{ $child['tooltip'] }}" aria-label="{{ $child['label'] }}" @if($childIsActive)aria-current="page"@endif class="nav-link flex items-center px-3 py-2 rounded-lg text-sm font-semibold transition {{ $childActiveClass }}">
                                <span class="nav-letter shrink-0 w-7 h-7 rounded-md bg-slate-900/5 text-slate-500 dark:bg-white/10 dark:text-white/70 text-[11px] font-bold flex items-center justify-center uppercase">{{ $child['letter'] }}</span>
                                <span class="nav-label">{{ $child['label'] }}</span>
                            </a>
                        @endif
                    @endforeach
                    </div>
                </div>
            @endif
        @else
            {{-- Simple link --}}
            @if($showItem)
                <a href="{{ route($parentUrl) }}" data-tooltip="{{ $item['tooltip'] }}" aria-label="{{ $item['label'] }}" @if($isActive)aria-current="page"@endif class="nav-link {{ $mobileOnly ? 'lg:hidden ' : '' }}flex items-center px-3 py-2 rounded-lg text-sm font-semibold transition {{ $activeClass }}">
                    <span class="nav-letter shrink-0 w-7 h-7 rounded-md bg-slate-900/5 text-slate-500 dark:bg-white/10 dark:text-white/70 text-[11px] font-bold flex items-center justify-center uppercase">{{ $item['letter'] }}</span>
                    <span class="nav-label">{{ $item['label'] }}</span>
                </a>
            @endif
        @endif
    @endforeach

</nav>
