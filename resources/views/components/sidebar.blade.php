@php
    $role = Auth::user()->role;
    $nav = config('navigation');
    $items = [];
    $sectionLabel = null;

    if ($role === 'buyer') {
        $items = $nav['buyer']['items'] ?? [];
        $sectionLabel = $nav['buyer']['section_label'] ?? null;
    } elseif (isset($nav[$role])) {
        $items = $nav[$role]['items'] ?? [];
        $sectionLabel = $nav[$role]['section_label'] ?? null;
    }
@endphp

<nav class="flex-1 px-3 py-6 overflow-y-auto custom-scroll space-y-7">

    {{-- Dashboard --}}
    <div class="space-y-1.5">
        <a href="/dashboard" data-tooltip="Dashboard" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->is('dashboard') ? 'nav-active' : 'text-slate-600 hover:text-accent-600 hover:bg-slate-900/5 dark:text-slate-300 dark:hover:text-accent-light dark:hover:bg-white/10' }}">
            <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-900/5 text-slate-500 dark:bg-white/10 dark:text-white/70 text-xs font-bold flex items-center justify-center uppercase">D</span>
            <span class="nav-label">Dashboard</span>
        </a>
    </div>

    @if($sectionLabel)
        <p class="section-label text-[10px] font-bold text-slate-400 dark:text-white/60 uppercase tracking-widest px-4">{{ $sectionLabel }}</p>
    @endif

    @foreach($items as $item)
        @php
            $mobileOnly = $item['mobile_only'] ?? false;
            $hasChildren = !empty($item['children']);
            $parentRoute = $item['route'];
            $parentUrl = $item['route_url'] ?? null;
            $isActive = request()->routeIs($parentRoute);

            $activeClass = $isActive ? 'nav-active' : 'text-slate-600 hover:text-accent-600 hover:bg-slate-900/5 dark:text-slate-300 dark:hover:text-accent-light dark:hover:bg-white/10';

            $childRoutes = $hasChildren ? collect($item['children'])->pluck('route')->implode('|') : null;
            $isSubmenuActive = $hasChildren ? request()->routeIs($childRoutes) : false;

            $itemCondition = $item['condition'] ?? null;
            $showItem = true;

            if ($itemCondition === 'farmer_join_coop') {
                $fp = Auth::user()->farmerProfile;
                $showItem = Auth::user()->role === 'farmer'
                    && ($fp?->isIndependent() || !$fp?->cooperative_id || $fp?->membership_status !== 'approved');
            } elseif ($itemCondition === 'cooperative_only') {
                $showItem = Auth::user()->role === 'logistics_partner' && Auth::user()->logisticsProfile?->isCooperative();
            } elseif ($itemCondition === 'independent_logistics_only') {
                $showItem = Auth::user()->role === 'logistics_partner' && (!Auth::user()->logisticsProfile?->isCooperative());
            }
        @endphp

        @if($hasChildren)
            @if($showItem)
            {{-- Parent with children (submenu toggle) --}}
            <div class="space-y-1.5">
                <button type="button" data-submenu-toggle data-tooltip="{{ $item['tooltip'] }}" class="nav-link w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-semibold transition text-slate-600 hover:text-accent-600 hover:bg-slate-900/5 dark:text-slate-300 dark:hover:text-accent-light dark:hover:bg-white/10 select-none {{ $isSubmenuActive ? ' nav-active' : '' }}">
                    <div class="flex items-center gap-3">
                        <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-900/5 text-slate-500 dark:bg-white/10 dark:text-white/70 text-xs font-bold flex items-center justify-center uppercase">{{ $item['letter'] }}</span>
                        <span class="nav-label">{{ $item['label'] }}</span>
                    </div>
                    <span class="nav-label">
                        <svg data-submenu-chevron xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transform transition-transform {{ $isSubmenuActive ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </span>
                </button>

                <div data-submenu-panel class="{{ $isSubmenuActive ? '' : 'hidden' }} mt-1 pl-4 space-y-1.5 transition-all">
                    @foreach($item['children'] as $child)
                        @php
                            $childCondition = $child['condition'] ?? null;
                            $childIsActive = request()->routeIs($child['route']);
                            $childActiveClass = $childIsActive ? 'nav-active' : 'text-slate-600 hover:text-accent-600 hover:bg-slate-900/5 dark:text-slate-300 dark:hover:text-accent-light dark:hover:bg-white/10';
                        @endphp

                        @if($childCondition === 'farmer_non_cooperative')
                            @if(Auth::user()->farmerProfile?->affiliation_type !== 'cooperative')
                                <a href="{{ route($child['route_url']) }}" data-tooltip="{{ $child['tooltip'] }}" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ $childActiveClass }}">
                                    <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-900/5 text-slate-500 dark:bg-white/10 dark:text-white/70 text-xs font-bold flex items-center justify-center uppercase">{{ $child['letter'] }}</span>
                                    <span class="nav-label">{{ $child['label'] }}</span>
                                </a>
                            @endif
                        @else
                            <a href="{{ route($child['route_url']) }}" data-tooltip="{{ $child['tooltip'] }}" class="nav-link flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ $childActiveClass }}">
                                <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-900/5 text-slate-500 dark:bg-white/10 dark:text-white/70 text-xs font-bold flex items-center justify-center uppercase">{{ $child['letter'] }}</span>
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
                <div class="space-y-1.5">
                    <a href="{{ route($parentUrl) }}" data-tooltip="{{ $item['tooltip'] }}" class="nav-link {{ $mobileOnly ? 'lg:hidden ' : '' }}flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition {{ $activeClass }}">
                        <span class="nav-letter shrink-0 w-8 h-8 rounded-lg bg-slate-900/5 text-slate-500 dark:bg-white/10 dark:text-white/70 text-xs font-bold flex items-center justify-center uppercase">{{ $item['letter'] }}</span>
                        <span class="nav-label">{{ $item['label'] }}</span>
                    </a>
                </div>
            @endif
        @endif
    @endforeach

</nav>
